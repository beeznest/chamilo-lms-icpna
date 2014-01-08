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
    public static function get_pre_req_id_by_row_id($entity_id, $row_entity_id, $course = null) {
        $row_entity_id = Database::escape_string($row_entity_id);
        $entity_id = Database::escape_string($entity_id);
        $course = Database::escape_string($course);
        if (is_numeric($row_entity_id) && is_numeric($entity_id)) {
            $course = is_numeric($course) ? $course : api_get_course_id();
            $seq_table = Database::get_main_table(TABLE_MAIN_SEQUENCE);
            $row_table = Database::get_main_table(TABLE_SEQUENCE_ROW_ENTITY);
            $sql = "SELECT row.id FROM $row_table row WHERE row.sequence_type_entity_id = $entity_id AND row.row_id = $row_entity_id AND row.c_id = $course LIMIT 0, 1";
            $sql = "SELECT main.sequence_row_entity_id FROM $seq_table main WHERE main.sequence_row_entity_id_next = ($sql)";
            $sql = "SELECT res.sequence_type_entity_id, res.row_id, res.c_id FROM $row_table res WHERE res.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $pre_req = Database::fetch_array($result);
                return $pre_req;
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
            $ent_table = Database::get_main_table(TABLE_SEQUENCE_TYPE_ENTITY);
            $sql = "SELECT * FROM $ent_table WHERE id = $entity_id LIMIT 0, 1";
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

    public static function get_value_by_row_user_id($entity_id, $row_entity_id, $course_id = null, $user_id = null) {
        $row_entity_id = Database::escape_string($row_entity_id);
        $entity_id = Database::escape_string($entity_id);
        $course_id = Database::escape_string($course_id);
        $user_id = Database::escape_string($user_id);
        if (is_numeric($row_entity_id) && is_numeric($entity_id)) {
            $course_id = is_numeric($course_id) ? $course_id : api_get_user_id();
            $user_id = is_numeric($user_id) ? $user_id : api_get_user_id();
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

    public static function execute_formulas_by_user_id($user_id = null) {
        $value = self::get_value_by_user_id($user_id);
        if ($value !== false) {
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $rul_table = Database::get_main_table(TABLE_SEQUENCE_RULE);
            $rul_met_table = Database::get_main_table(TABLE_SEQUENCE_RULE_METHOD);
            $met_table = Database::get_main_table(TABLE_SEQUENCE_METHOD);
            $var_table = Database::get_main_table(TABLE_SEQUENCE_VARIABLE);
            $sql = "SELECT var.name FROM $var_table var";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $var = Database::fetch_array($result);
            }
            $sql = "SELECT rul.id FROM $rul_table rul";
            $sql = "SELECT rm.sequence_method_id FROM $rul_met_table rm WHERE rm.sequence = ($sql) ORDER BY rm.method_order";
            $sql = "SELECT DISTINCT met.id, met.formula, met.assign FROM $met_table met WHERE met.id = ($sql)";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $formula= Database::fetch_array($result);
            }
            $pat = "/v#(\d+)/";
            $rep = '$val[$var[$1]["name"]]';
            if((isset($value) && isset($formula)) && isset ($var)) {
                foreach ($value as $val) {
                    $sql_array = array(
                        'update' => " $val_table SET "
                    );
                    foreach ($formula as $fml) {
                        $fml_exe = preg_replace($pat, $rep, $fml['formula']);
                        $fml_exe .= ';';
                        eval($fml_exe);
                        $assing_key = $var[$fml['assign']]['name'];
                        $assign_val = $val[$var[$fml['assign']]['name']];
                        $sql_array[$assing_key] = " = $assign_val";
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

    public static function get_value_by_user_id($user_id = null) {
        $user_id = Database::escape_string($user_id);
        $user_id = (isset($user_id))? $user_id : api_get_user_id();
        if (is_numeric($user_id)) {
            $val_table = Database::get_main_table(TABLE_SEQUENCE_VALUE);
            $sql = "SELECT * FROM $val_table val WHERE val.user_id = $user_id";
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

    public static function execute_formulas_by_user_row_id($user_id = null) {

    }
}