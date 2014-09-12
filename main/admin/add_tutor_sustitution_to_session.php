<?php
$language_file='admin';
// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';

if (!api_is_teacher_admin() || intval($_GET['id_session']) <= 0) {
    api_not_allowed(true);
}
$id_session = intval($_GET['id_session']);

$this_section = IN_OUT_MANAGEMENT;

$xajax = new xajax();
$xajax -> registerFunction ('search_users');

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'sessions_schedule.php','name' => get_lang('InOut'));
$interbreadcrumb[] = array('url' => "#", 'name' => get_lang('CoachSubstitute'));

// Database Table Definitions
$tbl_session                        = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_course                         = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user                           = Database::get_main_table(TABLE_MAIN_USER);
//$tbl_session_rel_user             = Database::get_main_table(TABLE_MAIN_SESSION_USER);

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type']!='') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}
$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;
$dataHeader = $_REQUEST;
$urlConcat = $_SERVER['QUERY_STRING'];

/**
 * Function is use for ajax
 * @param $needle
 * @param $type
 * @return XajaxResponse
 */
function search_users($needle, $type) {
    global $tbl_user, $tbl_session_rel_user, $id_session;
    $sqlNotIn = !empty($_SESSION['sqlNotIn']) ?  $_SESSION['sqlNotIn'] : '';
    $xajax_response = new XajaxResponse();
    $return = '';

    if (!empty($needle) && !empty($type)) {
        //normal behaviour
        if ($needle == 'false')  {
            $type = 'multiple';
            $needle = '';
        }

        // xajax send utf8 datas... datas in db can be non-utf8 datas
        $charset = api_get_system_encoding();
        $needle = Database::escape_string($needle);
        $needle = api_convert_encoding($needle, $charset, 'utf-8');

        switch ($type) {
            case 'multiple':
                $sql = 'SELECT u.user_id, u.username, u.lastname, u.firstname FROM '.$tbl_user.' u
                        WHERE u.lastname  LIKE "'.$needle.'%" AND u.status = 1  '.$sqlNotIn.' order by u.lastname';
                break;
        }

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $orderby = "ORDER BY u.lastname";
                switch($type) {
                    case 'multiple':
                        $sql = 'SELECT u.user_id, u.username, u.lastname, u.firstname FROM '.$tbl_user.' u
                        INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id = u.user_id)
                        WHERE access_url_id = '.$access_url_id.' AND u.lastname LIKE "'.$needle.'%" AND u.status = 1 ' .
                            $sqlNotIn . $orderby;
                        break;
                }
            }
        }

        $rs = Database::query($sql);
        if ($type == 'multiple') {
            $return .= '<select id="origin" name="usersList[]" multiple="multiple" size="20" style="width:360px;">';
            while ($user = Database :: fetch_array($rs)) {
                $person_name = api_get_person_name($user['firstname'], $user['lastname'], null, PERSON_NAME_EASTERN_ORDER);
                //echo $person_name;
                $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
            }
            $return .= '</select>';
            $xajax_response -> addAssign('ajax_list_users_multiple','innerHTML',api_utf8_encode($return));
        }
    }

    return $xajax_response;
}

$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_session (code, content) {
    document.getElementById("course_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";

    destination = document.getElementById("destination");
    
    for (i=0;i<destination.length;i++) {
        if(destination.options[i].text == content) {
            return false;
        }
    }
    // only one couch sustitute
    if ( destination.options.length == 0) {
        destination.options[destination.length] = new Option(content,code);
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }
}

function remove_item(origin)
{
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}
</script>';

$formSent = 0;
$errorMsg = '';
if (isset($_POST['formSent']) && $_POST['formSent']) {

    $userId  = (!empty($_POST['usersList'][0])) ? $_POST['usersList'][0] : 0;
    $errorMsg = (0 == $userId) ? get_lang('SelectedCoachSubstituteError') : '';
    if ($userId > 0 && $id_session > 0  && !empty($dataHeader['course_code'])) {
        $flagOperation = SessionManager::set_coach_sustitution_to_course_session($userId, $id_session, $dataHeader['course_code']);

        Security::clear_token();
        $tok = Security::get_token();
        if (true == $flagOperation) {
            $message = get_lang('CoachSubstituteAdded');
            header('Location: sessions_schedule.php?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
        } else {
            $message = get_lang('CoachSubstituteNotAdded');
        }
    }
}

// display the dokeos header
Display::display_header('');
if (!empty($message)) {
    Display::display_error_message($message);
}

// the form header
$session_info = SessionManager::fetch($id_session);

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
        <td><strong>{$dataHeader['schedule']}</strong></td>
        <td>Teacher</td>
        <td><strong>{$dataHeader['coach']}</strong></td>
    </tr>
</table>
EOD;
echo $headerInformation;


$ajax_search = ($add_type == 'unique') ? true : false;
$sessionCourses = array();
$sqlNotIn = '';
if ($ajax_search == true || $ajax_search == false) {

    // select user teacher substitute
    $userSubstitute = array();
    $roleSubstitute = ROLE_COACH_SUBSTITUTE;
    $sqlSubs = "SELECT sr.id_user, u.lastname, u.firstname, u.username FROM session_rel_course_rel_user sr
    INNER JOIN user u on sr.id_user = u.user_id
    WHERE sr.id_session = '$id_session'
    AND sr.course_code = '".$dataHeader['course_code']."'
    AND sr.status = '$roleSubstitute' ";

    $resultSubs = Database::query($sqlSubs);
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
            FROM $tbl_user AS u WHERE u.status = 1 $sqlNotIn order by u.lastname";

    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "SELECT u.user_id, lastname, firstname, username
            FROM $tbl_user u
            INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id = u.user_id)
            WHERE access_url_id = $access_url_id AND u.status = 1 $sqlNotIn ORDER BY u.lastname";
        }
    }

    $result = Database::query($sql);
    $Courses = Database::store_result($result);
    foreach($Courses as $course) {
        $sessionCourses[$course['user_id']] = $course ;
    }
}
unset($Courses);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?<?php echo $urlConcat ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
    <input type="hidden" name="formSent" value="1" />
    <?php if(!empty($errorMsg)) {
        Display::display_warning_message($errorMsg);
    } ?>

    <table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
        <?php if($add_type == 'multiple') { ?>
            <tr><td width="45%" align="center">
                    <?php echo get_lang('FirstLetterCourse'); ?> :
                    <select name="firstLetterCourse" onchange = "xajax_search_users(this.value,'multiple')">
                        <option value="%">--</option>
                        <?php
                        echo Display :: get_alphabet_options();
                        echo Display :: get_numeric_options(0,9,'');
                        ?>
                    </select>
                </td>
                <td>&nbsp;</td></tr>
        <?php } ?>

        <tr>
            <td width="45%" align="center">
                <?php if(!($add_type == 'multiple')) { ?>
                    <input type="text" id="course_to_add" onkeyup="xajax_search_users(this.value,'single')" />
                    <div id="ajax_list_users_single"></div>
                <?php } else { ?>
                    <div id="ajax_list_users_multiple">
                        <select id="origin" name="noUsersList[]" size="20" style="width:360px;">
                            <?php foreach($sessionCourses as $enreg) { ?>
                                <option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname'], null, PERSON_NAME_EASTERN_ORDER).' ('.$enreg['username'].')'; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php }  unset($sessionCourses); ?>
            </td>
            <td width="10%" valign="middle" align="center">
                <?php if ($ajax_search) { ?>
                    <button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination'))"></button>
                <?php } else { ?>
                    <button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
                    <br /><br />
                    <button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>
                <?php } ?>

                <br /><br /><br /><br /><br /><br />
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
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;

        document.forms.formulaire.submit();
    }
    -->
</script>
<?php Display::display_footer();?>