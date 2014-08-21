<?php

include_once '../../../main/inc/global.inc.php';
include_once 'add_external_pages_plugin.class.php';
include_once '../../../main/auth/sso/ssoServer.class.php';

if (api_is_teacher() || api_is_course_admin() || api_is_course_admin() || api_is_student()) {
    ;
} else {
    api_not_allowed(true);
}

$objAddExternalPage = AddExternalPagesPlugin::create();
$webPath = $objAddExternalPage->get('web_path');
$buttonName = $objAddExternalPage->get('button_name');

$paths = explode(';', $webPath);
$names = explode(';', $buttonName);

if (!empty($_GET['id']) || $_GET['id'] === '0') {
    $id = $_GET['id'];
    $isSsoEnable = $objAddExternalPage->get('sso_enable');
    $getNewPath = $paths[$id];
    if ($isSsoEnable === 'true') {
        $objSsoServer = new ssoServer();
        $objBranch = new Branch();
        $sessionId = api_get_session_id();
        $branchUid = $objBranch->getUidSede($sessionId);
        if (empty($sessionId)) {
            $programUid = 0;
        } else {
            $programUid = $objBranch->getUidProgram($sessionId);
        }
        $additionalParams['uididsede'] = $branchUid;
        $additionalParams['uididprograma'] = $programUid;


        $getNewPath = $objSsoServer->getUrl($paths[$id], $additionalParams);
    }

    $toolName = $objAddExternalPage->get_lang('external_page');
    $objTpl = new Template($toolName);
    $objTpl->assign('name', $names[$id]);
    $objTpl->assign('path', $getNewPath);
    $content = $objTpl->fetch('add_external_pages/views/showpage.tpl');
    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}