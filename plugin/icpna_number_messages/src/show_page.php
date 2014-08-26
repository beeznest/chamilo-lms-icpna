<?php

include_once '../../../main/inc/global.inc.php';
include_once 'icpna_number_messages_plugin.class.php';
include_once '../../../main/auth/sso/ssoServer.class.php';

if (api_is_teacher() || api_is_course_admin() || api_is_course_admin() || api_is_student()) {
    ;
} else {
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

    $branchUid = $objBranch->getUidSede($sessionId);
    $programUid = $objBranch->getUidProgram($sessionId);

    $additionalParams['uididsede'] = $branchUid;
    $additionalParams['uididprograma'] = $programUid;

    $getNewPath = $objSsoServer->getUrl($paths[$id], $additionalParams);

    $objTpl = new Template($names[$id]);
    $objTpl->assign('path', $getNewPath);

    $content = $objTpl->fetch('add_external_pages/views/showpage.tpl');

    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}