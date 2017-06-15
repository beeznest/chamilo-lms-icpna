<?php
/* For licensing terms, see /license.txt */

include_once '../../../main/inc/global.inc.php';

$allow = api_is_teacher() || api_is_course_admin() || api_is_student();

if (!$allow) {
    api_not_allowed(true);
}

$objIcpnaNumberMessagesPlugin = IcpnaNumberMessagesPlugin::create();
$webPath = rtrim($objIcpnaNumberMessagesPlugin->get('web_path'), ';');
$tabName = rtrim($objIcpnaNumberMessagesPlugin->get('tab_name'), ';');

$webUrls = explode(';', $webPath);
$names = explode(';', $tabName);

if (!empty($_GET['id']) || $_GET['id'] === '0') {
    $id = $_GET['id'];

    $objSsoServer = new SsoServer();

    $sessionId = api_get_session_id();
    $user = api_get_user_info();
    if (empty($sessionId)) {
        // if no session or branch context, pass 0 to login link
        $programUid = 0;
        $branchUid = 0;
    } else {
        $extraFieldValue = new ExtraFieldValue('session');

        $programValue = $extraFieldValue->get_values_by_handler_and_field_variable(
            $sessionId,
            'uidIdPrograma',
            true
        );
        $branchValue = $extraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'sede', true);

        $programUid = $programValue['value'];
        $branchUid = $branchValue['value'];
    }

    $additionalParams['uididsede'] = $branchUid;
    $additionalParams['uididprograma'] = $programUid;
    $additionalParams['vchcodigorrhh'] = $user['username'];

    $getNewPath = $objSsoServer->getUrl($webUrls[$id], $additionalParams);

    $objTpl = new Template($names[$id]);
    $objTpl->assign('path', $getNewPath);
    $content = $objTpl->fetch('icpna_number_messages/views/showpage.tpl');
    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}
