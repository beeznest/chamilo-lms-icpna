<?php
/* For licensing terms, see /license.txt */
/**
 * In/Out Management. Teacher attendance viewer
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
$language_file = array('admin', 'registration');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'sessions_schedule.lib.php';

$this_section = IN_OUT_MANAGEMENT;

$preventAccess = !api_is_teacher() && !api_is_teacher_admin();

if ($preventAccess) {
    api_not_allowed(true);
}

$toolName = get_lang('MyAttendance');;

if (api_is_platform_admin()) {
    $interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/sessions_schedule.php',
        'name' => get_lang('InOut')
    );
}

$userId = api_get_user_id();
$date = date('Y-m-d');

$attendances = getUserAttendanceByDate($userId, $date);

$tpl = new Template($toolName);

$tpl->assign('attendances', $attendances);

$tplContent = $tpl->fetch('default/attendance/teacher.tpl');

$tpl->assign('content', $tplContent);

$tpl->display_one_col_template();
