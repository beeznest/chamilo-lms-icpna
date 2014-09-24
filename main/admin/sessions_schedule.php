<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';

if (!api_is_teacher_admin()) {
    api_not_allowed(true);
}

$this_section = IN_OUT_MANAGEMENT;

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('InOut'));

$scheduleIdSelected = isset($_REQUEST['schedule']) ? $_REQUEST['schedule'] : 0;
$dateSelected = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');

if (isset($_REQUEST['branch'])) {
    $branchSelected = intval($_REQUEST['branch']);
} else {
    $objBranch = new Branch();
    $branchId = $objBranch->getBranchFromIP(api_get_real_ip());

    if ($branchId != false) {
        $branchSelected = $branchId;
    } else {
        $branchSelected = 2;
    }
}

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
        exportToXLS($scheduleIdSelected, $dateSelected, $branchSelected);
    } elseif ($_GET['type'] == 'pdf') {
        exportToPDF($scheduleIdSelected, $dateSelected, $branchSelected);
    }
}

Display::display_header();
$check = Security::check_token('get');
if ($_GET['action'] == 'show_message' && true == $check) {
    Display::display_confirmation_message(Security::remove_XSS(stripslashes($_GET['message'])));
    Security::clear_token();
}

$sessions = getSessionsList($scheduleIdSelected, $dateSelected, $branchSelected, $statusSelected);

if ($sessions != false) {
    ?>
    <div class="actions">
        <span style="float:right; padding-top: 0px;">
            <?php
            $exportXLSURL = api_get_self() . '?' . http_build_query(array(
                        'action' => 'export',
                        'type' => 'xls',
                        'branch' => $branchSelected,
                        'date' => $dateSelected,
                        'schedule' => $scheduleIdSelected,
                        'status' => $statusSelected,
            ));
            ?>
            <?php echo Display::url(Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), array(), ICON_SIZE_MEDIUM), $exportXLSURL); ?>
            <?php
            $exportPDFURL = api_get_self() . '?' . http_build_query(array(
                        'action' => 'export',
                        'type' => 'pdf',
                        'branch' => $branchSelected,
                        'date' => $dateSelected,
                        'schedule' => $scheduleIdSelected,
                        'status' => $statusSelected,
            ));
            ?>
            <?php echo Display::url(Display::return_icon('pdf.png', get_lang('ExportToPDF'), array(), ICON_SIZE_MEDIUM), $exportPDFURL); ?>
        </span>
    </div>
<?php } ?>
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
                        <th><?php echo get_lang('InAt') ?></th>
                        <th><?php echo get_lang('OutAt') ?></th>
                        <th><?php echo get_lang('Substitute') ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php echo get_lang('Schedule') ?></th>
                        <th><?php echo get_lang('Room') ?></th>
                        <th><?php echo get_lang('Course') ?></th>
                        <th><?php echo get_lang('Teacher') ?></th>
                        <th><?php echo get_lang('InAt') ?></th>
                        <th><?php echo get_lang('OutAt') ?></th>
                        <th><?php echo get_lang('Substitute') ?></th>
                    </tr>
                </tfoot>
                <tbody>
                    <?php if ($sessions != false) { ?>
                        <?php foreach ($sessions as $session) { ?>
                            <tr>
                                <td><?php echo $session['schedule'] ?></td>
                                <td><?php echo $session['room'] ?></td>
                                <td><?php echo $session['course'] ?></td>
                                <td><?php
                                    foreach ($session['coaches'] as $coach) {
                                        $profileURL = api_get_path(WEB_PATH) . "main/social/profile.php?u=" . $coach['user_id'];
                                        ?>
                                        <a href="<?php echo $profileURL ?>"><?php echo $coach['complete_name_with_username'] ?></a><br>
                                        <?php
                                    }

                                    if ($session['hasSubstitute']) {
                                        foreach ($session['susbtitutes'] as $coachSubstitute) {
                                            $profileURL = api_get_path(WEB_PATH) . "main/social/profile.php?u=" . $coachSubstitute['user_id'];
                                            ?>
                                            <strong>
                                                &xrarr; <a href="<?php echo $profileURL ?>"><?php echo $coachSubstitute['complete_name_with_username']; ?></a>
                                            </strong><br>
                                            <?php
                                        }
                                    }
                                    ?></td>
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
                $coachesId = SessionManager::get_session_course_coaches($course['code'], $session['id']);

                $coaches = array();

                foreach ($coachesId as $coachId) {
                    $coaches[] = api_get_user_info($coachId['user_id']);
                }

                $inOut = getInOut($session['id'], $course['id'], $room['id'], $date, $scheduleData);
                $hasSubstitute = hasSubstitute($session['id'], $course['code']);

                $substitutes = SessionManager::getSessionCourseSusbtituteCoachesWithInfo($course['code'], $session['id']);

                $row = array(
                    'id' => $session['id'],
                    'room' => $room['title'],
                    'course' => $course['title'],
                    'courseCode' => $course['code'],
                    'schedule' => $schedule['option_display_text'],
                    'coaches' => $coaches,
                    'susbtitutes' => $substitutes,
                    'in' => empty($inOut) ? null : $inOut['log_in_course_date'],
                    'out' => empty($inOut) ? null : $inOut['log_out_course_date'],
                    'hasSubstitute' => $hasSubstitute
                );

                switch ($listFilter) {
                    case 'reg':
                        if ($inOut) {
                            $rows[] = $row;
                        }
                        break;

                    case 'noreg':
                        if (empty($inOut)) {
                            $rows[] = $row;
                        }
                        break;

                    default :
                        $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    return false;
}

function convertToArray($scheduleId, $date, $branchId)
{
    $extraFieldOption = new ExtraFieldOption('session');
    $schedule = $extraFieldOption->get($scheduleId);

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
            $schedule['option_display_text']
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

            foreach ($session['susbtitutes'] as $substitute) {
                $substituteNames[] = $substitute['complete_name_with_username'];
            }

            $row['substitutes'] = implode(', ', $substituteNames);
        }

        $arrayData[] = $row;
    }

    return $arrayData;
}

function exportToXLS($scheduleId, $date, $branchId)
{
    $dataToConvert = convertToArray($scheduleId, $date, $branchId);

    $fileName = get_lang('InOutManagement') . ' ' . api_get_local_time();

    Export::export_table_xls($dataToConvert, $fileName);
    die;
}

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

    print_r($pdfContent);

    Export::export_html_to_pdf($pdfContent, $params);
    exit;
}
