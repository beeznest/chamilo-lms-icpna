<?php
/* For licensing terms, see /license.txt */

/*
 * This script will replicate learning paths (with their learning paths)
 * from a course to all (or specific) courses.
 *
 * First param will be considered as category ID.
 * The following params be considered as course codes.
 */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;

die("Script disabled".PHP_EOL);

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';
$args = [
    'CODE1' => [
        'courses' => ['CODE2', 'CODE3', 'CODE4'],
        'lps' => [
            'Category name 1' => [], // replicate all learning paths in this category
            'Category name 2' => ['Learning path 1 to ignore']
        ]
    ],
];

printLog('Replicating learning paths to courses');
printLog(PHP_EOL);

foreach ($args as $masterCourseCode => $courseParams) {
    $courseInfo = api_get_course_info($masterCourseCode);

    if (empty($courseInfo)) {
        printLog("Course $masterCourseCode not found");

        continue;
    }

    printLog("From course $masterCourseCode");

    $cbLpCategory = [];
    $cbLp = [];
    $cbDocuments = [];

    foreach ($courseParams['lps'] as $categoryName => $lpNamesToAvoid) {
        $lpCategoryId = getLpCategoryId($categoryName, $masterCourseCode);

        if (empty($lpCategoryId)) {
            continue;
        }

        $lpIds = getLpIds($lpCategoryId, $lpNamesToAvoid);

        $cbLpCategory[] = $lpCategoryId;
        $cbLp = array_merge($cbLp, $lpIds);
    }

    $cbDocuments = getDocumentIds($courseInfo['real_id'], $cbLp);

    printLog("Documents: ".implode(', ', $cbDocuments));
    printLog("Learning path categories: ".implode(', ', $cbLpCategory));
    printLog("Learning paths: ".implode(', ', $cbLp));

    $courseBuilder = new CourseBuilder('', $courseInfo);
    $courseBuilder->set_tools_to_build(['documents', 'learnpath_category', 'learnpaths']);
    $courseBuilder->set_tools_specific_id_list(
        [
            'documents' => $cbDocuments,
            'learnpath_category' => $cbLpCategory,
            'learnpaths' => $cbLp,
        ]
    );

    $course = $courseBuilder->build(0, $masterCourseCode);

    $courseParams['courses'] = array_filter($courseParams['courses'], function ($courseCode) use ($cbLp) {
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            printLog("\tCourse $courseCode not found");

            return false;
        }

        return true;
    });

    foreach ($courseParams['courses'] as $courseCode) {
        $courseRestorer = new CourseRestorer($course);
        $courseRestorer->set_add_text_in_items(false);
        $courseRestorer->restore(
            $courseCode,
            0,
            false,
            false
        );

        printLog("\tReplicated in course $courseCode");
    }
}

/**
 * @param string $categoryName
 * @param string $courseCode
 *
 * @return int
 */
function getLpCategoryId($categoryName, $courseCode)
{
    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

    $row = Database::fetch_assoc(
        Database::query(
            "SELECT lpc.iid FROM $tblLpCategory lpc
            INNER JOIN $tblCourse c ON lpc.c_id = c.id
            WHERE c.code = '$courseCode' AND lpc.name = '$categoryName'"
        )
    );

    if (empty($row)) {
        return 0;
    }

    return $row['iid'];
}

/**
 * @param int   $categoryId
 * @param array $lpNamesToAvoid
 *
 * @return array
 */
function getLpIds($categoryId, array $lpNamesToAvoid = [])
{
    $sql = "SELECT iid FROM c_lp WHERE category_id = $categoryId";

    if (!empty($lpNamesToAvoid)) {
        $sql .= " AND name NOT IN ('".implode(', ', $lpNamesToAvoid)."')";
    }

    $query = Database::query($sql);

    $result = [];

    while ($row = Database::fetch_assoc($query)) {
        $result[] = $row['iid'];
    }

    return $result;
}

/**
 * @param int   $courseId
 * @param array $categoryIds
 *
 * @return array
 */
function getDocumentIds($courseId, array $categoryIds = [])
{
    $tblDocument = Database::get_course_table(TABLE_DOCUMENT);
    $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);

    $query = Database::query(
        "SELECT d.iid, d.path FROM $tblDocument d
        INNER JOIN $tblLpItem lpi ON (d.iid = lpi.path AND d.c_id = lpi.c_id)
        WHERE d.c_id = $courseId AND lpi.lp_id IN (".implode(', ', $categoryIds).")
            AND (d.session_id = 0 OR d.session_id IS NULL)"
    );

    $result = [];

    while ($row = Database::fetch_assoc($query)) {
        $result[] = $row['iid'];

        $directoriesId = getDirectoriesId($row['path'], $courseId);

        $result = array_merge($result, $directoriesId);
    }

    $result = array_unique($result);

    sort($result);

    return $result;
}

/**
 * @param string $filePath
 * @param int    $courseId
 *
 * @return array
 */
function getDirectoriesId($filePath, $courseId)
{
    $tblDocument = Database::get_course_table(TABLE_DOCUMENT);

    $result = [];

    do {
        $filePath = dirname($filePath);

        $row = Database::fetch_assoc(
            Database::query(
                "SELECT iid FROM $tblDocument d
                WHERE path = '$filePath' AND c_id = $courseId AND (session_id = 0 OR session_id IS NULL)"
            )
        );

        if (empty($row)) {
            break;
        }

        $result[] = $row['iid'];
    } while (empty($filePath) || $filePath != '/');

    return $result;
}

/**
 * @param string $text
 */
function printLog($text)
{
    echo date(DateTime::ATOM)." $text".PHP_EOL;
}
