<?php
/* For licensing terms, see /license.txt */

/**
 *   HOME PAGE FOR EACH COURSE
 *
 *	This page, included in every course's index.php is the home
 *	page. To make administration simple, the teacher edits his
 *	course from the home page. Only the login detects that the
 *	visitor is allowed to activate, deactivate home page links,
 *	access to the teachers tools (statistics, edit forums...).
 *
 *	@package chamilo.course_home
 */
function return_block($title, $content)
{
    $html = '<div class="page-header">
<h3>'.$title.'</h3>
</div>
'.$content.'</div>';
    return $html;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$course_id = api_get_course_int_id();
$session_id = api_get_session_id();

//	MAIN CODE

if (api_is_allowed_to_edit(null, true)) {
	// HIDE
	if (!empty($_GET['hide'])) {
		$sql = "UPDATE $tool_table SET visibility=0 WHERE c_id = $course_id AND id=".$id;
		Database::query($sql);
		$show_message = Display::return_message(get_lang('ToolIsNowHidden'), 'confirmation');
	} elseif (!empty($_GET['restore'])) {
		// visibility 0,2 -> 1
		// REACTIVATE
		$sql = "UPDATE $tool_table SET visibility=1 WHERE c_id = $course_id AND id=".$id;
		Database::query($sql);
		//$show_message = Display::return_message(get_lang('ToolIsNowVisible'),'confirmation');
	}
}

// Work with data post askable by admin of course
if (api_is_platform_admin()) {
	// Show message to confirm that a tool it to be hidden from available tools
	// visibility 0,1->2
	if (!empty($_GET['askDelete'])) {
        $content .='<div id="toolhide">'.get_lang('DelLk').'<br />&nbsp;&nbsp;&nbsp;
            <a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;
            <a href="'.api_get_self().'?delete=yes&id='.$id.'">'.get_lang('Yes').'</a>
        </div>';
	} elseif (isset($_GET['delete']) && $_GET['delete']) {
        /*
        * Process hiding a tools from available tools.
        */
		//where $id is set?
		$id = intval($id);
		Database::query("DELETE FROM $tool_table WHERE c_id = $course_id AND id='$id' AND added_tool=1");
	}
}

//	COURSE ADMIN ONLY VIEW

// Start of tools for CourseAdmins (teachers/tutors)
$totalList = array();
if ($session_id == 0 && api_is_course_admin() && api_is_allowed_to_edit(null, true)) {
    $list = CourseHome::get_tools_category(TOOL_AUTHORING);
    $result = CourseHome::show_tools_category($list);
    $content .= return_block(get_lang('Authoring'), $result['content']);
    $totalList = $result['tool_list'];

    $list = CourseHome::get_tools_category(TOOL_INTERACTION);
    $list2 = CourseHome::get_tools_category(TOOL_COURSE_PLUGIN);
    $list = array_merge($list, $list2);
    $result = CourseHome::show_tools_category($list);
    $totalList = array_merge($totalList, $result['tool_list']);

    $content .= return_block(get_lang('Interaction'), $result['content']);

    $list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
    $totalList = array_merge($totalList, $list);
    $result = CourseHome::show_tools_category($list);

    $totalList = array_merge($totalList, $result['tool_list']);

    $content .= return_block(get_lang('Administration'), $result['content']);

} elseif (api_is_coach()) {

    $content .= '<div class="row">';
    $list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
    $content .= CourseHome::show_tools_category($result['content']);
    $totalList = array_merge($totalList, $result['tool_list']);
    $content .= '</div>';
} else {
    $list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
    if (count($list) > 0) {
        $content .= '<div class="row">';
        $result = CourseHome::show_tools_category($list);
        $content .= $result['content'];
        $totalList = array_merge($totalList, $result['tool_list']);
        $content .= '</div>';
    }
}

return array(
    'content' => $content,
    'tool_list' => $totalList
);
