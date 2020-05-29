<?php
/* For licensing terms, see /license.txt */

/*
 * This script will delete LPs and their categories replicated with replacte_lp_category.php script.
 *
 * First param will be considered as category ID.
 * The following params be considered as course codes.
 */

die("Script disabled".PHP_EOL);

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$args = [
    'B01' => ['B01TT', 'B01MW', 'B01AM', 'B01AT', 'B01SI', 'B01SA', 'AB02D'],
];

$deleteItemOnly = false;

// Process

foreach ($args as $masterCourseCode => $childrenCourseCodes) {
    $masterCourseInfo = api_get_course_info($masterCourseCode);

    if (empty($masterCourseInfo)) {
        echo "Course master $masterCourseCode not found".PHP_EOL;

        continue;
    }

    deleteFromCourse($masterCourseCode, $masterCourseCode, $deleteItemOnly);

    foreach ($childrenCourseCodes as $childCourseCode) {
        deleteFromCourse($childCourseCode, $masterCourseCode, $deleteItemOnly);
    }
}

// Functions

/**
 * @param string $categoryName
 * @param int    $courseId
 *
 * @return int
 */
function getLpCategoryId($categoryName, $courseId)
{
    $courseId = (int) $courseId;
    $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);

    $category = Database::fetch_assoc(
        Database::query(
            "SELECT iid FROM $tblLpCategory WHERE name = '$categoryName' AND c_id = $courseId LIMIT 1"
        )
    );

    if (empty($category)) {
        return 0;
    }

    return $category['iid'];
}

/**
 * @param string $lpName
 * @param int    $categoryId
 * @param int    $courseId
 *
 * @return int
 */
function getLpId($lpName, $categoryId, $courseId)
{
    $tblLp = Database::get_course_table(TABLE_LP_MAIN);

    $lp = Database::fetch_assoc(
        Database::query(
            "SELECT iid FROM $tblLp WHERE name = '$lpName' AND category_id = $categoryId AND c_id = $courseId"
        )
    );

    if (empty($lp)) {
        return 0;
    }

    return $lp['iid'];
}

/**
 * @param int $lpId
 * @param int $courseId
 *
 * @return int
 */
function getLpItemId($lpId, $courseId)
{
    $tblDocument = Database::get_course_table(TABLE_DOCUMENT);
    $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);

    $query = Database::query(
        "SELECT lpi.iid FROM $tblLpItem lpi
        INNER JOIN $tblDocument d ON (lpi.path = d.iid AND lpi.c_id = d.c_id)
        WHERE lpi.c_id = $courseId AND lpi.lp_id = $lpId AND (d.session_id = 0 OR d.session_id IS NULL)
            AND d.path LIKE '%Ebook%.pdf'"
    );

    $item = Database::fetch_assoc($query);

    if (empty($item)) {
        return 0;
    }

    return $item['iid'];
}

/**
 * @param int  $lpId
 * @param int  $courseId
 * @param bool $deleteItemOnly
 *
 * @return array
 */
function getDocumentIds($lpId, $courseId, $deleteItemOnly = false)
{
    $tblDocument = Database::get_course_table(TABLE_DOCUMENT);
    $tblLpItem = Database::get_course_table(TABLE_LP_ITEM);

    $sql = "SELECT d.iid FROM $tblDocument d
        INNER JOIN $tblLpItem lpi ON (d.iid = lpi.path AND d.c_id = lpi.c_id)
        WHERE d.c_id = $courseId AND lpi.lp_id = $lpId AND (d.session_id = 0 OR d.session_id IS NULL)";

    if ($deleteItemOnly) {
        $sql .= " AND d.path LIKE '%Ebook%.pdf'";
    }

    $query = Database::query($sql);

    $result = [];

    while ($row = Database::fetch_assoc($query)) {
        $result[] = $row['iid'];
    }

    $result = array_unique($result);

    sort($result);

    return $result;
}

/**
 * @param string $courseCode
 * @param string $prefixLp
 * @param bool   $deleteItemOnly
 */
function deleteFromCourse($courseCode, $prefixLp, $deleteItemOnly = false)
{
    echo "Deleting in course $courseCode".PHP_EOL;

    $courseInfo = api_get_course_info($courseCode);

    if (empty($courseInfo)) {
        echo "\tCourse $courseCode not found".PHP_EOL;

        return;
    }

    $documentDirectory = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/document';

    $lpCategoryId = getLpCategoryId("$prefixLp Ebook", $courseInfo['real_id']);

    if (empty($lpCategoryId)) {
        echo "\tLP category \"$prefixLp Ebook\" not found in $courseCode".PHP_EOL;

        return;
    }

    $lpId = getLpId("$prefixLp Ebook", $lpCategoryId, $courseInfo['real_id']);

    if (empty($lpCategoryId)) {
        echo "\tLP \"$prefixLp Ebook\" not found in $courseCode".PHP_EOL;

        return;
    }

    $documentIds = getDocumentIds($lpId, $courseInfo['real_id'], $deleteItemOnly);

    foreach ($documentIds as $documentId) {
        $documentIsDeleted = DocumentManager::delete_document($courseInfo, null, $documentDirectory, 0, $documentId);

        echo "\tDocument $documentId ".($documentIsDeleted ? 'deleted' : 'not deleted').PHP_EOL;
    }

    $lp = new learnpath($courseCode, $lpId, 0);

    if (!$deleteItemOnly) {
        $lp->delete($courseInfo, $lpId);

        echo "\tLearning path $lpId deleted".PHP_EOL;

        learnpath::deleteCategory($lpCategoryId);

        echo "\tLearning path category $lpCategoryId deleted".PHP_EOL;
    } else {
        $itemId = getLpItemId($lpId, $courseInfo['real_id']);

        if (empty($itemId)) {
            echo "\tLP item for Ebook not found in LP $lpId".PHP_EOL;
        }

        $lp->delete_item($itemId);
    }
}
