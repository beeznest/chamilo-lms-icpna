<?php

//Avoid execution if not from the command line
if (PHP_SAPI != 'cli') { die('This script fix a bug, but only by CLI.'); }

require_once '../main/inc/global.inc.php';

$sql = "SELECT c_id, id_auto
FROM c_quiz_answer
WHERE
length(answer) > 64000 AND
answer NOT lIKE '%</p>';
";
$result = Database::query($sql);
if (Database::num_rows($result) > 0) {
    $sql0 = "ALTER TABLE c_quiz_answer MODIFY COLUMN answer MEDIUMTEXT";
    Database::query($sql0);
    while ($temp_row=Database::fetch_array($result, 'ASSOC')) {
        //Store unique id
        $c_id = $temp_row['c_id'];
        $id_auto = $temp_row['id_auto'];
        //Get answer's ends
        $sql1 = "SELECT SUBSTR(answer,-10) FROM c_quiz_answer WHERE c_id = $c_id AND id_auto = $id_auto";
        $res = Database::query($sql1);
        $ends = Database::fetch_row($res)[0];
        error_log('ENDS WHIT...  ' . $ends);

        $add = '';
        //Search if contain -, --, -->, <, </, </p
        if (strpos($ends, '-') !== false) {
            if (strpos($ends, '--') !== false) {
                if (strpos($ends, '-->') !== false) {
                    if (strpos($ends, '<') !== false) {
                        if (strpos($ends, '</') !== false) {
                            if (strpos($ends, '</p') !== false){
                                $add ='>';
                            } else {
                                $add = 'p>';
                            }
                        } else {
                            $add = '/p>';
                        }
                    } else {
                        $add = '</p>';
                    }
                } else {
                    $add = '></p>';
                }
            } else {
                $add = '-></p>';
            }
        } else {
            $add = '--></p>';
        }

        //Update answer whit quick fix
        $sql2 = "UPDATE c_quiz_answer SET answer = CONCAT(answer, '$add') WHERE c_id = $c_id AND id_auto = $id_auto";
        Database::query($sql2);
    }
}