<?php

/* For licensing terms, see /license.txt */
/**
 * Show the page to sign-in to NGL
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com
 * @package chamilo.plugin.externalPageNGL
 */
require_once '../../main/inc/global.inc.php';

if (api_is_course_admin()) {
    api_not_allowed(true);
}

$objExternalPageNGL = ExternalPageNglPlugin::create();
$buttonName = $objExternalPageNGL->get('button_name');
$loginProcess = $objExternalPageNGL->get('login_process');
$username = $objExternalPageNGL->getLoginUser();
$password = $objExternalPageNGL->getLoginPassword();

$sessionId = api_get_session_id();
$coursePath = api_get_course_path();

if (empty($coursePath)) {
    $backToURL = api_get_path(WEB_PATH);
} else {
    $backToURL = api_get_path(WEB_COURSE_PATH).$coursePath.'/?id_session='.$sessionId;
}

$toolName = $objExternalPageNGL->get_lang('external_page');

$objTpl = new Template($toolName);
$objTpl->assign('username', $username);
$objTpl->assign('password', $password);
$objTpl->assign('path', $loginProcess);
$objTpl->assign('redirect_path', $loginProcess);
$objTpl->assign('back_to', $backToURL);

$content = $objTpl->fetch('external_page_ngl/views/showpage.tpl');

$objTpl->assign('header', $buttonName);
$objTpl->assign('content', $content);
$objTpl->display_one_col_template();
