<?php

/* For licensing terms, see /license.txt */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class IcpnaOnlineEnrollmentPlugin extends Plugin
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
     * @param string $jwt
     *
     * @throws Exception
     *
     * @return array
     */
    public function getJwtData($jwt)
    {
        $jwt = trim($jwt);

        if (empty($jwt)) {
            throw new Exception($this->get_lang('UserNotAdded'));
        }

        $keyFilePath = $this->get(self::SETTING_JWT_PUBLIC_KEY);

        if (!file_exists($keyFilePath) || !is_readable($keyFilePath)) {
            throw new Exception($this->get_lang('PlublicKeyNotFound'));
        }

        $key = new Key(
            file_get_contents($keyFilePath),
            'RS256'
        );

        $decoded = (array) JWT::decode($jwt, $key);

        if (empty($decoded['data'])) {
            throw new Exception($this->get_lang('NoData'));
        }

        $jwtData = (array) $decoded['data'];
        $jwtData['uididprograma'] = strtoupper($jwtData['uididprograma']);
        $jwtData['uididpersona'] = strtoupper($jwtData['uididpersona']);

        return $jwtData;
    }

    /**
     * @param string $uidIdPrograma
     *
     * @throws Exception
     *
     * @return int
     */
    public function getSessionId($uidIdPrograma)
    {
        $sessionExtraValue = new ExtraFieldValue('session');

        $uidProgramaValue = $sessionExtraValue->get_item_id_from_field_variable_and_field_value(
            'uidIdPrograma',
            $uidIdPrograma
        );

        if (!is_array($uidProgramaValue) || empty($uidProgramaValue['item_id'])) {
            throw new Exception($this->get_lang('UidProgramaNotFound'));
        }

        return (int) $uidProgramaValue['item_id'];
    }

    /**
     * @param int    $userId
     * @param string $courseCode
     * @param int    $sessionId
     *
     * @return void
     */
    public function impersonate($userId, $courseCode, $sessionId)
    {
        ChamiloSession::clear();
        ChamiloSession::write(
            '_user',
            api_get_user_info($userId)
        );
        ChamiloSession::write('_user_auth_source', 'platform');
        ChamiloSession::write(
            'redirect_after_not_allow_page',
            api_get_course_url($courseCode, $sessionId)
        );

        Event::eventLogin($userId);
    }

    public function deletePreviousExerciseAttempt($userId, $sessionId)
    {
        $tblTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tblTrackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        Database::delete(
            $tblTrackExercises,
            ['session_id = ? AND exe_user_id = ?' => [$sessionId, $userId]]
        );
        Database::delete(
            $tblTrackAttempt,
            ['session_id = ? AND user_id = ?' => [$sessionId, $userId]]
        );

        Event::addEvent(
            'reset_session',
            'session_and_user_id',
            [
                'session_id' => $sessionId,
                'user_id' => $userId,
            ],
            api_get_utc_datetime()
        );
    }
}
