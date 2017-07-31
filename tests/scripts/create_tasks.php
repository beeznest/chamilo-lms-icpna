<?php
/* For licensing terms, see /license.txt */
/**
 * This script creates tasks directories in each course, at the session level,
 * only for sessions active at the time of running the script
 * In order for the script to set the right permissions, it has to be launched
 * either as www-data or root
 */
exit();
if (PHP_SAPI != 'cli') {
    exit('This script can only be executed from the command line');
}
/**
 * Validate if is the correct user to execute the script
 */
$user = trim(shell_exec('whoami'));
if ($user != 'www-data') {
    $advice = PHP_EOL;
    $advice .= "The current user is *" . $user . "* you must be *www-data* to execute this command" . PHP_EOL;
    $advice .= "Follow this example:" . PHP_EOL;
    $advice .= "\t# su - www-data" . PHP_EOL;
    $advice .= "\t$ cd /var/www/virtual.icpna.edu.pe/www/tests" . PHP_EOL;
    $advice .= "\t$ php5 create_tasks.php" . PHP_EOL;
    $advice .= PHP_EOL;
    exit ($advice);
}

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/work/work.lib.php';
$date = api_get_utc_datetime();
$workNameConstant = 'ALP';
$courseList = [];
$sessionList = [];

/**
 * Get the sessions list
 */
$sql = "SELECT id FROM session 
        WHERE access_start_date < '$date' AND access_end_date > '$date'";
$res = Database::query($sql);

/**
 * Get the course-session couple
 */
$sessionCourse = array();
while ($row = Database::fetch_assoc($res)) {
    $courseList = SessionManager::getCoursesInSession($row['id']);
    $sessionList[$row['id']] = $courseList;
}

//$cacheCourseList = [];

/**
 * Now create the tasks using the addDir function
 */
foreach ($sessionList as $sessionId => $courseList) {
    foreach ($courseList as $courseId) {
        // Check if a folder already exists in this session
        $sql = "SELECT id, title 
                FROM c_student_publication
                WHERE 
                    filetype = 'folder' AND 
                    c_id = $courseId AND 
                    session_id = $sessionId";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            // Task found, skip
            $row = Database::fetch_assoc($res);
            echo "Task ".$row['title']." already found in course $courseId, session $sessionId\n";
            continue;
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        $workName = $workNameConstant.'_'.$courseInfo['code'];
        $workNameWithSession = $workName.'_'.$sessionId;
        $params = array(
            'new_dir' => $workNameWithSession, // the internal name path
            'work_title' => $workName, // soft name
            'description' => '',
            'qualification' => 0,
            'weight' => 0,
            'allow_text_assignment' => 0
        );
        $res = addDir($params, 1, $courseInfo, null, $sessionId);
        if ($res === false) {
            echo "Could not create task $workName in course #$courseId, session #$sessionId, for some reason\n";
        } else {
            echo "Task $workName created in course $courseId, session $sessionId. Task ID is $res\n";
        }
    }
}
echo "All done!\n";


