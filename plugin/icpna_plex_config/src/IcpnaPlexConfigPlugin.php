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
}
