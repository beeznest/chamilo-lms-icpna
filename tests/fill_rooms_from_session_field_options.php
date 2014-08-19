<?php
/**
 * Fills the branch_room table with data from the session_field_options table
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
/**
 * Setup db connection and queries
 */
require '../main/inc/global.inc.php';

$roomField = 0;
$branchField = 0;

$sqlFieldId = "SELECT id, field_variable FROM session_field";
$r = Database::query($sqlFieldId);
while ($row = Database::fetch_assoc($r)) {
    if ($row['field_variable'] == 'aula') {
        $roomField = $row['id'];
    }
    if ($row['field_variable'] == 'sede') {
        $branchField = $row['id'];
    }
}

if (empty($roomField)) {
    die('Could not find a room field in session_field'.PHP_EOL);
}

if (empty($branchField)) {
    die('Could not find a branch field in session_field'.PHP_EOL);
}

$currentSessionId = 0;
$currentBranchId = 0;
$currentRoomId = 0;
$branchRoomMatch = array();
$sqlSessionFieldValues = "SELECT session_id, field_id, field_value FROM session_field_values WHERE field_id in ($branchField, $roomField) ORDER BY session_id";
$r = Database::query($sqlSessionFieldValues);
while ($row = Database::fetch_assoc($r)) {
    if ($row['session_id'] != $currentSessionId) {
        $currentSessionId = $row['session_id'];
        $currentBranchId = 0;
        $currentRoomId = 0;
    }
    if ($row['field_id'] == $branchField) {
        $currentBranchId = $row['field_value'];
    }
    if ($row['field_id'] == $roomField) {
        $currentRoomId = $row['field_value'];
    }
    if (!empty($currentBranchId) && !empty($currentRoomId)) {
        if (!isset($branchRoomMatch[$currentRoomId])) {
            $branchRoomMatch[$currentRoomId] = $currentBranchId;
        }
    }
}

// Prepare branches array
$branches = array();
$sql = "SELECT id, title FROM branch";
$r = Database::query($sql);
while ($row = Database::fetch_assoc($r)) {
    $branches[$row['title']] = $row['id'];
}

// Prepare rooms array
$rooms = array();
$sql = "SELECT option_value, option_display_text FROM session_field_options WHERE field_id = $roomField";
$r = Database::query($sql);
while ($row = Database::fetch_assoc($r)) {
    $rooms[$row['option_value']] = $row['option_display_text'];
}

// Now make the match in heaven and insert into the branch_room table
$i = 0;
foreach ($branchRoomMatch as $room => $branch) {
    // Get branch info
    $sql = "SELECT option_display_text FROM session_field_options WHERE field_id = $branchField AND option_value = '$branch'";
    $r = Database::query($sql);
    $branchId = 0;
    while ($row = Database::fetch_assoc($r)) {
        $branchId = $branches[$row['option_display_text']];
        break;
    }
    // Get room info
    $roomName = $rooms[$room];
    $sql = "SELECT * FROM branch_room WHERE title = '$roomName' AND branch_id = $branchId";
    $r = Database::query($sql);
    if (Database::num_rows($r) > 0) {
        // This room already exists for this branch, ignore...
    } else {
        // Insert the branch-room details
        $sql = "INSERT INTO branch_room (branch_id, title) VALUES ($branchId, '$roomName')";
        $r = Database::query($sql);
        if ($r === false) {
            echo "Error inserting $branchId, $roomName: ".Database::error().PHP_EOL;
        } else {
            $i++;
        }
    }
}
echo "Done. Inserted $i rooms into the branch_room table".PHP_EOL;