<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;

/**
 * Class IcpnaPlexConfigPlugin.
 */
class IcpnaPlexConfigPlugin extends Plugin
{
    const SETTING_USERNAME = 'username';
    const SETTING_COURSES = 'courses';
    const SETTING_ERROR_EMAIL = 'error_email';
    const SETTING_WS_URL = 'ws_url';
    const SETTING_ENROLLMENT_PAGE = 'enrollment_page';

    const SCORE_ADVANCED_MIN = 18;

    /**
     * IcpnaPlexConfigPlugin constructor.
     */
    protected function __construct()
    {
        $version = '0.1';
        $author = 'Angel Fernando Quiroz Campos';
        $settings = [
            self::SETTING_USERNAME => 'text',
            self::SETTING_COURSES => 'text',
            self::SETTING_WS_URL => 'text',
            self::SETTING_ERROR_EMAIL => 'text',
            self::SETTING_ENROLLMENT_PAGE => 'text',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * @return IcpnaPlexConfigPlugin
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'icpna_plex_config';
    }

    /**
     * @param CQuizDestinationResult $destinationResult
     *
     * @return CQuizQuestionCategory
     */
    public static function getQuestionCategoryByDestination(CQuizDestinationResult $destinationResult)
    {
        return Database::getManager()
            ->getRepository('ChamiloCourseBundle:CQuizQuestionCategory')
            ->findOneBy(
                [
                    'title' => $destinationResult->getAchievedLevel(),
                    'cId' => $destinationResult->getExe()->getCId(),
                ]
            );
    }

    /**
     * @param string $achievedLevel
     * @param int    $exe_id
     *
     * @return string
     */
    public static function fixAchievedLevelWhenIsAdvanced($achievedLevel, $exe_id)
    {
        $achievedLevel = strtoupper($achievedLevel);

        if ('ADVANCED' !== $achievedLevel) {
            return $achievedLevel;
        }

        $advancedCategory = Database::getManager()
            ->getRepository('ChamiloCourseBundle:CQuizQuestionCategory')
            ->findOneBy(['title' => $achievedLevel, 'cId' => api_get_course_int_id()]);

        if (empty($advancedCategory)) {
            return $achievedLevel;
        }

        $levelScore = TestCategory::getCatScoreForExeidForUserid(
            $advancedCategory->getId(),
            $exe_id,
            api_get_user_id()
        );

        if ($levelScore < self::SCORE_ADVANCED_MIN) {
            return 'INTERMEDIATE 12';
        }

        return $achievedLevel;
    }

    public function createDbTables()
    {
        $queries = [
            "CREATE TABLE plugin_plex_log (id INT AUTO_INCREMENT NOT NULL, exe_id INT DEFAULT NULL, request LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', response LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', success TINYINT(1) NOT NULL, INDEX IDX_5727B343B5A18F57 (exe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "CREATE TABLE plugin_plex_enrollment (id INT AUTO_INCREMENT NOT NULL, exe_id INT DEFAULT NULL, score DOUBLE PRECISION NOT NULL, exam_validity VARCHAR(255) NOT NULL, period_validity VARCHAR(255) NOT NULL, level_reached VARCHAR(255) NOT NULL, INDEX IDX_FB93A9F0B5A18F57 (exe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "ALTER TABLE plugin_plex_log ADD CONSTRAINT FK_5727B343B5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id) ON DELETE SET NULL",
            "ALTER TABLE plugin_plex_enrollment ADD CONSTRAINT FK_FB93A9F0B5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id) ON DELETE SET NULL",
        ];

        foreach ($queries as $query) {
            Database::query($query);
        }
    }

    public function dropDbTables()
    {
        $sql = "DROP TABLE IF EXISTS plugin_plex_log";
        Database::query($sql);

        $sql = "DROP TABLE IF EXISTS plugin_plex_enrollment";
        Database::query($sql);
    }

    /**
     * @param int $exeId
     *
     * @return array
     */
    public static function getEnrollmentByExeId($exeId)
    {
        return Database::select(
            '*',
            'plugin_plex_enrollment',
            ['where' => ['exe_id = ?' => [(int) $exeId]]],
            'first'
        );
    }

    public static function generateQrCodeAndNotifyUser(CQuizDestinationResult $destinationResult)
    {
        $origin = api_get_origin();
        $user = $destinationResult->getUser();
        $exe = $destinationResult->getExe();

        $enrollmentInfo = self::getEnrollmentByExeId($exe->getExeId());

        if (empty($enrollmentInfo)) {
            return [];
        }

        $destinationResult->setAchievedLevel($enrollmentInfo['level_reached']);

        $quizzesDir = ExerciseLib::checkQuizzesPath($user->getId());

        $qrUrl = api_get_path(WEB_CODE_PATH).'exercise/progressive_adaptive_results.php?'
            .http_build_query(['hash' => $destinationResult->getHash(), 'origin' => $origin]);
        $qrFileName = $destinationResult->getHash().'.png';

        $content = [
            $user->getCompleteNameWithUsername(),
            sprintf(get_lang('LevelReachedX'), $destinationResult->getAchievedLevel()),
            api_convert_and_format_date(
                $exe->getStartDate(),
                DATE_TIME_FORMAT_SHORT
            ),
            $qrUrl,
        ];
        $content = array_map(
            function ($item) {
                return strip_tags($item);
            },
            $content
        );
        $qrContent = implode("\n\r", $content);
        $qrSystemPath = $quizzesDir['system'].$qrFileName;

        PHPQRCode\QRcode::png($qrContent, $qrSystemPath, 'H', 2, 2);

        ExerciseLib::sendEmailNotificationForAdaptiveResult($destinationResult);

        $plexConfig = api_get_plugin_setting('icpna_plex_config', self::SETTING_ENROLLMENT_PAGE);

        return [
            'quiz_dir_web' => $quizzesDir['web'],
            'destination_result' => $destinationResult,
            'user_complete_name' => $user->getCompleteNameWithUsername(),
            'origin' => $origin,
            'mail_sent' => true,
            'enrollment_page' => $plexConfig,
            'exam_validity' => $enrollmentInfo['exam_validity'],
            'period_validity' => $enrollmentInfo['period_validity'],
        ];
    }

    public function isEnableInCourse($courseCode)
    {
        $enabledCourses = (string) $this->get(self::SETTING_COURSES);
        $enabledCourses = explode(',', $enabledCourses);

        return in_array($courseCode, $enabledCourses);
    }
}
