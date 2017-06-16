<?php
/* For licensing terms, see /license.txt */
/**
 * Library to diagnose transactions issues
 */
/**
 * TransactionDiagnostic class
 */
class TransactionDiagnostic
{
    static public function getLastTransactionsByBranch()
    {
        $sql = "SELECT branch_id, max(transaction_id) FROM branch_transaction GROUP BY branch_id";
        $res = Database::query($sql);
        $last = array();
        while ($row = Database::fetch_row($res)) {
            $last[$row[0]] = $row[1];
        }
        return $last;
    }
    static public function getLastSuccessfulTransactionByBranch()
    {
        $sql = "SELECT branch_id, max(transaction_id) FROM branch_transaction WHERE status_id = 2 GROUP BY branch_id";
        $res = Database::query($sql);
        $last = array();
        while ($row = Database::fetch_row($res)) {
            $last[$row[0]] = $row[1];
        }
        return $last;
         
    }
    static public function getLockFilesState()
    {
        $last_file = __DIR__.'/chamilo.transaction.last';
        $last_fix_file = __DIR__.'/chamilo.transaction.fix.last';
        $pid_file = __DIR__.'/chamilo.transaction.pid';
        $pid_fix_file = __DIR__.'/chamilo.transaction.fix.pid';
        $now = time();
        $failing = false;
        $status = array(
            'exec' => array(
                'now' => $now,
                'last'=> $now,
                'diff'=> 0,
            ), 
            'fix' => array(
                'now' => $now,
                'last'=> $now,
                'diff'=> 0,
            ), 
        );

        // check if the normal transactions pid file is present
        if (is_file($pid_file)) {
            //read contents of last transaction run log
            if (is_file($last_file)) {
                $s = file_get_contents($last_file);
                $matches = array();
                if (preg_match('/x::(\d*)::x/',$s,$matches)) {
                    //enter in panic mode if last execution time was more than 10' ago
                    $failing = $failing || ($now>$matches[1]+(60*10));
                    $status['exec']['last'] = $matches[1];
                    $status['exec']['diff'] = ($now - $matches[1])/60;
                }
            } else {
                $status['exec'] = -1;
            }
        }
        //If no pid file, nothing to worry about...
        //Do the same for the fixes log
        if (is_file($pid_fix_file)) {
            //read contents of last transaction run log
            if (is_file($last_fix_file)) {
                $s = file_get_contents($last_fix_file);
                $matches = array();
                if (preg_match('/x::(\d*)::x/',$s,$matches)) {
                    //enter in panic mode if last execution time was more than 10' ago
                    $failing = $failing || ($now>$matches[1]+(60*10));
                    $status['fix']['last'] = $matches[1];
                    $status['fix']['diff'] = ($now - $matches[1])/60;
                }
            } else {
                $status['fic'] = -1;
            }
        }
        return $status; 
    }
    static public function countFailuresInLastXTransactions($num)
    {
        $num = intval($num);
        //$sql = "SELECT distinct(branch_id) FROM branch_transaction";
        //$res = Database::query($sql);
        $branches = array(1, 2, 3, 4, 5);
        //while ($row = Database::fetch_row($res)) {
        foreach ($branches as $branch) {
            $sql = "SELECT id, status_id FROM branch_transaction WHERE branch_id = $branch ORDER BY id DESC LIMIT $num".PHP_EOL;
            $res2 = Database::query($sql);
            $array = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);
            while ($row2 = Database::fetch_row($res2)) {
                $array[$row2[1]]++;
            }
            $branches[$branch] = $array;
        }
        return $branches;
    }
}
