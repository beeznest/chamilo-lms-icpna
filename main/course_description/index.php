<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_description
*/

// name of the language file that needs to be included
$language_file = array ('course_description', 'accessibility');

// including files 
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_COURSE_DESCRIPTION;

require_once api_get_path(LIBRARY_PATH).'course_description.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'course_description_controller.php';
require_once api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php';

// defining constants
define('ADD_BLOCK', 8);

// current section
$this_section = SECTION_COURSES;

// protect a course script
api_protect_course_script(true);

// get actions
$actions = array('listing', 'add', 'edit', 'delete', 'history');
$action = 'listing';
if (isset($_GET['action']) && in_array($_GET['action'],$actions)) {
	$action = $_GET['action'];
}

$description_type = '';
if (isset($_GET['description_type'])) {
	$description_type = intval($_GET['description_type']);
}

$id = null;
if (isset($_GET['id'])) {
	$id = intval($_GET['id']);
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') {
	$action = 'listing';
}

// interbreadcrumb
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('CourseProgram'));

// course description controller object
$course_description_controller = new CourseDescriptionController();

// distpacher actions to controller
switch ($action) {	
	case 'listing':	
    	$course_description_controller->listing();
    	break;	
	case 'history':		
		$course_description_controller->listing(true);
		break;
	case 'add'	  :
        if (api_is_platform_admin()) {
            if (api_is_allowed_to_edit(null,true)) {
                $course_description_controller->add();
            }
        } else {
            header('Location: '.$_SERVER['PHP_SELF']);
        }
		break;
	case 'edit'	  :	
		if (api_is_platform_admin()) {
            if (api_is_allowed_to_edit(null,true)) {
                $course_description_controller->edit($id, $description_type);
            }
        } else {
            header('Location: '.$_SERVER['PHP_SELF']);
        }
		break;
	case 'delete' :	
        if (api_is_platform_admin()) {
            if (api_is_allowed_to_edit(null,true)) {
                $course_description_controller->destroy($id);
            }
        } else {
            header('Location: '.$_SERVER['PHP_SELF']);
        }
		break;
	default		  :	
		$course_description_controller->listing();
}
