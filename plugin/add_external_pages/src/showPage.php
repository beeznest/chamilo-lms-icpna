<?php

include_once '../../../main/inc/global.inc.php';
include_once 'add_external_pages_plugin.class.php';

if (api_is_teacher() || api_is_course_admin() || api_is_course_admin()) {
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
    $toolName = $objAddExternalPage->get_lang('external_page');
    $objTpl = new Template($toolName);
    $objTpl->assign('name', $names[$id]);
    $objTpl->assign('path', $paths[$id]);
    $content = $objTpl->fetch('add_external_pages/views/showpage.tpl');
    $objTpl->assign('content', $content);
    $objTpl->display_one_col_template();
}