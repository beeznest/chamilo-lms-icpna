<?php
/* For licensing terms, see /license.txt */

/*
 * This script will replicate learning paths (with their learning paths)
 * from a course to all (or specific) courses.
 *
 * First param will be considered as category ID.
 * The following params be considered as course codes.
 *
 * To replicate in all courses.
 *
 * php tests/scripts/replicate_lp_category.php 1
 *
 * To replicate in specific courses.
 *
 * php tests/scripts/replicate_lp_category.php 1 CODE1 CODE2 CODE3
 */

die("Script disabled".PHP_EOL);

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

array_shift($argv);

// Params
$origLpCategoryId = array_shift($argv);
$destCourses = $argv;

if (empty($origLpCategoryId)) {
    die("LP Category ID not indicated.".PHP_EOL);
}

// Process

$tblLp = Database::get_course_table(TABLE_LP_MAIN);

$lpCategory = getCategoryInfo($origLpCategoryId);
$lps = getLearningPaths($origLpCategoryId);

printLog(
    "Learning path category to replicate: {$lpCategory['iid']} - \"{$lpCategory['name']}\" with "
        .count($lps).' learning paths.'
);

foreach (getCourses($lpCategory['c_id'], $destCourses) as $courseInfo) {
    printLog("Replicating in course {$courseInfo['id']} - \"{$courseInfo['title']}\"");

    $newLpCategoryId = learnpath::createCategory(['c_id' => $courseInfo['id'], 'name' => $lpCategory['name']]);

    printLog("\tCategory created: $newLpCategoryId");

    foreach ($lps as $lp) {
        $newLpId = createLp($lp, $newLpCategoryId, $courseInfo['id']);

        printLog("\tLearning path created: $newLpId");
    }
}

printLog('Ending.');

/**
 * Get category info.
 *
 * @param int $id
 *
 * @return array
 */
function getCategoryInfo($id)
{
    $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

    return Database::select('*', $tblLpCategory, ['WHERE' => ['iid = ?' => $id]], 'first');
}

/**
 * @param int $lpCategoryId
 *
 * @return array
 */
function getLearningPaths($lpCategoryId)
{
    $tblLp = Database::get_course_table(TABLE_LP_MAIN);

    return Database::select('*', $tblLp, ['WHERE' => ['category_id = ? AND session_id = 0' => [$lpCategoryId]]]);
}

/**
 * @param int   $origCourseId
 * @param array $destCourses
 *
 * @return array
 */
function getCourses($origCourseId, array $destCourses = [])
{
    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);

    $filterCourses = $destCourses ? "AND code IN('".implode("', '", $destCourses)."')" : '';

    $result = Database::query("SELECT id, title FROM $tblCourse WHERE id != $origCourseId $filterCourses");

    while ($row = Database::fetch_assoc($result)) {
        yield $row;
    }
}

/**
 * @param array $origLp
 * @param int   $lpCategoryId
 * @param int   $destCourseId
 *
 * @return false|int
 */
function createLp(array $origLp, $lpCategoryId, $destCourseId)
{
    $tblLp = Database::get_course_table(TABLE_LP_MAIN);

    $lpParams = [
        'c_id' => $destCourseId,
        'lp_type' => $origLp['lp_type'],
        'name' => $origLp['name'],
        'path' => $origLp['path'],
        'ref' => $origLp['ref'],
        'description' => $origLp['description'],
        'content_local' => $origLp['content_local'],
        'default_encoding' => $origLp['default_encoding'],
        'default_view_mod' => $origLp['default_view_mod'],
        'prevent_reinit' => $origLp['prevent_reinit'],
        'force_commit' => $origLp['force_commit'],
        'content_maker' => $origLp['content_maker'],
        'display_order' => $origLp['display_order'],
        'js_lib' => $origLp['js_lib'],
        'content_license' => $origLp['content_license'],
        'author' => $origLp['author'],
        'preview_image' => $origLp['preview_image'],
        'use_max_score' => $origLp['use_max_score'],
        'autolaunch' => isset($origLp['autolaunch']) ? $origLp['autolaunch'] : '',
        'created_on' => empty($origLp['created_on']) ? api_get_utc_datetime() : $origLp['created_on'],
        'modified_on' => empty($origLp['modified_on']) ? api_get_utc_datetime() : $origLp['modified_on'],
        'publicated_on' => empty($origLp['publicated_on']) ? api_get_utc_datetime() : $origLp['publicated_on'],
        'expired_on' => $origLp['expired_on'],
        'debug' => $origLp['debug'],
        'theme' => '',
        'session_id' => 0,
        'prerequisite' => 0,
        'hide_toc_frame' => 0,
        'seriousgame_mode' => 0,
        'category_id' => $lpCategoryId,
        'max_attempts' => 0,
        'subscribe_users' => 0,
    ];

    $lpId = Database::insert($tblLp, $lpParams);

    Database::query("UPDATE $tblLp SET id = iid WHERE iid = $lpId");

    return $lpId;
}

/**
 * @param string $text
 */
function printLog($text)
{
    echo date(DateTime::ATOM)." $text".PHP_EOL;
}
