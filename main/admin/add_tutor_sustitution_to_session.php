<?php

$language_file='admin';
// resetting the course id
$cidReset = true;
require_once '../inc/global.inc.php';


$id_session = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;
$id_session = 462137;
SessionManager::protect_session_edit($id_session);

$xajax = new xajax();
$xajax -> registerFunction ('search_users');

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'inout.php','name' => get_lang('InOut'));
$interbreadcrumb[] = array('url' => "#", 'name' => get_lang('CoachSustitute'));
//$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id_session,"name" => get_lang('CoachSustitute'));

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
//$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type']!='') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}
$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;

$dataHeader = getDataHeader($id_session);
/**
 * Get data basic, for panel sustitution
 * @param $id_session
 * @return array
 */
function getDataHeader($id_session) {
    $session_field = new SessionField();
    $session_fields = $session_field->get_all();
    $data = array();
    foreach ($session_fields as $session_field) {
        if ($session_field['field_visible'] != '1') {
            continue;
        }
        $obj = new SessionFieldValue();
        $result = $obj->get_values_by_handler_and_field_id($id_session, $session_field['id'], true);

        $session_key = strtolower($session_field['field_display_text']);
        $session_value = ($result) ? $result['field_value'] : null;

        if ($session_field['id'] == 4) {
            $data[$session_key] = $session_value;
        } else if ($session_field['id'] == 6) {
            $data[$session_key] = $session_value;
        }
    }

    return $data;
}


/**
 * Function is use for ajax
 * @param $needle
 * @param $type
 * @return XajaxResponse
 */
function search_users($needle, $type) {
    global $tbl_user, $tbl_session_rel_user, $id_session;
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

        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

        switch($type) {
            case 'single':
                $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                        WHERE (username LIKE "'.$needle.'%" OR firstname LIKE "'.$needle.'%"
                            OR lastname LIKE "'.$needle.'%") AND user.status = 1'. $order_clause.' LIMIT 11';
                break;
            case 'multiple':
                $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                        WHERE '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.status=1 '.$order_clause;
                break;
        }

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                switch($type) {
                    case 'single':
                        $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                        INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
                        WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
                        OR firstname LIKE "'.$needle.'%"
                        OR lastname LIKE "'.$needle.'%") AND user.status=1 '.$order_clause.' LIMIT 11';
                        break;
                    case 'multiple':
                        $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                        INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
                        WHERE access_url_id = '.$access_url_id.' AND
                                '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.status = 1 '.$order_clause;
                        break;
                }
            }
        }

        $rs = Database::query($sql);
        $i = 0;
        if ($type == 'single') {
            while ($user = Database :: fetch_array($rs)) {
                $i++;
                if ($i <= 10) {
                    $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
            $xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));
        } else {
            $return .= '<select id="origin_users" name="usersList[]" multiple="multiple" size="20" style="width:360px;">';
            while ($user = Database :: fetch_array($rs)) {
                $person_name = api_get_person_name($user['firstname'], $user['lastname']);
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

	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
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

    $userId  = $_POST['usersList'][0];
    if ($userId > 0 ) {
        $course_code = $dataHeader['course_code'];
        SessionManager::set_coach_sustitution_to_course_session($userId, $id_session, $course_code);
        header('Location: pagina.php?id_session='.$id_session);
    }

}

// display the dokeos header
Display::display_header('');
if($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').' ';
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'&nbsp;&nbsp;&nbsp;';
    $link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}

// the form header
$session_info = SessionManager::fetch($id_session);
echo '<div class="actions">';
echo $link_add_type_unique.$link_add_type_multiple;
echo '</div>';
$headerInformation = <<<EOD
<table class="data_table">
    <tr>
        <td>Phase</td>
        <td><strong>(001) Basic Daily</strong></td>
        <td>Room</td>
        <td><strong>{$dataHeader['aula']}</strong></td>
    </tr>
    <tr>
        <td>Course</td>
        <td><strong>(B10) Basic Ten</strong></td>
        <td>Status</td>
        <td><strong>Registered</strong></td>
    </tr>
    <tr>
        <td>Schedule</td>
        <td><strong>{$dataHeader['horario']}</strong></td>
        <td>Teacher</td>
        <td><strong>{$dataHeader['teacher']}</strong></td>
    </tr>
</table>
EOD;
echo $headerInformation;


$ajax_search = ($add_type == 'unique') ? true : false;
$sessionCourses = array();

if ($ajax_search == true || $ajax_search == false) {

    $order_clause = api_sort_by_first_name() ?
        ' ORDER BY u.firstname, u.lastname, u.username' :
        ' ORDER BY u.lastname, u.firstname, u.username';

    $sql = "SELECT user_id, lastname, firstname, username
            FROM $tbl_user AS u WHERE u.status = 1" . $order_clause;


    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "SELECT u.user_id, lastname, firstname, username
            FROM $tbl_user u
            INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id = u.user_id)
            WHERE access_url_id = $access_url_id AND u.status = 1 " . $order_clause;
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
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo $page; ?>&id_session=<?php echo $id_session; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
    <input type="hidden" name="formSent" value="1" />
    <?php if(!empty($errorMsg)) {
        Display::display_normal_message($errorMsg); //main API
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
                        <select id="origin" name="noUsersList[]" multiple="multiple" size="20" style="width:360px;">
                            <?php foreach($sessionCourses as $enreg) { ?>
                                <option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
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
                </select>
            </td>
        </tr>
    </table>
</form>

<script type="text/javascript">
    <!--
    function moveItem(origin , destination) {
        for(var i = 0 ; i<origin.options.length ; i++) {
            if(origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                origin.options[i]=null;
                i = i-1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
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