<?php

/* For licensing terms, see /license.txt */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = IcpnaOnlineEnrollmentPlugin::create();

if ('true' !== $plugin->get('tool_enable')) {
    api_not_allowed(true);
}

$httpRequest = HttpRequest::createFromGlobals();

$methodIsGet = HttpRequest::METHOD_GET === $httpRequest->getMethod();

$jwt = $methodIsGet
    ? $httpRequest->query->get('token')
    : $httpRequest->request->get('token');

try {
    $jwtData = $plugin->getJwtData($jwt);

    $sessionId = $plugin->getSessionId($jwtData['uididprograma']);

    $courseList = SessionManager::get_course_list_by_session_id($sessionId);

    if (empty($courseList)) {
        throw new Exception($plugin->get_lang('NoCoursesForThisSession'));
    }

    $firstCourseInfo = current($courseList);

    $chamiloUserInfo = api_get_user_info_from_username($jwtData['username']);
    $userExists = false;

    if (false !== $chamiloUserInfo) {
        $userExists = true;
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

        if (empty($chamiloUserId)) {
            throw new Exception($plugin->get('UserNotAdded'));
        }
    }

    $userExtraValue = new ExtraFieldValue('user');
    $userExtraValue->save([
        'item_id' => $chamiloUserId,
        'variable' => 'uididpersona',
        'value' => $jwtData['uididpersona'],
    ]);

    $existingUserSubscription = SessionManager::getUserSession($chamiloUserId, $sessionId);
    $userSubscriptionExists = !empty($existingUserSubscription);

    if (!$userSubscriptionExists) {
        if ('student' === $jwtData['role']) {
            SessionManager::subscribeUsersToSession(
                $sessionId,
                [$chamiloUserId],
                SESSION_VISIBLE_READ_ONLY,
                false
            );
        } else {
            SessionManager::set_coach_to_course_session(
                $chamiloUserId,
                $sessionId,
                $firstCourseInfo['real_id']
            );
        }
    }

    $plugin->deletePreviousExerciseAttempt($chamiloUserId, $sessionId);

    if ($methodIsGet) {
        $plugin->impersonate($chamiloUserId, $firstCourseInfo['code'], $sessionId);

        $_GET['redirect_after_not_allow_page'] = 1;

        Redirect::session_request_uri(true, $chamiloUserId);
    } else {
        $httpStatus = $userExists || $userSubscriptionExists ? HttpResponse::HTTP_OK : HttpResponse::HTTP_CREATED;

        HttpResponse::create('', $httpStatus)->send();
    }
} catch (Exception $exception) {
    $message = $exception->getMessage();

    if ($methodIsGet) {
        api_not_allowed(
            true,
            Display::return_message($message)
        );
    } else {
        HttpResponse::create($message, HttpResponse::HTTP_FORBIDDEN)->send();
    }
}
