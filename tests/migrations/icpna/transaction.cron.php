<?php
/* For licensing terms, see /license.txt */
/**
 * This script should be called by cron.d to update items based on stored
 * transactions
 * @package chamilo.migration.icpna
 */
/**
 * Init
 */
if (PHP_SAPI != 'cli') {
    exit;
}

// If the script if called with a 'fix' argument, then deal with it differently
// (do not call the webservice, but instead try to re-execute previously
// failed transactions). The default mode is "process".
$modes = array('fix', 'process');
$mode = 'process';
$cli_branch = 0;
if (($argc < 2) or empty($argv[1])) {
    error_log('No mode provided for transaction cron.d process in '.__FILE__.', assuming "process"');
} elseif (!in_array($argv[1],$modes)) {
    error_log('Mode '.$argv[1].' not recognized in '.__FILE__);
    //die();
} else {
    $mode = $argv[1];
    if (!empty($argv[2]) && $argv[2] == intval($argv[2]) && $argv[2] < 20) {
        $cli_branch = intval($argv[2]);
    }
}

// Check for a pid file. Normally, no concurrent write should happen as transactions
//  should be started at about 120 seconds intervals.
$pidFile = __DIR__.'/chamilo.transaction.pid';
$lastExecutionFile = __DIR__.'/chamilo.transaction.last';
$lastFixExecutionFile = __DIR__.'/chamilo.transaction.fix.last';
if ($mode == 'fix') {
  $pidFile = __DIR__.'/chamilo.transaction.fix.pid';
}

$pid = getmypid();

if (is_file($pidFile)) {
  $pid = file_get_contents($pidFile);
  error_log($mode.": Transaction run frozen: PID file already exists with PID $pid in $pidFile");
  die('PID exists - Transaction run frozen');
} else {
  $res = @file_put_contents($pidFile,$pid);
  if ($res === false) {
    error_log($mode.': Failed writing PID file - Transaction run frozen');
    die('Failed writing PID file - Transaction run frozen');
  }
  error_log($mode.': Written PID file with PID '.$pid.'. Now starting transaction run');
}

// Load config files
$cron_start = time();
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require_once 'config.php';
require_once 'migration.class.php';
$branch_id = 0;
// We need $branch_id defined before calling db_matches.php
// The only thing we need from db_matches is the definition of the web service
require_once 'db_matches.php';
// redefine web services config
if (!is_file(__DIR__.'/ws.conf.php')) {
    die ('Please define a ws.conf.php file (copy ws.conf.dist.php) before you run the transactions');
}
require_once 'ws.conf.php';
    
$migration = new Migration();    
$migration->set_web_service_connection_info($matches);    
require $migration->web_service_connection_info['filename'];
$mig = new $migration->web_service_connection_info['class'];
error_log('Building in-memory data_list for speed-up '.time());
$data_list = array('boost_users'=>true, 'boost_courses'=>true, 'boost_sessions'=>true);
if (count($data_list['users'])<1) {
    MigrationCustom::fillDataList($data_list);
}
error_log('Built in-memory data_list for speed-up '.time());

// Counter for transactions found and dealt with
$count_transactions = 0;
$count_total_transactions = 0;

// Check all branches one by one
$branches = $migration->get_branches();
foreach ($branches as $id => $branch) {
    $response = '';
    $branch_id = $branch['branch_id'];
    //if ($branch_id != 5) continue; //put priority on branch 5
    //if ($branch_id != 4) continue; //put priority on branch 4
    //if ($branch_id == 4) continue; //skip branch 4
    //if ($branch_id != 2) continue; //put priority on branch 4
    //if a specific branch was given, check only this one
    if (!empty($cli_branch)) {
        if ($branch_id != $cli_branch) { continue; }
        error_log('CLI argument forces to check only branch '.$cli_branch);
    }
    if ($mode == 'process') {
        //Load transactions saved before
	$params = array('branch_id' => $branch_id, 'number_of_transactions' => '500');
        $count_total_transactions += $migration->get_transactions_from_webservice($params);
        $count_transactions += $migration->execute_transactions($params);
        // $trans_id = $migration->get_latest_transaction_id_by_branch($branch_id);
        // error_log("Last transaction was $trans_id for branch $branch_id");
        // $params = array(
        //     'ultimo' => $trans_id,
        //     'cantidad' => 100,
        //     'intIdSede' => $branch_id,
        // );
        // $result = $mig->processTransactions($params,$migration->web_service_connection_info);
    } else {
        //if mode==fix
        error_log('Fixing transactions');
        $params = array('branch_id' => $branch_id, 'number_of_transactions' => '2000', 'check_attend' => false);
        $migration->execute_transactions($params);
    }
    //$result = $migration->load_transaction_by_third_party_id($trans_id, $branch_id);
    //$response .= $result['message'];
    //if (isset($result['raw_reponse'])) {
    //    $response .= $result['raw_reponse'];
    //}
    //if (!empty($response)) {
    //    error_log($response);
    //}
}

// Free the PID file
if (is_file($pidFile)) {
    $opid = trim(file_get_contents($pidFile));
    if (intval($opid) == intval($pid)) {
        $res = @exec('rm '.$pidFile);
        if ($res === false) {
            error_log('Could not delete PID file');
            die('Could not delete PID file');
        }
        error_log('PID file deleted for PID '.$pid);
        error_log(str_repeat('=',40));
    } else {
        error_log('PID file is not of current process. Not deleting.');
        die('PID file is not of current process. Not deleting.'."\n");
    }
}

// Script shutdown and logging
$cron_total = time() - $cron_start;
error_log($mode.': Total time taken for transaction run: '.$cron_total.'s for '.$count_transactions.' transactions (of '.$count_total_transactions.')');
if ($mode == 'process') {
  $time = time();
  @file_put_contents($lastExecutionFile,'x::'.$time.'::x');
  error_log('YZYZ - written last with '.$time.'. Shutting down');
} else {
  $time = time();
  @file_put_contents($lastFixExecutionFile,'x::'.$time.'::x');
  error_log('YZYZ - written last with '.$time.'. Shutting down');
}
exit();
