<?php
/* For licensing terms, see /license.txt */

/**
 * Branch Logout
 */

require_once '../global.inc.php';

$trackECourseAccessTable = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

$userId = api_get_user_id();

$lastCourseAccessSQL = "SELECT session_id FROM $trackECourseAccessTable WHERE user_id = $userId ORDER BY login_course_date DESC LIMIT 0, 1";
$lastCourseAccessResult = Database::query($lastCourseAccessSQL);

$branchId = '0';

if (Database::num_rows($lastCourseAccessResult) > 0) {
    $lastSessionId = Database::result($lastCourseAccessResult, 0, 'session_id');

    $branch = new Branch();
    $branchId = $branch->getBranchId($lastSessionId);
}

if ($branchId != '0') {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $_configuration['branch_logout']['ecl'][$branchId]);
}
