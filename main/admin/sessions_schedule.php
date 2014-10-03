<?php
/* For licensing terms, see /license.txt */
/**
 * Form for the In/Out Management
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Anibal Copitan <anibal.copitan@beeznest.com>
 * @package chamilo.admin
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'sessions_schedule.lib.php';

$preventAccess = !api_is_teacher_admin() && !api_is_platform_admin();

if ($preventAccess) {
    api_not_allowed(true);
}

$this_section = IN_OUT_MANAGEMENT;



// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('InOut'));

$scheduleIdSelected = isset($_REQUEST['schedule']) ? $_REQUEST['schedule'] : 'all';
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
$selectedSubstitutionStatus = isset($_REQUEST['substitution_status']) ? $_REQUEST['substitution_status'] : 'all';

$branches = array();

foreach (Branch::getAll() as $branchId => $branch) {
    $branches[$branchId] = $branch['title'];
}

$schedules = getScheduleList($dateSelected, $branchSelected);

$htmlHeadXtra[] = "" .
"<script>
    $(document).on('ready', function() {
        $('#date').datepicker({
            dateFormat: 'yy-mm-dd'
        });

        $('#date').on('change', function () {
            var url = '" . api_get_path(WEB_AJAX_PATH) . "sessions_schedule.ajax.php';

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: {
                    date: $('#date').val(),
                    branch: $('#branch').val()
                },
                success: function (response) {
                    $('#schedule').empty();

                    $.each(response, function (index, schedule){
                        $('#schedule').append('<option value=\"' + schedule.id + '\">' + schedule.optionDisplayText + '</option>');
                    });
                }
            });
        });
    });
</script>";

if (isset($_GET['action']) && $_GET['action'] == 'export') {
    if ($_GET['type'] == 'xls') {
        exportToXLS($scheduleIdSelected, $dateSelected, $branchSelected);
    } elseif ($_GET['type'] == 'pdf') {
        exportToPDF($scheduleIdSelected, $dateSelected, $branchSelected);
    }
}

Display::display_header();
$check = Security::check_token('get');
if (isset($_GET['action']) && $_GET['action'] == 'show_message' && true == $check) {
    Display::display_confirmation_message(Security::remove_XSS(stripslashes($_GET['message'])));
    Security::clear_token();
}

$sessions = getSessionsList($scheduleIdSelected, $dateSelected, $branchSelected, $statusSelected, $selectedSubstitutionStatus);

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
        <label class="control-label" for="date"><?php echo get_lang('Date') . ' ' . get_lang('And') . ' ' . get_lang('Schedule') ?></label>
        <div class="controls">
            <?php
            $dateInputAttributes = array(
                'readonly' => '',
                'id' => 'date',
                'class' => 'input-small'
            );

            echo Display::input('date', 'date', $dateSelected, $dateInputAttributes);
            ?>
            <?php echo Display::select('schedule', $schedules, $scheduleIdSelected, null, false) ?>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="status"><?php echo get_lang('InOutStatus') ?></label>
        <div class="controls">
            <?php
            $statusSelectValues = array(
                'all' => get_lang('All'),
                'reg' => get_lang('Registered'),
                'noreg' => get_lang('NotRegistered')
            );

            $statusSelectAttributes = array(
                'class' => 'input-medium'
            );

            echo Display::select('status', $statusSelectValues, $statusSelected, $statusSelectAttributes, false)
            ?>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="substitution_status"><?php echo get_lang('SubstitutionStatus') ?></label>
        <div class="controls">
            <?php
            $substitutionStatusSelectValues = array(
                'all' => get_lang('All'),
                'with' => get_lang('OnlyWithSubstitution'),
                'without' => get_lang('OnlyWithoutSubstitution')
            );

            $substitutionStatusSelectAttributes = array(
                'class' => 'input-large'
            );

            echo Display::select('substitution_status', $substitutionStatusSelectValues, $selectedSubstitutionStatus, $substitutionStatusSelectAttributes, false)
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
                                        foreach ($session['substitutes'] as $coachSubstitute) {
                                            $profileURL = api_get_path(WEB_PATH) . "main/social/profile.php?u=" . $coachSubstitute['user_id'];
                                            ?>
                                            <strong>
                                                &gt;&gt;&gt; <a href="<?php echo $profileURL ?>"><?php echo $coachSubstitute['complete_name_with_username']; ?></a>
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
                                        'substitution_status' => $selectedSubstitutionStatus,
                                        'id_session' => $session['id'],
                                        'room' => $session['room'],
                                        'course' => $session['course'],
                                        'schedule_display' => $session['schedule'],
                                        'course_code' => $session['courseCode']
                                    );

                                    $addSubstituteFormURL = api_get_path(WEB_PATH) . 'main/admin/add_tutor_sustitution_to_session.php';
                                    $addSubstituteFormURL .= '?' . http_build_query($urlParams);
                                    ?>
                                    <a href="<?php echo $addSubstituteFormURL ?>">
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
 * @return array The room data. Otherwise return false
 */
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

/**
 * Get the teacher in/out inside a room for a course in session
 * @param int $sessionId The session id
 * @param int $courseId The course id
 * @param int $roomId The room id
 * @param date $date The report date
 * @param array $schedule The schedule data
 * @return array The in/out data
 */
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
            $room = getRoom($session['id']);
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

                $row = array(
                    'id' => $session['id'],
                    'room' => $room['title'],
                    'course' => $course['title'],
                    'courseCode' => $course['code'],
                    'schedule' => $scheduleDisplayText,
                    'coaches' => $coaches,
                    'substitutes' => $substitutes,
                    'in' => empty($inOut) ? null : $inOut['log_in_course_date'],
                    'out' => empty($inOut) ? null : $inOut['log_out_course_date'],
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
