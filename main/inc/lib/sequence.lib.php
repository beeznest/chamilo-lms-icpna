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
            $sql = "SELECT main.sequence_row_entity_id FROM $seq_table main WHERE main.sequence_row_entity_id_next IN ($sql)";
            $sql = "SELECT * FROM $row_table res WHERE res.id IN ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_pre_req = Database::fetch_array($result, 'ASSOC')){
                    $pre_req = $temp_pre_req;
                }
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
            $sql = "SELECT main.sequence_row_entity_id_next FROM $seq_table main WHERE main.sequence_row_entity_id_next IN ($sql)";
            $sql = "SELECT * FROM $row_table res WHERE res.id IN ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_next = Database::fetch_array($result, 'ASSOC')){
                    $next[] = $temp_next;
                }
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
                while ($temp_entity = Database::fetch_array($result, 'ASSOC')){
                    $entity[] = $temp_entity;
                }
                return $entity;
            }
        }
        return false;
    }


    public static function validate_rule_by_row_id($row_entity_id, $user_id = null, $rule_id = 1) {
        $condition = self::get_condition_by_rule_id($rule_id);
        if ($condition !== false) {
            $con = $condition[0];
            while(isset($con)) {
                $var = self::get_variable_by_condition_id($con['id']);
                $val = self::get_value_by_user_id($row_entity_id, $user_id, 1);
                var_dump($var);
                var_dump($val);
                $statement = 'return ('.floatval($val[0][$var[0]["name"]]).' '.$con["mat_op"].' '.$con["param"].');';
                if (eval($statement)) {
                    $go = (!isset($con['act_true']))? -1 : intval($con['act_true']);
                } else {
                    $go = (!isset($con['act_false']))? -1 : intval($con['act_false']);
                }
                var_dump($go);
                if ($go === 0) {
                    return true;
                } else {
                    $con = $condition[--$go];
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
            $sql = "SELECT rc.sequence_condition_id FROM $rul_con_table rc WHERE rc.sequence_rule_id = $rule_id";
            $sql = "SELECT * FROM $con_table co WHERE co.id IN ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_condition = Database::fetch_array($result, 'ASSOC')) {
                    $condition[] = $temp_condition;
                }
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
            $sql = "SELECT * FROM $met_table co WHERE co.id IN ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_method = Database::fetch_array($result, 'ASSOC')){
                    $method[] = $temp_method;
                }
                return $method;
            }
        }
        return false;
    }

    public static function get_value_by_row_entity_id($row_entity_id) {
        $row_entity_id = intval(Database::escape_string($row_entity_id));
        if ($row_entity_id > 0) {
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT * FROM $val_table val WHERE val.sequence_row_entity_id = $row_entity_id";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_value = Database::fetch_array($result, 'ASSOC')){
                    $value[] = $temp_value;
                }
                return $value;
            }
        }
        return false;
    }

    public static function get_variable_by_condition_id($condition_id) {
        $condition_id = Database::escape_string($condition_id);
        if (is_numeric($condition_id)) {
            $condition_id = intval($condition_id);
            $var_table = Database::get_main_table(TABLE_SEQUENCE_VARIABLE);
            $vld_table = Database::get_main_table(TABLE_SEQUENCE_VALID);
            $sql = "SELECT DISTINCT vld.sequence_variable_id FROM $vld_table vld WHERE vld.sequence_condition_id = $condition_id";
            $sql = "SELECT * FROM $var_table var WHERE var.id IN ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_variable = Database::fetch_array($result, 'ASSOC')) {
                    $variable[] = $temp_variable;
                }
                return $variable;
            }
        }
        return false;
    }

    public static function execute_formulas_by_user_id($row_entity_id = null ,$user_id = null, $met_type = '', $available = 1, $complete_items = 1, $total_items = 1, $available_end_date =null) {
        $value = self::get_value_by_user_id($row_entity_id, $user_id, $available);
        if ($value !== false) {
            if (empty($met_type)) {
                $met_filter = " AND met.met_type NOT IN ('success', 'pre', 'update') ";
            } else {
                $met_filter = " AND met.met_type = '$met_type' ";
            }
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $rul_table = Database::get_main_table(TABLE_SEQUENCE_RULE);
            $rul_met_table = Database::get_main_table(TABLE_SEQUENCE_RULE_METHOD);
            $met_table = Database::get_main_table(TABLE_SEQUENCE_METHOD);
            $var_table = Database::get_main_table(TABLE_SEQUENCE_VARIABLE);
            $sql = "SELECT var.id, var.name FROM $var_table var";
            $result = Database::query($sql);
            $variable = [];
            while ($temp_var = Database::fetch_row($result)) {
                $variable[$temp_var['0']] = $temp_var['1'];
            }
            $sql = "SELECT rul.id FROM $rul_table rul";
            $sql = "SELECT met.formula, met.assign assign FROM $met_table met, $rul_met_table rm WHERE
            met.id = rm.sequence_method_id $met_filter ORDER BY rm.method_order";
            $result = Database::query($sql);
            while ($temp_fml = Database::fetch_array($result, 'ASSOC')) {
                $formula[] = $temp_fml;
            }
            $pat = "/v#(\d+)/";
            if (isset($formula) && isset($variable)) {
                if (is_array($value[0])) {
                    $rep = '$val[$variable[$1]]';
                    foreach ($value as $val) {
                        $sql_array = array(
                            'UPDATE' => " $val_table SET "
                        );
                        foreach ($formula as $fml) {
                            $assign_key = $variable[$fml['assign']];
                            $assign_val = &$val[$variable[$fml['assign']]];
                            $fml_exe = preg_replace($pat, $rep, $fml['formula']);
                            $fml_exe = 'return '.$fml_exe;
                            $assign_val = eval($fml_exe);
                            $sql_array[$assign_key] = " = '$assign_val', ";
                        }
                        $sql_array['WHERE'] = ' id = '.$val['id'];
                        $sql = '';
                        foreach ($sql_array as $sql_key => $sql_val) {
                            $sql .= $sql_key;
                            $sql .= $sql_val;
                        }
                        $sql = preg_replace('/, WHERE/', ' WHERE', $sql);
                        $result = Database::query($sql);
                    }
                }
                return true;
            }
        }
        return false;
    }

    public static function get_value_by_user_id($row_entity_id = null,$user_id = null, $available = -1, $success = -1) {
        $user_id = (isset($user_id))? intval(Database::escape_string($user_id)) : api_get_user_id();
        if ($user_id > 0) {
            $available = Database::escape_string($available);
            $available = (is_numeric($available))? intval($available) : -1;
            if ($available < 0) {
                $available_filter = '';
            } else {
                $available_filter = " AND available = $available ";
            }
            $success = Database::escape_string($success);
            $success = (is_numeric($success))? intval($success) : -1;
            if ($success < 0) {
                $success_filter = '';
            } else {
                $success_filter = " AND success = $success ";
            }
            $row_entity_id = Database::escape_string($row_entity_id);
            $row_entity_id = (is_numeric($row_entity_id))? intval($row_entity_id) : 0;
            if ($row_entity_id !== 0) {
                $row_entity_filter = " AND sequence_row_entity_id = $row_entity_id ";
            } else {
                $row_entity_filter = '';
            }
            $user_id = intval($user_id);
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT * FROM $val_table WHERE user_id = $user_id $available_filter $success_filter $row_entity_filter";
            $result = Database::query($sql);
            var_dump($sql);
            if (Database::num_rows($result) > 0) {
                while($temp_value = Database::fetch_array($result,'ASSOC')){
                    $value[] = $temp_value;
                }
                return $value;
            }
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

    public static function action_post_success_by_user_id($row_entity_id, $user_id, $available_end_date = null) {
        if (self::execute_formulas_by_user_id($row_entity_id, $user_id, 'success', 1, null, null, null)) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $value = self::get_value_by_user_id($row_entity_id, $user_id, 1, 1);
            //$check_array = []; Update later
            foreach ($value as $val) {
                $row_entity_id_prev = $val['sequence_row_entity_id'];
                $sql = "SELECT seq.sequence_row_entity_id_next FROM $seq_table seq WHERE seq.sequence_row_entity_id = $row_entity_id_prev";
                $result = Database::query($sql);
                while ($temp_next = Database::fetch_array($result, 'ASSOC')) {
                    $next[] = $temp_next;
                }
                foreach ($next as $nx) {
                    $row_entity_id_next = $nx['sequence_row_entity_id_next'];
                    $sql = "SELECT val.id, val.success FROM $seq_table seq, $val_table val WHERE
                    seq.is_part != 1 AND
                    val.user_id = $user_id AND
                    val.sequence_row_entity_id = seq.sequence_row_entity_id AND
                    seq.sequence_row_entity_id_next = $row_entity_id_next";
                    $result = Database::query($sql);
                    while ($temp_pre_req = Database::fetch_array($result, 'ASSOC')){
                        $pre_req[] = $temp_pre_req;
                    }
                    foreach ($pre_req as $pr) {
                        if($pr['success'] === 0) {
                            continue 2;
                        }
                    }
                    self::execute_formulas_by_user_id($row_entity_id_next, $user_id, 'pre', 0, null, null, $available_end_date);
                }
            }
        }
    }

    public static function get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id) {
        $row_id = intval(Database::escape_string($row_id));
        $entity_id = intval(Database::escape_string($entity_id));
        $c_id = intval(Database::escape_string($c_id));
        $session_id = intval(Database::escape_string($session_id));
        if ($row_id > 0 && $entity_id > 0 && $c_id > 0 && $session_id >= 0) {
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE 
            row.sequence_type_entity_id = $entity_id AND 
            row.row_id = $row_id AND 
            row.c_id = $c_id AND 
            row.session_id = $session_id 
            LIMIT 0, 1";
            $result = Database::query($sql);
            while ($temp_row_entity = Database::fetch_array($result, 'ASSOC')) {
                $row_entity[] = $temp_row_entity;
            }
            return $row_entity[0]['id'];
        }
        return 0;
    }

    public static function temp_hack_4_update($entity_id, $row_id, $c_id, $session_id, $user_id, $rule_id, $items_completed = 1, $total_items = 1, $available_end_date =null ){
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id,$row_id,$c_id,$session_id);
        self::execute_formulas_by_user_id($row_entity_id, $user_id, 'update', 1, $items_completed, $total_items);
        if (self::validate_rule_by_row_id($row_entity_id, $user_id, $rule_id) && !self::get_value_by_user_id($row_entity_id, $user_id, 1, 1)) {
            self::action_post_success_by_user_id($row_entity_id, $user_id, $available_end_date);
        }
    }
    public static function get_table_by_entity_name($entity_name){
        $entity_name = Database::escape_string($entity_name);
        $ety_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
        $sql = "SELECT ety.ent_table FROM $ety_table ety WHERE ety.name = $entity_name LIMIT 0, 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($temp_entity = Database::fetch_array($result, 'ASSOC')) {
                $entity[] = $temp_entity;
            }
            return $entity[0]['ent_table'];
        }
        return false;
    }

    public static function get_entity_by_entity_name($entity_name){
        $entity_name = Database::escape_string($entity_name);
        $ety_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
        $sql = "SELECT ety.* FROM $ety_table ety WHERE ety.name = $entity_name LIMIT 0, 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($temp_entity = Database::fetch_array($result,'ASSOC')) {
                $entity[] = $temp_entity;
            }
            return $entity;
        }
        return false;
    }

    public static function temp_hack_2_insert($entity_id, $row_id, $c_id, $session_id = 0) {
        $row_id = intval(Database::escape_string($row_id));
        $c_id = intval(Database::escape_string($c_id));
        $session_id = intval(Database::escape_string($session_id));
        $entity_id = intval(Database::escape_string($entity_id));
        if ($entity_id > 0 && $row_id > 0 && $c_id >0 && $session_id >= 0) {
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "INSERT INTO $row_table (sequence_type_entity_id, c_id, session_id, row_id) VALUES
            ($entity_id, $c_id, $session_id, $row_id)";
            $result = Database::query($sql);
            if (Database::affected_rows() > 0) {
                return Database::insert_id();
            }
        }
        return false;
    }
    public static function temp_hack_3_insert($entity_id_prev, $entity_id_next, $row_id_prev = 0, $row_id_next = 0, $c_id = 1, $session_id = 0, $is_part = 0) {
        $is_part = intval(Database::escape_string($is_part));
        $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
        $prev = self::get_row_entity_id_by_row_id($entity_id_prev, $row_id_prev, $c_id, $session_id);
        $next = self::get_row_entity_id_by_row_id($entity_id_next, $row_id_next, $c_id, $session_id);
        if ($prev != 0 || $next != 0) {
            $sql = "INSERT INTO  $seq_table (sequence_row_entity_id, sequence_row_entity_id_next, is_part) VALUES
            ($prev, $next, $is_part)";
            $result = Database::query($sql);
            if (Database::affected_rows() > 0) {
                return Database::insert_id();
            }
        }
        return false;
    }

    public static function temp_hack_4_insert($total_items, $entity_id ,$row_id = 0, $c_id = 0, $session_id = 0, $user_id = 0, $available = 0) {
        $user_id = intval(Database::escape_string($user_id));
        $total_items = intval(Database::escape_string($total_items));
        $available = intval(Database::escape_string($available));
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
        $sql = "INSERT INTO $val_table (user_id, sequence_row_entity_id, total_items, available) VALUES
        ($user_id, $row_entity_id, $total_items, $available)";
        Database::query($sql);
        return Database::insert_id();
    }

    public static function temp_hack_3_update($entity_id_prev, $entity_id_next, $row_id_prev = 0, $row_id_next = 0, $c_id = 0, $session_id = 0) {
        $row_entity_id_prev = self::get_row_entity_id_by_row_id($entity_id_prev, $row_id_prev, $c_id, $session_id);
        $row_entity_id_next = self::get_row_entity_id_by_row_id($entity_id_next, $row_id_next, $c_id, $session_id);
        if ($row_entity_id_prev !== 0 || $row_entity_id_next !== 0) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $sql = "UPDATE $seq_table SET sequence_row_entity_id = $row_entity_id_prev WHERE sequence_row_entity_id_next = $row_entity_id_next";
            Database::query($sql);
            if ($row_entity_id_prev === 0) {
                self::temp_hack_4_set_aval($row_entity_id_next, 1);
            } else {
                self::temp_hack_4_set_aval($row_entity_id_next, 0);
            }
        }
    }

    public static function temp_hack_4_delete($entity_id, $row_id, $c_id, $session_id) {
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        if ($row_entity_id !== false) {
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "DELETE FROM $val_table WHERE sequence_row_entity_id = $row_entity_id";
            $result = Database::query($sql);
            if (Database::affected_rows() > 0) {
                return Database::affected_rows();
            }
        }
        return false;
    }
    public static function temp_hack_3_delete($entity_id, $row_id, $c_id, $session_id, $rule_id)
    {
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        if ($row_entity_id !== false) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $sql = "SELECT * FROM $seq_table WHERE sequence_row_entity_id = $row_entity_id";
            $result = Database::query($sql);
            var_dump($sql);
            while ($temp_seq_array = Database::fetch_array($result, 'ASSOC')){
                $seq_array[] = $temp_seq_array;
            }
            var_dump($seq_array);
            if (is_array($seq_array)) {
                foreach ($seq_array as $seq) {
                    $sql = "SELECT id FROM $seq_table WHERE sequence_row_entity_id_next = ".$seq['sequence_row_entity_id_next'];
                    $result = Database::query($sql);
                    var_dump($sql);
                    if (Database::num_rows($result) > 1){
                        $value = self::get_value_by_row_entity_id($seq['sequence_row_entity_id_next']);
                        var_dump($value);
                        foreach ($value as $val) {
                            if ($seq['is_part'] === 1) {
                                self::execute_formulas_by_user_id($seq['sequence_row_entity_id_next'], $val['user_id'], '', 1, 0, -1, null);
                            }
                            if (self::validate_rule_by_row_id($seq['sequence_row_entity_id_next'], $val['user_id'], $rule_id) && $val['success'] !== 1) {
                                self::action_post_success_by_user_id($seq['sequence_row_entity_id_next'], $val['user_id'], null);
                            }
                        }
                    } else {
                        $sql = "UPDATE $seq_table SET sequence_row_entity_id = 0 WHERE sequence_row_entity_id_next = ".$seq['sequence_row_entity_id_next'];
                        Database::query($sql);
                        self::temp_hack_4_set_aval($seq['sequence_row_entity_id_next'], 1);
                        var_dump("VAAAAAAAAAAAAAAAAAAR");
                    }
                }
            }
            $sql = "DELETE FROM $seq_table WHERE
            (sequence_row_entity_id = $row_entity_id AND sequence_row_entity_id_next = 0 ) OR
            (sequence_row_entity_id_next = $row_entity_id)";
            Database::query($sql);
            var_dump($sql);
            if (Database::affected_rows() > 0) {
                return (!empty($seq_array))? $seq_array : true;
            }
        }
        return false;
    }

    public static function temp_hack_2_delete($entity_id, $row_id, $c_id, $session_id)
    {
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        if ($row_entity_id !== false) {
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "DELETE FROM $row_table WHERE id = $row_entity_id";
            $result = Database::query($sql);
            if (Database::affected_rows() > 0) {
                return Database::affected_rows();
            }
        }
        return false;
    }
    public static function temp_hack_5($entity_id, $row_id, $c_id, $session_id, $rule_id)
    {
        if (self::temp_hack_3_delete($entity_id, $row_id, $c_id, $session_id, $rule_id)) {
            if (self::temp_hack_4_delete($entity_id, $row_id, $c_id, $session_id)) {
                if (self::temp_hack_2_delete($entity_id, $row_id, $c_id, $session_id)) {
                    return true;
                }
            }
        }
        return false;
    }
    public static function temp_hack_4_set_aval($row_entity_id, $available)
    {
        $available = intval(Database::escape_string($available));
        $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
        $sql = "UPDATE $val_table SET available = $available WHERE sequence_row_entity_id = $row_entity_id";
        Database::query($sql);
    }
}
