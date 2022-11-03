<?php

/* For licensing terms, see /license.txt */

class IcpnaOnlineEnrollmentPlugin extends Plugin implements HookPluginInterface
{
    const SETTING_JWT_PUBLIC_KEY = 'jwt_public_key';

    const FIELD_SO_SESSION = 'o_e_session';

    protected function __construct()
    {
        $settings = [
            'tool_enable' => 'boolean',
            self::SETTING_JWT_PUBLIC_KEY => 'text',
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $settings);
    }

    static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_name()
    {
        return 'icpna_online_enrollment';
    }

    /**
     * @throws Exception
     */
    public function throwException($languageVariable)
    {
        throw new Exception($this->get_lang($languageVariable));
    }

    public function installHook()
    {
        $observer = IcpnaOnlineEnrollmentQuizEndHook::create();

        HookQuizEnd::create()->attach($observer);
    }

    public function uninstallHook()
    {
        $observer = IcpnaOnlineEnrollmentQuizEndHook::create();

        HookQuizEnd::create()->detach($observer);
    }
}
