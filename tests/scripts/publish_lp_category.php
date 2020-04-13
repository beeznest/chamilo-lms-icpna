<?php
/* For licensing terms, see /license.txt */

/*
 * This script will publish the learning paths in their course home.
 *
 * First param will be considered as category name.
 *
 * To replicate in all courses.
 *
 * php tests/scripts/publish_lp_category.php 'The category name'
 */

//die("Script disabled".PHP_EOL);

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

array_shift($argv);

$lpCategoryName = array_shift($argv);

printLog("Learning path category \"{$lpCategoryName}\" to course tool.");

foreach (getCoursesToPublish($lpCategoryName) as $courseInfo) {
    $lpCategoryId = getLpCategoryIdByName($lpCategoryName, $courseInfo['id']);

    if (empty($lpCategoryId)) {
        continue;
    }

    if (existsToolInCourse($lpCategoryName, $courseInfo['id'])) {
        printLog("Tool \"$lpCategoryName\" already exists in course \"{$courseInfo['title']}\"");

        continue;
    }

    printLog("Replicating in course \"{$courseInfo['title']}\"");

    $toolId = createTool($lpCategoryId, $lpCategoryName, $courseInfo['id']);

    printLog("\tCourse tool created: $toolId");
}

printLog("Ending");

/**
 * @param string $categoryName
 *
 * @return Generator
 */
function getCoursesToPublish($categoryName)
{
    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

    $result = Database::query(
        "SELECT DISTINCT c.id, c.title FROM $tblCourse c
            INNER JOIN $tblLpCategory clp ON c.id = clp.c_id
            WHERE clp.name = '$categoryName'");

    while ($row = Database::fetch_assoc($result)) {
        yield $row;
    }
}

/**
 * @param string $categoryName
 * @param int    $cId
 *
 * @return int
 */
function getLpCategoryIdByName($categoryName, $cId)
{
    $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

    $result = Database::fetch_assoc(
        Database::query(" SELECT iid FROM $tblLpCategory WHERE name = '$categoryName' AND c_id = $cId LIMIT 1")
    );

    if (empty($result)) {
        return 0;
    }

    return $result['iid'];
}

/**
 * @param string $categoryName
 * @param int    $cId
 *
 * @return bool
 */
function existsToolInCourse($categoryName, $cId)
{
    $tblCTool = Database::get_course_table(TABLE_TOOL_LIST);

    $result = Database::fetch_assoc(
        Database::query("SELECT COUNT(1) c FROM $tblCTool WHERE name = '$categoryName' AND c_id = $cId")
    );

    return $result['c'] > 0;
}

function createTool($categoryId, $categoryName, $cId)
{
    $tblCTool = Database::get_course_table(TABLE_TOOL_LIST);

    $toolId = Database::insert(
        $tblCTool,
        [
            'category' => 'authoring',
            'c_id' => $cId,
            'name' => $categoryName,
            'link' => "lp/lp_controller.php?action=view_category&id=$categoryId",
            'image' => 'lp_category.gif',
            'visibility' => 1,
            'admin' => 0,
            'address' => 'pastillegris.gif',
            'added_tool' => 0,
            'session_id' => 0,
            'target' => '_self',
        ]
    );

    Database::query("UPDATE $tblCTool SET id = iid WHERE iid = $toolId");

    return $toolId;
}

function getCategoryLink($categoryId)
{
    return '';
}

/**
 * @param string $text
 */
function printLog($text)
{
    echo date(DateTime::ATOM)." $text".PHP_EOL;
}
