<?php
/* For licensing terms, see /license.txt */
/**
 * 	This script allows platform admins to add users to courses.
 * 	It displays a list of users and a list of courses;
 * 	you can select multiple users and courses and then click on
 * 	'Add to this(these) course(s)'.
 *
 * 	@package chamilo.admin
 * 	@todo use formvalidator for the form
 */
/* INIT SECTION */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

/* Global constants and variables */

$first_letter_user = '';
$first_letter_course = '';
$courses = array();
$users = array();

global $_configuration, $where_filter;

$xajax = new xajax();
$xajax->registerFunction('search_users');

$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

/* Header */
$tool_name = get_lang('AddUsersToACourse');
$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');

foreach (array('jquery.ba-bbq.min.js', 'jquery.validate.js', 'jquery.form.js', 'jquery.form.wizard.js', 'jquery.dataTables.min.js') as $js)
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$js.'" type="text/javascript" language="javascript"></script>'."\n";

$htmlHeadXtra[] = '
<script type="text/javascript">
function validate_filter() {
        document.formulaire.form_sent.value=0;
        document.formulaire.submit();
}
</script>';
$htmlHeadXtra[] = '
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
	var newOptions = new Array();
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
	if (a.text.toLowerCase() > b.text.toLowerCase()) {
		return 1;
	}
	if (a.text.toLowerCase() < b.text.toLowerCase()) {
		return -1;
	}
	return 0;
}

function valide() {
	var options = document.getElementById("destination").options;
	for (i = 0 ; i<options.length ; i++) {
		options[i].selected = true;
	}
	document.forms.formulaire.submit();
}
function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
-->
</script>';

//Extra Fields
//checking for extra field with filter on
$extra_field_list = UserManager::get_extra_fields();
global $new_field_list;
$new_field_list = array();
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        //if is enabled to filter and is a "<select>" field type
        if ($extra_field[8] == 1 && $extra_field[2] == 4) {
            $new_field_list[] = array(
                'name' => $extra_field[3],
                'variable' => $extra_field[1],
                'data' => $extra_field[9],
                'default' => $extra_field[4]
            );
        }
    }
}

//Filter by Extra Fields
function getWhereExtraFields($new_field_list, $arrFields = array()) {
    global $_configuration;

    $where_filter = '';
    if (!empty($new_field_list)) {
        $use_extra_fields = false;
        if (is_array($new_field_list) && count($new_field_list) > 0) {
            $result_list = array();
            foreach ($new_field_list as $new_field) {
                $varname = 'field_' . $new_field['variable'];
                if (UserManager::is_extra_field_available($new_field['variable'])) {

                    if (!empty($arrFields))
                        $new_field['default'] = $arrFields[$varname];

                    if (isset($new_field['default']) && $new_field['default'] != '0') {
                        $use_extra_fields = true;
                        $extra_field_result[] = UserManager::get_extra_user_data_by_value($new_field['variable'], $new_field['default'], false);
                    }
                }
            }
        }

        $final_result = array();
        if (count($extra_field_result) > 1) {
            for ($i = 0; $i < count($extra_field_result) - 1; $i++) {
                if (is_array($extra_field_result[$i + 1])) {
                    $final_result = array_intersect($extra_field_result[$i], $extra_field_result[$i + 1]);
                }
            }
        } else {
            $final_result = $extra_field_result[0];
        }

        if ($use_extra_fields) {
            if ($_configuration['multiple_access_urls']) {
                if (is_array($final_result) && count($final_result) > 0) {
                    $where_filter = " AND u.user_id IN  ('" . implode("','", $final_result) . "') ";
                } else {
                    //no results
                    $where_filter = " AND u.user_id  = -1";
                }
            } else {
                if (is_array($final_result) && count($final_result) > 0) {
                    $where_filter = " AND user_id IN  ('" . implode("','", $final_result) . "') ";
                } else {
                    //no results
                    $where_filter = " AND user_id  = -1";
                }
            }
        }
    }

    return $where_filter;
}

function search_users($dataForm) {
    global $_configuration, $tbl_user, $users, $new_field_list;
    $needle = $dataForm['user_to_add'];
    if (empty($needle))
        $needle = '%';

    $xajax_response = new XajaxResponse();
    $return = '';

    $arrFields = array();
    foreach ($dataForm as $key => $value)
        if (strpos($key, 'field_') !== false)
            $arrFields[$key] = $value;

    $where_filter = getWhereExtraFields($new_field_list, $arrFields);

    $target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
    $sql = "SELECT user_id,lastname,firstname,username
        FROM $tbl_user
        WHERE user_id<>2 $where_filter AND " .
            $target_name . " LIKE '%" . $needle . "%'
        ORDER BY " . (count($users) > 0 ? "(user_id IN(" . implode(',', $users) . ")) DESC," : "") . " " . $target_name;

    if ($_configuration['multiple_access_urls']) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "SELECT u.user_id,lastname,firstname,username  FROM " . $tbl_user . " u
                        INNER JOIN $tbl_user_rel_access_url user_rel_url
                        ON (user_rel_url.user_id = u.user_id)
                        WHERE u.user_id<>2 $where_filter AND access_url_id =  $access_url_id AND (" . $target_name . " LIKE '%" . $needle . "%' )
                        ORDER BY " . (count($users) > 0 ? "(u.user_id IN(" . implode(',', $users) . ")) DESC," : "") . " " . $target_name;
        }
    }

    $rs = Database::query($sql);

    $return .= '<select name="UserList[]" multiple="multiple" size="20" style="width:300px;">';

    while ($user = Database :: fetch_array($rs)) {
        $personName = api_get_person_name($user['firstname'], $user['lastname']);
        $isSelected = (in_array($user['user_id'], $users)) ? 'selected="selected"' : "";
        $return .= '<option value="' . $user['user_id'] . '"' .
                $isSelected .
                '>' . $personName .
                ' (' . $user['username'] . ')</option>';
    }
    $return .= '</select>';
    $xajax_response->addAssign('ajax_list_users_multiple', 'innerHTML', api_utf8_encode($return));

    return $xajax_response;
}

$xajax->processRequests();

// displaying the header
Display :: display_header($tool_name);

$link_add_group = '<a href="usergroups.php">' . Display::return_icon('multiple.gif', get_lang('RegistrationByUsersGroups')) . get_lang('RegistrationByUsersGroups') . '</a>';
echo '<div class="actions">' . $link_add_group . '</div>';

// displaying the tool title
// api_display_tool_title($tool_name);

$form = new FormValidator('subscribe_user2course');
$form->addElement('header', '', $tool_name);
$form->display();

/* MAIN CODE */


/* Display GUI */
if (empty($first_letter_user)) {
    $sql = "SELECT count(*) as nb_users FROM $tbl_user";
    $result = Database::query($sql);
    $num_row = Database::fetch_array($result);
    if ($num_row['nb_users'] > 1000) {//if there are too much users to gracefully handle with the HTML select list,
        // assign a default filter on users names
        $first_letter_user = 'A';
    }
    unset($result);
}

$where_filter = getWhereExtraFields($new_field_list);

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sql = "SELECT user_id,lastname,firstname,username
        FROM $tbl_user
        WHERE user_id<>2 AND " . $target_name . " LIKE '" . $first_letter_user . "%' $where_filter
        ORDER BY " . (count($users) > 0 ? "(user_id IN(" . implode(',', $users) . ")) DESC," : "") . " " . $target_name;

if ($_configuration['multiple_access_urls']) {
    $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT u.user_id,lastname,firstname,username  FROM " . $tbl_user . " u
        INNER JOIN $tbl_user_rel_access_url user_rel_url
        ON (user_rel_url.user_id = u.user_id)
        WHERE u.user_id<>2 AND access_url_id =  $access_url_id AND (" . $target_name . " LIKE '" . $first_letter_user . "%' ) $where_filter
        ORDER BY " . (count($users) > 0 ? "(u.user_id IN(" . implode(',', $users) . ")) DESC," : "") . " " . $target_name;
    }
}

$result = Database::query($sql);
$db_users = Database::store_result($result);
unset($result);

/* React on POSTed request */
if (!empty($_POST['form_sent'])) {
    $form_sent = $_POST['form_sent'];
    $users = (isset($_POST['UserList']) && is_array($_POST['UserList']) ? $_POST['UserList'] : array());
    $courses = (isset($_POST['CourseList']) && is_array($_POST['CourseList']) ? $_POST['CourseList'] : array());
    $first_letter_user = $_POST['firstLetterUser'];
    $first_letter_course = $_POST['firstLetterCourse'];

    foreach ($users as $key => $value) {
        $users[$key] = intval($value);
    }

    if ($form_sent == 1) {
        if (count($users) == 0 || count($courses) == 0) {
            Display :: display_error_message(get_lang('AtLeastOneUserAndOneCourse'));
        } else {
            foreach ($courses as $course_code) {
                foreach ($users as $user_id) {
                    CourseManager::subscribe_user($user_id,$course_code);
                }
            }
            Display :: display_confirmation_message(get_lang('UsersAreSubscibedToCourse'));
        }
    }
}

$sql = "SELECT code,visual_code,title FROM $tbl_course WHERE visual_code LIKE '" . $first_letter_course . "%' ORDER BY " . (count($courses) > 0 ? "(code IN('" . implode("','", $courses) . "')) DESC," : "") . " visual_code";

if ($_configuration['multiple_access_urls']) {
    $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT code, visual_code, title
                FROM $tbl_course as course
                  INNER JOIN $tbl_course_rel_access_url course_rel_url
                ON (course_rel_url.course_code= course.code)
                  WHERE access_url_id =  $access_url_id  AND (visual_code LIKE '" . $first_letter_course . "%' ) ORDER BY " . (count($courses) > 0 ? "(code IN('" . implode("','", $courses) . "')) DESC," : "") . " visual_code";
    }
}

$result = Database::query($sql);
$db_courses = Database::store_result($result);
unset($result);

if ($_configuration['multiple_access_urls']) {
    $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sqlNbCours = "	SELECT course_rel_user.course_code, course.title
            FROM $tbl_course_user as course_rel_user
            INNER JOIN $tbl_course as course
            ON course.code = course_rel_user.course_code
              INNER JOIN $tbl_course_rel_access_url course_rel_url
            ON (course_rel_url.course_code= course.code)
              WHERE access_url_id =  $access_url_id  AND course_rel_user.user_id='" . $_user['user_id'] . "' AND course_rel_user.status='1'
              ORDER BY course.title";
    }
}
?>

<form class="form-horizontal" id="formulaire" name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
    <?php
    if (is_array($extra_field_list)) {
        if (is_array($new_field_list) && count($new_field_list) > 0) {

            echo '<h3>' . get_lang('FilterUsers') . '</h3>';
            foreach ($new_field_list as $new_field) {
                echo $new_field['name'];
                $varname = 'field_' . $new_field['variable'];
                echo '&nbsp;<select id="' . $varname . '" name="' . $varname . '">';
                echo '<option value="0">--' . get_lang('Select') . '--</option>';
                foreach ($new_field['data'] as $option) {
                    $checked = '';
                    if ($new_field_list[0]['default'] == $option[1]) {
                        $checked = 'selected="selected"';
                    }
                    echo '<option value="' . $option[1] . '" ' . $checked . '>' . $option[1] . '</option>';
                }
                echo '</select>';
                echo '&nbsp;&nbsp;';
            }
            echo '<input type="button" value="' . get_lang('Filter') . '" onclick="OnKeyRequestBuffer.modified(\'formulaire\', \'user_to_add\')" />';
            echo '<br /><br />';
        }
    }
    ?>
    <input type="hidden" name="form_sent" value="1"/>
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td width="40%" align="center">
                <b><?php echo get_lang('UserList'); ?></b>
                <br/><br/>
                <input type="text" id="user_to_add" name="user_to_add" onkeyup="OnKeyRequestBuffer.modified('formulaire', 'user_to_add')"/>
            </td>
            <td width="20%">&nbsp;</td>
            <td width="40%" align="center">
                <b><?php echo get_lang('CourseList'); ?> :</b>
                <br/><br/>
                <?php echo get_lang('FirstLetterCourse'); ?> :
                <select name="firstLetterCourse" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
                    <option value="">--</option>
                    <?php
                    echo Display :: get_alphabet_options($first_letter_course);
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%" align="center"
                <div id="ajax_list_users_multiple">
                    <select name="UserList[]" multiple="multiple" size="20" style="width:300px;">
                        <?php
                        foreach ($db_users as $user) {
                            ?>
                            <option value="<?php echo $user['user_id']; ?>" <?php if (in_array($user['user_id'], $users)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($user['firstname'], $user['lastname']) . ' (' . $user['username'] . ')'; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </td>
            <td width="20%" valign="middle" align="center">
                <button type="submit" class="add" value="<?php echo get_lang('AddToThatCourse'); ?> &gt;&gt;"><?php echo get_lang('AddToThatCourse'); ?></button>
            </td>
            <td width="40%" align="center">
                <select name="CourseList[]" multiple="multiple" size="20" style="width:300px;">
                    <?php
                    foreach ($db_courses as $course) {
                        ?>
                        <option value="<?php echo $course['code']; ?>" <?php if (in_array($course['code'], $courses)) echo 'selected="selected"'; ?>><?php echo '(' . $course['visual_code'] . ') ' . $course['title']; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
    </table>
</form>
<script>
    var OnKeyRequestBuffer = 
        {
        bufferText: false,
        bufferTime: 500,
        
        modified : function(frmId ,txtId)
        {
            setTimeout('OnKeyRequestBuffer.compareBuffer("' + frmId + '","' + txtId + '");', this.bufferTime);
        },
        compareBuffer : function(frmId, txtId)
        {
            if (xajax.$(txtId).value.length > 3 || xajax.$(txtId).value.length == 0)
            {
                OnKeyRequestBuffer.makeRequest(frmId);
            }
        },
        makeRequest : function(frmId)
        {
            xajax_search_users(xajax.getFormValues(frmId));
        }
    }
    $(document).ready(function(){
        $('#formulaire').validate({
                    rules: {
                        user_to_add: {
                            minlength: 4
                        }
                    }
         });
    })
</script>
<?php
/* FOOTER */
Display :: display_footer();