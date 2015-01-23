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
 * Get the schedule end time
 * @param string $scheduleDisplayText The schedule
 * @param string $format The format to get the schedule start
 * @return array
 */
function getScheduleEnd($scheduleDisplayText, $format = 'string')
{
    $scheduleDisplayText = trim($scheduleDisplayText);
    $parts = preg_split("/(\ )+/", $scheduleDisplayText);

    if (array_key_exists(2, $parts)) {
        $time = $parts[2];

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

    return false;
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
    $sessionId = 0;

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

    while ($sessionData = Database::fetch_assoc($sessionResult)) {
        $extraValue = new ExtraFieldValue('session');
        $scheduleValue = $extraValue->get_values_by_handler_and_field_variable($sessionData['id_session'], 'horario', true);

        $extraOption = new ExtraFieldOption('session');
        $scheduleOption = $extraOption->get_field_option_by_field_and_option($scheduleValue['field_id'], $scheduleValue['field_value']);
        $scheduleOption = current($scheduleOption);

        $scheduleStartData = getScheduleStart($scheduleOption['option_display_text'], 'array');
        $scheduleEndData = getScheduleEnd($scheduleOption['option_display_text'], 'array');

        $scheduleInData = calculateInTime($scheduleStartData['hours'], $scheduleStartData['minutes'], 'array');

        $timezone = _api_get_timezone();

        $scheduleIn = new DateTime('now', new DateTimeZone($timezone));
        $scheduleIn->setTime($scheduleInData['hours'], $scheduleInData['minutes']);
        $scheduleIn->setTimezone(new DateTimeZone('UTC'));

        $scheduleEnd = new DateTime('now', new DateTimeZone($timezone));
        $scheduleEnd->setTime($scheduleEndData['hours'], $scheduleEndData['minutes']);
        $scheduleEnd->setTimezone(new DateTimeZone('UTC'));

        $currentTime = new DateTime($date, new DateTimeZone('UTC'));

        if ($currentTime >= $scheduleIn && $currentTime <= $scheduleEnd) {
            $sessionId = $sessionData['id_session'];
            break;
        }
    }

    return $sessionId;
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

/**
 * Get the list of sessions
 * @return array The list
 */
function getSchedulesList()
{
    $sessionExtras = new ExtraField('session');

    $scheduleExtraFields = $sessionExtras->get_all(array(
        'field_variable = ?' => 'horario'
    ));

    $scheduleExtraField = reset($scheduleExtraFields);

    $schedules = array();

    foreach ($scheduleExtraField['options'] as $schedule) {
        $schedules[$schedule['id']] = $schedule['option_display_text'];
    }

    return $schedules;
}

/**
 * Get the room data (id, title)
 * @param int $sessionId The session id
 * @param int $branchId The branch id
 * @return array The room data. Otherwise return false
 */
function getRoom($sessionId, $branchId)
{
    $branchRoomTable = Database::get_statistic_table(TABLE_BRANCH_ROOM);
    $fieldTable = Database::get_statistic_table(TABLE_MAIN_SESSION_FIELD);
    $optionTable = Database::get_statistic_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
    $valueTable = Database::get_statistic_table(TABLE_MAIN_SESSION_FIELD_VALUES);

    $fieldResult = Database::select('id', $fieldTable, array(
                'where' => array(
                    'field_variable = ?' => 'aula'
                )
    ));

    if (!empty($fieldResult)) {
        $fieldAula = current($fieldResult);

        $sql = "SELECT option_display_text FROM $optionTable AS o "
                . "INNER JOIN $valueTable AS v "
                . "ON o.option_value = v.field_value "
                . "WHERE v.field_id = '{$fieldAula['id']}' "
                . "AND v.session_id = $sessionId";

        $optionRoomResult = Database::query($sql);

        if ($optionRoomResult) {
            $optionRoomData = Database::fetch_assoc($optionRoomResult);

            $sql = "SELECT id, title FROM $branchRoomTable "
                    . "WHERE title = {$optionRoomData['option_display_text']} "
                    . "AND branch_id = $branchId";

            $roomResult = Database::query($sql);

            if ($roomResult) {
                $roomData = Database::fetch_assoc($roomResult);

                return $roomData;
            }
        }
    }

    return false;
}

/**
 * Get the teacher in/out inside a room for a course in session
 * @param int $sessionId The session id
 * @param int $courseId The course id
 * @param int $roomId The room id
 * @param date $date The report date
 * @return array The in/out data
 */
function getInOut($sessionId, $courseId, $roomId, $date)
{
    $trackIOTable = Database::get_statistic_table(TABLE_TRACK_E_TEACHER_IN_OUT);

    $trackResult = Database::select('*', $trackIOTable, array(
                'where' => array(
                    "session_id = ? AND " => $sessionId,
                    "course_id = ? AND " => $courseId,
                    "room_id = ? AND " => $roomId,
                    "log_in_course_date LIKE %?%" => $date
                ),
        'order' => 'log_in_course_date'
    ));

    return $trackResult;
}

/**
 * Check if the course in the session has a substitute
 * @param int $sessionId The session id
 * @param int $courseId The course id
 * @param date $date The substitution date
 * @return boolean True has a subtitue
 */
function hasSubstitute($sessionId, $courseId, $date)
{
    $sessionCourseUserDateTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER_DATE);
    
    $sql = "SELECT COUNT(1) AS is_io "
            . "FROM $sessionCourseUserDateTable "
            . "WHERE session_id = $sessionId "
            . "AND course_id = $courseId "
            . "AND status = " . ROLE_COACH_SUBSTITUTE . " "
            . "AND substitution_date = '$date'";

    $result = Database::query($sql);

    if ($result) {
        $count = Database::fetch_assoc($result);

        if ($count['is_io'] > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Get the list of sessions for the in/out and substitution tracking
 * @param int|stirng $scheduleId The schedule id or 'all'
 * @param date $date The report date
 * @param int $branchId The 
 * @param string $listFilter The filter type for in/out status
 * @param string $substitutionFilter The filter type for substitution status
 * @return array The list. Otherwise return false
 */
function getSessionsList($scheduleId, $date, $branchId, $listFilter = 'all', $substitutionFilter = 'all')
{
    $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
    $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sessionFieldValuesTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

    if (!empty($scheduleId)) {
        $rows = array();
        
        if ($scheduleId != 'all') { // The schedule id is a integer
            $scheduleFieldOption = new ExtraFieldOption('session');
            $schedule = $scheduleFieldOption->get($scheduleId);
            $scheduleDisplayText = getFormatedSchedule($schedule['option_display_text']);
            $scheduleData = getScheduleStart($schedule['option_display_text'], 'array');
        } else {
            $scheduleField = new ExtraField('session');
            $scheduleFieldData = $scheduleField->get_handler_field_info_by_field_variable('horario');
        }

        $branchFieldOption = new ExtraFieldOption('session');
        
        $branch = $branchFieldOption->get($branchId);
        
        $sql = "SELECT s.id, s.id_coach, s.nbr_courses, s.access_start_date, s.access_end_date "
                . "FROM $sessionTable as s "
                . "INNER JOIN $sessionCourseUserTable AS scu ON s.id = scu.id_session "
                . "INNER JOIN $sessionFieldValuesTable AS valBr ON s.id = valBr.session_id ";
        
        if ($scheduleId != 'all') {
            $sql.= "INNER JOIN $sessionFieldValuesTable as valSch ON s.id = valSch.session_id "
                    . "AND valSch.field_value = '{$schedule['option_value']}' "
                    . "AND valSch.field_id = '{$schedule['field_id']}' ";
        }

        $sql.= "AND valBr.field_value = '{$branch['option_value']}' "
                . "AND valBr.field_id = '{$branch['field_id']}' "
                . "AND '$date' BETWEEN DATE(s.access_start_date) AND DATE(s.access_end_date) "
                . "AND s.id_coach = scu.id_user";
                
        $listResult = Database::query($sql);

        while ($session = Database::fetch_assoc($listResult)) {
            $room = getRoom($session['id'], $branchId);
            $courses = SessionManager::get_course_list_by_session_id($session['id']);
            
            if ($scheduleId == 'all') {
                $sessionScheduleValue = new ExtraFieldValue('session');
                $schedule = $sessionScheduleValue->get_values_by_handler_and_field_id($session['id'], $scheduleFieldData['id'], true);
                
                $scheduleData = getScheduleStart($schedule['field_value'], 'array');
                $scheduleDisplayText = getFormatedSchedule($schedule['field_value']);
            }

            foreach ($courses as $course) {
                $coachesId = SessionManager::get_session_course_coaches($course['code'], $session['id']);

                $coaches = array();

                foreach ($coachesId as $coachId) {
                    $coaches[] = api_get_user_info($coachId['user_id']);
                }

                $inOut = getInOut($session['id'], $course['id'], $room['id'], $date, $scheduleData);
                $hasSubstitute = hasSubstitute($session['id'], $course['id'], $date);

                $substitutes = SessionManager::getSessionCourseSubstituteCoachesWithInfo($course['id'], $session['id'], $date);

                if (empty($inOut)) {
                    $inDateTime = array();
                    $outDateTime = array();
                } else {
                    foreach ($inOut as $io) {
                        if (!empty($io['log_in_course_date'])) {
                            $inDateTime[] = api_get_local_time($io['log_in_course_date']);
                        } else {
                            $outDateTime[] = "&nbsp;";
                        }

                        if (!empty($io['log_out_course_date'])) {
                            $outDateTime[] = api_get_local_time($io['log_out_course_date']);
                        } else {
                            $outDateTime[] = "&nbsp;";
                        }
                    }
                }

                $row = array(
                    'id' => $session['id'],
                    'room' => $room['title'],
                    'course' => $course['title'],
                    'courseCode' => $course['code'],
                    'schedule' => $scheduleDisplayText,
                    'coaches' => $coaches,
                    'substitutes' => $substitutes,
                    'in' => $inDateTime,
                    'out' => $outDateTime,
                    'hasSubstitute' => $hasSubstitute
                );
                
                switch ($substitutionFilter) {
                    case 'with':
                        if (!$hasSubstitute) {
                            $row = array();
                        }
                        break;
                    
                    case 'without':
                        if ($hasSubstitute) {
                            $row = array();
                        }
                        break;
                }
                
                switch ($listFilter) {
                    case 'reg':
                        if (empty($inOut)) {
                            $row = array();
                        }
                        break;

                    case 'noreg':
                        if (!empty($inOut)) {
                            $row = array();
                        }
                        break;
                }
                
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    return false;
}

/**
 * Convert the data to array for to be exported
 * @param int $scheduleId The schedule id
 * @param date $date The report date
 * @param int $branchId The branch Id
 * @return array The converted data
 */
function convertToArray($scheduleId, $date, $branchId)
{
    $extraFieldOption = new ExtraFieldOption('session');
    $schedule = $extraFieldOption->get($scheduleId);
    $scheduleDisplayText = getFormatedSchedule($schedule['option_display_text']);

    $arrayData = array(
        array(
            get_lang('InOutManagement')
        ),
        null,
        array(
            get_lang('Branch'),
            Branch::getName($branchId)
        ),
        array(
            get_lang('Date'),
            $date,
            '',
            get_lang('Schedule'),
            $scheduleDisplayText
        ),
        null,
        array(
            get_lang('Schedule'),
            get_lang('Room'),
            get_lang('Course'),
            get_lang('Teacher'),
            get_lang('Substitute'),
            get_lang('InAt'),
            get_lang('OutAt')
        )
    );

    $sessionsToExport = getSessionsList($scheduleId, $date, $branchId);

    foreach ($sessionsToExport as $session) {
        $row = array(
            'schedule' => $session['schedule'],
            'room' => $session['room'],
            'course' => $session['course'],
            'coaches' => '',
            'substitutes' => '',
            'in' => $session['in'],
            'out' => $session['out']
        );

        $coachNames = array();

        foreach ($session['coaches'] as $coach) {
            $coachNames[] = $coach['complete_name_with_username'];
        }

        $row['coaches'] = implode(', ', $coachNames);

        if ($session['hasSubstitute']) {
            $substituteNames = array();

            foreach ($session['substitutes'] as $substitute) {
                $substituteNames[] = $substitute['complete_name_with_username'];
            }

            $row['substitutes'] = implode(', ', $substituteNames);
        }

        $arrayData[] = $row;
    }

    return $arrayData;
}

/**
 * Export the data to a XLS file
 * @param int $scheduleId
 * @param date $date
 * @param ind $branchId
 * @return void;
 */
function exportToXLS($scheduleId, $date, $branchId)
{
    $dataToConvert = convertToArray($scheduleId, $date, $branchId);

    $fileName = get_lang('InOutManagement') . ' ' . api_get_local_time();

    Export::export_table_xls($dataToConvert, $fileName);
    die;
}

/**
 * Export the data to a PDF file
 * @param int $scheduleId The schedule id
 * @param date $date The report date
 * @param int $branchId The branch id
 * @return void;
 */
function exportToPDF($scheduleId, $date, $branchId)
{
    $dataToConvert = convertToArray($scheduleId, $date, $branchId);

    $params = array(
        'add_signatures' => false,
        'filename' => get_lang('InOutManagement') . ' ' . api_get_local_time(),
        'pdf_title' => get_lang('InOutManagement'),
        'pdf_description' => get_lang('InOutManagement'),
        'format' => 'A4-L',
        'orientation' => 'L'
    );

    $pdfContent = Export::convert_array_to_html($dataToConvert);

    Export::export_html_to_pdf($pdfContent, $params);
    exit;
}

/**
 * Get the user attendaces by a date
 * @param int $userId The user ID
 * @param string $date The date
 */
function getUserAttendanceByDate($userId, $date)
{
    $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
    $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $trackIOTable = Database::get_statistic_table(TABLE_TRACK_E_TEACHER_IN_OUT);
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $branchRoomTable = Database::get_main_table(TABLE_BRANCH_ROOM);

    $attendances = array();

    $sql = "SELECT s.id, c.title, io.log_in_course_date, io.log_out_course_date, br.title room "
        . "FROM $sessionTable s "
        . "INNER JOIN $sessionCourseUserTable scu ON s.id = scu.id_session "
        . "INNER JOIN $trackIOTable io ON s.id = io.session_id "
        . "INNER JOIN $courseTable c ON io.course_id = c.id "
        . "INNER JOIN $branchRoomTable br ON io.room_id = br.id "
        . "WHERE scu.id_user = $userId "
        . "AND io.user_id = $userId "
        . "AND DATE(log_in_course_date) = '$date'";

    $result = Database::query($sql);

    $sessionFieldValue = new ExtraFieldValue('session');
    
    $sessionOption = new ExtraFieldOption('session');

    while ($row = Database::fetch_assoc($result)) {
        $scheduleValue = $sessionFieldValue->get_values_by_handler_and_field_variable($row['id'], 'horario', true);
        
        $scheduleField = $sessionOption->get_field_option_by_field_and_option(
            $scheduleValue['field_id'],
            $scheduleValue['field_value']
        );

        $schedule = current($scheduleField);

        $attendances[] = array(
            'schedule' => getFormatedSchedule($schedule['option_display_text']),
            'room' => $row['room'],
            'course' => $row['title'],
            'inAt' => api_get_local_time($row['log_in_course_date']),
            'outAt' => api_get_local_time($row['log_out_course_date'])
        );
    }

    return $attendances;
}
