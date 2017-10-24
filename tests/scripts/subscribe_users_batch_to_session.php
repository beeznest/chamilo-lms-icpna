<?php
/* For licensing terms, see /license.txt */

//die();

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

use Chamilo\CoreBundle\Entity\Session;

require_once __DIR__.'/../../main/inc/global.inc.php';

/* Params */
$countUsers = 0; //Number of users for sessions
$sessionsIds = [
    37,
    36,
    35,
    34,
    33,
    32,
]; //Session IDs
/* End params */

$em = Database::getManager();
$date = date('Y-m-d h:i:s');

for ($i = 0; $i < count($sessionsIds); $i++) {
    $from = $i * $countUsers;

    $sessionId = $sessionsIds[$i];

    $users1 = Database::query("SELECT id FROM user ORDER BY id DESC LIMIT $from, $countUsers");
    $users2 = Database::query("SELECT id FROM user ORDER BY id DESC LIMIT $from, $countUsers");

    while ($user = Database::fetch_assoc($users1)) {
        Database::query("
            INSERT INTO session_rel_user (session_id, user_id, relation_type, registered_at)
            VALUES ($sessionId, {$user['id']}, 0, '$date')
        ");
        echo "Inserted user {$user['id']} into session $sessionId".PHP_EOL;
    }

    $result = Database::select(
        ['c_id'],
        'session_rel_course',
        ['where' => ['session_id = ? ' => $sessionId]]
    );

    foreach ($result as $item) {
        $courseId = $item['c_id'];

        while ($user = Database::fetch_assoc($users2)) {

            Database::query("
                INSERT INTO session_rel_course_rel_user (session_id, user_id, c_id, visibility, status)
                VALUES ($sessionId, ${user['id']}, $courseId, 1, 0)
            ");
            echo "Inserted user {$user['id']} into course {$courseId} for session $sessionId".PHP_EOL;
        }
    }
}
