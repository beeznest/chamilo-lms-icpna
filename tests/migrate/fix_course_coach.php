<?php
/* For licensing terms, see /license.txt */
/**
 * Set course coach if there is not assigned already one 
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.migration
 */
if (PHP_SAPI != 'cli') {
    exit;
}

require_once dirname(__FILE__) . '/../../main/inc/global.inc.php';

$year = '2015';

$sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
$sessionCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$sql = "SELECT s.id, s.id_coach, sc.course_code 
    FROM $sessionTable s 
    INNER JOIN $sessionCourseTable sc ON s.id = sc.id_session 
    WHERE YEAR( s.display_start_date ) >= $year 
    AND s.id NOT IN( 
        SELECT scu.id_session 
        FROM $sessionCourseUserTable scu 
        INNER JOIN $sessionTable s2 ON scu.id_session = s2.id 
        WHERE scu.status = 2 AND 
        YEAR( s2.display_start_date ) >= $year
    )";

$result = Database::query($sql);

$numRows = Database::num_rows($result);

echo "$numRows sessions without course coach\n\n";

if ($numRows <= 0) {
    die("Exit\n");
}

while ($resultData = Database::fetch_assoc($result)) {
    echo "Inserting course coach ({$resultData['id_coach']}) "
    . "in course ({$resultData['course_code']}) in session ({$resultData['id']})\n";

    SessionManager::set_coach_to_course_session($resultData['id_coach'], $resultData['id'], $resultData['course_code']);
}

echo "Finish\n";
