<?php

include_once '../../../main/inc/global.inc.php';
include_once 'icpna_number_messages_plugin.class.php';
include_once '../../../main/auth/sso/ssoServer.class.php';

$allow = api_is_teacher() || api_is_course_admin() || api_is_student() || api_is_teacher_admin();

if (!$allow) {
    api_not_allowed(true);
}

$objIcpnaNumberMessagesPlugin = IcpnaNumberMessagesPlugin::create();
$webPath = rtrim($objIcpnaNumberMessagesPlugin->get('web_path'), ';');
$tabName = rtrim($objIcpnaNumberMessagesPlugin->get('tab_name'), ';');

$paths = explode(';', $webPath);
$names = explode(';', $tabName);

if (!empty($_GET['id']) || $_GET['id'] === '0') {
    $id = $_GET['id'];

    $objSsoServer = new ssoServer();
    $objBranch = new Branch();

    $sessionId = api_get_session_id();
    $user = api_get_user_info();
    if (empty($sessionId)) {
        // if no session or branch context, pass 0 to login link
        $programUid = 0;
        $branchUid = 0;
    } else {
        $programUid = $objBranch->getUidProgram($sessionId);
        $branchUid = $objBranch->getUidSede($sessionId);
    }

    $additionalParams['uididsede'] = $branchUid;
    $additionalParams['uididprograma'] = $programUid;
    $additionalParams['vchcodigorrhh'] = $user['username'];

    $getNewPath = $objSsoServer->getUrl($paths[$id], $additionalParams);

    $objTpl = new Template($names[$id]);
    $objTpl->assign('path', $getNewPath);
    $content = $objTpl->fetch('icpna_number_messages/views/showpage.tpl');
    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}