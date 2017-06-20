<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes personal files (MyFiles section) from teachers
 * directories.
 * This script should be located inside the tests/scripts/ folder to work
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - Cleanup and debug
 */
die();
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}
echo PHP_EOL . "Usage: php ".basename(__FILE__)." [options]" . PHP_EOL;
echo "Where [options] can be " . PHP_EOL;
echo "  -s         Simulate execution - Do not delete anything, just show numbers" . PHP_EOL . PHP_EOL;
echo "Processing..." . PHP_EOL . PHP_EOL;

//require __DIR__.'/../../main/inc/conf/configuration.php';
require __DIR__.'/../../main/inc/global.inc.php';
$simulate = false;
if (!empty($argv[1]) && $argv[1] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled" . PHP_EOL;
}

$sessionCourses = array();
$coursesCodes = array();
$coursesDirs = array();
/*
if (!$conexion = mysql_connect($_configuration['db_host'], $_configuration['db_user'], $_configuration['db_password'])) {
    echo 'Could not connect to database';
    exit;
}

if (!mysql_select_db($_configuration['main_database'], $conexion)) {
    echo 'Could not select database';
    exit;
}
*/
echo "[".time()."] Querying users to identify teachers\n";
$sql = "SELECT user_id FROM user WHERE status = 1 AND user_id NOT IN (SELECT user_id FROM admin)";

$res = Database::query($sql, $conexion);
if ($res === false) {
    die("Error querying users: ".Database::error($res)."\n");
}

$countUsers = Database::num_rows($res);
$users = array();
while ($row = Database::fetch_row($res, 'ASSOC')) {
    $users[] = $row['user_id'];
}

$sql = "SELECT count(user_id) FROM user";
$res = Database::query($sql, $conexion);
if ($res === false) {
    die("Error querying users count: ".Database::error($res)."\n");
}
$countAllUsers = Database::result($res, 0);

echo "[".time()."] Found $countUsers teachers on a total of $countAllUsers users."."\n";

/* Get setting about spreading users directories */

/**
 * Locate and destroy files from teachers
 */ 

$totalSize = 0;
$usersWithFiles = 0;
foreach ($users as $uid) {
    $hop = 1; 
    $prefix = substr($uid, 0, 1) . '/';
    $carpetaAlpElimina = $_configuration['root_sys'].'main/upload/users/' . $prefix . $uid . '/my_files/';
            
    if (is_dir($carpetaAlpElimina)) {
        echo "rm -rf ".$carpetaAlpElimina."*\n";
        //echo " ->  Carpeta existe" . PHP_EOL;
        $size = folderSize($carpetaAlpElimina);
        $totalSize += $size;
        echo "Freeing an additional " . round($size/(1024*1024)) . " MB summing to a total $totalSize bytes in user $uid's folder\n";
        if ($simulate == false) {
            exec('rm -rf '.$carpetaAlpElimina.'*');
        }
        $usersWithFiles++;
    } else {
        //echo " ->  Carpeta no existe" . PHP_EOL;
    }
}
echo "[".time()."] ".($simulate ? "Would delete" : "Deleted")." files from $usersWithFiles teachers on a total of $countAllUsers users for a total estimated size of " . round($totalSize/(1024*1024)) . " MB."."\n";

function folderSize($dir) {
    $size = 0;
    $contents = glob(rtrim($dir, '/').'/*', GLOB_NOSORT);
    foreach ($contents as $contents_value) {
        if (is_file($contents_value)) {
            $size += filesize($contents_value);
        } else {
            $size += folderSize($contents_value);
        }
    }
    return $size;
}
