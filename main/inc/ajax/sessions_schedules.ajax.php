<?php

/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */
require_once '../global.inc.php';

function getSessionsList($scheduleId, $date, $listFilter = 'all')
{
    $scheduleFieldOption = new ExtraFieldOption('session');

    $schedule = $scheduleFieldOption->get($scheduleId);

    if (!empty($schedule)) {
        $rows = array();

        $sql = "SELECT s.id, s.id_coach, s.nbr_courses, s.access_start_date, s.access_end_date "
                . "FROM session as s "
                . "INNER JOIN session_rel_course_rel_user AS scu "
                . "ON s.id = scu.id_session "
                . "INNER JOIN session_field_values as val "
                . "ON s.id = val.session_id "
                . "AND val.field_value = '{$schedule['option_value']}' "
                . "AND val.field_id = '{$schedule['field_id']}' "
                . "AND '$date' BETWEEN DATE(s.access_start_date) AND DATE(s.access_end_date) "
                . "AND s.id_coach = scu.id_user";

        $listResult = Database::query($sql);

        $scheduleData = getScheduleStart($schedule['option_display_text'], 'array');

        while ($session = Database::fetch_assoc($listResult)) {
            $room = getRoom($session['id']);
            $courses = SessionManager::get_course_list_by_session_id($session['id']);

            foreach ($courses as $course) {
                $coaches = SessionManager::get_session_course_coaches_to_string($course['code'], $session['id']);
                $inOut = getInOut($session['id'], $course['id'], $room['id'], $date, $scheduleData);
                $hasSubstitute = hasSubstitute($session['id'], $course['code']);


                switch ($listFilter) {
                    case 'reg':
                        if ($inOut) {
                            $rows[] = array(
                                'id' => $session['id'],
                                'room' => $room['title'],
                                'course' => $course['title'],
                                'schedule' => $schedule['option_display_text'],
                                'coach' => $coaches,
                                'in' => $inOut['log_in_course_date'],
                                'out' => $inOut['log_out_course_date'],
                                'hasSubstitute' => $hasSubstitute
                            );
                        }
                        break;

                    case 'noreg':
                        if (empty($inOut)) {
                            $rows[] = array(
                                'id' => $session['id'],
                                'room' => $room['title'],
                                'course' => $course['title'],
                                'schedule' => $schedule['option_display_text'],
                                'coach' => $coaches,
                                'in' => null,
                                'out' => null,
                                'hasSubstitute' => $hasSubstitute
                            );
                        }
                        break;

                    default :
                        $rows[] = array(
                            'id' => $session['id'],
                            'room' => $room['title'],
                            'course' => $course['title'],
                            'schedule' => $schedule['option_display_text'],
                            'coach' => $coaches,
                            'in' => empty($inOut) ? null : $inOut['log_in_course_date'],
                            'out' => empty($inOut) ? null : $inOut['log_out_course_date'],
                            'hasSubstitute' => $hasSubstitute
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

function getInOut($sessionId, $courseId, $roomId, $date, $schedule)
{
    $trackIOTable = Database::get_statistic_table(TABLE_TRACK_E_TEACHER_IN_OUT);

    $inTime = calculateInTime($schedule['hours'], $schedule['minutes']);
    $inDatetime = "$date $inTime";

    $trackResult = Database::select('*', $trackIOTable, array(
                'where' => array(
                    'session_id = ? AND ' => $sessionId,
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

function hasSubstitute($sessionId, $courseCode)
{
    $sql = "SELECT COUNT(1) AS is_io FROM session_rel_course_rel_user "
            . "WHERE id_session = $sessionId "
            . "AND course_code = '$courseCode' "
            . "AND status = " . ROLE_COACH_SUBSTITUTE;

    $result = Database::query($sql);

    if ($result) {
        $count = Database::fetch_assoc($result);

        if ($count['is_io'] > 0) {
            return true;
        }
    }

    return false;
}

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$scheduleIdSelected = isset($_POST['schedule']) ? $_POST['schedule'] : 0;
$dateSelected = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$status = isset($_POST['status']) ? $_POST['status'] : 'all';

$sessions = getSessionsList($scheduleIdSelected, $dateSelected, $status);

echo json_encode($sessions);
