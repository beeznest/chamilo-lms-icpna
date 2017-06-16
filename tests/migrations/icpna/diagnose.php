<?php
/* For licensing terms, see /license.txt */
/**
 * This script serves as a helper in diagnozing an issue
 */
require __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/transaction_diagnostic.class.php';

$lastTransactions = TransactionDiagnostic::getLastTransactionsByBranch();
$lastSuccessfulTransactions = TransactionDiagnostic::getLastSuccessfulTransactionByBranch();
$lockFilesState = TransactionDiagnostic::getLockFilesState();
$countFailuresInLast1000 = TransactionDiagnostic::countFailuresInLastXTransactions(1000);

/**
 * Display results
 */
echo "This is the transactions diagnostic tool".PHP_EOL;
echo "========================================".PHP_EOL.PHP_EOL;
echo "Last transactions (by branch) were:".PHP_EOL;
foreach ($lastTransactions as $branch => $trans) {
    echo "Branch $branch: $trans".PHP_EOL;
}
echo PHP_EOL;
echo "Lock files:".PHP_EOL;
if ($lockFilesState['exec'] == -1) {
    echo "Last execution file (reference) could not be found".PHP_EOL;
} else {
    echo "File analyzed at UNIX timestamp ".$lockFilesState['exec']['now']." (".date('Y-m-d H:i:s', $lockFilesState['exec']['now']).")".PHP_EOL;
    echo "Last successful execution of the execution process was ".$lockFilesState['exec']['diff']."m ago (at ".$lockFilesState['exec']['last'].")".PHP_EOL;
    echo "Last successful execution of the review (fix) process was ".$lockFilesState['fix']['diff']."m ago (at ".$lockFilesState['fix']['last'].")".PHP_EOL;
}
echo PHP_EOL;
echo "Last *successful* transactions (by branch) were:".PHP_EOL;
foreach ($lastSuccessfulTransactions as $branch => $trans) {
    $diff = $lastTransactions[$branch] - $trans;
    echo "Branch $branch: ".round($trans)." (difference of $diff from last registered)".PHP_EOL;
}
echo PHP_EOL;
echo "Number of transactions by status in last 1000, by branch:".PHP_EOL;
echo "Branch    1 (wait)  2 (succ)  3 (depr)  4 (fail)  5 (aban)  6 (froz)".PHP_EOL;
foreach ($countFailuresInLast1000 as $branch => $counts) {
    for ($i=1; $i<=6; $i++) {
        $counts[$i] = str_pad($counts[$i], 8, " ", STR_PAD_LEFT);
    }
    echo "B".$branch."        ".$counts[1]."  ".$counts[2]."  ".$counts[3]."  ".$counts[4]."  ".$counts[5]."  ".$counts[6].PHP_EOL;
}
echo PHP_EOL;
echo "wait: Still not executed".PHP_EOL;
echo "succ: Executed and successful".PHP_EOL;
echo "depr: Deprecated (other transaction replaced it)".PHP_EOL;
echo "fail: Failed (could not be executed because something failed)".PHP_EOL;
echo "aban: Abandonned after several failed attempts to execute".PHP_EOL;
echo "froz: Frozen manually".PHP_EOL;
echo PHP_EOL;
