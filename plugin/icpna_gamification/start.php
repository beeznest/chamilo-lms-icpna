<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = IcpnaGamificationPlugin::create();

$isStudent = !api_is_allowed_to_edit();

if (!$isStudent) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ModuleAvailableForStudentsOnly'), 'error')
    );
}

$userInfo = api_get_user_info();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

if (empty($sessionId)) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ModuleNotAvailableForThisCourse'), 'error')
    );
}

$plugin->getGamificationData($sessionId, $userInfo['username']);

if (!$plugin->isCourseValid()) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ModuleNotAvailableForThisCourse'), 'error')
    );
}

$sysPluginPath = api_get_path(SYS_PLUGIN_PATH).$plugin->get_name();

$pageName = $plugin->get_lang('LevelUp');

$template = new Template($pageName);
$template->assign('attendance_text', $plugin->getAttendanceText());
$template->assign('attendance_image', $plugin->getAttendanceImage());
$template->assign('activities_text', $plugin->getActivitiesText());
$template->assign('activities_image', $plugin->getActivitiesImage());

$content = $template->fetch($plugin->get_name().'/views/start.html.twig');

$template->assign('header', $pageName);
$template->assign('content', $content);
$template->display_one_col_template();
