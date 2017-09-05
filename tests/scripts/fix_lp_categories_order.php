<?php
/* For license terms, see /license.txt */
/**
 * Fix c_lp_category positions
 * From 1.9.x the category positions was defined by the display_order field
 * From 1.11.x the positions are defined by position field
 * This script take the display_order from database-1.9 and update the position from categories
 */

exit;

if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

/**
 * Old database configuration
 */
$oldDBHost = 'localhost';
$oldDBName = 'db1.9';
$oldDBUser = 'db1.9';
$oldDBPass = 'db1.9';
try {
    $oldDBH = new PDO('mysql:host=localhost;dbname='.$oldDBName, $oldDBUser, $oldDBPass);
} catch (PDOException $e) {
    echo "Error connecting to old database: ".$e->getMessage().PHP_EOL;
}

/**
 * New database configuration
 */
$newDBHost = 'localhost';
$newDBName = 'db1.11';
$newDBUser = 'db1.11';
$newDBPass = 'db1.11';
try {
    $newDBH = new PDO('mysql:host=localhost;dbname='.$newDBName, $newDBUser, $newDBPass);
} catch (PDOException $e) {
    echo "Error connecting to new database: ".$e->getMessage().PHP_EOL;
}

$sqlCourseOld = "SELECT id, title FROM course";

foreach ($oldDBH->query($sqlCourseOld) as $oldCourse) {
    echo "Course \"{$oldCourse['title']}\"".PHP_EOL;

    $sqlCatOld = "SELECT * FROM c_lp_category WHERE c_id = {$oldCourse['id']}";

    foreach ($oldDBH->query($sqlCatOld) as $oldCat) {
        echo 'Old cat: '.print_r($oldCat, true).PHP_EOL;

        $sqlCatNew = "
            UPDATE c_lp_category SET position = {$oldCat['display_order']}
            WHERE c_id = {$oldCourse['id']} AND iid = {$oldCat['id']}
        ";

        $newDBH->query($sqlCatNew);

        $sqlCatNew = "SELECT * FROM c_lp_category WHERE c_id = {$oldCourse['id']} AND iid = {$oldCat['id']}";
        $newCat = $newDBH->query($sqlCatNew)->fetch();

        echo 'New cat: '.print_r($newCat, true).PHP_EOL;
    }

    echo PHP_EOL;
}
