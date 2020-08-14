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
}
