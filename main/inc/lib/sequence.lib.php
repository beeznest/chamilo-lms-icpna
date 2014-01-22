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
    public static function get_pre_req_id_by_row_entity_id($row_entity_id) {
        $row_entity_id = intval(Database::escape_string($row_entity_id));
        if ($row_entity_id > 0) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $sql = "SELECT sequence_row_entity_id FROM $seq_table WHERE sequence_row_entity_id_next = $row_entity_id";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_pre_req = Database::fetch_array($result, 'ASSOC')){
                    $pre_req[] = intval($temp_pre_req['sequence_row_entity_id']);
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
                $statement = 'return ('.floatval($val[0][$var[0]["name"]]).' '.$con["mat_op"].' '.$con["param"].');';
                if (eval($statement)) {
                    $go = (!isset($con['act_true']))? -1 : intval($con['act_true']);
                } else {
                    $go = (!isset($con['act_false']))? -1 : intval($con['act_false']);
                }
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

    public static function get_user_id_by_row_entity_id($row_entity_id = 0)
    {
        $row_entity_id = intval(Database::escape_string($row_entity_id));
        if ($row_entity_id > 0) {
            $row_entity_filter = "WHERE sequence_row_entity_id = $row_entity_id";
        } else {
            $row_entity_filter = '';
        }
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT DISTINCT user_id FROM $val_table $row_entity_filter";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_user = Database::fetch_array($result, 'ASSOC')) {
                    $user[] = $temp_user['user_id'];
                }
                return $user;
            }
        return false;
    }

    public static function action_pre_init($row_entity_id, $user_id, $available_end_date = null) {
        $user_id = intval(Database::escape_string($user_id));
        $row_entity_id = intval(Database::escape_string($row_entity_id));
        $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
        $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
        $sql = "SELECT DISTINCT seq.sequence_row_entity_id, val.user_id FROM $seq_table seq, $val_table val WHERE
                    seq.is_part != 1 AND
                    val.success = 0 AND
                    val.user_id = $user_id AND
                    val.sequence_row_entity_id = seq.sequence_row_entity_id AND
                    seq.sequence_row_entity_id_next = $row_entity_id";
        $result = Database::query($sql);
        while ($temp = Database::fetch_array($result, 'ASSOC')){
            $pre_req[$temp['user_id']] = $temp['sequence_row_entity_id'];
        }
        if (empty($pre_req)) {
            if (self::get_value_by_user_id($row_entity_id, $user_id) === false) {
                self::temp_hack_4_insert(1, $row_entity_id, $user_id, 0);
            }
            self::execute_formulas_by_user_id($row_entity_id, $user_id, 'pre', 0, null, null, $available_end_date);
            return true;
        } else {
            self::temp_hack_4_set_aval($row_entity_id, $user_id, 0);
        }
        return false;
    }

    public static function action_post_success_by_user_id($row_entity_id, $user_id, $available_end_date = null) {
        if (self::execute_formulas_by_user_id($row_entity_id, $user_id, 'success', 1, null, null, null)) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $value = self::get_value_by_user_id($row_entity_id, $user_id, 1, 1);
            //$check_array = []; Update later
            foreach ($value as $val) {
                $row_entity_id_prev = $val['sequence_row_entity_id'];
                $sql = "SELECT seq.sequence_row_entity_id_next FROM $seq_table seq WHERE seq.sequence_row_entity_id = $row_entity_id_prev";
                $result = Database::query($sql);
                while ($temp_next = Database::fetch_array($result, 'ASSOC')) {
                    $next[] = $temp_next['sequence_row_entity_id_next'];
                }
                foreach ($next as $nx) {
                    self::action_pre_init($nx, $user_id, $available_end_date);
                }
            }
        }
    }

    public static function get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id, $name = '') {
        $row_id = intval(Database::escape_string($row_id));
        $entity_id = intval(Database::escape_string($entity_id));
        $c_id = intval(Database::escape_string($c_id));
        $session_id = intval(Database::escape_string($session_id));
        $name = Database::escape_string($name);
        if ($row_id > 0 && $entity_id > 0 && $c_id > 0 && $session_id >= 0) {
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE
            row.sequence_type_entity_id = $entity_id AND 
            row.row_id = $row_id AND 
            row.c_id = $c_id AND 
            row.session_id = $session_id 
            LIMIT 0, 1";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp_row_entity = Database::fetch_array($result, 'ASSOC')) {
                    $row_entity[] = $temp_row_entity;
                }
                return $row_entity[0]['id'];
            } else {
                $typ_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
                $sql = "SELECT ent_table FROM $typ_table WHERE id = $entity_id LIMIT 0, 1";
                $result =Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $ent_table = Database::fetch_array($result, 'ASSOC');
                    $table = $ent_table['ent_table'];
                    if ($entity_id !== 2) {
                        $sql = "SELECT name, prerequisite FROM $table WHERE c_id = $c_id AND session_id = $session_id AND id = $row_id LIMIT 0, 1";
                        $result = Database::query($sql);
                        if (Database::num_rows($result)) {
                            $temp_arr = Database::fetch_array($result, 'ASSOC');
                            $name = $temp_arr['name'];
                            $pre = ($temp_arr['prerequisite'] > 0)? self::get_row_entity_id_by_row_id($entity_id, $temp_arr['prerequisite'], $c_id, $session_id) : 0 ;
                            $sql = "INSERT INTO $row_table (sequence_type_entity_id, c_id, session_id, row_id, name) VALUES
                            ($entity_id, $c_id, $session_id, $row_id, '$name')";
                            Database::query($sql);
                            $id = Database::insert_id();
                            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
                            $sql = "INSERT INTO $seq_table (sequence_row_entity_id, sequence_row_entity_id_next, is_part) VALUES
                            ($pre, $id, 0)";
                            Database::query($sql);
                            if ($pre !== 0) {
                                $user_id = self::get_user_id_by_row_entity_id($pre);
                                foreach ($user_id as $us_id) {
                                    self::action_pre_init($id, $us_id);
                                }
                            }
                            $sql = "SELECT id FROM $row_table WHERE sequence_type_entity_id = 2 AND c_id = $c_id AND session_id = $session_id and name = 'EXAM' LIMIT 0, 1";
                            $result = Database::query($sql);
                            if (Database::num_rows($result) > 0) {
                                $temp_arr = Database::fetch_array($result, 'ASSOC');
                                $exam_id = $temp_arr['id'];
                                $sql = "INSERT INTO $seq_table (sequence_row_entity_id, sequence_row_entity_id_next, is_part) VALUES
                                ($id, $exam_id, 0)";
                                Database::query($sql);
                                $user_id = self::get_user_id_by_row_entity_id($exam_id);
                                foreach ($user_id as $us_id) {
                                    self::temp_hack_4_set_aval($exam_id, $us_id, 0);
                                }								
                            }
                            return $id;
                        }
                    } elseif (strpos(strtolower($name),'exam') !== false) {
                        $sql = "SELECT id FROM $row_table WHERE c_id = $c_id AND session_id = $session_id";
                        $result = Database::query($sql);
                        while ($temp_arr = Database::fetch_array($result, 'ASSOC')) {
                            $pre_req[] = $temp_arr['id'];
                        }
                        $name = 'EXAM';
                        $sql = "INSERT INTO $row_table (sequence_type_entity_id, c_id, session_id, row_id, name) VALUES
                        ($entity_id, $c_id, $session_id, $row_id, '$name')";
                        Database::query($sql);
                        $id = Database::insert_id();
                        $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
                        foreach ($pre_req as $pr) {
                            $sql = "INSERT INTO $seq_table (sequence_row_entity_id, sequence_row_entity_id_next, is_part) VALUES
                            ($pr, $id, 0)";
                            Database::query($sql);	
                        }
                        return $id;
                    }
                }
            }
        }
        return 0;
    }

    public static function temp_hack_4_update($entity_id, $row_id, $c_id, $session_id, $user_id, $rule_id, $items_completed = 1, $total_items = 1, $available_end_date =null ){
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        self::execute_formulas_by_user_id($row_entity_id, $user_id, 'update', 1, $items_completed, $total_items);
        if (self::validate_rule_by_row_id($row_entity_id, $user_id, $rule_id) && self::get_value_by_user_id($row_entity_id, $user_id, 1, 1 === false)) {
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

    public static function temp_hack_2_insert($entity_id, $row_id, $c_id, $session_id = 0, $name = '') {
        $row_id = intval(Database::escape_string($row_id));
        $c_id = intval(Database::escape_string($c_id));
        $session_id = intval(Database::escape_string($session_id));
        $entity_id = intval(Database::escape_string($entity_id));
        $name = Database::escape_string($name);
        if ($entity_id > 0 && $row_id > 0 && $c_id >0 && $session_id >= 0) {
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "INSERT INTO $row_table (sequence_type_entity_id, c_id, session_id, row_id, name) VALUES
            ($entity_id, $c_id, $session_id, $row_id, '$name')";
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

    public static function temp_hack_4_insert($total_items, $row_entity_id, $user_id = 0, $available = 0) {
        $user_id = intval(Database::escape_string($user_id));
        $total_items = intval(Database::escape_string($total_items));
        $available = intval(Database::escape_string($available));
        if ($available === 0) {
            $pre_req = self::get_pre_req_id_by_row_entity_id($row_entity_id);
            foreach ($pre_req as $pr) {
                if($pr === 0) {
                    $available = 1;
                    break;
                }
            }
        }
        if (self::get_value_by_user_id($row_entity_id, $user_id) === false) {
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "INSERT INTO $val_table (user_id, sequence_row_entity_id, total_items, available) VALUES
            ($user_id, $row_entity_id, $total_items, $available)";
            Database::query($sql);
            return Database::insert_id();
        }
    }

    public static function temp_hack_3_update($entity_id_prev, $entity_id_next, $row_id_prev = 0, $row_id_next = 0, $c_id = 0, $session_id = 0, $user_id = 0) {
        $user_id = intval(Database::escape_string($user_id));
        $row_entity_id_prev = self::get_row_entity_id_by_row_id($entity_id_prev, $row_id_prev, $c_id, $session_id);
        $row_entity_id_next = self::get_row_entity_id_by_row_id($entity_id_next, $row_id_next, $c_id, $session_id);
        if ($row_entity_id_prev !== 0 || $row_entity_id_next !== 0) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $sql = "UPDATE $seq_table SET sequence_row_entity_id = $row_entity_id_prev WHERE sequence_row_entity_id_next = $row_entity_id_next";
            Database::query($sql);
            if ($row_entity_id_prev === 0) {
                if ($user_id === 0) {
                    $user_id = self::get_user_id_by_row_entity_id($row_entity_id_next);
                    foreach ($user_id as $us_id) {
                        self::action_pre_init($row_entity_id_next, $us_id);
                    }
                } else {
                    self::temp_hack_4_set_aval($row_entity_id_next, $user_id, 1);
                }
            } else {
                if ($user_id === 0) {
                    $user_id = self::get_user_id_by_row_entity_id($row_entity_id_prev);
                    foreach ($user_id as $us_id) {
                        self::action_pre_init($row_entity_id_next, $us_id);
                    }
                } else {
                    self::action_pre_init($row_entity_id_next, $user_id);
                }
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
    public static function temp_hack_3_delete($entity_id, $row_id, $c_id, $session_id, $rule_id) {
        $row_entity_id = self::get_row_entity_id_by_row_id($entity_id, $row_id, $c_id, $session_id);
        if ($row_entity_id !== false) {
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $sql = "SELECT * FROM $seq_table WHERE sequence_row_entity_id = $row_entity_id";
            $result = Database::query($sql);
            while ($temp_seq_array = Database::fetch_array($result, 'ASSOC')){
                $seq_array[] = $temp_seq_array;
            }
            if (is_array($seq_array)) {
                foreach ($seq_array as $seq) {
                    $sql = "SELECT id FROM $seq_table WHERE sequence_row_entity_id_next = ".$seq['sequence_row_entity_id_next'];
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 1){
                        $value = self::get_value_by_row_entity_id($seq['sequence_row_entity_id_next']);
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
                        $user = self::get_user_id_by_row_entity_id($seq['sequence_row_entity_id_next']);
                        foreach ($user as $us) {
                            self::temp_hack_4_set_aval($seq['sequence_row_entity_id_next'], $us['user_id'], 1);
                        }
                    }
                }
            }
            $sql = "DELETE FROM $seq_table WHERE
            (sequence_row_entity_id = $row_entity_id AND sequence_row_entity_id_next = 0 ) OR
            (sequence_row_entity_id_next = $row_entity_id)";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                return (!empty($seq_array))? $seq_array : true;
            }
        }
        return false;
    }

    public static function temp_hack_2_delete($entity_id, $row_id, $c_id, $session_id) {
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
    public static function temp_hack_5($entity_id, $row_id, $c_id, $session_id, $rule_id) {
        if (self::temp_hack_3_delete($entity_id, $row_id, $c_id, $session_id, $rule_id)) {
            if (self::temp_hack_4_delete($entity_id, $row_id, $c_id, $session_id)) {
                if (self::temp_hack_2_delete($entity_id, $row_id, $c_id, $session_id)) {
                    return true;
                }
            }
        }
        return false;
    }
    public static function temp_hack_4_set_aval($row_entity_id, $user_id, $available, $available_end_date = null) {
        $available_end_date = Database::escape_string($available_end_date);
        $available = intval(Database::escape_string($available));
        $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
        if ($available == 1) {
            $available_date = api_get_utc_datetime();
            $available_date = ", available_start_date = '$available_date'";
            if (!empty($available_end_date)) {
                $available_end_date = api_get_utc_datetime($available_end_date);
                $available_date .= "available_end_date = '$available_end_date'";
            }
        } else {
            $available_date = '';
        }
        $sql = "UPDATE $val_table SET available = $available $available_date WHERE
        sequence_row_entity_id = $row_entity_id AND
        user_id = $user_id";
        Database::query($sql);
    }

    public static function get_row_entity_id_by_user_id($user_id) {
        $user_id = intval(Database::escape_string($user_id));
        if ($user_id > 0) {
            $user_filter = "WHERE user_id = $user_id";
        } else {
            $user_filter = '';
        }
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT sequence_row_entity_id FROM $val_table $$user_filter";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($temp = Database::fetch_array($result, 'ASSOC')) {
                    $row_entity[] = $temp;
                }
                return $row_entity;
            }
        return false;
    }

    /**
     * Bool Available for LP
     */
    public static function get_state_lp_by_row_entity_id ($row_entity_id, $user_id) {
        $row_entity_id = intval($row_entity_id);
        $user_id = intval($user_id);
        $seq_val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
        if ($row_entity_id > 0 && $user_id > 0) {
            if (self::get_value_by_user_id($row_entity_id, $user_id) === false) {
                self::temp_hack_4_insert(1,$row_entity_id,$user_id, 0);
                return self::get_state_lp_by_row_entity_id($row_entity_id, $user_id);
            }
            $sql_seq = "SELECT val.available, val.success FROM $seq_val_table val WHERE val.sequence_row_entity_id = $row_entity_id AND val.user_id = $user_id LIMIT 0, 1";
            $result_seq = Database::query($sql_seq);
            $arr_seq = Database::fetch_array($result_seq);
            if (intval($arr_seq['available']) === 1) {
                if (intval($arr_seq[success]) === 1) {
                    return "completed";
                } else {
                    return "process";
                }
            } else {
                return "closed";
            }
        }
        return false;
    }
}
