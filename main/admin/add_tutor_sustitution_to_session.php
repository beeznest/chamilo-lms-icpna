<?php
/* For licensing terms, see /license.txt */
/**
 * Form for the In/Out Management
 * @author Anibal Copitan <anibal.copitan@beeznest.com>
 * @package chamilo.admin
 */
$language_file = array('admin', 'registration');
// resetting the course id
$cidReset = true;
require_once '../inc/global.inc.php';

if (!api_is_teacher_admin() || intval($_GET['id_session']) <= 0) {
    api_not_allowed(true);
}
$idSession = intval($_GET['id_session']);

$this_section = IN_OUT_MANAGEMENT;

// setting breadcrumbs
$tool_name = get_lang('CoachSubstitute');
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'sessions_schedule.php','name' => get_lang('InOut'));

// Database Table Definitions
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$dataHeader = $_REQUEST;
$urlConcat = $_SERVER['QUERY_STRING'];


$ajaxPath = api_get_path(WEB_CODE_PATH) . 'inc/ajax/admin.ajax.php'; 
$htmlHeadXtra[] = '
<script type="text/javascript">

function remove_item(origin)
{
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}

/*
* ajax list teachear.List all less teacher selected zone rigth
*/
function listOrderBy(needle)
{
    var selector = $("#ajax_list_users_multiple");
    selector.html("");
    var data = {a:"substituteCoachList", needle : needle};
    $.ajax({
        url: "'.$ajaxPath.'",
        data: data,
        success: function(data) {
            selector.html(data);
        }
    });
}

</script>';

$formSent = 0;
$errorMsg = '';
if (isset($_POST['formSent']) && $_POST['formSent']) {

    $userId  = (!empty($_POST['usersList'][0])) ? $_POST['usersList'][0] : 0;
    $errorMsg = (0 == $userId) ? get_lang('SelectedCoachSubstituteError') : '';
    if ($userId >= 0 && $idSession > 0  && !empty($dataHeader['course_code'])) {
        $flagOperation = SessionManager::setCoachSustitutionToCourseSession($userId, $idSession, $dataHeader['course_code'], $dataHeader['date']);

        Security::clear_token();
        $tok = Security::get_token();
        if (true == $flagOperation) {
            $message = get_lang('CoachSubstituteAdded');
            
            $redirectParams = array(
                'action' => 'show_message',
                'message' => $message,
                'sec_token' => $tok,
                'schedule' => $dataHeader['schedule'],
                'branch' => $dataHeader['branch'],
                'date' => $dataHeader['date'],
                'status' => $dataHeader['status'],
                'substitution_status' => $dataHeader['substitution_status']
            );
            
            header('Location: sessions_schedule.php?' . http_build_query($redirectParams));
        } else {
            $message = get_lang('CoachSubstituteNotAdded');
        }
    }
}

// display the dokeos header
Display::display_header($tool_name);
if (!empty($message)) {
    Display::display_error_message($message);
}

// the form header
$session_info = SessionManager::fetch($idSession);
$coacheNames = SessionManager::get_session_course_coaches_to_string($dataHeader['course_code'], $dataHeader['id_session']);

$headerInformation = <<<EOD
<table class="data_table">
    <tr>
        <td>Course</td>
        <td><strong>{$dataHeader['course']}</strong></td>
        <td>Room</td>
        <td><strong>{$dataHeader['room']}</strong></td>
    </tr>
    <tr>
        <td>Schedule</td>
        <td><strong>{$dataHeader['schedule_display']}</strong></td>
        <td>Teacher</td>
        <td><strong>$coacheNames</strong></td>
    </tr>
</table>
EOD;
echo $headerInformation;

$sessionCoach = array();
$sqlNotIn = '';
if ($idSession > 0 && !empty($dataHeader['course_code'])) {

    $resultSubs = SessionManager::getSessionCourseCoachesSubstitute($dataHeader['course_code'], $idSession, $dataHeader['date']);
    $userSubstitute = array();
    if (Database::num_rows($resultSubs) > 0) {
        $sqlNotIn = ' AND u.user_id NOT IN (';
        $users = Database::store_result($resultSubs, 'ASSOC');
        foreach($users as $user) {
            $userSubstitute[$user['id_user']] = $user ;
            $sqlNotIn .= "'" . $user['id_user']. "',";
        }
        $sqlNotIn = substr($sqlNotIn, 0, -1);
        $sqlNotIn .= ") ";
        $_SESSION['sqlNotIn'] = $sqlNotIn;
        unset($users);
    }

    $sql = "SELECT u.user_id, u.lastname, u.firstname, u.username
            FROM $tblUser AS u WHERE u.status = 1 $sqlNotIn order by u.lastname";
    $result = Database::query($sql);
    $coaches = Database::store_result($result);
    foreach($coaches as $coach) {
        $sessionCoach[$coach['user_id']] = $coach ;
    }
}
unset($coaches);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?<?php echo $urlConcat ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" onsubmit="valide();" >
    <input type="hidden" name="formSent" value="1" />
    <?php if(!empty($errorMsg)) {
        Display::display_warning_message($errorMsg);
    } ?>

    <table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
        <tr>
            <td width="45%" align="center">
                <?php echo get_lang('FirstLetterCourse'); ?> :
                <select name="firstLetterCourse" onchange = "listOrderBy(this.value)">
                    <option value="%">--</option>
                    <?php
                    echo Display :: get_alphabet_options();
                    echo Display :: get_numeric_options(0,9,'');
                    ?>
                </select>
            </td>
            <td></td>
        </tr>
        <tr>
            <td width="45%" align="center">
                <div id="ajax_list_users_multiple">
                    <select id="origin" name="noUsersList[]" size="20" style="width:360px;">
                        <?php foreach($sessionCoach as $enreg) { ?>
                            <option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname'], null, PERSON_NAME_EASTERN_ORDER).' ('.$enreg['username'].')'; ?></option>
                        <?php } unset($sessionCoach); ?>
                    </select>
                </div>
            </td>
            <td width="10%" valign="middle" align="center">
                <button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
                <br /><br />
                <button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>                <br /><br /><br /><br /><br /><br />
                <button class="save" type="button" value="" onclick="valide()" ><?php echo get_lang('Substitute') ?> </button>
            </td>
            <td width="45%" align="center">
                <select id='destination' name="usersList[]" multiple="multiple" size="20" style="width:360px;">
                    <?php foreach($userSubstitute as $enreg) : ?>
                        <option value="<?php echo $enreg['id_user']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname'], null, PERSON_NAME_EASTERN_ORDER).' ('.$enreg['username'].')'; ?></option>
                    <?php endforeach; unset($userSubstitute); ?>
                </select>
            </td>
        </tr>
    </table>
</form>

<script type="text/javascript">
    <!--
    function moveItem(origin , destination) {

        if('origin' == origin.id) {
            if (destination.length == 0) { // only first coach.
                _helper(origin, destination);
            }
        } else if ('destination' == origin.id) {
            _helper(origin, destination);
        };

        function _helper(origin, destination) {
            for(var i = 0 ; i<origin.options.length ; i++) {
                if(origin.options[i].selected) {
                    destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                    origin.options[i] = null;
                    i = i-1;
                }
            }            
            destination.selectedIndex = -1;
            sortOptions(destination.options);            
        }
    }

    function sortOptions(options) {
        newOptions = new Array();

        for (i = 0 ; i<options.length ; i++) {
            newOptions[i] = options[i];
        }

        newOptions = newOptions.sort(mysort);
        options.length = 0;

        for(i = 0 ; i < newOptions.length ; i++){
            options[i] = newOptions[i];
        }

    }

    function mysort(a, b) {
        if(a.text.toLowerCase() > b.text.toLowerCase()){
            return 1;
        }
        if(a.text.toLowerCase() < b.text.toLowerCase()){
            return -1;
        }
        return 0;
    }

    function valide(){
        var options = document.getElementById('destination').options;
        for (i = 0 ; i<options.length ; i++) {
            options[i].selected = true;
        }

        if (options.length == 0) {
            var messageConfirm = 'You sure you want to save without a substitute coach?';
            if (window.confirm(messageConfirm)) {
                document.forms.formulaire.submit();
            }
        } else {
            document.forms.formulaire.submit();
        }
    }
    -->
</script>
<?php Display::display_footer();?>
