<?php
/**
 * Manage branches (in case the same Chamilo portal works for several branches
 * of the institution)
 * @package chamilo.branch
 */
/**
 * Class Branch
 */
class Branch
{
    /**
     * Returns the id of the branch matching this IP
     * @param string $ip
     * @return int $id
     */
    public function getBranchFromIP($ip)
    {
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
    public function findRoom($ipId)
    {
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
     * Returns the branch id by session
     * @param $sessionId
     * @return integer / bool
     */
    public function getBranchId($sessionId)
    {
        $objExtraFieldValue = new ExtraFieldValue('session');
        $objExtraFieldOption = new ExtraFieldOption('session');
        $data = $objExtraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'sede', true);

        if (!empty($data)) {
            $optionTmp = $objExtraFieldOption->get_field_option_by_field_and_option($data['field_id'], $data['field_value']);
            if (!empty($optionTmp)) {
                $option = current($optionTmp);
                $branchId = $option['id'];

                return $branchId;
            }
        }

        return false;
    }

    /**
     * Returns the branch uid by session
     * @param $sessionId
     * @return integer / bool
     */
    public function getUidSede($sessionId)
    {
        $objExtraFieldValue = new ExtraFieldValue('session');
        $data = $objExtraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'sede', true);

        if (!empty($data['field_value'])) {
            $uidIdSede = $data['field_value'];

            return $uidIdSede;
        }

        return false;
    }

    /**
     * Returns the program uid by session
     * @param $sessionId
     * @return integer / bool
     */
    public function getUidProgram($sessionId)
    {
        $objExtraFieldValue = new ExtraFieldValue('session');
        $data = $objExtraFieldValue->get_values_by_handler_and_field_variable($sessionId, 'uidIdPrograma', true);
        if (!empty($data['field_value'])) {
            $uidIdProgram = $data['field_value'];

            return $uidIdProgram;
        }

        return false;
    }

    /**
     * Retuns the id of a room from its name
     * @param string $room The name of the room
     * @param int $branchId The ID of the branch (or 0 if no room involved)
     * @return mixed
     */
    public function getRoomId($room, $branchId = 0)
    {
        $tableRoom = Database::get_main_table(TABLE_BRANCH_ROOM);
        if (strlen($room) > 30) {
            //Throw exception?
            $room = substr(0, 30, $room);
        }
        $whereCondition = array(
            'where' => array(
                'title = ? AND branch_id = ?' => array(
                    $room,
                    $branchId
                )
            )
        );
        $roomData = Database::select('id', $tableRoom, $whereCondition);
        $room = current($roomData);
        return $room['id'];
    }
    
    /**
     * List all branches
     * @return array Branches list
     */
    public static function getAll()
    {
        $branchTable = Database::get_statistic_table(TABLE_BRANCH);

        return Database::select('*', $branchTable);
    }

    /**
     * Get the name of the branch from its id
     * @param int $branchId Branch id
     * @return string Branch name
     */
    public static function getName($branchId)
    {
        $branchTable = Database::get_statistic_table(TABLE_BRANCH);

        $branchResult = Database::select('title', $branchTable, array(
                    'where' => array(
                        'id = ?' => intval($branchId)
                    )
        ));

        if (!empty($branchResult)) {
            $branch = current($branchResult);

            return $branch['title'];
        }

        return get_lang('None');
    }

} 