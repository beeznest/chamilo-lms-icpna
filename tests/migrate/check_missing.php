<?php
/**
 * This script checks if the sequential IDs from each branch are indeed sequential
 * @author Yannick Warnier <y@beeznest.com>
 */
/**
 * Init
 */
require_once '../../main/inc/global.inc.php';
require_once 'config.php';
require_once 'migration.class.php'; 
require_once 'db_matches.php';
// redefine web servces config
require_once 'ws.conf.php';
$eol = "\n";
$exec = true;
/**
 * Prepare connectors
 */
$migration = new Migration();    
$migration->set_web_service_connection_info($matches);    
require $migration->web_service_connection_info['filename'];
$mig = new $migration->web_service_connection_info['class'];
//error_log('Building in-memory data_list for speed-up '.time());
$data_list = array('boost_users'=>true, 'boost_courses'=>true, 'boost_sessions'=>true);
if (count($data_list['users'])<1) {
    MigrationCustom::fill_data_list($data_list);
}
//error_log('Built in-memory data_list for speed-up '.time());
/**
 * Browse all branches
 */
$sql = 'SELECT DISTINCT(branch_id) FROM branch_transaction';
$res = Database::query($sql);
$num = Database::num_rows($res);
if ($num <= 0) {
  die("No result found looking for branches$eol");
}
//quick exec
$n = execute(4,1399039);
die();
while ($branch = Database::fetch_row($res)) {
  $branch_id = $branch[0];
  echo "Branch $branch_id$eol";
  $i = 0;
  $k = 0;
  $missing = false;
  $sqli = 'SELECT transaction_id FROM branch_transaction WHERE branch_id = '.$branch_id.' ORDER BY transaction_id';
  $resi = Database::query($sqli);
  $numi = Database::num_rows($resi);
  while ($trans = Database::fetch_row($resi)) {
//    if ($k >= 1000) { break; }
//echo "i: $i, t: ".$trans[0].$eol;
    if ($i == 0) {
      // initialize $i if 0
      $i = $trans[0];
//      echo "-Starting with transaction $i$eol";
    } else {
      // if $i is not 0, check if it is the same as the last + 1
      $expected = $i+1;
      if ($trans[0] == $expected) {
        //all in order
        $i = $expected;
      } else {
        // we've missed at least one transaction
        $missing = true;
        // set value we are expecting as transaction
        $j = $expected;
        while ($trans[0] != $j) {
          // doesn't match expected transaction id
          if ($exec) {
            //$n = execute($branch_id,$i);
            // we need to send expected_transaction-1 to ensure we get expected_transaction
            $n = execute($branch_id,$j-1);
          }
          //increase internal counter
          $j++;
          if ($j%10000 == 0) { echo "$j$eol"; }
          $k++;
        }
        // Found new transaction with id $j
        if ($j == $i+2) {
          echo "--Missing transaction ".($j-1).$eol;
        } else {
          echo "--Missing transactions from $i to ".($j-1).$eol;
        }
        $i = $j;
      }
    }
  }
  echo "Found $k holes in branch $branch_id\n";
}
/**
 * Re-execute a specific transaction
 */
function execute($branch_id, $trans_id) {
  global $migration;
  $params = array('branch_id' => $branch_id, 'transaction_id' => $trans_id, 'number_of_transactions' => 1);
  $count_total_transactions = $migration->get_transactions_from_webservice($params);
  //$params = array('branch_id' => $branch_id, 'transaction_id' => $trans_id, 'number_of_transactions' => 1);
  $count_transactions = $migration->execute_transactions($params);
  return $count_transactions;
}
