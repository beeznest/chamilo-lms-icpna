<?php

/* For licensing terms, see /license.txt */
/**
 * Form for the In/Out Management
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
/**
 * Define functions
 */

/**
 * List the active sessions id according to the date indicated. Concateanted by a comma (,)
 * @param date $date The date
 * @return array The session list
 */
function getSessionIdByDate($date, $branchId)
{
    $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
    $fieldOptionTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
    $fieldValueTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

    $sessionExtras = new ExtraField('session');
    $branchData = $sessionExtras->get_handler_field_info_by_field_variable('sede');

    $sql = "SELECT s.id FROM $sessionTable AS s "
            . "INNER JOIN $fieldValueTable AS val "
            . "ON s.id = val.session_id "
            . "INNER JOIN $fieldOptionTable AS opt "
            . "ON val.field_value = opt.option_value "
            . "WHERE opt.field_id = {$branchData['id']} "
            . "AND opt.id = $branchId "
            . "AND '$date' BETWEEN DATE(s.access_start_date) AND DATE(s.access_end_date)";

    $sessionsResult = Database::query($sql);

    $sessionsId = '';

    while ($sessionData = Database::fetch_assoc($sessionsResult)) {
        $sessionsId .= $sessionData['id'] . ', ';
    }

    return rtrim($sessionsId, ', ');
}

/**
 * Format the displat text of a schedule
 * @param string $displayText The display text
 * @return string The formated display text
 */
function getFormatedSchedule($displayText)
{
    $displayText = trim($displayText);
    $parts = preg_split("/(\ )+/", $displayText);

    $formated = "{$parts[1]} ";

    if (isset($parts[2])) {
        $formated .= "{$parts[2]} ";
    }

    $formated .= "({$parts['0']})";

    return $formated;
}

/**
 * Get the list of schedules by date and branch
 * @param date $date The report date
 * @param int $branchId The branch id
 * @return array The list
 */
function getScheduleList($date, $branchId)
{
    $schedules = array();

    $sessionsId = getSessionIdByDate($date, $branchId);

    $fieldOptionTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
    $fieldValueTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

    $sessionExtras = new ExtraField('session');
    $scheduleData = $sessionExtras->get_handler_field_info_by_field_variable('horario');

    $sql = "SELECT DISTINCT opt.id, opt.option_display_text "
            . "FROM $fieldOptionTable AS opt "
            . "INNER JOIN $fieldValueTable AS val "
            . "ON opt.option_value = val.field_value "
            . "WHERE opt.field_id = {$scheduleData['id']} "
            . "AND val.session_id IN($sessionsId)";

    $fieldsResult = Database::query($sql);

    if ($fieldsResult != false) {
        while ($fieldValueData = Database::fetch_assoc($fieldsResult)) {
            $scheduleId = $fieldValueData['id'];

            $schedules[$scheduleId] = getFormatedSchedule($fieldValueData['option_display_text']);
        }
    }

    return $schedules;
}
