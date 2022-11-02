<?php

/* For licensing terms, see /license.txt */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = IcpnaOnlineEnrollmentPlugin::create();

$httpRequest = HttpRequest::createFromGlobals();

$jwt = trim($httpRequest->query->get('token'));

try {
    if (empty($jwt)) {
        $plugin->throwException('TokenIsMissing');
    }

    $keyFilePath = $plugin->get(IcpnaOnlineEnrollmentPlugin::SETTING_JWT_PUBLIC_KEY);

    if (!file_exists($keyFilePath)
        || !is_readable($keyFilePath)
    ) {
        $plugin->throwException('PlublicKeyNotFound');
    }

    $key = new Key(
        file_get_contents($keyFilePath),
        'RS256'
    );

    $decoded = (array) JWT::decode($jwt, $key);

    if (empty($decoded['data'])) {
        $plugin->throwException('NoData');
    }

    $jwtData = (array) $decoded['data'];
    $jwtData['uididprograma'] = strtoupper($jwtData['uididprograma']);
    $jwtData['uididpersona'] = strtoupper($jwtData['uididpersona']);

    $sessionExtraValue = new ExtraFieldValue('session');
    $userExtraValue = new ExtraFieldValue('user');

    $uidProgramaValue = $sessionExtraValue->get_item_id_from_field_variable_and_field_value(
        'uidIdPrograma',
        $jwtData['uididprograma']
    );

    if (false === $uidProgramaValue || empty($uidProgramaValue['item_id'])) {
        $plugin->throwException('UidProgramaNotFound');
    }

    $sessionId = (int) $uidProgramaValue['item_id'];

    $courseList = SessionManager::get_course_list_by_session_id($sessionId);

    if (empty($courseList)) {
        $plugin->throwException('NoCoursesForThisSession');
    }

    $chamiloUserInfo = api_get_user_info_from_username($jwtData['username']);

    if (false !== $chamiloUserInfo) {
        $chamiloUserId = $chamiloUserInfo['id'];
    } else {
        $chamiloUserId = UserManager::create_user(
            $jwtData['firstname'],
            $jwtData['lastname'],
            'student' === $jwtData['role'] ? STUDENT : COURSEMANAGER,
            $jwtData['email'],
            $jwtData['username'],
            $jwtData['password'],
            '',
            '',
            $jwtData['phone']
        );
    }

    if (empty($chamiloUserId)) {
        $plugin->throwException('UserNotAdded');
    }

    $userExtraValue->save([
        'item_id' => $chamiloUserId,
        'variable' => 'uididpersona',
        'value' => $jwtData['uididpersona'],
    ]);

    $chamiloUserInfo = api_get_user_info($chamiloUserId);

    if ('student' === $jwtData['role']) {
        SessionManager::subscribeUsersToSession(
            $sessionId,
            [$chamiloUserId],
            SESSION_VISIBLE_READ_ONLY,
            false
        );
    } else {
        $firstCourseInfo = current($courseList);

        SessionManager::set_coach_to_course_session(
            $chamiloUserId,
            $sessionId,
            $firstCourseInfo['real_id']
        );
    }

    ChamiloSession::clear();
    ChamiloSession::write('_user', $chamiloUserInfo);
    ChamiloSession::write('_user_auth_source', 'platform');

    Event::eventLogin($chamiloUserId);

    Redirect::session_request_uri(true, $chamiloUserId);
} catch (Exception $exception) {
    api_not_allowed(
        true,
        Display::return_message($exception->getMessage())
    );
}
