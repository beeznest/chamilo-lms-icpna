<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 07/01/14
 * Time: 08:33 AM
 */

class Sequence {


    /*
     *
     *
     *
     */
    public static function get_pre_req_by_row_id($entity_id, $row_id, $course_id = null, $session_id =null) {
        $row_id = Database::escape_string($row_id);
        $entity_id = Database::escape_string($entity_id);
        $course_id = Database::escape_string($course_id);
        $session_id = Database::escape_string($session_id);
        if (is_numeric($row_id) && is_numeric($entity_id)) {
            $row_id = intval($row_id);
            $entity_id = intval($entity_id);
            $course_id = is_numeric($course_id) ? intval($course_id) : api_get_course_int_id();
            $session_id = is_numeric($session_id) ? intval($session_id) : api_get_session_id();
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE row.sequence_type_entity_id = $entity_id AND row.row_id = $row_id AND row.c_id = $course_id  AND row. session_id = $session_id LIMIT 0, 1";
            $sql = "SELECT main.sequence_row_entity_id FROM $seq_table main WHERE main.sequence_row_entity_id_next = ($sql)";
            $sql = "SELECT * FROM $row_table res WHERE res.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $pre_req = Database::fetch_array($result);
                return $pre_req;
            }
        }
        return false;
    }

    public static function get_next_by_row_id($entity_id, $row_entity_id, $course_id = null, $session_id =null) {
        $row_entity_id = Database::escape_string($row_entity_id);
        $entity_id = Database::escape_string($entity_id);
        $course_id = Database::escape_string($course_id);
        $session_id = Database::escape_string($session_id);
        if (is_numeric($row_entity_id) && is_numeric($entity_id)) {
            $row_entity_id = intval($row_entity_id);
            $entity_id = intval($entity_id);
            $course_id = is_numeric($course_id) ? intval($course_id) : api_get_course_int_id();
            $session_id = is_numeric($session_id) ? intval($session_id) : api_get_session_id();
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE row.sequence_type_entity_id = $entity_id AND row.row_id = $row_entity_id AND row.c_id = $course_id  AND row. session_id = $session_id LIMIT 0, 1";
            $sql = "SELECT main.sequence_row_entity_id_next FROM $seq_table main WHERE main.sequence_row_entity_id_next = ($sql)";
            $sql = "SELECT * FROM $row_table res WHERE res.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $next = Database::fetch_array($result);
                return $next;
            }
        }
        return false;
    }

    /*
     *
     */
    public static function get_entity_by_id($entity_id) {
        $entity_id = Database::escape_string($entity_id);
        if (is_numeric($entity_id)) {
            $entity_id = intval($entity_id);
            $ety_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
            $sql = "SELECT * FROM $ety_table WHERE id = $entity_id LIMIT 0, 1";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $entity = Database::fetch_array($result);
                return $entity;
            }
        }
        return false;
    }


    public static function validate_rule_by_row_id($entity_id, $row_entity_id, $course_id = null, $user_id = null, $rule_id = 1) {
        $condition = self::get_condition_by_rule_id($rule_id);
        if ($condition !== false) {
            $con = $condition[0];
            while(isset($con)) {
                $var = self::get_variable_by_condition_id($con['id']);
                $val = self::get_value_by_row_user_id($entity_id,$row_entity_id,$course_id,$user_id);
                $statement = 'return ($val[0][$var["name"]] $con["mat_op"] $con["param"]);';
                if (eval($statement)) {
                    $go = $con['act_true'];
                } else {
                    $go = $con['act_false'];
                }
                if ($go === 0) {
                    return true;
                } else {
                    $con = $condition[[--$go]];
                }
            }
        }
        return false;
    }

    public static function get_condition_by_rule_id($rule_id = 1) {
        $rule_id = Database::escape_string($rule_id);
        if (is_numeric($rule_id)) {
            $rule_id = intval($rule_id);
            $con_table = Database::get_main_table(TABLE_SEQUENCE_CONDITION);
            $rul_con_table = Database::get_main_table(TABLE_SEQUENCE_RULE_CONDITION);
            $sql = "SELECT FROM $rul_con_table rc WHERE rc.sequence_rule_id = $rule_id";
            $sql = "SELECT * FROM $con_table co WHERE co.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $condition = Database::fetch_array($result);
                return $condition;
            }
        }
        return false;
    }

    public static function get_method_by_rule_id($rule_id =1 ) {
        $rule_id = Database::escape_string($rule_id);
        if (is_numeric($rule_id)) {
            $rule_id = intval($rule_id);
            $met_table = Database::get_main_table(TABLE_SEQUENCE_METHOD);
            $rul_met_table = Database::get_main_table(TABLE_SEQUENCE_RULE_METHOD);
            $sql = "SELECT rm.sequence_method_id FROM $rul_met_table rm WHERE rc.sequence_rule_id = $rule_id";
            $sql = "SELECT * FROM $met_table co WHERE co.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $method = Database::fetch_array($result);
                return $method;
            }
        }
        return false;
    }

    public static function get_value_by_row_id($entity_id, $row_entity_id, $course_id = null, $user_id = null) {
        $row_entity_id = Database::escape_string($row_entity_id);
        $entity_id = Database::escape_string($entity_id);
        $course_id = Database::escape_string($course_id);
        $user_id = Database::escape_string($user_id);
        if (is_numeric($row_entity_id) && is_numeric($entity_id)) {
            $row_entity_id = intval($row_entity_id);
            $entity_id = intval($entity_id);
            $course_id = is_numeric($course_id) ? intval($course_id) : api_get_user_id();
            $user_id = is_numeric($user_id) ? intval($user_id) : api_get_user_id();
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE row.sequence_type_entity_id = $entity_id AND row.row_id = $row_entity_id AND row.c_id = $course_id LIMIT 0, 1";
            $sql = "SELECT * FROM $val_table val WHERE val.user_id = $user_id AND val.sequence_row_entity_id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $value = Database::fetch_array($result);
                return $value;
            }
        }
    }

    public static function get_variable_by_condition_id($condition_id) {
        $condition_id = Database::escape_string($condition_id);
        if (is_numeric($condition_id)) {
            $condition_id = intval($condition_id);
            $var_table = Database::get_main_table(TABLE_SEQUENCE_VARIABLE);
            $vld_table = Database::get_main_table(TABLE_SEQUENCE_VALID);
            $sql = "SELECT DISTINCT vld.sequence_variable_id FROM $vld_table vld WHERE vld.sequence_condition_id = $condition_id";
            $sql = "SELECT * FROM $var_table var WHERE var.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $variable = Database::fetch_array($result);
                return $variable;
            }
        }
        return false;
    }

    public static function execute_formulas_by_user_id($row_entity_id = null ,$user_id = null, $met_type = '', $available = 1, $total_items, $available_end_date =null) {
        $value = self::get_value_by_user_id($row_entity_id, $user_id, $available);
        if ($value !== false) {
            if (!empty($met_type)) {
                $met_filter = " met.met_type NOT IN ('success','init','pre') ";
            } else {
                $met_filter = " met.met_type = $met_type ";
            }
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $rul_table = Database::get_main_table(TABLE_SEQUENCE_RULE);
            $rul_met_table = Database::get_main_table(TABLE_SEQUENCE_RULE_METHOD);
            $met_table = Database::get_main_table(TABLE_SEQUENCE_METHOD);
            $var_table = Database::get_main_table(TABLE_SEQUENCE_VARIABLE);
            $sql = "SELECT var.name FROM $var_table var";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $variable = Database::fetch_array($result);
            }
            $sql = "SELECT rul.id FROM $rul_table rul";
            $sql = "SELECT rm.sequence_method_id FROM $rul_met_table rm WHERE rm.sequence = ($sql) ORDER BY rm.method_order";
            $sql = "SELECT DISTINCT met.id, met.formula, met.assign FROM $met_table met WHERE $met_filter met.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $formula= Database::fetch_array($result);
            }
            $pat = "/v#(\d+)/";
            $rep = '$val[$variable[$1]["name"]]';
            if((isset($value) && isset($formula)) && isset ($variable)) {
                foreach ($value as $val) {
                    $sql_array = array(
                        'update' => " $val_table SET "
                    );
                    foreach ($formula as $fml) {
                        $assign_key = $variable[$fml['assign']]['name'];
                        $assign_val = &$val[$variable[$fml['assign']]['name']];
                        $fml_exe = preg_replace($pat, $rep, $fml['formula']);
                        $fml_exe = 'return '.$fml_exe;
                        $assign_val = eval($fml_exe);
                        $sql_array[$assign_key] = " = $assign_val, ";
                    }
                    $sql_array['where'] = ' id = '.$val['id'];
                    $sql = '';
                    foreach ($sql_array as $sql_key => $sql_val) {
                        $sql .= $sql_key;
                        $sql .= $sql_val;
                    }
                    $result = Database::query($sql);
                }
                if (Database::affected_rows() > 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function get_value_by_user_id($row_entity_id = null,$user_id = null, $available = -1) {
        $user_id = Database::escape_string($user_id);
        $user_id = (isset($user_id))? $user_id : api_get_user_id();
        if (is_numeric($user_id)) {
            $available = Database::escape_string($available);
            $available = (is_numeric($available))? intval($available) : -1;
            if ($available < 0) {
                $available_filter = '';
            } else {
                $available_filter = " AND val.available = $available ";
            }
            $row_entity_id = Database::escape_string($row_entity_id);
            $row_entity_id = (is_numeric($row_entity_id))? intval($row_entity_id) : 0;
            if ($row_entity_id !== 0) {
                $row_entity_filter = " AND val.sequence_row_entity_id = $row_entity_id ";
            } else {
                $row_entity_filter = '';
            }
            $user_id = intval($user_id);
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT * FROM $val_table val WHERE val.user_id = $user_id $available_filter $row_entity_filter";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $value = Database::fetch_array($result);
            }
            return $value;
        }
        return false;
    }

    public static function find_variables_in_formula($formula) {
        $formula = Database::escape_string($formula);
        if (isset($formula)) {
            $pat = "/v#(\d+)/";
            preg_match_all($pat, $formula, $v_array);
            if (isset($v_array)){
                $var_array = array_unique($v_array[1]);
                sort($var_array);
                return $var_array;
                }
            }
        return false;
    }

    public static function action_post_success_by_user_id($row_entity_id = null, $user_id = null, $available_end_date = null) {
        if (self::execute_formulas_by_user_id($row_entity_id, $user_id, 'success', 1, null, $available_end_date)) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $value = self::get_value_by_user_id($row_entity_id, $user_id, 1);
            foreach ($value as $val) {
                $row_entity_id_prev = $val['sequence_row_entity_id'];
                $sql = "SELECT seq.sequence_row_entity_id_next FROM $seq_table seq WHERE seq.sequence_row_entity_id = $row_entity_id_prev";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $next = Database::fetch_array($result);
                }
                foreach ($next as $nx) {
                    $row_entity_id_next = $nx['sequence_row_entity_id_next'];
                    $sql = "SELECT val.success FROM $seq_table seq, $val_table val WHERE seq1.is_part != 1 AND val.user_id = $user_id AND val.sequence_row_entity_id = seq.sequence_row_entity_id AND seq.sequence_row_entity_id_next = $row_entity_id_next";
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 0) {
                        $pre_req = Database::fetch_array($result);
                    }
                    foreach($pre_req as $pr) {
                        if($pr['success'] === 0) {
                            continue 2;
                        }
                    }
                    self::execute_formulas_by_user_id($row_entity_id_next, $user_id, 'pre', 0, null, $available_end_date);
                }
            }
        }
    }

    public static function get_row_entity_id_by_row_id($entity_id, $row_id) {
        $row_id = Database::escape_string($row_id);
        $entity_id = Database::escape_string($entity_id);
        if (is_numeric($row_id) && is_numeric($entity_id)) {
            $row_id = intval($row_id);
            $entity_id = intval($entity_id);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE row.sequence_type_entity_id = $entity_id AND row.row_id = $row_id LIMIT 0, 1";
            $result = Database::query($sql);
            $row_entity = Database::fetch_row($result);
            return $row_entity['id'];
        }
        return 0;
    }

    public static function temp_hack_1($entity_id, $row_id, $progress, $user_id) {
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id,$row_id);
        if ($row_entity_id !== false) {
            --$progress;
            $user_id = Database::escape_string($user_id);
            $user_id = (!empty($user_id))? intval($user_id) : api_get_user_id();
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "UPDATE $val_table SET complete_items = $progress WHERE user_id = $user_id AND sequence_row_entity_id = $row_entity_id";
            $result = Database::query($sql);
            self::action_post_success_by_user_id($row_entity_id, $user_id);
        }
    }

    public static function get_table_by_entity_name($entity_name){
        $entity_name = Database::escape_string($entity_name);
        $ety_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
        $sql = "SELECT ety.ent_table FROM $ety_table ety WHERE ety.name = $entity_name LIMIT 0, 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $entity = Database::fetch_row($result);
            return $entity['ent_table'];
        }
        return false;
    }

    public static function get_entity_by_entity_name($entity_name){
        $entity_name = Database::escape_string($entity_name);
        $ety_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
        $sql = "SELECT ety.* FROM $ety_table ety WHERE ety.name = $entity_name LIMIT 0, 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $entity = Database::fetch_row($result);
            return $entity;
        }
        return false;
    }

    public static function temp_hack_2($entity_name, $row_id, $c_id, $session_id = 0) {
        $row_id = Database::escape_string($row_id);
        $c_id = Database::escape_string($c_id);
        $session_id = Database::escape_string($session_id);
        $entity = self::get_entity_by_entity_name($entity_name);
        $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
        if ($entity !== false) {
            $entity_id = $entity['id'];
            $sql = "INSERT INTO $row_table (sequence_type_entity_id, c_id, session_id, row_id) VALUES
            ($entity_id, $c_id, $session_id, $row_id)";
            $result = Database::query($sql);
            if (Database::affected_rows() > 0) {
                return true;
            }
        }
        return false;
    }
    public static function temp_hack_3($entity_id_prev, $entity_id_next, $row_id_prev = 0, $row_id_next = 0, $is_part = 0) {
        $entity_id_next = Database::escape_string($entity_id_next);
        $entity_id_prev = Database::escape_string($entity_id_prev);
        $row_id_prev = Database::escape_string($row_id_prev);
        $row_id_next = Database::escape_string($row_id_next);
        $is_part = Database::escape_string($is_part);
        $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
        if (($row_id_next == 0) || ($row_id_prev == 0)) {
            $prev = self::get_row_entity_id_by_row_id($entity_id_prev, $row_id_prev);
            $next = self::get_row_entity_id_by_row_id($entity_id_next, $row_id_next);
            if (($prev != 0 || next != 0)) {
                $sql = "INSERT INTO  $seq_table (sequence_row_entity_id, sequence_row_entity_id_next, is_part) VALUES
                ($prev, $next, $is_part)";
                $result = Database::query($sql);
                if (Database::affected_rows() > 0) {
                    return true;
                }
            }
        }
        return false;
    }
}