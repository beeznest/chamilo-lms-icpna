<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = IcpnaUbitsPlugin::create();

$courseCodes = $plugin->getCourseCodesSetting();

if (empty($courseCodes)) {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

$userIsSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseCode, !empty($sessionId), $sessionId);

if (!$userIsSubscribed) {
    api_not_allowed(true);
}

$loginUrl = $plugin->get('login_url');

if (empty($loginUrl)) {
    api_not_allowed(true);
}

$uuid = $plugin->get('uuid');

if (empty($uuid)) {
    api_not_allowed(true);
}

$userInfo = api_get_user_info();

try {
    $ubits = new UbitsEncryption();
} catch (Exception $exception) {
    api_not_allowed(
        true,
        Display::return_message($exception->getMessage(), 'error')
    );
}

$token = $ubits->encrypt(
    $plugin->get('uuid'),
    $userInfo['email']
);

header("Location: $loginUrl?cu=$uuid&ct=$token");
