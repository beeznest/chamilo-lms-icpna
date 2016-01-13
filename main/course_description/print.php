<?php
/* For license terms, see /license.txt */

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'course_description.lib.php';

api_protect_course_script(true);

$descriptionId = isset($_GET['description']) ? intval($_GET['description']) : 0;

if (empty($descriptionId)) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$sessionInfo = api_get_session_info($sessionId);

$courseDescription = new CourseDescription();
$courseDescription->set_session_id($sessionId);

$descriptionData = $courseDescription->get_data_by_id($descriptionId);

echo Display::display_reduced_header();
echo  '
    <div class="container">
        <h2 class="page-header">' . $courseInfo['title'] . ' (' . $sessionInfo['name'] . ')</h2>
        <div class="row">
            <div class="span12" id="description-content"></div>
        </div>
    </div>
';
echo Display::display_footer();
