<?php
/* For licensing terms, see /license.txt */

/**
 * Show the page to sign-in
 */

require_once '../../main/inc/global.inc.php';

api_block_anonymous_users();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

--$id;

$plugin = IcpnaSsoPlugin::create();
$settings = $plugin->getSettings();

$buttonName = $settings['names'];
$loginProcess = $settings['urls'];

$username = $plugin->getLoginUser();
$password = $plugin->getLoginPassword();

$sessionId = api_get_session_id();
$coursePath = api_get_course_path();

$objTpl = new Template($buttonName[$id]);
$objTpl->assign('username', $username);
$objTpl->assign('password', $password);
$objTpl->assign('path', $loginProcess[$id]);
$objTpl->assign('redirect_path', $loginProcess[$id]);

$content = $objTpl->fetch($plugin->get_name().'/views/start_sso.html.twig');

$objTpl->assign('header', $buttonName[$id]);
$objTpl->assign('content', $content);
$objTpl->display_one_col_template();
