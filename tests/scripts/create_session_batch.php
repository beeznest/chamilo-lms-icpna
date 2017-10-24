<?php
/* For licensing terms, see /license.txt */

//die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

/* Params */
$numberOfSessions = 1; //Number of session to create
$courseCodes = [
]; //Course code for sessions. One course for session
$sessionStartDate = '2017-11-01 00:00:00'; //Session start date (include timezone shift)
$sessionEndDate = '2017-11-01 04:59:59'; //Session end date (include timezone shift)
$prefix = ''; //Prefix for session name. Identify by Task ticket
/* End params  */

$executions = ceil($numberOfSessions / count($courseCodes));
$created = 0;

$createSessionForCourse = function ($courseCode, $suffix = '') use ($prefix, $sessionStartDate, $sessionEndDate) {
    $courseId = api_get_course_int_id($courseCode);
    $sessionName = trim("$prefix $courseCode $suffix");

    $sessionId = SessionManager::create_session(
        $sessionName,
        $sessionStartDate,
        $sessionEndDate,
        $sessionStartDate,
        $sessionEndDate,
        $sessionStartDate,
        $sessionEndDate,
        1,
        0
    );

    if (intval($sessionId) == 0) {
        echo "Session $sessionName no created".PHP_EOL;

        return;
    }

    SessionManager::add_courses_to_session($sessionId, [$courseId]);

    $sessionInfo = SessionManager::fetch($sessionId);

    echo "Session created {$sessionId} - {$sessionInfo['name']}".PHP_EOL;
};

for ($i = 0; $i < $executions; $i++) {
    foreach ($courseCodes as $code) {
        if ($created >= $numberOfSessions) {
            continue;
        }

        $createSessionForCourse($code, '#'.($i + 1));

        $created++;
    }
}
