<?php
/* For licensing terms, see /license.txt */

include_once '../../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = IcpnaTabZonePlugin::create();

if ($plugin->get('tool_enable') != 'true') {
    api_not_allowed(true);
    exit;
}

$isStudent = api_is_student();

$tabName = $isStudent ? $plugin->get_lang('StudentsZone') : $plugin->get_lang('TeachersZone');
$zonUrl = $isStudent ? $plugin->get('student_zone_url') : $plugin->get('teacher_zone_url');

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'icpna_tab_zone/views/tab_zone.css');

$objTpl = new Template($tabName);
$objTpl->assign('path', $zonUrl);

$content = $objTpl->fetch('icpna_tab_zone/views/zone.tpl');

$objTpl->assign('content', $content);
$objTpl->display_one_col_template();
