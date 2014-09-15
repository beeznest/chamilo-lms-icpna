<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

if (!api_is_teacher_admin()) {
    api_not_allowed(true);
}

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('InOut'));

$scheduleIdSelected = isset($_REQUEST['schedule']) ? $_REQUEST['schedule'] : 0;
$dateSelected = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');
$branchSelected = isset($_REQUEST['branch']) ? $_REQUEST['branch'] : 2;
$statusSelected = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'all';

$branches = array();

foreach (Branch::getAll() as $branchId => $branch) {
    $branches[$branchId] = $branch['title'];
}

$schedules = getSchedulesList();

$htmlHeadXtra[] = <<<EOD
<script>
    $(document).on('ready', function() {
        $('#date').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    });
</script>
EOD;

if ($_GET['action'] == 'export') {
    if ($_GET['type'] == 'xls') {
        $data[0] = array('id', 'nombre', 'apellido');
        $data[1] = array('user_id' => '1', 'firstname' =>'pepe', 'lastname'=>'rios');
        $data[2] = array('user_id' => '3', 'firstname' =>'pepe', 'lastname'=>'rios');
        Export::export_table_xls($data);
        exit;
    } elseif($_GET['type'] == 'pdf') {

    }
}

Display::display_header();
$check = Security::check_token('get');
if ($_GET['action'] == 'show_message' && true == $check) {
    Display::display_confirmation_message(Security::remove_XSS(stripslashes($_GET['message'])));
    Security::clear_token();
}
?>
<a href="?action=export&type=xls">exel</a>
<a href="?action=export&type=pdf">pdf</a>
<form class="form-horizontal" name="frmlistsessions" method="get" method="<?php echo api_get_self() ?>">
    <div class="control-group">
        <label class="control-label" for="branch"><?php echo get_lang('Branch') ?></label>
        <div class="controls">
            <?php echo Display::select('branch', $branches, $branchSelected, null, false) ?>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="alt-date"><?php echo get_lang('Date') . ' ' . get_lang('And') . ' ' . get_lang('Schedule') ?></label>
        <div class="controls">
            <?php
            echo Display::input('date', 'date', $dateSelected, array(
                'readonly' => '',
                'id' => 'date',
                'class' => 'input-small'
            ))
            ?>
            <?php echo Display::select('schedule', $schedules, $scheduleIdSelected, null, false) ?>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="status"><?php echo get_lang('Status') ?></label>
        <div class="controls">
            <?php
            echo Display::select('status', array(
                'all' => get_lang('All'),
                'reg' => get_lang('Registrered'),
                'noreg' => get_lang('NoRegistrered')), $statusSelected, array(
                'class' => 'input-large'), false)
            ?>
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <button type="submit" class="btn btn-primary"><?php echo get_lang('Submit') ?></button>
        </div>
    </div>
    <div class="row">
        <div class="span12">
            <hr>
            <table class="table table-striped" id="tbl-list-sessions">
                <thead>
                    <tr>
                        <th><?php echo get_lang('Schedule') ?></th>
                        <th><?php echo get_lang('Room') ?></th>
                        <th><?php echo get_lang('Course') ?></th>
                        <th><?php echo get_lang('Teacher') ?></th>
                        <th><?php echo get_lang('In') ?></th>
                        <th><?php echo get_lang('Out') ?></th>
                        <th><?php echo get_lang('Actions') ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php echo get_lang('Schedule') ?></th>
                        <th><?php echo get_lang('Room') ?></th>
                        <th><?php echo get_lang('Course') ?></th>
                        <th><?php echo get_lang('Teacher') ?></th>
                        <th><?php echo get_lang('In') ?></th>
                        <th><?php echo get_lang('Out') ?></th>
                        <th><?php echo get_lang('Actions') ?></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php $sessions = getSessionsList($scheduleIdSelected, $dateSelected, $branchSelected, $statusSelected) ?>
                    <?php if ($sessions != false) { ?>
                        <?php foreach ($sessions as $session) { ?>
                            <tr>
                                <td><?php echo $session['schedule'] ?></td>
                                <td><?php echo $session['room'] ?></td>
                                <td><?php echo $session['course'] ?></td>
                                <td><?php echo $session['coach'] ?></td>
                                <td><?php echo $session['in'] ?></td>
                                <td><?php echo $session['out'] ?></td>
                                <td>
                                    <?php
                                    $urlParams = array(
                                        'branch' => $branchSelected,
                                        'date' => $dateSelected,
                                        'schedule' => $scheduleIdSelected,
                                        'status' => $statusSelected,
                                        'id_session' => $session['id'],
                                        'room' => $session['room'],
                                        'course' => $session['course'],
                                        'coach' => $session['coach'],
                                        'schedule_display' => $session['schedule'],
                                        'course_code' => $session['courseCode']
                                    );

                                    $addSusbtituteFormURL = api_get_path(WEB_PATH) . 'main/admin/add_tutor_sustitution_to_session.php';
                                    $addSusbtituteFormURL .= '?' . http_build_query($urlParams);
                                    ?>
                                    <a href="<?php echo $addSusbtituteFormURL ?>">
                                        <?php
                                        if ($session['hasSubstitute']) {
                                            echo Display::display_icon('group.gif', get_lang('Substitute'));
                                        } else {
                                            echo Display::display_icon('students.gif', get_lang('Substitute'));
                                        }
                                        ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7"><?php echo get_lang('NoCoursesForThisSession') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php
Display::display_footer();

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

    $inTime = $datetime->format('h:i:s');

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

function getInOut($sessionId, $courseId, $roomId, $date, $schedule)
{
    $trackIOTable = Database::get_statistic_table(TABLE_TRACK_E_TEACHER_IN_OUT);

    $inTime = calculateInTime($schedule['hours'], $schedule['minutes']);
    $inDatetime = "$date $inTime";

    $trackResult = Database::select('*', $trackIOTable, array(
                'where' => array(
                    "session_id = ? AND " => $sessionId,
                    "course_id = ? AND " => $courseId,
                    "room_id = ? AND " => $roomId,
                    "log_in_course_date >= '?'" => $inDatetime
                )
    ));

    return current($trackResult);
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

function getSessionsList($scheduleId, $date, $branchId, $listFilter = 'all')
{
    $scheduleFieldOption = new ExtraFieldOption('session');
    $branchFieldOption = new ExtraFieldOption('session');

    $schedule = $scheduleFieldOption->get($scheduleId);
    $branch = $branchFieldOption->get($branchId);

    if (!empty($schedule)) {
        $rows = array();

        $sql = "SELECT s.id, s.id_coach, s.nbr_courses, s.access_start_date, s.access_end_date "
                . "FROM session as s "
                . "INNER JOIN session_rel_course_rel_user AS scu ON s.id = scu.id_session "
                . "INNER JOIN session_field_values as valSch ON s.id = valSch.session_id "
                . "INNER JOIN session_field_values AS valBr ON s.id = valBr.session_id "
                . "AND valSch.field_value = '{$schedule['option_value']}' "
                . "AND valSch.field_id = '{$schedule['field_id']}' "
                . "AND valBr.field_value = '{$branch['option_value']}' "
                . "AND valBr.field_id = '{$branch['field_id']}' "
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
                                'courseCode' => $course['code'],
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
                                'courseCode' => $course['code'],
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
                            'courseCode' => $course['code'],
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
