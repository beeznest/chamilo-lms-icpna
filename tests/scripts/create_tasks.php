<?php
/* For licensing terms, see /license.txt */
/**
 * This script creates tasks directories in each course, at the session level,
 * only for sessions active at the time of running the script
 * In order for the script to set the right permissions, it has to be launched
 * either as www-data or root
 */
die();
if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
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
    die ($advice);
}


require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/work/work.lib.php';
$date = date('Y-m-d h:i:s');
$workName = 'ALP';
$courseInfos = array();

/**
 * Get the sessions list
 */
//$sd = 'date_start';
$sd = 'access_start_date';
//$ed = 'date_end';
$ed = 'access_end_date';
$sql = "SELECT id FROM session WHERE $sd < '$date' AND $ed > '$date'";
$res = Database::query($sql);
if ($res === false) {
    die("Error querying sessions: ".Database::error($res)."\n");
}
/**
 * Get the course-session couple
 */
$sessionCourse = array();
while ($row = Database::fetch_assoc($res)) {
    $sql2 = "SELECT c.id AS cid, c.code as ccode 
            FROM course c, session_rel_course s 
            WHERE s.id_session = ".$row['id']." 
            AND s.course_code = c.code";
    $res2 = Database::query($sql2);
    if ($res2 === false) {
        die("Error querying courses for session ".$row['id'].": ".Database::error($res2)."\n");
    }
    if (Database::num_rows($res2) > 0) {
        while ($row2 = Database::fetch_assoc($res2)) {
            $sessionCourse[$row['id']] = $row2['cid'];
            if (empty($courseInfos[$row2['ccode']])) {
                $courseInfos[$row2['cid']] = api_get_course_info($row2['ccode']);
            }
        }
    }
}
/**
 * Now create the tasks using the addDir function
 */
foreach ($sessionCourse as $sid => $cid) {
    // Check if a folder already exists in this session
    $sql = "SELECT id, title FROM c_student_publication
        WHERE filetype = 'folder'
            AND c_id = $cid
            AND session_id = $sid";
    $res = Database::query($sql);
    if ($res === false) {
        echo "Error querying table c_student_publication: $sql\n";
        echo "The error message was: ".Database::error($res)."\n";
        continue;
    }
    if (Database::num_rows($res) > 0) {
        //Task found, skip
        $row = Database::fetch_assoc($res);
        echo "Task ".$row['title']." already found in course $cid, session $sid\n";
        continue;
    }
    $params = array(
        'new_dir' => $workName,
        'description' => '',
        'qualification' => 0,
        'weight' => 0,
        'allow_text_assignment' => 0
    );
    $res = addDir($params, 1, $courseInfos[$cid], null, $sid);
    if ($res === false) {
        echo "Could not create task $workName in course $cid, session $sid, for some reason\n";
    } else {
        echo "Task $workName created in course $cid, session $sid. Task ID is $res\n";
    }
}
echo "All done!\n";

/**
 * Creates a new task (directory) in the assignment tool
 * @param array $params
 * @param int $user_id
 * @param array $courseInfo
 * @param int $group_id
 * @param int $session_id
 * @return bool|int
 * @note $params can have the following elements, but should at least have the 2 first ones: (
 *       'new_dir' => 'some-name',
 *       'description' => 'some-desc',
 *       'qualification' => someintvalue (e.g. 20),
 *       'weight' => someintweight (percentage) to add to gradebook (e.g. 50),
 *       'allow_text_assignment' => 0/1/2,
 * @todo Rename createAssignment or createWork, or something like that
 */
function addDir($params, $user_id, $courseInfo, $group_id, $session_id)
{
    $work_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
    $user_id = intval($user_id);
    $group_id = intval($group_id);
    $session_id = intval($session_id);

    $base_work_dir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/work';
    $course_id = $courseInfo['real_id'];

    $directory          = replace_dangerous_char($params['new_dir']);
    //$directory                = disable_dangerous_file($directory);
    $created_dir        = create_unexisting_work_directory($base_work_dir, $directory);

    if (!empty($created_dir)) {
        $dir_name_sql = '/'.$created_dir;
        $today = api_get_utc_datetime();
        $sql = "INSERT INTO " . $work_table . " SET
                c_id = $course_id,
                url = '".Database::escape_string($dir_name_sql)."',
                title = '".Database::escape_string($params['new_dir'])."',
                description = '".Database::escape_string($params['description'])."',
                author = '',
                active = '1',
                accepted = '1',
                filetype = 'folder',
                post_group_id = '".$group_id."',
                sent_date = '".$today."',
                qualification = '".(($params['qualification'] != '') ? Database::escape_string($params['qualification']) : '') ."',
                parent_id = '',
                qualificator_id = '',
                date_of_qualification = '0000-00-00 00:00:00',
                weight = '".Database::escape_string($params['weight'])."',
                session_id = '".$session_id."',
                allow_text_assignment = '".Database::escape_string($params['allow_text_assignment'])."',
                contains_file = 0,
                user_id = '".$user_id."'";

        Database::query($sql);
        // Add the directory
        $id = Database::insert_id();
        if ($id) {
            // Folder created
            api_item_property_update($courseInfo, 'work', $id, 'DirectoryCreated', $user_id, $group_id);
            //updatePublicationAssignment($id, $params, $courseInfo, $group_id);

            if (api_get_course_setting('email_alert_students_on_new_homework') == 1) {
                send_email_on_homework_creation(api_get_course_id(), null, $id);
            }
            return $id;
        }
    }
    return false;
}

