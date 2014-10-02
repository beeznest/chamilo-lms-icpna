<?php

include_once '../../../main/inc/global.inc.php';
include_once 'external_page_ngl_plugin.class.php';
include_once '../../../main/auth/sso/sso_ngl.class.php';

if (!api_is_teacher() && !api_is_course_admin() && !api_is_teacher_admin()) {
    api_not_allowed(true);
}

$objExternalPageNGL = ExternalPageNGLPlugin::create();
$buttonName = $objExternalPageNGL->get('button_name');
$loginProcess = $objExternalPageNGL->get('login_process');
$username = $objExternalPageNGL->getLoginUser();
$password = $objExternalPageNGL->getLoginPassword();

$sessionId = api_get_session_id();
$coursePath = api_get_course_path();

if (empty($coursePath)) {
    $backToURL = api_get_path(WEB_PATH);
} else {
    $backToURL = api_get_path(WEB_COURSE_PATH) . $coursePath . '/?id_session=' . $sessionId;
}

$toolName = $objExternalPageNGL->get_lang('external_page');
$waitMessage = get_lang('Wait');

$objTpl = new Template($toolName);
$objTpl->assign('name', $buttonName);
$objTpl->assign('msg_wait', $waitMessage);
$objTpl->assign('username', $username);
$objTpl->assign('password', $password);
$objTpl->assign('path', $loginProcess);
$objTpl->assign('redirect_path', $loginProcess);
$objTpl->assign('back_to', $backToURL);

$content = $objTpl->fetch('external_page_ngl/views/showpage.tpl');

$objTpl->assign('content', $content);
$objTpl->display_one_col_template();