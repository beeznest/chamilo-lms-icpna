<?php

/**
 * Branch Logout
 */
require_once '../global.inc.php';

$trackECourseAccessTable = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

$userId = api_get_user_id();

$lastCourseAccessSQL = "SELECT session_id FROM $trackECourseAccessTable WHERE user_id = $userId ORDER BY login_course_date DESC LIMIT 0, 1";
$lastCourseAccessResult = Database::query($lastCourseAccessSQL);

if (Database::num_rows($lastCourseAccessResult) > 0) {
    $lastSessionId = Database::result($lastCourseAccessResult, 0, 'session_id');

    $branch = new Branch();
    $branchId = $branch->getBranchId($lastSessionId);

    foreach ($_configuration['branch_logout'] as $tool) {
        if (!array_key_exists($branchId, $tool)) {
            continue;
        }

        $url = $tool[$branchId];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        curl_close($ch);
    }
}