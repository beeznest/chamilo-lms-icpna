<?php
/* For licensing terms, see /license.txt */
/**
 * This script creates tasks directories in each course, at the session level,
 * only for sessions active at the time of running the script
 * In order for the script to set the right permissions, it has to be launched
 * either as www-data or root
 */
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
    $advice .= "\t$ php5 create_tasks_ajt.php" . PHP_EOL;
    $advice .= PHP_EOL;
    file_put_contents('/tmp/ajt.log', '['.date('Y-m-d H:i:s').'] User was not www-data'."\n", FILE_APPEND);
    exit ($advice);
}
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/work/work.lib.php';
$date = api_get_utc_datetime();
$workNameConstant = 'AJT';
$courseList = [];
$sessionList = [];
$coursesFilter = [
    'B01',
    'B02',
    'B03',
    'B04',
    'B05',
    'B06',
    'B07',
    'B08',
    'B09',
    'B10',
    'B11',
    'B12',
    'AB02D',
    'AB04D',
    'AB06D',
    'AB08D',
    'AB10D',
    'AB12D',
    'B01SA',
    'B02SA',
    'B03SA',
    'B04SA',
    'B05SA',
    'B06SA',
    'B07SA',
    'B08SA',
    'B09SA',
    'B10SA',
    'B11SA',
    'B12SA',
    'B01SI',
    'B02SI',
    'B03SI',
    'B04SI',
    'B05SI',
    'B06SI',
    'B07SI',
    'B08SI',
    'B09SI',
    'B10SI',
    'B11SI',
    'B12SI',
    'I01',
    'I02',
    'I03',
    'I04',
    'I05',
    'I06',
    'I07',
    'I08',
    'I09',
    'I10',
    'I11',
    'I12',
    'AI02D',
    'AI04D',
    'AI06D',
    'AI08D',
    'AI10D',
    'AI12D',
    'I01SA',
    'I02SA',
    'I03SA',
    'I04SA',
    'I05SA',
    'I06SA',
    'I07SA',
    'I08SA',
    'I09SA',
    'I10SA',
    'I11SA',
    'I12SA',
    'ALS1',
    'ALS2',
    'ALS3',
    'AG1',
    'AG2',
    'AG3',
    'ARW1',
    'ARW2',
    'ARW3',
    'AP1',
    'AP2',
    'AP3',
    'TPC1',
    'TPC2',
    'MET1',
    'MET2',
    'MET3',
    'MET4',
    'MET5',
    'MET6',
];
$coursesFilterString = '"'.implode('" ,"', $coursesFilter).'"';
$sql = 'SELECT id FROM course WHERE code IN ('.$coursesFilterString.')';
$res = Database::query($sql);
$coursesIds = '';
while ($row = Database::fetch_assoc($res)) {
    $coursesIds .= $row['id'].', ';
}
$coursesIds = substr($coursesIds, 0, -2);

/**
 * Get the sessions list
 */
$sql = "SELECT distinct(s.id) as sid, count(sc.c_id) as cc FROM session s, session_rel_course sc
	WHERE s.id = sc.session_id
	AND s.access_start_date < '$date' AND s.access_end_date > '$date'
	AND sc.c_id IN ($coursesIds)
        GROUP BY s.id";
$res = Database::query($sql);
/**
 * Get the course-session couple
 */
$sessionCourse = array();
while ($row = Database::fetch_assoc($res)) {
    if ($row['cc'] > 1) {
        //ignore sessions with more than 1 course
        continue;
    }
    // Re-calling getCoursesInSession is inefficient but makes the algorithm easier to read
    $courseList = SessionManager::getCoursesInSession($row['sid']);
    $sessionList[$row['sid']] = $courseList;
}

//$cacheCourseList = [];

/**
 * Now create the tasks using the addDir function
 */
$counter = 0;
foreach ($sessionList as $sessionId => $courseList) {
    foreach ($courseList as $courseId) {
        // Check if a /AJT* folder already exists in this session
        $sql = "SELECT id, title
                FROM c_student_publication
                WHERE
                    filetype = 'folder' AND
                    c_id = $courseId AND
		    session_id = $sessionId AND
                    url LIKE '/".$workNameConstant."%'";
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
	//echo(print_r($params, 1));
        $res = addDir($params, 1, $courseInfo, null, $sessionId);
        if ($res === false) {
            echo "Could not create task $workName in course #$courseId, session #$sessionId, for some reason\n";
        } else {
            echo "Task $workName created in course $courseId, session $sessionId. Task ID is $res\n";
	    $counter++;
	}
    }
}
file_put_contents('/tmp/ajt.log', '['.date('Y-m-d H:i:s').'] '.$counter.' tasks created'."\n", FILE_APPEND);
echo "All done!\n";

