<?php
/* For licensing terms, see /license.txt */
/**
 * Recover LPs from deleted categories
 */
/**
 * Include main libs
 */
die();
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/lp/learnpath.class.php';
/**
 * Get all learnpath categories
 */
$sql = "SELECT id, c_id from c_lp_category ORDER BY c_id, id";
$res = Database::query($sql);
$listCats = array();
while ($row = Database::fetch_array($res)) {
    $listCats[$row['c_id']][$row['id']] = true;
}
/**
 * Get all learnpaths in general
 */
$sql = "SELECT c_id, id, category_id FROM c_lp WHERE category_id != 0 ORDER BY c_id, category_id";
$res = Database::query($sql);
while ($row = Database::fetch_array($res)) {
    $co = $row['c_id'];
    $ca = $row['category_id'];
    $lp = $row['id'];
    if (is_array($listCats[$co]) && !empty($listCats[$co][$ca])) {
        ; //the category exists, leave untouched
    } else {
        //$sqlu = "UPDATE c_lp SET category_id = 0 WHERE c_id = $co and id = $lp";
        $sqlu = "DELETE FROM c_lp WHERE c_id = $co and id = $lp";
        echo $sqlu."\n";
        Database::query($sqlu);
        //learnpath::toggle_visibility($lp, 0, $co);
        //die('finished one');
    }
}
