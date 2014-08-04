<?php
/**
 * Fix missing evaluations by checking all empty evaluations in the
 * gradebook_result table and calling, for each miss, the corresponding
 * web service (transaction_31) to update the data.
 */
require '../../main/inc/global.inc.php';
require '../../main/inc/lib/attendance.lib.php';
require 'config.php';
require 'db_matches.php';
// redefine web servces config
require_once 'ws.conf.php';
require 'migration.class.php';
require 'migration.mssql.class.php';
require 'migration.custom.class.php';
$sql = "SELECT gc.id as cat_id, gc.session_id, ge.id as eval_id ".
       " FROM gradebook_category gc ".
       " INNER JOIN gradebook_evaluation ge ON ge.category_id = gc.id ".
       " AND ge.name = 'Evaluación General' ".
       " ORDER BY gc.session_id, eval_id";
$res = Database::query($sql);
$count = Database::num_rows($res);
echo "Found $count total evaluations\n";

//build an array with those evaluations, by session
$evals = array();
while ($row = Database::fetch_assoc($res)) {
    if (!empty($row['session_id']) && !empty($row['eval_id'])) {
        $evals[$row['session_id']] = $row['eval_id'];
    }
}

$s1 = "SELECT id FROM user_field WHERE field_variable = 'uidIdPersona'";
$r1 = Database::query($s1);
$uidf = Database::result($r1);

$s2 = "SELECT id FROM session_field WHERE field_variable = 'sede'";
$r2 = Database::query($s2);
$sedef = Database::result($r2);

$s3 = "SELECT id FROM session_field WHERE field_variable = 'uidIdPrograma'";
$r3 = Database::query($s3);
$sidf = Database::result($r3);

$sedes = array(
    'UUID branch 1' => 1,
    'UUID branch 2' => 2,
    'UUID branch 3' => 3,
    'UUID branch 4' => 4,
    'UUID branch 5' => 5,
);

// Select all sessions and their corresponding course IDs
$sqlsc = "SELECT s.id_session, c.id as course_id FROM session_rel_course s INNER JOIN course c ON s.course_code=c.code ORDER BY id_session, course_id";
$ressc = Database::query($sqlsc);
$sessions_list = array();
while ($rowsc = Database::fetch_assoc($ressc)) {
    $sessions_list[$rowsc['id_session']] = $rowsc['course_id'];
}
//$sessions_list = SessionManager::get_sessions_list();
$min = 1;
$max = 1000000;
$total_missing = 0;
$total_fixed = 0;
// Get sessions
foreach ($sessions_list as $session_id => $course_id) {
    if ($session_id < $min) { continue; }
    $out1 = "Session ".$session_id.", course ".$course_id."\n";
    echo $out1;
    //if no evaluation exists for this session, skip it
    if (!isset($evals[$session_id])) { echo "No eval found\n"; continue; }
    // Get branch for session
    $ss1 = "SELECT field_value FROM session_field_values WHERE field_id = $sedef AND session_id = ".$session_id;
    $rs1 = Database::query($ss1);
    $sede = Database::result($rs1);
    // Get uidIdPrograma
    $ss2 = "SELECT field_value FROM session_field_values WHERE field_id = $sidf AND session_id = ".$session_id;
    $rs2 = Database::query($ss2);
    $sid = Database::result($rs2);

    // Get users in session to build users list
    $users = SessionManager::get_users_by_session($session_id);
    $u = array();
    foreach ($users as $user) {
        $u[] = $user['user_id'];
    }
    // Get courses list to get the right course (only one in each session)
    //$courses = SessionManager::get_course_list_by_session_id($session_id);
    //if (count($courses)>0) {
    //    foreach ($courses as $course) {
    //        $course_id = $course['id'];
    //        break;
    //    }
    if (!empty($course_id)) {
        $out2 = "-- Course ".$course_id."\n";
        $sqlue = "SELECT DISTINCT(user_id) as user_id FROM gradebook_result WHERE evaluation_id = ".$evals[$session_id];
        $resue = Database::query($sqlue);
        $num = Database::num_rows($resue);
        if (count($u) == $num) {
            //same number of results, so skip
            echo "Number of results is correct. Skipping.\n";
            continue;
        } else {
            if ($num === 0) {
                echo "No evaluation here\n";
            }
            // There is a difference in the number of users and evaluations
            $ue = array();
            while ($rowue = Database::fetch_assoc($resue)) {
                $ue[] = $rowue['user_id'];
            }
            foreach ($u as $user_id) {
                if (!in_array($user_id,$ue)) {
                    //user is missing from results
                    echo "User $user_id is missing from session $session_id, eval ".$evals[$session_id]."\n";
                    $total_missing++;
                    $sqlu = "SELECT field_value FROM user_field_values where field_id = $uidf and user_id = $user_id";
                    $resu = Database::query($sqlu);
                    $uid = Database::result($resu);                   
                    $params = array(
                        'item_id' => $uid,
                        'orig_id' => $sid,
                        'branch_id' => $sedes[$sede],
                    );
                    $r8 = MigrationCustom::transaction_31($params,$matches['web_service_calls']);
                    if ($r8['status_id'] == 2) {
                        $total_fixed++;
                    }
                    var_dump($r8);
                }
            }
        }
    }
    if ($session_id>=$max) { break; }
}
echo "Total fixed/missing: ".$total_fixed."/".$total_missing."\n";
die('Finished processing '.$max."\n");

