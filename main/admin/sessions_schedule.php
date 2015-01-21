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
        <a href="<?php echo api_get_path(WEB_CODE_PATH) ?>attendance/teacher.php" title="<?php echo get_lang('MyAttendance') ?>">
            <img src="<?php echo api_get_path(WEB_IMG_PATH) ?>icons/32/attendance.png" alt="<?php echo get_lang('MyAttendance') ?>">
        </a>
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
                                <td>
                                    <?php foreach ($session['in'] as $in) { ?>
                                        <p><?php echo $in ?></p>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php foreach ($session['out'] as $out) { ?>
                                        <p><?php echo $out ?></p>
                                    <?php } ?>
                                </td>
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
