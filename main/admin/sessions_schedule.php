<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';

$scheduleIdSelected = isset($_GET['schedule']) ? $_GET['schedule'] : 0;
$dateSelected = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$branchSelected = isset($_GET['branch']) ? $_GET['branch'] : 2;

$this_section = SECTION_PLATFORM_ADMIN;

$branchs = Branch::getAll();

$schedules = getSchedulesList();

Display::display_header();
?>
<script>
    $(document).on('ready', function() {
        $('#alt-date').datepicker({
            dateFormat: 'DD, d MM, yy',
            altField: '#date',
            altFormat: 'yy-mm-dd'
        });
    });
</script>
<form class="form-inline" method="get" method="<?php echo api_get_self() ?>">
    <div class="row">
        <div class="span4">
            <label for="branch"><?php echo get_lang('Branch') ?></label>
            <select name="branch" id="branch" class="input-xlarge">
                <?php foreach ($branchs as $branch) { ?>
                    <option value="<?php echo date('Y-m-d') ?>"><?php echo $branch['title'] ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="span4">
            <label for="alt-date"><?php echo get_lang('Date') ?></label>
            <input id="alt-date" type="text" class="input-xlarge" readonly>
            <input id="date" type="hidden" name="date">
        </div>
    </div>
    <div class="row">
        <div class="span3">
            <label for="schedule"><?php echo get_lang('Schedule') ?></label>
            <select name="schedule" id="schedule">
                <?php foreach ($schedules as $schedule) { ?>
                    <?php $selected = ($scheduleIdSelected == $schedule['id']) ? 'selected' : ''; ?>
                    <option value="<?php echo $schedule['id'] ?>" <?php echo $selected ?>><?php echo $schedule['option_display_text'] ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="span3 offset1">
            <label><?php echo get_lang('Status') ?></label>
            <select id="status" name="status" class="input-large">
                <option value="all"><?php echo get_lang('All') ?></option>
                <option value="reg"><?php echo get_lang('Registrered') ?></option>
                <option value="noreg"><?php echo get_lang('NoRegistrered') ?></option>
            </select>
        </div>
        <div class="span2 offset1">
            <button type="submit"><?php echo get_lang('Submit') ?></button>
        </div>
    </div>
    <div class="row">
        <div class="span12">
            <hr>
            <?php if ($scheduleIdSelected != 0) { ?>
                <table class="table table-striped">
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
                        <?php $sessions = getSessionsList($scheduleIdSelected, $dateSelected); ?>
                        <?php foreach ($sessions as $session) { ?>
                            <tr>
                                <td><?php echo $session['schedule'] ?></td>
                                <td><?php echo $session['room'] ?></td>
                                <td><?php echo $session['course'] ?></td>
                                <td><?php echo $session['coach'] ?></td>
                                <td><?php echo $session['in'] ?></td>
                                <td><?php echo $session['out'] ?></td>
                                <td><a class="btn btn-info" href="<?php echo api_get_path(WEB_PATH) ?>"><?php echo get_lang('Substitution') ?></a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p><?php echo get_lang('PleaseSelectASchedule') ?></p>
            <?php } ?>
        </div>
    </div>
</form>
<?php
Display::display_footer();

function getBranchList()
{
    $sessionExtras = new ExtraField('session');

    $scheduleExtraFields = $sessionExtras->get_all(array(
        'field_variable = ?' => 'sede'
    ));

    $scheduleExtraField = reset($scheduleExtraFields);

    return $scheduleExtraField['options'];
}

function getSchedulesList()
{
    $sessionExtras = new ExtraField('session');

    $scheduleExtraFields = $sessionExtras->get_all(array(
        'field_variable = ?' => 'horario'
    ));

    $scheduleExtraField = reset($scheduleExtraFields);

    return $scheduleExtraField['options'];
}

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
