<?php
die();
require __DIR__.'/../../../main/inc/global.inc.php';
$s = 'SELECT id, branch_id as b, transaction_id as t FROM branch_transaction ORDER BY branch_id, transaction_id';
$q = Database::query($s);
$b = 0;
$t = 0;
while ($r = Database::fetch_assoc($q)) {
    if ($r['b'] == $b && $r['t'] == $t) {
        //echo "Found duplicate transaction ".$r['id']."\nSELECT * FROM branch_transaction where branch_id = $b AND transaction_id = $t;\n";
        $s2 = "DELETE FROM branch_transaction where id = ".$r['id'];
        echo "$s2\n";
        //$q2 = Database::query($s2);
    }
    $b = $r['b']; $t = $r['t'];
}
echo "Done";
