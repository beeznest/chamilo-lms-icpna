<?php
/**
 * Created by PhpStorm.
 * User: fgonzales
 * Date: 03/06/14
 * Time: 12:59 PM
 */

class branch
{

    /**
     * Returns the id is in the range if not returns false
     * @param string $ip
     * @return int $id
     */
    public function getIpMatch($ip) {
        $tableBranchIp = Database::get_main_table(TABLE_BRANCH_IP);
        $branchIps = Database::select('*', $tableBranchIp);
        $id = false;

        if (!empty($branchIps)) {
            foreach ($branchIps as $branchIp) {
                if (api_check_ip_in_range($ip, $branchIp['ip_range'])) {
                    $id = $branchIp['id'];
                }
            }
        }

        return $id;
    }

    /**
     * Finds the room
     * @param $ipId
     * @return int $roomId
     */
    public function findRoom($ipId) {
        $tableBranchIpRoom = Database::get_main_table(TABLE_BRANCH_IP_REL_ROOM);
        $whereCondition = array(
            'where' => array(
                'ip_id = ?' => array(
                    $ipId
                )
            )
        );
        $branchRoom = Database::select('*', $tableBranchIpRoom, $whereCondition);
        $roomId = false;

        if (!empty($branchRoom)) {
            $branchRoomData = current($branchRoom);
            $roomId = $branchRoomData['room_id'];
        }

        return $roomId;
    }
    /**
     * Set the room globally in a cookie
     * @param $ip
     * @return bool
     */
    public function setRoomGlobally($ip) {
        $ipId = $this->getIpMatch($ip); // Find the ip ID in the branch_ips table from the IP of the user
        if ($ipId === false) { // We can't find a matching IP or range
            return false;
        }
        $roomId = $this->findRoom($ipId);

        if ($roomId === false) { // We can't find a matching room
            return false;
        }
        setcookie('room_id', $roomId,  time()+3600*24*365*10, '/'); //Ten Years

        return true;
    }
} 