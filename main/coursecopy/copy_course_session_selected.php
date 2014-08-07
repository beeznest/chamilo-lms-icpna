<?php
/* For licensing terms, see /license.txt */
/**
 * Copy resources from one course in a session to another one.
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @author Julio Montoya    <gugli100@gmail.com> Lots of bug fixes/improvements
 * @package chamilo.backup
 */
/**
 * Code
 */
/*  INIT SECTION  */

// Language files that need to be included
$language_file = array('coursebackup', 'admin');
require_once '../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true, true);

require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'xajax/xajax.inc.php';

require_once 'classes/CourseBuilder.class.php';
require_once 'classes/CourseRestorer.class.php';
require_once 'classes/CourseSelectForm.class.php';

$xajax = new xajax();
$xajax->registerFunction('search_courses');

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

if (!api_is_coach()) {
    api_not_allowed(true);
}

$courseCode = api_get_course_id();
$courseInfo = api_get_course_info($courseCode);
$sessionId = api_get_session_id();

if (empty($courseCode) OR empty($sessionId)) {
    api_not_allowed(true);
}

// Remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 1800);
}

$this_section = SECTION_COURSES;
$nameTools = get_lang('CopyCourse');
$returnLink = api_get_path(
        WEB_CODE_PATH
    ) . 'course_info/maintenance_coach.php?' . api_get_cidreq();
$interbreadcrumb[] = array(
    'url' => $returnLink,
    'name' => get_lang('Maintenance')
);

// Database Table Definitions
$tbl_session_rel_course_rel_user = Database::get_main_table(
    TABLE_MAIN_SESSION_COURSE_USER
);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

/* FUNCTIONS */

function make_select_session_list($name, $sessions, $attr = array())
{

    $attrs = '';
    if (count($attr) > 0) {
        foreach ($attr as $key => $value) {
            $attrs .= ' ' . $key . '="' . $value . '"';
        }
    }
    $output = '<select name="' . $name . '" ' . $attrs . '>';

    if (count($sessions) == 0) {
        $output .= '<option value = "0">' . get_lang(
                'ThereIsNotStillASession'
            ) . '</option>';
    } else {
        $output .= '<option value = "0">' . get_lang(
                'SelectASession'
            ) . '</option>';
    }

    if (is_array($sessions)) {
        foreach ($sessions as $session) {
            $category_name = '';
            if (!empty($session['category_name'])) {
                $category_name = ' (' . $session['category_name'] . ')';
            }

            $output .= '<option value="' . $session['id'] . '">' . $session['name'] . ' ' . $category_name . '</option>';
        }
    }
    $output .= '</select>';
    return $output;
}

function display_form()
{
    global $returnLink;

    $courseInfo = api_get_course_info();
    $sessionId = api_get_session_id();
    $sessions = SessionManager::get_sessions_coached_by_user($sessionId);
    $html = '';
    // Actions
    $html .= '<div class="actions">';
    // Link back to the documents overview
    $html .= '<a href="' . $returnLink . '">' . Display::return_icon(
            'back.png',
            get_lang('BackTo') . ' ' . get_lang('Maintenance'),
            '',
            ICON_SIZE_MEDIUM
        ) . '</a>';
    $html .= '</div>';

    $html .= Display::return_message(
        get_lang('CopyCourseFromSessionToSessionExplanation')
    );

    $html .= '<form name="formulaire" method="post" action="' . api_get_self(
        ) . '?' . api_get_cidreq() . '" >';
    $html .= '<table border="0" cellpadding="5" cellspacing="0" width="100%">';

    // Source
    $html .= '<tr><td width="15%"><b>' . get_lang(
            'OriginCoursesFromSession'
        ) . ':</b></td>';
    $html .= '<td width="10%" align="left">' . api_get_session_name(
            $sessionId
        ) . '</td>';
    $html .= '<td width="50%">';
    $html .= $courseInfo['title'] . '</td></tr>';

    // Destination
    $html .= '<tr><td width="15%"><b>' . get_lang(
            'DestinationCoursesFromSession'
        ) . ':</b></td>';
    $html .= '<td width="10%" align="left"><div id="ajax_sessions_list_destination">';
    $html .= '<select name="sessions_list_destination" onchange="javascript: xajax_search_courses(this.value,\'destination\');">';
    if (empty($sessions)) {
        $html .= '<option value = "0">' . get_lang(
                'ThereIsNotStillASession'
            ) . '</option>';
    } else {
        $html .= '<option value = "0">' . get_lang(
                'SelectASession'
            ) . '</option>';
        foreach ($sessions as $session) {
            if ($session['id'] == $sessionId) {
                continue;
            }
            $html .= '<option value="' . $session['id'] . '">' . $session['name'] . '</option>';
        }
    }

    $html .= '</select ></div></td>';

    $html .= '<td width="50%">';
    $html .= '<div id="ajax_list_courses_destination">';
    $html .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" ></select></div></td>';
    $html .= '</tr></table>';

    $html .= '<h3>' . get_lang('TypeOfCopy') . '</h3>';
    $html .= '<label class="radio"><input type="radio" id="copy_option_1" name="copy_option" value="full_copy" checked="checked"/>';
    $html .= get_lang('FullCopy') . '</label><br/>';
    $html .= '<label class="radio"><input type="radio" id="copy_option_2" name="copy_option" value="select_items"/>';
    $html .= ' ' . get_lang('LetMeSelectItems') . '</label><br/>';

    $html .= '<label class="checkbox"><input type="checkbox" id="copy_base_content_id" name="copy_only_session_items" />' . get_lang(
            'CopyOnlySessionItems'
        ) . '</label><br /><br/>';

    $html .= '<button class="save" type="submit" onclick="javascript:if(!confirm(' . "'" . addslashes(
            api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)
        ) . "'" . ')) return false;">' . get_lang('CopyCourse') . '</button>';
    $html .= '</form>';
    echo $html;
}

function search_courses($id_session, $type)
{
    $xajax_response = new XajaxResponse();
    $return = null;
    $courseCode = api_get_course_id();

    if (!empty($type)) {
        $id_session = intval($id_session);
        $courseList = SessionManager::get_course_list_by_session_id(
            $id_session
        );
        $return .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" >';
        foreach ($courseList as $course) {
            $course_list_destination[] = $course['code'];
            if ($course['code'] != $courseCode) {
                continue;
            }
            $course_title = $course['title'];
            $course_title = str_replace("'", "\'", $course_title);
            $return .= '<option value="' . $course['code'] . '" title="' . @htmlspecialchars(
                        $course['title'] . ' (' . $course['visual_code'] . ')',
                        ENT_QUOTES,
                        api_get_system_encoding()
                    ) . '">' .
                    $course['title'] . ' (' . $course['visual_code'] . ')
                    </option>';
        }
        $return .= '</select>';
        $_SESSION['course_list_destination'] = $course_list_destination;

        // Send response by ajax
        $xajax_response ->addAssign(
            'ajax_list_courses_destination',
            'innerHTML',
            api_utf8_encode($return)
        );
    }
    return $xajax_response;
}

$xajax ->processRequests();

/* HTML head extra */

$htmlHeadXtra[] = $xajax->getJavascript(
    api_get_path(WEB_LIBRARY_PATH) . 'xajax/'
);
$htmlHeadXtra[] = '<script>

function checkSelected(id_select,id_radio,id_title,id_destination) {
    var num=0;
    obj_origin = document.getElementById(id_select);
    obj_destination = document.getElementById(id_destination);

    for (x=0;x<obj_origin.options.length;x++) {
      if (obj_origin.options[x].selected) {
            if (obj_destination.options.length > 0) {
                for (y=0;y<obj_destination.options.length;y++) {
                        if (obj_origin.options[x].value == obj_destination.options[y].value) {
                            obj_destination.options[y].selected = true;
                        }
                }
            }
            num++;
        } else {
            if (obj_destination.options.length > 0) {
                for (y=0;y<obj_destination.options.length;y++) {
                    if (obj_origin.options[x].value == obj_destination.options[y].value) {
                        obj_destination.options[y].selected = false;
                    }
                }
            }
        }
    }

    if (num == 1) {
      document.getElementById(id_radio).disabled = false;
      document.getElementById(id_title).style.color = \'#000\';
    } else {
      document.getElementById(id_radio).disabled = true;
      document.getElementById(id_title).style.color = \'#aaa\';
    }

}
</script>';

Display::display_header($nameTools);

$with_base_content = true;
if (isset($_POST['copy_only_session_items']) && $_POST['copy_only_session_items']) {
    $with_base_content = false;
}

/*  MAIN CODE  */

if ((isset($_POST['action']) && $_POST['action'] == 'course_select_form') ||
    (isset($_POST['copy_option']) && $_POST['copy_option'] == 'full_copy')
) {

    $destination_course = $destination_session = '';
    $origin_course = api_get_course_id();
    $origin_session = api_get_session_id();

    if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {

        $destination_course = $_POST['destination_course'];
        $destination_session = $_POST['destination_session'];
        $course = CourseSelectForm::get_posted_course(
            'copy_course',
            $origin_session,
            $origin_course
        );

        $cr = new CourseRestorer($course);
        $cr->restore($destination_course, $destination_session);
        Display::display_confirmation_message(get_lang('CopyFinished'));
        display_form();
    } else {

        $arr_course_origin = SessionManager::get_course_list_by_session_id(
            api_get_session_id()
        );
        $arr_course_destination = array();
        $destination_session = '';

        if (isset($_POST['SessionCoursesListDestination'])) {
            $arr_course_destination = $_POST['SessionCoursesListDestination'];
        }

        if (isset($_POST['sessions_list_destination'])) {
            $destination_session = $_POST['sessions_list_destination'];
        }

        if ((is_array($arr_course_origin) && count(
                    $arr_course_origin
                ) > 0) && !empty($destination_session)
        ) {
            //We need only one value
            if (count($arr_course_origin) > 1 || count(
                    $arr_course_destination
                ) > 1
            ) {
                Display::display_error_message(
                    get_lang('YouMustSelectACourseFromOriginalSession')
                );
            } else {
                //foreach ($arr_course_origin as $course_origin) {
                //first element of the array
                $course_code = $arr_course_origin[0];
                $course_destinatination = $arr_course_destination[0];

                $course_origin = api_get_course_info($course_code);
                $cb = new CourseBuilder('', $course_origin);
                $course = $cb->build(
                    $origin_session,
                    $course_code,
                    $with_base_content
                );
                $cr = new CourseRestorer($course);
                $cr->restore($course_destinatination, $destination_session);

            }
            Display::display_confirmation_message(get_lang('CopyFinished'));
            display_form();
        } else {
            Display::display_error_message(
                get_lang('YouMustSelectACourseFromOriginalSession')
            );
            display_form();
        }
    }
} elseif (isset($_POST['copy_option']) && $_POST['copy_option'] == 'select_items') {

    // Else, if a CourseSelectForm is requested, show it
    if (api_get_setting('show_glossary_in_documents') != 'none') {
        Display::display_normal_message(
            get_lang('ToExportDocumentsWithGlossaryYouHaveToSelectGlossary')
        );
    }

    $origin_session = api_get_session_id();
    $courseCode = api_get_course_id();
    $arr_course_destination = array();
    $destination_session = '';

    if (isset($_POST['SessionCoursesListDestination'])) {
        $arr_course_destination = $_POST['SessionCoursesListDestination'];
    }
    if (isset($_POST['sessions_list_destination'])) {
        $destination_session = $_POST['sessions_list_destination'];
    }

    if (!empty($destination_session)) {
        Display::display_normal_message(
            get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz')
        );
        $course_origin = api_get_course_info();
        $cb = new CourseBuilder('', $course_origin);
        $course = $cb->build($origin_session, $courseCode, $with_base_content);
        $hidden_fields['destination_course'] = $arr_course_destination[0];
        $hidden_fields['destination_session'] = $destination_session;
        $hidden_fields['origin_course'] = api_get_course_id();
        $hidden_fields['origin_session'] = api_get_session_id();

        CourseSelectForm :: display_form($course, $hidden_fields, true);
        echo '<div style="float:right"><a href="javascript:window.back();">' .
            Display::return_icon(
                'back.png',
                get_lang('Back') . ' ' . get_lang('To') . ' ' . get_lang(
                    'PlatformAdmin'
                ),
                array('style' => 'vertical-align:middle')
            ) .
            get_lang('Back') . '</a></div>';
    } else {
        Display::display_error_message(
            get_lang(
                'You must select a course from original session and select a destination session'
            )
        );
        display_form();
    }
} else {
    display_form();
}

Display::display_footer();
