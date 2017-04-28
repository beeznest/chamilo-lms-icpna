<?php

include_once '../../../main/inc/global.inc.php';
include_once 'IcpnaTabZonePlugin.php';
include_once '../../../main/auth/sso/ssoServer.class.php';

api_block_anonymous_users();

$plugin = IcpnaTabZonePlugin::create();

if ($plugin->get('tool_enable') != 'true') {
    api_not_allowed(true);
    exit;
}

$isStudent = api_is_student();

$tabName = $isStudent ? $plugin->get_lang('StudentsZone') : $plugin->get_lang('TeachersZone');
$zonUrl = $isStudent ? $plugin->get('student_zone_url') : $plugin->get('teacher_zone_url');

//$objSsoServer = new ssoServer();
//$objBranch = new Branch();
//
//$sessionId = api_get_session_id();
//$user = api_get_user_info();
//
//// if no session or branch context, pass 0 to login link
//$programUid = empty($sessionId) ? 0 : $objBranch->getUidProgram($sessionId);
//$branchUid = empty($sessionId) ? 0 : $objBranch->getUidSede($sessionId);
//
//$additionalParams = [
//    'profile' => $isStudent ? 'learner' : 'trainer'
//];
//
//$zoneSsoUrl = $objSsoServer->getUrl($zonUrl, $additionalParams);

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_PLUGIN_PATH).'icpna_tab_zone/views/tab_zone.css">'
    .'</link>';

$objTpl = new Template($tabName);
$objTpl->assign('path', $zonUrl);

$content = $objTpl->fetch('icpna_tab_zone/views/zone.tpl');

$objTpl->assign('content', $content);
$objTpl->display_one_col_template();
