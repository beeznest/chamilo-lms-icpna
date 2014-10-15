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

    // $formated .= "({$parts['0']})";

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
        $schedules['all'] = get_lang('All');

        while ($fieldValueData = Database::fetch_assoc($fieldsResult)) {
            $scheduleId = $fieldValueData['id'];

            $schedules[$scheduleId] = getFormatedSchedule($fieldValueData['option_display_text']);
        }
    }
    asort($schedules, SORT_STRING);

    return $schedules;
}

/**
 * Get the schedule start time
 * @param string $scheduleDisplayText The schedule
 * @param string $format The format to get the schedule start
 * @return array
 */
function getScheduleStart($scheduleDisplayText, $format = 'string')
{
    $scheduleDisplayText = trim($scheduleDisplayText);
    $parts = preg_split("/(\ )+/", $scheduleDisplayText);

    $time = $parts[1];

    switch ($format) {
        case 'array':
            $timeParts = explode(':', $time);

            return array(
                'hours' => $timeParts[0],
                'minutes' => $timeParts[1]
            );

        default:
            return $time;
    }
}

/**
 * Calculate the income time. Time - 5 minutes
 * @param string $hours The hours
 * @param string $minutes The minutes
 * @param string $format The formart to get the calculated time
 * @return array The teacher income time. Depending the format
 */
function calculateInTime($hours, $minutes, $format = 'string')
{
    $datetime = new DateTime();
    $datetime->setTime($hours, $minutes);

    $datetime->modify('-5 minutes');

    $inTime = $datetime->format('H:i:s');

    switch ($format) {
        case 'array':
            $inTimeParts = explode(':', $inTime);

            return array(
                'hours' => $inTimeParts[0],
                'minutes' => $inTimeParts[1],
                'seconds' => $inTimeParts[2]
            );

        default:
            return $inTime;
    }
}

/**
 * Guess the session id when a In is registred
 * @param int $userId The id of the user making the record
 * @param date $date The record date
 * @param tine $branchId The branch of a room
 * @param string $roomName The room title
 * @return int The session id
 */
function searchSession($userId, $date, $branchId, $roomName)
{
    $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
    $scuTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sessionFieldOptionsTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
    $sessionFieldValueTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

    $sql = "SELECT scu.id_session "
            . "FROM $scuTable AS scu "
            . "INNER JOIN $sessionTable AS s ON scu.id_session = s.id "
            . "INNER JOIN $sessionFieldValueTable AS val_room ON scu.id_session = val_room.session_id "
            . "INNER JOIN $sessionFieldOptionsTable AS opt_room ON val_room.field_value = opt_room.option_value "
            . "INNER JOIN $sessionFieldValueTable AS val_branch ON scu.id_session = val_branch.session_id "
            . "INNER JOIN $sessionFieldOptionsTable AS opt_branch ON val_branch.field_value = opt_branch.option_value "
            . "WHERE scu.id_user = $userId "
            . "AND opt_room.option_display_text = '$roomName' "
            . "AND opt_branch.id = $branchId "
            . "AND ('$date' BETWEEN s.display_start_date AND s.display_end_date)";

    $sessionResult = Database::query($sql);

    $sessionData = Database::fetch_assoc($sessionResult);

    return $sessionData['id_session'];
}

/**
 * Get the course id of a session
 * @param int $sessionId The session id
 * @return int The course id
 */
function searchCourse($sessionId)
{
    $courses = SessionManager::get_course_list_by_session_id($sessionId);

    $course = current($courses);

    return $course['id'];
}
