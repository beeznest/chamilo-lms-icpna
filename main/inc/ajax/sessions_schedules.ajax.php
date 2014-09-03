<?php

/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */
require_once '../global.inc.php';

function getSessionsList($scheduleId, $date)
{
    $scheduleFieldOption = new ExtraFieldOption('session');

    $schedule = $scheduleFieldOption->get($scheduleId);

    if (!empty($schedule)) {
        $sql = "SELECT s.id, s.id_coach, s.nbr_courses, s.access_start_date, s.access_end_date "
                . "FROM session as s "
                . "INNER JOIN session_field_values as val "
                . "ON s.id = val.session_id "
                . "WHERE val.field_id = 3 "
                . "AND val.field_value = '7379A7D3-6DC5-42CA-9ED4-97367519F1D9' "
                . "AND s.access_start_date <= '$date' "
                . "AND s.access_end_date >= '$date'";

        $listResult = Database::query($sql);

        $rows = array();

        while ($session = Database::fetch_assoc($listResult)) {
            $coach = api_get_user_info($session['id_coach']);
            $room = getRoom($session['id']);

            $coursesList = SessionManager::get_course_list_by_session_id($session['id']);

            if (!empty($coursesList)) {
                foreach ($coursesList as $courseId => $course) {
                    $inOut = getInOut($session['id'], $coach['user_id'], $course['id'], $room['id'], $date, $schedule);

                    $rows[] = array(
                        'id' => $session['id'],
                        'room' => $room['title'],
                        'course' => $course['title'],
                        'schedule' => $schedule['option_display_text'],
                        'coach' => $coach['complete_name_with_username'],
                        'in' => empty($inOut) ? null : $inOut['log_in_course_date'],
                        'out' => empty($inOut) ? null : $inOut['log_out_course_date']
                    );
                }
            }
        }

        return $rows;
    }

    return false;
}

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

function calculateInTime($hours, $minutes, $format = 'string')
{
    $datetime = new DateTime();
    $datetime->setTime($hours, $minutes);

    $datetime->modify('-5 minutes');

    $inTime = $datetime->format('h:i');

    switch ($format) {
        case 'array':
            $inTimeParts = explode(':', $inTime);

            return array(
                'hours' => $inTimeParts[0],
                'minutes' => $inTimeParts[1]
            );

        default:
            return $inTime;
    }
}

function getInOut($sessionId, $userId, $courseId, $roomId, $date, $schedule)
{
    $trackIOTable = Database::get_statistic_table(TABLE_TRACK_E_TEACHER_IN_OUT);

    $inTime = calculateInTime($schedule['hours'], $schedule['minutes'], 'array');
    $inDatetime = "$date : $inTime";

    $trackResult = Database::select('*', $trackIOTable, array(
                'where' => array(
                    'session_id = ? AND ' => $sessionId,
                    'user_id = ? AND ' => $userId,
                    'course_id = ? AND ' => $courseId,
                    'room_id = ? AND ' => $roomId,
                    'log_in_course_date >= ?' => $inDatetime
                )
    ));

    return $trackResult;
}

function getRoom($sessionId)
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
                    . "WHERE title = {$optionRoomData['option_display_text']}";

            $roomResult = Database::query($sql);

            if ($roomResult) {
                $roomData = Database::fetch_assoc($roomResult);

                return $roomData;
            }
        }
    }

    return false;
}

$scheduleIdSelected = isset($_POST['schedule']) ? $_POST['schedule'] : 0;
$dateSelected = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

$sessions = getSessionsList($scheduleIdSelected, $dateSelected);

echo json_encode($sessions);
