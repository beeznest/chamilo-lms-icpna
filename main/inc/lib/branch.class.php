<?php
/**
 * Created by PhpStorm.
 * User: fgonzales
 * Date: 03/06/14
 * Time: 12:59 PM
 */

class Branch
{

    /**
     * Returns the id is in the range if not returns false
     * @param string $ip
     * @return int $id
     */
    public function getIpMatch($ip)
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
} 