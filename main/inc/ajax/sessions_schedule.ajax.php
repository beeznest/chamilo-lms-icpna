<?php

/* For licensing terms, see /license.txt */
/**
 * Form for the In/Out Management
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
/**
 * Init
 */
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'sessions_schedule.lib.php';

if (!api_is_teacher_admin()) {
    api_not_allowed(true);
}

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$branchId = isset($_GET['branch']) ? $_GET['branch'] : 2;

$json = array();

$scheduleList = getScheduleList($date, $branchId);

foreach ($scheduleList as $scheduleId => $displayText) {
    $json[] = array(
        'id' => $scheduleId,
        'optionDisplayText' => $displayText
    );
}

echo json_encode($json);
