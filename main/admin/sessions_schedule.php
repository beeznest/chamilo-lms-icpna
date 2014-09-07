<?php
/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$scheduleIdSelected = isset($_GET['schedule']) ? $_GET['schedule'] : 0;
$dateSelected = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$branchSelected = isset($_GET['branch']) ? $_GET['branch'] : 2;

$this_section = SECTION_PLATFORM_ADMIN;

$branchs = Branch::getAll();

$schedules = getSchedulesList();
if ($_GET['action'] == 'show_message')
    Display::display_header();

    $check = Security::check_token('get');
    if ($check) {
        Display::display_confirmation_message(Security::remove_XSS(stripslashes($_GET['message'])));
        Security::clear_token();
    }

?>
<script>
    $(document).on('ready', function() {
        $('#alt-date').datepicker({
            dateFormat: 'DD, d MM, yy',
            altField: '#date',
            altFormat: 'yy-mm-dd'
        });

        $('form[name="frmlistsessions"]').on('submit', function(e) {
            e.preventDefault();

            $('#tbl-list-sessions tbody').html('');

            var urlPost = '<?php echo api_get_path(WEB_AJAX_PATH) . 'sessions_schedules.ajax.php' ?>';

            $.post(urlPost, $(this).serialize(), function(sessions) {
                var sessionTr = '';

                $(sessions).each(function(index, session) {

                    var substitutionURL = '<?php echo api_get_path(WEB_PATH) . 'main/admin/add_tutor_sustitution_to_session.php' ?>';
                    var params = '?id_session=' + session.id
                        +'&room='+session.room
                        +'&course='+session.course
                        +'&coach='+session.coach
                        +'&schedule='+session.schedule;


                    sessionTr += '<tr><td>' + session.schedule + '</td>' +
                            '<td>' + session.room + '</td>' +
                            '<td>' + session.course + '</td>' +
                            '<td>' + session.coach + '</td>' +
                            '<td>' + (session.in ? session.in : '') + '</td>' +
                            '<td>' + (session.out ? session.out : '') + '</td>' +
                            '<td><a class="btn btn-info" href="' + substitutionURL + params + '"><?php echo get_lang('Substitution') ?></a></td><tr>';
                });

                $('#tbl-list-sessions tbody').append(sessionTr);
            }, 'json');
        });
    });
</script>
<form class="form-inline" name="frmlistsessions" method="get" method="<?php echo api_get_self() ?>">
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
