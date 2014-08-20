<?php

include_once '../../../main/inc/global.inc.php';
include_once 'external_page_ngl_plugin.class.php';
include_once '../../../main/auth/sso/sso_ngl.class.php';

if (!api_is_teacher() && !api_is_course_admin() && !api_is_course_admin()) {
    api_not_allowed(true);
}

$objExternalPageNGL = ExternalPageNGLPlugin::create();
$buttonName = $objExternalPageNGL->get('button_name');
$loginProcess = $objExternalPageNGL->get('login_process');

$objBranch = new Branch();
$sessionId = api_get_session_id();

$branchUid = $objBranch->getUidSede($sessionId);
$programUid = $objBranch->getUidProgram($sessionId);

$additionalParams = array(
    'uididsede' => $branchUid,
    'uididprograma' => $programUid,
    'loginprocess' => $loginProcess
);

$objSsoServer = new SSONGL();
$getNewPath = $objSsoServer->getUrl(api_get_path(WEB_PLUGIN_PATH) . 'external_page_ngl/src/page_ngl.php', $additionalParams);

$toolName = $objExternalPageNGL->get_lang('external_page');

$objTpl = new Template($toolName);
$objTpl->assign('name', $buttonName);
$objTpl->assign('path', $getNewPath);

$content = $objTpl->fetch('add_external_pages/views/showpage.tpl');

$objTpl->assign('content', $content);
$objTpl->display_one_col_template();
