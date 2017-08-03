<?php
/* For licensing terms, see /license.txt */

require_once '../../main/inc/global.inc.php';

$isAllowed = api_is_teacher() || api_is_course_admin() || api_is_course_admin() || api_is_student();

if (!$isAllowed) {
    api_not_allowed(true);
}

$objAddExternalPage = AddExternalPagesPlugin::create();
$webPath = $objAddExternalPage->get('web_path');
$buttonName = $objAddExternalPage->get('button_name');

$paths = explode(';', $webPath);
$names = explode(';', $buttonName);

if (!empty($_GET['id']) || $_GET['id'] === '0') {
    $id = $_GET['id'];
    $isSsoEnable = $objAddExternalPage->get('sso_enable') === 'true';
    $getNewPath = $paths[$id];

    if ($isSsoEnable) {
        $ssoServer = new SsoServer();
        $extraFieldValue = new ExtraFieldValue('session');

        $sessionId = api_get_session_id();
        $user = api_get_user_info();

        if (empty($sessionId)) {
            // if no session or branch context, pass 0 to login link
            $programUid = 0;
            $branchUid = 0;
        } else {
            $programValue = $extraFieldValue->get_values_by_handler_and_field_variable(
                $sessionId,
                'uidIdPrograma',
                true
            );
            $programUid = $programValue['value'];
            $branchValue = $extraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'sede', true);
            $branchUid = $branchValue['value'];
        }
        $additionalParams['uididprograma'] = $programUid;
        $additionalParams['uididsede'] = $branchUid;
        $additionalParams['vchcodigorrhh'] = $user['username'];

        $getNewPath = $ssoServer->getUrl($getNewPath, $additionalParams);
    }

    $toolName = $objAddExternalPage->get_lang('external_page');
    $objTpl = new Template($toolName);
    $objTpl->assign('path', $getNewPath);
    $content = $objTpl->fetch('add_external_pages/views/showpage.tpl');
    $objTpl->assign('header', $names[$id]);
    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}
