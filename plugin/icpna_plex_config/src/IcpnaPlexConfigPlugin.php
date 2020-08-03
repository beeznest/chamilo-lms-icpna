<?php
/* For licensing terms, see /license.txt */

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
}
