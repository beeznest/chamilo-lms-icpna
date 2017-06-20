<?php
/* For licensing terms, see /license.txt */
/**
 * This script checks how many files from the c_student_publication table
 * are not present on disk to check, for example, if there is some kind
 * of widespread issue in writing files to disk when students upload them.
 */
die();
require_once '../../main/inc/global.inc.php';

$dateStart = '2016-02-01';
$dateEnd   = '2016-02-29';

$tCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT id, code, directory FROM $tCourse";
$result = Database::query($sql);
$courses = array();
while ($row = Database::fetch_assoc($result)) {
    $courses[$row['id']] = $row['directory'];
}

$tWork = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$sql = "SELECT c_id, id, url, title, active, sent_date, session_id, user_id
    FROM $tWork
    WHERE sent_date >= '$dateStart'
      AND sent_date <= '$dateEnd'
      AND active = 1
      AND filetype = 'file'
      AND url != ''
    ORDER BY c_id, session_id, sent_date";
$result = Database::query($sql);
$list = array();
while ($row = Database::fetch_assoc($result)) {
    $list[] = $row;
}
echo "Total number of tasks registered: " . count($list) . PHP_EOL;

$notFilesCounter = 0;
$coursesDir = api_get_path(SYS_COURSE_PATH);
$missingList = array();
foreach ($list as $item) {
    $path = $coursesDir . $courses[$item['c_id']] . '/'. $item['url'];
    if (!is_file($path) || !is_readable($path)) {
        echo $path . ' ' . $item['title'] . ' User: ' . $item['user_id'] . PHP_EOL;
        $notFilesCounter++;
        $missingList[] = array($item['session_id'], $item['c_id'], $courses[$item['c_id']], $path);
    }
}
echo "$notFilesCounter were missing from disk" . PHP_EOL;

$file = '/tmp/list.csv';
foreach ($missingList as $item) {
    file_put_contents($file, join(';', $item) . "\r\n", FILE_APPEND);
}
echo "Missing files written to $file" . PHP_EOL;
