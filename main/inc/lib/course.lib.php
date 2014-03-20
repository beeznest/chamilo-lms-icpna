<?php
/* For licensing terms, see /license.txt*/
/**
 * This is the course library for Chamilo.
 *
 * All main course functions should be placed here.
 *
 * Many functions of this library deal with providing support for
 * virtual/linked/combined courses (this was already used in several universities
 * but not available in standard Chamilo).
 *
 * The implementation changed, initially a course was a real course
 * if target_course_code was 0 , this was changed to NULL.
 * There are probably some places left with the wrong code.
 *
 * @package chamilo.library
 */
/**
 *    CourseManager Class
 *    @package chamilo.library
 */
class CourseManager {

    CONST MAX_COURSE_LENGTH_CODE = 40;

    //This constant is used to show separate user names in the course list (userportal), footer, etc
    CONST USER_SEPARATOR = ' |';

    CONST COURSE_FIELD_TYPE_CHECKBOX = 10;

    var $columns = array();

    /**
     * Creates a course
     * @param   array   with the columns in the main.course table
     * @param   mixed   false if the course was not created, array with the course info
     * @return mixed False on error, or an array with course attributes on success
     * @assert () === false
     */
    static function create_course($params) {
        global $_configuration;

        // Check portal limits
        $access_url_id = 1;
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }

        if (is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_courses']) && $_configuration[$access_url_id]['hosting_limit_courses'] > 0) {
            $num = self::count_courses();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_courses']) {
                return api_set_failure('PortalCoursesLimitReached');
            }
        }

        if (empty($params['title'])) {
            return false;
        }

        if (empty($params['wanted_code'])) {
            $params['wanted_code'] = $params['title'];
            // Check whether the requested course code has already been occupied.
            $params['wanted_code'] = self::generate_course_code(api_substr($params['title'], 0, self::MAX_COURSE_LENGTH_CODE));
        }

        // Create the course keys
        $keys = self::define_course_keys($params['wanted_code']);

        $params['exemplary_content'] = isset($params['exemplary_content']) ? $params['exemplary_content'] : false;

        if (count($keys)) {

            $params['code']             = $keys['currentCourseCode'];
            $params['visual_code']      = $keys['currentCourseId'];
            $params['directory']        = $keys['currentCourseRepository'];

            $course_info = api_get_course_info($params['code']);

            if (empty($course_info)) {
                $course_id      = self::register_course($params);
                $course_info    = api_get_course_info_by_id($course_id);

                if (!empty($course_info)) {
                    self::prepare_course_repository($course_info['directory'], $course_info['code']);
                    self::fill_db_course($course_id, $course_info['directory'], $course_info['course_language'], $params['exemplary_content']);

                    //Create an empty gradebook
                    if (isset($params['create_gradebook_evaluation']) && $params['create_gradebook_evaluation'] == true) {
                        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                        $category_id = create_default_course_gradebook($course_info['code']);
                        if ($category_id && isset($params['gradebook_params'])) {
                            $eval = new Evaluation();
                            $eval->set_name($params['gradebook_params']['name']);
                            $eval->set_user_id($params['gradebook_params']['user_id']);
                            $eval->set_course_code($course_info['code']);
                            $eval->set_category_id($category_id);
                            $eval->set_weight($params['gradebook_params']['weight']);
                            $eval->set_max($params['gradebook_params']['max']);
                            $eval->set_visible(0);
                            $eval->add();
                        }
                    }
                    if (api_get_setting('gradebook_enable_grade_model') == 'true') {
                        //Create gradebook_category for the new course and add a gradebook model for the course
                        if (isset($params['gradebook_model_id']) && !empty($params['gradebook_model_id']) && $params['gradebook_model_id'] != '-1') {
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                            create_default_course_gradebook($course_info['code'], $params['gradebook_model_id']);
                        }
                    }

                    //Saving extra course fields
                    $field_value = new ExtraFieldValue('course');
                    $params['course_code'] = $course_info['code'];
                    $field_value->save_field_values($params);

                    return $course_info;
                }
            } else {
                //error_log('Course already exists with params: '.$params['code']);
                //error_log(print_r($params, 1));
                //Course already exists
                if (isset($params['return_item_if_already_exists']) && $params['return_item_if_already_exists']) {
                    return $course_info;
                }
            }
        }
        return false;
    }
    /**
     * Updates a course with the given array of attributes
     * @param array Array of attributes
     * @return array Array of course attributes
     * @assert () === false
     */
    static function update($params) {
        if (!is_array($params) or count($params)<1) { return false; }
        $course_user_table  = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $course_table       = Database::get_main_table(TABLE_MAIN_COURSE);

        $course_code = $params['code'];
        $visual_code = $params['visual_code'];

        $field_value = new ExtraFieldValue('course');
        $params['course_code'] = $course_code;
        $field_value->save_field_values($params);

        /*$tutor_id = $params['tutor_name'];
        $tutor_name=$platform_teachers[$tutor_id];*/

        $teachers = $params['group']['course_teachers'];

        $title = $params['title'];
        $category_code = $params['category_code'];
        $department_name = $params['department_name'];
        $department_url = $params['department_url'];
        $course_language = $params['course_language'];

        $course['disk_quota'] = $params['disk_quota']*1024*1024;

        $disk_quota = $params['disk_quota'];
        $visibility = $params['visibility'];
        $subscribe = $params['subscribe'];
        $unsubscribe = $params['unsubscribe'];
        if (!stristr($department_url, 'http://')) {
            $department_url = 'http://'.$department_url;
        }

        //tutor_name='".Database::escape_string($tutor_name)."',
        $sql = "UPDATE $course_table SET
                    course_language='".Database::escape_string($course_language)."',
                    title='".Database::escape_string($title)."',
                    category_code='".Database::escape_string($category_code)."',
                    visual_code='".Database::escape_string($visual_code)."',
                    department_name='".Database::escape_string($department_name)."',
                    department_url='".Database::escape_string($department_url)."',
                    disk_quota='".Database::escape_string($disk_quota)."',
                    visibility = '".Database::escape_string($visibility)."',
                    subscribe = '".Database::escape_string($subscribe)."',
                    unsubscribe='".Database::escape_string($unsubscribe)."'
                WHERE code='".Database::escape_string($course_code)."'";
        Database::query($sql);

        //Delete only teacher relations that doesn't match the selected teachers
        $cond='';
        if (count($teachers)>0) {
            foreach($teachers as $key) $cond.=" AND user_id<>'".$key."'";
        }
        $sql='DELETE FROM '.$course_user_table.' WHERE course_code="'.Database::escape_string($course_code).'" AND status="1"'.$cond;
        Database::query($sql);

        if (count($teachers)>0) {
            foreach ($teachers as $key) {
                //We check if the teacher is already subscribed in this course
                $sql_select_teacher = 'SELECT 1 FROM '.$course_user_table.' WHERE user_id = "'.$key.'" AND course_code = "'.$course_code.'" ';
                $result = Database::query($sql_select_teacher);

                if (Database::num_rows($result) == 1) {
                    $sql = 'UPDATE '.$course_user_table.' SET status = "1" WHERE course_code = "'.$course_code.'" AND user_id = "'.$key.'"  ';
                } else {
                    $sql = "INSERT INTO ".$course_user_table . " SET
                        course_code = '".Database::escape_string($course_code). "',
                        user_id = '".$key . "',
                        status = '1',
                        role = '',
                        tutor_id='0',
                        sort='0',
                        user_course_cat='0'";
                }
                Database::query($sql);
            }
        }

        //user_id = '".$tutor_id . "',
        $sql = "INSERT IGNORE INTO ".$course_user_table . " SET
                    course_code = '".Database::escape_string($course_code). "',
                    status = '1',
                    role = '',
                    tutor_id='0',
                    sort='0',
                    user_course_cat='0'";
        Database::query($sql);

        $course_info = api_get_course_info($course_code);
        //$course_id = $course_info['real_id'];
        //That table does no exists
        /*$forum_config_table = Database::get_course_table(TOOL_FORUM_CONFIG_TABLE);
        $sql = "UPDATE ".$forum_config_table." SET default_lang='".Database::escape_string($course_language)."' WHERE c_id = $course_id ";
        Database::query($sql);*/
        return $course_info;
    }

    /**
     * Returns all the information of a given coursecode from the course table
     * @param string $course_code, the course code
     * @return an array with all the fields of the course table
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @assert (array(1,2,3)) === false
     */
    public static function get_course_information($course_code) {
        return Database::fetch_array(Database::query(
            "SELECT *, id as real_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
            WHERE code='".Database::escape_string($course_code)."'"),'ASSOC'
        );
    }

    /**
     * Returns all the information of a given coursecode from the course
     * table by it integer ID
     * @param   int     the course id
     * @return an array with all the fields of the course table
     * @assert (-1) === false
     */
    public static function get_course_information_by_id($course_id) {
        return Database::select('*, id as real_id', Database::get_main_table(TABLE_MAIN_COURSE), array('where'=>array('id = ?' =>intval($course_id))),'first');
    }

    /**
     * Returns a list of courses. Should work with quickform syntax
     * @param    integer    Offset (from the 7th = '6'). Optional.
     * @param    integer    Number of results we want. Optional.
     * @param    string    The column we want to order it by. Optional, defaults to first column.
     * @param    string    The direction of the order (ASC or DESC). Optional, defaults to ASC.
     * @param    string    The visibility of the course, or all by default.
     * @param    string    If defined, only return results for which the course *title* begins with this string
     * @return mixed Array of courses details, or false on error
     * @assert (array(1,2,3)) === false
     */
    public static function get_courses_list($from = 0, $howmany = 0, $orderby = 1, $orderdirection = 'ASC', $visibility = -1, $startwith = '') {

        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." ";
        if (!empty($startwith)) {
            $sql .= "WHERE title LIKE '".Database::escape_string($startwith)."%' ";
            if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
                $sql .= " AND visibility = $visibility ";
            }
        } else {
            $sql .= "WHERE 1 ";
            if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
                $sql .= " AND visibility = $visibility ";
            }
        }
        if (!empty($orderby)) {
            $sql .= " ORDER BY ".Database::escape_string($orderby)." ";
        } else {
            $sql .= " ORDER BY 1 ";
        }

        if (!in_array($orderdirection, array('ASC', 'DESC'))) {
            $sql .= 'ASC';
        } else {
            $sql .= Database::escape_string($orderdirection);
        }

        if (!empty($howmany) && is_int($howmany) and $howmany > 0) {
            $sql .= ' LIMIT '.Database::escape_string($howmany);
        } else {
            $sql .= ' LIMIT 1000000'; //virtually no limit
        }
        if (!empty($from)) {
            $from = intval($from);
            $sql .= ' OFFSET '.Database::escape_string($from);
        } else {
            $sql .= ' OFFSET 0';
        }

        return Database::store_result(Database::query($sql));
    }

    /**
     * Returns the access settings of the course:
     * which visibility;
     * wether subscribing is allowed;
     * wether unsubscribing is allowed.
     *
     * @param string $course_code, the course code
     * @todo for more consistency: use course_info call from database API
     * @return an array with int fields "visibility", "subscribe", "unsubscribe"
     * @assert () === false
     */
    public static function get_access_settings($course_code) {
        return Database::fetch_array(Database::query(
            "SELECT visibility, subscribe, unsubscribe from ".Database::get_main_table(TABLE_MAIN_COURSE)."
            WHERE code = '".Database::escape_string($course_code)."'")
        );
    }

    /**
     * Returns the status of a user in a course, which is COURSEMANAGER
     * or STUDENT.
     * @param   int      User ID
     * @param   string   Course code
     * @return int the status of the user in that course
     * @assert () === false
     */
    public static function get_user_in_course_status($user_id, $course_code) {
        $result = Database::fetch_array(Database::query(
            "SELECT status FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
            WHERE course_code = '".Database::escape_string($course_code)."' AND user_id = ".Database::escape_string($user_id))
        );
        return $result['status'];
    }
    /**
     * Gets the ID of a course tutor for a user, given the user and the course
     * @param int User ID
     * @param string Course code
     * @return mixed The tutor ID
     * @assert () === false
     */
    public static function get_tutor_in_course_status($user_id, $course_code) {
        $result = Database::fetch_array(Database::query(
                "SELECT tutor_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE course_code = '".Database::escape_string($course_code)."' AND user_id = ".Database::escape_string($user_id))
        );
        return $result['tutor_id'];
    }


    /**
     * Unsubscribe one or more users from a course
     * @param   mixed   user_id or an array with user ids
     * @param   int     session id
     * @param   string  course code
     * @return  mixed   False on user ID not found, void otherwise
     * @assert () === false
     */
    public static function unsubscribe_user($user_id, $course_code, $session_id = 0) {
        if (!isset($user_id)) { return false; }
        if (!is_array($user_id)) {
            $user_id = array($user_id);
        }
        if (count($user_id) == 0) {
            return false;
        }
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = intval($_SESSION['id_session']);
        }

        $user_list = array();

        //Cleaning the $user_id variable
        if (is_array($user_id)) {
            $new_user_id_list = array();
            foreach($user_id as $my_user_id) {
                $new_user_id_list[]= intval($my_user_id);
            }
            $new_user_id_list = array_filter($new_user_id_list);
            $user_list = $new_user_id_list;
            $user_ids = implode(',', $new_user_id_list);
        } else {
            $user_ids = intval($user_id);
            $user_list[] = $user_id;
        }


        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        // Unsubscribe user from all groups in the course.
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_USER)."  WHERE c_id = $course_id AND user_id IN (".$user_ids.")");
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_TUTOR)." WHERE c_id = $course_id AND user_id IN (".$user_ids.")");

        // Erase user student publications (works) in the course - by André Boivin
        //@todo field student_publication.author should be the user id

        $sqlu = "SELECT * FROM $table_user WHERE user_id IN (".$user_ids.")";
        $resu = Database::query($sqlu);
        $username = Database::fetch_array($resu,'ASSOC');
        $userfirstname = $username['firstname'];
        $userlastname = $username['lastname'];
        $publication_name = $userfirstname.' '.$userlastname ;

        $table_course_user_publication     = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
        $sql = "DELETE FROM $table_course_user_publication WHERE c_id = $course_id AND author = '".Database::escape_string($publication_name)."'";
        Database::query($sql);


        // Unsubscribe user from all blogs in the course.
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_REL_USER)." WHERE c_id = $course_id AND  user_id IN (".$user_ids.")");
        Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER)."WHERE c_id = $course_id AND  user_id IN (".$user_ids.")");

        //Deleting users in forum_notification and mailqueue course tables
        $sql_delete_forum_notification = "DELETE FROM  ".Database::get_course_table(TABLE_FORUM_NOTIFICATION)." WHERE c_id = $course_id AND user_id IN (".$user_ids.")";
        Database::query($sql_delete_forum_notification);

        $sql_delete_mail_queue = "DELETE FROM ".Database::get_course_table(TABLE_FORUM_MAIL_QUEUE)." WHERE c_id = $course_id AND user_id IN (".$user_ids.")";
        Database::query($sql_delete_mail_queue);


        // Unsubscribe user from the course.
        if (!empty($session_id)) {
            // Delete in table session_rel_course_rel_user
            Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session ='".$session_id."' AND course_code = '".Database::escape_string($_SESSION['_course']['id'])."' AND id_user IN ($user_ids)");

            foreach ($user_id as $uid) {
                // check if a user is register in the session with other course
                $sql = "SELECT id_user FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session='$session_id' AND id_user='$uid'";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) == 0) {
                    // Delete in table session_rel_user
                    Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                                     WHERE id_session ='".$session_id."' AND id_user='$uid' AND relation_type<>".SESSION_RELATION_TYPE_RRHH."");
                }
            }

            // Update the table session
            $row = Database::fetch_array(Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                    WHERE id_session = '".$session_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH."  "));
            $count = $row[0]; // number of users by session
            $result = Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count'
                    WHERE id = '".$session_id."'");

            // Update the table session_rel_course
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session = '$session_id' AND course_code = '$course_code' AND status<>2" ));
            $count = $row[0]; // number of users by session and course
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." SET nbr_users = '$count' WHERE id_session = '$session_id' AND course_code = '$course_code' ");

        } else {
            Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE user_id IN (".$user_ids.") AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '".$course_code."'");

            // add event to system log
            $user_id = api_get_user_id();
            event_system(LOG_UNSUBSCRIBE_USER_FROM_COURSE, LOG_COURSE_CODE, $course_code, api_get_utc_datetime(), $user_id);

            foreach ($user_list as $user_id_to_delete) {
                $user_info = api_get_user_info($user_id_to_delete);
                event_system(LOG_UNSUBSCRIBE_USER_FROM_COURSE, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), $user_id);
            }
        }
    }

    /**
     * Subscribe a user to a course. No checks are performed here to see if
     * course subscription is allowed.
     * @param   int     User ID
     * @param   string  Course code
     * @param   int     Status (STUDENT, COURSEMANAGER, COURSE_ADMIN, NORMAL_COURSE_MEMBER)
     * @return  bool    True on success, false on failure
     * @see add_user_to_course
     */
    public static function subscribe_user($user_id, $course_code, $status = STUDENT, $session_id = 0) {

        if ($user_id != strval(intval($user_id))) {
            return false; //detected possible SQL injection
        }

        $course_code = Database::escape_string($course_code);
        if (empty ($user_id) || empty ($course_code)) {
            return false;
        }

        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        $status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
        //$role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;

        // A preliminary check whether the user has bben already registered on the platform.
        if (Database::num_rows(@Database::query("SELECT status FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                WHERE user_id = '$user_id' ")) == 0) {
            return false; // The user has not been registered to the platform.
        }

        // Check whether the user has not been already subscribed to the course.
        if (empty($session_id)) {
            if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$course_code'")) > 0) {
                return false; // The user has been already subscribed to the course.
            }
        }

        if (!empty($session_id)) {

            // Check whether the user has not already been stored in the session_rel_course_user table
            if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE course_code = '".$_SESSION['_course']['id']."'
                    AND id_session ='".$session_id."'
                    AND id_user = '".$user_id."'")) > 0) {
                return false;
            }

            // check if the user is registered in the session with other course
            $sql = "SELECT id_user FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session='".$session_id."' AND id_user='$user_id'";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                // Check whether the user has not already been stored in the session_rel_user table
                if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                        WHERE id_session ='".$session_id."'
                        AND id_user = '".$user_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH." ")) > 0) {
                    return false;
                }
            }

            // Add him/her in the table session_rel_course_rel_user
            @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    SET id_session ='".$session_id."',
                    course_code = '".$_SESSION['_course']['id']."',
                    id_user = '".$user_id."'");

            // Add him/her in the table session_rel_user
            @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
                    SET id_session ='".$session_id."',
                    id_user = '".$user_id."'");

            // Update the table session
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)." WHERE id_session = '".$session_id."' AND relation_type<>".SESSION_RELATION_TYPE_RRHH.""));
            $count = $row[0]; // number of users by session
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count' WHERE id = '".$session_id."'");

            // Update the table session_rel_course
            $row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE id_session = '".$session_id."' AND course_code = '$course_code' AND status<>2" ));
            $count = $row[0]; // number of users by session
            $result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." SET nbr_users = '$count' WHERE id_session = '".$session_id."' AND course_code = '$course_code' ");

        } else {
            $course_sort = self::userCourseSort($user_id, $course_code);
            $result = @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    SET course_code = '$course_code',
                        user_id     = '$user_id',
                        status      = '".$status."',
                        sort        = '". ($course_sort)."'");

            // Add event to the system log
            event_system(LOG_SUBSCRIBE_USER_TO_COURSE, LOG_COURSE_CODE, $course_code, api_get_utc_datetime(), api_get_user_id());

            $user_info = api_get_user_info($user_id);
            event_system(LOG_SUBSCRIBE_USER_TO_COURSE, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), api_get_user_id());
        }
        return (bool)$result;
    }

    /**
     * Get the course id based on the original id and field name in the extra fields. Returns 0 if course was not found
     *
     * @param string Original course id
     * @param string Original field name
     * @return int Course id
     */
    public static function get_course_code_from_original_id($original_course_id_value, $original_course_id_name) {
        $t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $table_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $sql_course = "SELECT course_code FROM $table_field cf INNER JOIN $t_cfv cfv ON cfv.field_id=cf.id WHERE field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
        $res = Database::query($sql_course);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->course_code;
        } else {
            return 0;
        }
    }

    /**
     * Gets the course code from the course id. Returns null if course id was not found
     *
     * @param int Course id
     * @return string Course code
     */
    public static function get_course_code_from_course_id($id) {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $id = intval($id);
        $sql = "SELECT code FROM $table WHERE id = '$id' ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->code;
        } else {
            return null;
        }
    }

    /**
     * Gets the course id from the course code. Returns null if course id was not found
     *
     * @param string Course code
     * @return int Course id
     */
    public static function get_course_id_from_course_code($code) {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $code = Database::escape_string($code);
        $sql = "SELECT id FROM $table WHERE code = '$code' ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->id;
        } else {
            return null;
        }
    }

    /**
     * Gets the course title from the course id. Returns null if course id was not found
     *
     * @param string Course code
     * @return string Course title
     */
    public static function get_course_title_from_course_id($id) {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $id = Database::escape_string($id);
        $sql = "SELECT title FROM $table WHERE id = '$id' ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->title;
        } else {
            return null;
        }
    }

    /**
     * Subscribe a user $user_id to a course $course_code.
     * @author Hugues Peeters
     * @author Roan Embrechts
     *
     * @param  int $user_id the id of the user
     * @param  string $course_code the course code
     * @param string $status (optional) The user's status in the course
     *
     * @return boolean true if subscription succeeds, boolean false otherwise.
     */
    public static function add_user_to_course($user_id, $course_code, $status = STUDENT) {
        $debug = false;
        $user_table         = Database::get_main_table(TABLE_MAIN_USER);
        $course_table       = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_user_table  = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
        if (empty($user_id) || empty($course_code) || ($user_id != strval(intval($user_id)))) {
            return false;
        }
        $course_code = Database::escape_string($course_code);

        // Check in advance whether the user has already been registered on the platform.
        if (Database::num_rows(Database::query("SELECT status FROM ".$user_table." WHERE user_id = '$user_id' ")) == 0) {
            if ($debug) error_log('The user has not been registered to the platform');
            return false; // The user has not been registered to the platform.
        }

        // Check whether the user has already been subscribed to this course.
        if (Database::num_rows(Database::query("SELECT * FROM ".$course_user_table." WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$course_code'")) > 0) {
            if ($debug) error_log('The user has been already subscribed to the course');
            return false; // The user has been subscribed to the course.
        }

        // Check in advance whether subscription is allowed or not for this course.
        if (Database::num_rows(Database::query("SELECT code, visibility FROM ".$course_table." WHERE code = '$course_code' AND subscribe = '".SUBSCRIBE_NOT_ALLOWED."'")) > 0) {
            if ($debug) error_log('Subscription is not allowed for this course');
            return false; // Subscription is not allowed for this course.
        }

        // Ok, subscribe the user.
        $max_sort = api_max_sort_value('0', $user_id);
        return (bool)Database::query("INSERT INTO ".$course_user_table."
                SET course_code = '$course_code',
                user_id = '$user_id',
                status = '".$status."',
                sort = '". ($max_sort + 1)."'");
    }

    /**
     *    Checks wether a parameter exists.
     *    If it doesn't, the function displays an error message.
     *
     *    @return true if parameter is set and not empty, false otherwise
     *    @todo move function to better place, main_api ?
     */
    public static function check_parameter($parameter, $error_message) {
        if (empty($parameter)) {
            Display::display_normal_message($error_message);
            return false;
        }
        return true;
    }

    /**
     *    Lets the script die when a parameter check fails.
     *    @todo move function to better place, main_api ?
     */
    public static function check_parameter_or_fail($parameter, $error_message) {
        if (!self::check_parameter($parameter, $error_message)) {
            die();
        }
    }

    /**
     *    @return true if there already are one or more courses
     *    with the same code OR visual_code (visualcode), false otherwise
     */
    public static function course_code_exists($wanted_course_code) {
        $wanted_course_code = Database::escape_string($wanted_course_code);
        $result = Database::fetch_array(Database::query("SELECT COUNT(*) as number FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE code = '$wanted_course_code' OR visual_code = '$wanted_course_code'"));
        return $result['number'] > 0;
    }

    /**
     *    @return an array with the course info of all real courses on the platform
     */
    public static function get_real_course_list() {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NULL");
        $real_course_list = array();
        while ($result = Database::fetch_array($sql_result)) {
            $real_course_list[$result['code']] = $result;
        }
        return $real_course_list;
    }

    /**
     * Lists all virtual courses
     * @return array   Course info (course code => details) of all virtual courses on the platform
     */
    public static function get_virtual_course_list() {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NOT NULL");
        $virtual_course_list = array();
        while ($result = Database::fetch_array($sql_result)) {
            $virtual_course_list[$result['code']] = $result;
        }
        return $virtual_course_list;
    }

    /**
     * Returns an array with the course info of the real courses of which
     * the current user is course admin
     * @return array   A list of courses details for courses to which the user is subscribed as course admin (status = 1)
     */
    public static function get_real_course_list_of_user_as_course_admin($user_id) {
        $result_array = array();
        if ($user_id != strval(intval($user_id))) {
            return $result_array;
        }
        $sql_result = Database::query("SELECT *
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                ON course.code = course_user.course_code
                WHERE course.target_course_code IS NULL
                    AND course_user.user_id = '$user_id'
                    AND course_user.status = '1'");
        if ($sql_result === false) { return $result_array; }
        while ($result = Database::fetch_array($sql_result)) {
            $result_array[] = $result;
        }
        return $result_array;
    }

    /**
     * Get course list as coach
     *
     * @param     int        user id
     * @return    array    course list
     *
     *  */
    public static function get_course_list_as_coach($user_id, $include_courses_in_sessions = false) {

        //1. Getting courses as teacher (No session)
        $courses_temp           = CourseManager::get_course_list_of_user_as_course_admin($user_id);
        $courses_as_admin         = array();

        if (!empty($courses_temp)) {
            foreach($courses_temp as $course_item) {
                $courses_as_admin[0][$course_item['course_code']] = $course_item['course_code'];
            }
        }

        //2. Include courses in sessions
        if ($include_courses_in_sessions) {
            $sessions      = SessionManager::get_sessions_coached_by_user($user_id);

            if (!empty($sessions)) {
                foreach($sessions as $session_item) {
                    $courses  = Tracking :: get_courses_followed_by_coach($user_id, $session_item['id']);
                    if (is_array($courses)) {
                        foreach($courses as $course_item) {
                            $courses_as_admin[$session_item['id']][$course_item] = $course_item;
                        }
                    }
                }
            }
        }
        return $courses_as_admin;
    }

    public static function get_user_list_from_courses_as_coach($user_id, $include_sessions = true) {
        $courses_as_admin = $students_in_courses = array();

        $sessions = CourseManager::get_course_list_as_coach($user_id, true);

        if (!empty($sessions)) {
            foreach($sessions as $session_id => $courses) {
                if (!$include_sessions) {
                    if (!empty($session_id)) {
                        continue;
                    }
                }
                if (empty($session_id)) {
                    foreach($courses as $course_code) {
                        $students_in_course = CourseManager::get_user_list_from_course_code($course_code);

                        foreach($students_in_course as $user_item) {
                            //Only students
                            if ($user_item['status_rel'] == STUDENT)
                                $students_in_courses[$user_item['user_id']] = $user_item['user_id'];
                        }
                    }
                } else {
                    $students_in_course = SessionManager::get_users_by_session($session_id, '0');
                    if (is_array($students_in_course)) {
                        foreach ($students_in_course as $user_item) {
                            if ($user_item['moved_to'] == 0) {
                                $students_in_courses[$user_item['user_id']] = $user_item['user_id'];
                            }
                        }
                    }
                }
            }
        }

        $students = Tracking :: get_student_followed_by_coach($user_id);
        if (!empty($students_in_courses)) {
            if (!empty($students)) {
                $students = array_merge($students, $students_in_courses);
            } else {
                $students = $students_in_courses;
            }
        }

        if (!empty($students)) {
            $students = array_unique($students);
        }
        return $students;
    }

    /**
     *    @return an array with the course info of all the courses (real and virtual) of which
     *    the current user is course admin
     */
    public static function get_course_list_of_user_as_course_admin($user_id) {
        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        // Definitions database tables and variables
        $tbl_course      = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $user_id = intval($user_id);
        $data = array();

        $sql_nb_cours = "SELECT course_rel_user.course_code, course.title, course.id, course.db_name, course.id as real_id
            FROM $tbl_course_user as course_rel_user
            INNER JOIN $tbl_course as course
                ON course.code = course_rel_user.course_code
            WHERE course_rel_user.user_id='$user_id' AND course_rel_user.status='1'
            ORDER BY course.title";

        if (api_get_multiple_access_url()) {
            $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql_nb_cours = "    SELECT course_rel_user.course_code, course.title, course.id, course.db_name, course.id as real_id
                    FROM $tbl_course_user as course_rel_user
                    INNER JOIN $tbl_course as course
                        ON course.code = course_rel_user.course_code
                      INNER JOIN $tbl_course_rel_access_url course_rel_url
                        ON (course_rel_url.course_code= course.code)
                      WHERE access_url_id =  $access_url_id  AND course_rel_user.user_id='$user_id' AND course_rel_user.status='1'
                      ORDER BY course.title";
            }
        }

        $result_nb_cours = Database::query($sql_nb_cours);
        if (Database::num_rows($result_nb_cours) > 0) {
            while ($row = Database::fetch_array($result_nb_cours,'ASSOC')) {
                $data[$row['course_code']] = $row;
            }
        }

        return $data;
    }

    /**
     * Find out for which courses the user is registered and determine a visual course code and course title from that.
     * Takes virtual courses into account
     *
     * Default case: the name and code stay what they are.
     *
     * Scenarios:
     * - User is registered in real course and virtual courses; name / code become a mix of all
     * - User is registered in real course only: name stays that of real course
     * - User is registered in virtual course only: name becomes that of virtual course
     * - user is not registered to any of the real/virtual courses: name stays that of real course
     * (I'm not sure about the last case, but this seems not too bad)
     *
     * @author Roan Embrechts
     * @param $user_id, the id of the user
     * @param $course_info, an array with course info that you get using Database::get_course_info($course_system_code);
     * @return an array with indices
     *    $return_result['title'] - the course title of the combined courses
     *    $return_result['code']  - the course code of the combined courses
     * @deprecated use api_get_course_info()
     */
    public static function determine_course_title_from_course_info($user_id, $course_info) {

        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        $real_course_id = $course_info['system_code'];
        $real_course_info = Database::get_course_info($real_course_id);
        $real_course_name = $real_course_info['title'];
        $real_course_visual_code = $real_course_info['visual_code'];
        $real_course_real_code = Database::escape_string($course_info['system_code']);

        //is the user registered in the real course?
        $result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE user_id = '$user_id' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_code = '$real_course_real_code'"));
        $user_is_registered_in_real_course = !empty($result);

        //get a list of virtual courses linked to the current real course and to which the current user is subscribed
        $user_subscribed_virtual_course_list = self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_id);
        $virtual_courses_exist = count($user_subscribed_virtual_course_list) > 0;

        //now determine course code and name
        if ($user_is_registered_in_real_course && $virtual_courses_exist) {
            $course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
            $course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
        }
        elseif ($user_is_registered_in_real_course) {
            //course name remains real course name
            $course_info['name'] = $real_course_name;
            $course_info['official_code'] = $real_course_visual_code;
        }
        elseif ($virtual_courses_exist) {
            $course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
            $course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
        } else {
            //course name remains real course name
            $course_info['name'] = $real_course_name;
            $course_info['official_code'] = $real_course_visual_code;
        }

        $return_result['title'] = $course_info['name'];
        $return_result['code'] = $course_info['official_code'];
        return $return_result;
    }

    /**
     * Create a course title based on all real and virtual courses the user is registered in.
     * @param boolean $user_is_registered_in_real_course
     * @param string $real_course_name, the title of the real course
     * @param array $virtual_course_list, the list of virtual courses
     */
    public static function create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list) {

        $complete_course_name = array();

        if ($user_is_registered_in_real_course) {
            // Add the real name to the result.
            $complete_course_name[] = $real_course_name;
        }

        // Add course titles of all virtual courses.
        foreach ($virtual_course_list as $current_course) {
            $complete_course_name[] = $current_course['title'];
        }

        // 'CombinedCourse' is from course_home language file.
        return (($user_is_registered_in_real_course || count($virtual_course_list) > 1) ? get_lang('CombinedCourse').' ' : '').implode(' &amp; ', $complete_course_name);
    }

    /**
     *    Create a course code based on all real and virtual courses the user is registered in.
     */
    public static function create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list) {

        $complete_course_code = array();

        if ($user_is_registered_in_real_course) {
            // Add the real code to the result
            $complete_course_code[] = $real_course_code;
        }

        // Add codes of all virtual courses.
        foreach ($virtual_course_list as $current_course) {
            $complete_course_code[] = $current_course['visual_code'];
        }

        return implode(' &amp; ', $complete_course_code);
    }

    /**
     *    Return course info array of virtual course
     *
     *    Note this is different from getting information about a real course!
     *
     *    @param $real_course_code, the id of the real course which the virtual course is linked to
     *  @deprecated virtual courses doesn't exist anymore
     */
    public static function get_virtual_course_info($real_course_code) {
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE target_course_code = '".Database::escape_string($real_course_code)."'");
        $result = array();
        while ($virtual_course = Database::fetch_array($sql_result)) {
            $result[] = $virtual_course;
        }
        return $result;
    }

    /**
     *    @param string $system_code, the system code of the course
     *    @return true if the course is a virtual course, false otherwise
     *  @deprecated virtual courses doesn't exist anymore
     */
    public static function is_virtual_course_from_system_code($system_code) {
        $result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($system_code)."'"));
        return !empty($result['target_course_code']);
    }

    /**
     *    Returns whether the course code given is a visual code
     *  @param  string  Visual course code
     *    @return true if the course is a virtual course, false otherwise
     *  @deprecated virtual courses doesn't exist anymore
     */
    public static function is_virtual_course_from_visual_code($visual_code) {
        $result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE visual_code = '".Database::escape_string($visual_code)."'"));
        return !empty($result['target_course_code']);
    }

    /**
     * @return true if the real course has virtual courses that the user is subscribed to, false otherwise
     *  @deprecated virtual courses doesn't exist anymore
     */
    public static function has_virtual_courses_from_code($real_course_code, $user_id) {
        return count(self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)) > 0;
    }

    /**
     * This function returns the course code of the real course
     * to which a virtual course is linked.
     *
     * @param the course code of the virtual course
     * @return the course code of the real course
     */
    public static function get_target_of_linked_course($virtual_course_code) {
        //get info about the virtual course
        $result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($virtual_course_code)."'"));
        return $result['target_course_code'];
    }

    /*
        USER FUNCTIONS
    */

    /**
     * Check if user is subscribed inside a course
     * @param     int        User id
     * @param    string    Course code, if this parameter is null, it'll check for all courses
     * @param    bool    True for checking inside sessions too, by default is not checked
     * @return     bool     true if the user is registered in the course, false otherwise
     */
    public static function is_user_subscribed_in_course($user_id, $course_code = null, $in_a_session = false, $session_id = null) {

        $user_id = intval($user_id);

        if (empty($session_id)) {
            $session_id = api_get_session_id();
        } else {
            $session_id = intval($session_id);
        }

        $condition_course = '';
        if (isset($course_code)) {
            $course_code = Database::escape_string($course_code);
            $condition_course = ' AND course_code = "'.$course_code.'" ';
        }

        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE user_id = $user_id AND relation_type<>".COURSE_RELATION_TYPE_RRHH." $condition_course ";

        $result = Database::fetch_array(Database::query($sql));

        if (!empty($result)) {
            return true; // The user has been registered in this course.
        }

        if (!$in_a_session) {
            return false; // The user has not been registered in this course.
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_user = '.$user_id.' '.$condition_course.' ')) > 0) {
            return true;
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_user = '.$user_id.' AND status=2 '.$condition_course.' ')) > 0) {
            return true;
        }

        if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION).
                ' WHERE id='.$session_id.' AND id_coach='.$user_id)) > 0) {
            return true;
        }

        return false;
    }

    /**
     *    Is the user a teacher in the given course?
     *
     *    @param $user_id, the id (int) of the user
     *    @param $course_code, the course code
     *
     *    @return true if the user is a teacher in the course, false otherwise
     */
    public static function is_course_teacher($user_id, $course_code) {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        $sql_result = Database::query('SELECT status FROM '.Database::get_main_table(TABLE_MAIN_COURSE_USER).
                ' WHERE course_code="'.Database::escape_string($course_code).'" and user_id="'.$user_id.'"');
        if (Database::num_rows($sql_result) > 0) {
            return Database::result($sql_result, 0, 'status') == 1;
        }
        return false;
    }

    /**
     *    Is the user subscribed in the real course or linked courses?
     *
     *    @param int the id of the user
     *    @param array info about the course (comes from course table, see database lib)
     *
     *    @return true if the user is registered in the real course or linked courses, false otherwise
     */
    public static function is_user_subscribed_in_real_or_linked_course ($user_id, $course_code, $session_id = '') {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);

        if ($session_id == '') {
            $result = Database::fetch_array(Database::query("SELECT *
                    FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                    LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                    ON course.code = course_user.course_code
                    WHERE course_user.user_id = '$user_id' AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND ( course.code = '$course_code' OR target_course_code = '$course_code')"));
            return !empty($result);
        }

        // From here we trust session id.

        // Is he/she subscribed to the session's course?

        // A user?
        if (Database::num_rows(Database::query("SELECT id_user
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session='".$_SESSION['id_session']."'
                    AND id_user='$user_id'"))) {
            return true;
        }

        // A course coach?
        if (Database::num_rows(Database::query("SELECT id_user
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                    WHERE id_session='".$_SESSION['id_session']."'
                    AND id_user = '$user_id' AND status = 2
                    AND course_code='$course_code'"))) {
            return true;
        }

        // A session coach?
        if (Database::num_rows(Database::query("SELECT id_coach
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION)." AS session
                    WHERE session.id='".$_SESSION['id_session']."'
                    AND id_coach='$user_id'"))) {
            return true;
        }

        return false;
    }

    /**
     *    Return user info array of all users registered in the specified real or virtual course
     *    This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param string    $course_code the code of the course
     * @param boolean   $with_session determines if the course is used in a session or not
     * @param integer   $session_id the id of the session
     * @param string    $limit the LIMIT statement of the sql statement
     * @param string    $order_by the field to order the users by. Valid values are 'lastname', 'firstname', 'username', 'email', 'official_code' OR a part of a SQL statement that starts with ORDER BY ...
     * @param int       if using the session_id: 0 or 2 (student, coach), if using session_id = 0 STUDENT or COURSEMANAGER
     * @return array
     */
    public static function get_user_list_from_course_code($course_code = null, $session_id = 0, $limit = null, $order_by = null, $filter_by_status = null, $return_count = null, $add_reports = false, $resumed_report = false, $extra_field = null) {
        // variable initialisation
        $session_id     = intval($session_id);
        $course_code    = Database::escape_string($course_code);
        $where          = array();

        // if the $order_by does not contain 'ORDER BY' we have to check if it is a valid field that can be sorted on
        if (!strstr($order_by,'ORDER BY')) {
            //if (!empty($order_by) AND in_array($order_by, array('lastname', 'firstname', 'username', 'email', 'official_code'))) {
            if (!empty($order_by)) {
                $order_by = 'ORDER BY '.$order_by;
            } else {
                $order_by = '';
            }
        }

        $filter_by_status_condition = null;

        if (!empty($session_id)) {
            $sql = 'SELECT DISTINCT user.user_id, session_course_user.status as status_session, user.*  ';
            $sql .= ' FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' as session_course_user
                      ON user.user_id = session_course_user.id_user
                      AND session_course_user.course_code="'.$course_code.'"
                      AND session_course_user.id_session = '.$session_id;
            $where[] = ' session_course_user.course_code IS NOT NULL ';

            // 2 = coach
            // 0 = student
            if (isset($filter_by_status)) {
                $filter_by_status = intval($filter_by_status);
                $filter_by_status_condition = " session_course_user.status = $filter_by_status AND ";
            }
        } else {
            if ($return_count) {
                $sql = " SELECT COUNT(*) as count";
                if ($resumed_report) {
                    //$sql = " SELECT count(field_id) ";
                }
            } else {
                if (empty($course_code)) {
                    $sql = 'SELECT DISTINCT course.title, course.code, course_rel_user.status as status_rel, user.user_id, course_rel_user.role, course_rel_user.tutor_id, user.*  ';
        } else {
            $sql = 'SELECT DISTINCT course_rel_user.status as status_rel, user.user_id, course_rel_user.role, course_rel_user.tutor_id, user.*  ';
                }
            }
            $sql .= ' FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' as course_rel_user
                        ON user.user_id = course_rel_user.user_id AND
                        course_rel_user.relation_type <> '.COURSE_RELATION_TYPE_RRHH.'  ';
            if (!empty($course_code)) {
                $sql .= ' AND course_rel_user.course_code="'.$course_code.'"';
            } else {
                $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
                $sql .= " INNER JOIN  $course_table course ON course_rel_user.course_code = course.code ";
            }
            $where[] = ' course_rel_user.course_code IS NOT NULL ';

            if (isset($filter_by_status) && $filter_by_status != '') {
                $filter_by_status = intval($filter_by_status);
                $filter_by_status_condition = " course_rel_user.status = $filter_by_status AND ";
            }
        }

        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql  .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).'  au ON (au.user_id = user.user_id) ';
        }

        if ($return_count && $resumed_report) {
            $extra_field_info = UserManager::get_extra_field_information_by_name($extra_field);
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES).' as ufv ON (user.user_id = ufv.user_id AND (field_id = '.$extra_field_info['id'].' OR field_id IS NULL ) )';
        }
        $sql .= ' WHERE '.$filter_by_status_condition.' '.implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }
        if ($return_count && $resumed_report) {
            $sql .= ' AND field_id IS NOT NULL  GROUP BY field_value ';
        }

        $sql .= ' '.$order_by.' '.$limit;

        $rs = Database::query($sql);
        $users = array();
        if ($add_reports) {
            $extra_fields = UserManager::get_extra_fields(0, 100, null, null, true, true);
        }
        $counter = 1;
        $count_rows = Database::num_rows($rs);
        if ($return_count && $resumed_report) {
            return $count_rows;
            }
        $table_user_field_value = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        if ($count_rows) {
            while ($user = Database::fetch_array($rs)) {
                $report_info = array();

                if ($return_count) {
                    return $user['count'];
                }
                $user_info = $user;
                $user_info['status'] = $user['status'];

                if (isset($user['role'])) {
                    $user_info['role'] = $user['role'];
                }
                if (isset($user['tutor_id'])) {
                    $user_info['tutor_id'] = $user['tutor_id'];
                }
                if (!empty($session_id)) {
                    $user_info['status_session'] = $user['status_session'];
                }
                if ($add_reports) {
                    $course_code = $user['code'];
                    if ($resumed_report) {

                        foreach ($extra_fields as $extra) {
                            if ($extra['1'] == $extra_field) {
                                $user_data = UserManager::get_extra_user_data_by_field($user['user_id'], $extra['1']);
                                break;
                            }
                        }

                        if (empty($user_data[$extra['1']])) {
                            $row_key = '-1';
                            $name = '-';
                        } else {
                            $row_key = $user_data[$extra['1']];
                            $name = $user_data[$extra['1']];
                        }

                        $users[$row_key]['extra_'.$extra['1']] = $name;
                        $users[$row_key]['training_hours'] += Tracking::get_time_spent_on_the_course($user['user_id'], $course_code, 0);
                        $users[$row_key]['count_users'] += $counter;
                        $registered_users_with_extra_field = 0;

                        if (!empty($name) && $name != '-') {
                            $name = Database::escape_string($name);
                            $sql = "SELECT count(user_id) as count FROM $table_user_field_value WHERE field_value = '$name'";
                            $result_count = Database::query($sql);
                            if (Database::num_rows($result_count)) {
                                $row_count = Database::fetch_array($result_count);
                                $registered_users_with_extra_field = $row_count['count'];
                            }
                        }

                        $users[$row_key]['count_users_registered'] = $registered_users_with_extra_field;
                        $users[$row_key]['average_hours_per_user'] = $users[$row_key]['training_hours'] / $users[$row_key]['count_users'];

                        $category = Category :: load (null, null, $course_code);
                        if (!isset($users[$row_key]['count_certificates'])) {
                            $users[$row_key]['count_certificates'] = 0;
                        }
                        if (isset($category[0]) && $category[0]->is_certificate_available($user['user_id'])) {
                            $users[$row_key]['count_certificates']++;
                        }
                    } else {
                    $report_info['course'] = $user['title'];
                    $report_info['user'] = api_get_person_name($user['firstname'], $user['lastname']);
                    $report_info['time'] = api_time_to_hms(Tracking::get_time_spent_on_the_course($user['user_id'], $course_code, 0));


                    $category = Category :: load (null, null, $course_code);
                    $report_info['certificate'] = Display::label(get_lang('No'));
                    if (isset($category[0]) && $category[0]->is_certificate_available($user['user_id'])) {
                        $report_info['certificate'] = Display::label(get_lang('Yes'), 'success');
                    }
                    //$report_info['score'] = Tracking::get_avg_student_score($user['user_id'], $course_code, array(), 0);

                    $progress = intval(Tracking::get_avg_student_progress($user['user_id'], $course_code, array(), 0));
                    $report_info['progress_100'] =  $progress == 100 ? Display::label(get_lang('Yes'), 'success') : Display::label(get_lang('No'));
                    $report_info['progress'] = $progress."%";

                    foreach ($extra_fields as $extra) {
                        $user_data = UserManager::get_extra_user_data_by_field($user['user_id'], $extra['1']);
                        $report_info[$extra['1']] = $user_data[$extra['1']];
                    }
                    $users[] = $report_info;
                    }
                } else {
                $users[$user['user_id']] = $user_info;
            }
        }
            $counter++;
        }
        if ($add_reports) {
            if ($resumed_report) {
                //var_dump($counter);
            }
        }
        //var_dump($users);
        return $users;
    }

    static function get_count_user_list_from_course_code($resumed_report = false, $extra_field = null) {
        return self::get_user_list_from_course_code(null, 0, null, null, null, true, false, $resumed_report, $extra_field);
    }
    /**
     * Gets subscribed users in a course or in a course/session
     *
     * @param   string    $course_code
     * @param   int       $session_id
     * @return  int
     */
    public static function get_users_count_in_course($course_code, $session_id = 0) {
        // variable initialisation
        $session_id     = intval($session_id);
        $course_code    = Database::escape_string($course_code);

        $sql .= 'SELECT DISTINCT count(*) as count  FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';
        $where = array();
        if (!empty($session_id)) {
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' as session_course_user
                      ON user.user_id = session_course_user.id_user
                      AND session_course_user.course_code = "'.$course_code.'"
                      AND session_course_user.id_session  = '.$session_id.'
                      INNER JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_USER).' as su
                      ON ( su.id_session = session_course_user.id_session AND
                           user.user_id = su.id_user AND
                           su.moved_to = 0 AND
                           su.moved_status <> '.SessionManager::SESSION_CHANGE_USER_REASON_ENROLLMENT_ANNULATION.'
                         )';

            $where[] = ' session_course_user.course_code IS NOT NULL ';
        } else {
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' as course_rel_user
                        ON user.user_id = course_rel_user.user_id AND course_rel_user.relation_type<>'.COURSE_RELATION_TYPE_RRHH.'
                        AND course_rel_user.course_code="'.$course_code.'"';
            $where[] = ' course_rel_user.course_code IS NOT NULL ';
        }

        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql  .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).'  au ON (au.user_id = user.user_id) ';
        }

        $sql .= ' WHERE '.implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }
        $rs = Database::query($sql);
        $count = 0;
        if (Database::num_rows($rs)) {
            $user = Database::fetch_array($rs);
            $count = $user['count'];
        }
        return $count;
    }

    /**
     * Get a list of coaches of a course and a session
     * @param   string  Course code
     * @param   int     Session ID
     * @return  array   List of users
     */
    public static function get_coach_list_from_course_code($course_code, $session_id) {

        if ($session_id != strval(intval($session_id))) {
            return array();
        }

        $course_code = Database::escape_string($course_code);

        $users = array();

        // We get the coach for the given course in a given session.
        $rs = Database::query('SELECT id_user FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
                ' WHERE id_session="'.$session_id.'" AND course_code="'.$course_code.'" AND status = 2');
        while ($user = Database::fetch_array($rs)) {
            $user_info = Database::get_user_info_from_id($user['id_user']);
            $user_info['status'] = $user['status'];
            $user_info['role'] = $user['role'];
            $user_info['tutor_id'] = $user['tutor_id'];
            $user_info['email'] = $user['email'];
            $users[$user['id_user']] = $user_info;
        }

        // We get the session coach.
        $rs = Database::query('SELECT id_coach FROM '.Database::get_main_table(TABLE_MAIN_SESSION).' WHERE id="'.$session_id.'"');
        $session_id_coach = Database::result($rs, 0, 'id_coach');
        $user_info = Database::get_user_info_from_id($session_id_coach);
        $user_info['status'] = $user['status'];
        $user_info['role'] = $user['role'];
        $user_info['tutor_id'] = $user['tutor_id'];
        $user_info['email'] = $user['email'];
        $users[$session_id_coach] = $user_info;
        return $users;
    }


    /**
     *    Return user info array of all users registered in the specified real or virtual course
     *    This only returns the users that are registered in this actual course, not linked courses.
     *
     *    @param string $course_code
     *    @param boolean $full list to true if we want sessions students too
     *    @return array with user id
     */
    public static function get_student_list_from_course_code($course_code, $with_session = false, $session_id = 0, $group_id = 0) {
        $session_id = intval($session_id);
        $course_code = Database::escape_string($course_code);

        $students = array();

        if ($session_id == 0) {
            if (empty($group_id)) {
                // students directly subscribed to the course
                $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                       WHERE course_code = '$course_code' AND status = ".STUDENT;
                $rs = Database::query($sql);
                while ($student = Database::fetch_array($rs)) {
                    $students[$student['user_id']] = $student;
                }
            } else {
                $course_info = api_get_course_info($course_code);
                $students = GroupManager::get_users($group_id, false, $course_info['real_id']);
                $students = array_flip($students);
            }
        }

        // students subscribed to the course through a session

        if ($with_session) {
            $sql_query = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                          WHERE course_code = '$course_code' AND status <> 2";
            if ($session_id != 0) {
                $sql_query .= ' AND id_session = '.$session_id;
            }
            $rs = Database::query($sql_query);
            while($student = Database::fetch_array($rs)) {
                $students[$student['id_user']] = $student;
            }
        }
        return $students;
    }

    /**
     *    Return user info array of all teacher-users registered in the specified real or virtual course
     *    This only returns the users that are registered in this actual course, not linked courses.
     *
     *    @param string $course_code
     *    @return array with user id
     */
    public static function get_teacher_list_from_course_code($course_code) {
        $course_code = Database::escape_string($course_code);
        $teachers = array();
        $sql = "SELECT DISTINCT u.user_id, u.lastname, u.firstname, u.email, u.username, u.status
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." u
                ON (cu.user_id = u.user_id)
                WHERE   cu.course_code = '$course_code' AND
                        cu.status = 1 ";
        $rs = Database::query($sql);
        while ($teacher = Database::fetch_array($rs)) {
            $teachers[$teacher['user_id']] = $teacher;
        }
        return $teachers;
    }

    public static function get_teacher_list_from_course_code_to_string($course_code, $separator = self::USER_SEPARATOR, $add_link_to_profile = false) {
        $teacher_list = self::get_teacher_list_from_course_code($course_code);
        $teacher_string = '';
        $list = array();
        if (!empty($teacher_list)) {
            foreach($teacher_list as $teacher) {
                $teacher_name = api_get_person_name($teacher['firstname'], $teacher['lastname']);
                if ($add_link_to_profile) {
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&resizable=0&height=300&user_id='.$teacher['user_id'];
                    $teacher_name = Display::url($teacher_name, $url, array('class' => 'ajax'));
                } else {
                    $teacher_name = '&nbsp;'.$teacher_name;
                }
                $list[]= $teacher_name;
            }
            if (!empty($list)) {
                $teacher_string = array_to_string($list, $separator);
            }
        }
        return $teacher_string;
    }

     /**
     * This function returns information about coachs from a course in session
     * @param int       - optional, session id
     * @param string    - optional, course code
     * @return array    - array containing user_id, lastname, firstname, username
     *
     */
    public static function get_coachs_from_course($session_id=0, $course_code='') {

        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        if (!empty($course_code)) {
            $course_code = Database::escape_string($course_code);
        } else {
            $course_code = api_get_course_id();
        }

        $tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $coaches = array();

        $sql = "SELECT DISTINCT u.user_id, u.lastname, u.firstname, u.username
                FROM $tbl_user u, $tbl_session_course_user scu
                WHERE u.user_id = scu.id_user AND scu.id_session = '$session_id' AND scu.course_code = '$course_code' AND scu.status = 2";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $coaches[] = $row;
            }
            return $coaches;
        } else {
            return false;
        }
    }

    public static function get_coachs_from_course_to_string($session_id = 0, $course_code = null, $separator = self::USER_SEPARATOR, $add_link_to_profile = false) {
        $coachs_course = self::get_coachs_from_course($session_id, $course_code);
        $course_coachs = array();

        if (is_array($coachs_course)) {
            foreach ($coachs_course as $coach_course) {
                $coach_name = api_get_person_name($coach_course['firstname'], $coach_course['lastname']);
                if ($add_link_to_profile) {
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&resizable=0&height=300&user_id='.$coach_course['user_id'];
                    $coach_name = Display::url($coach_name, $url, array('class' => 'ajax'));
                } else {
                    $coach_name = '&nbsp;'.$coach_name;
                }
                $course_coachs[] = $coach_name;
            }
        }
        $coaches_to_string = null;
        if (is_array($course_coachs) && count($course_coachs)> 0 ) {
            $coaches_to_string = array_to_string($course_coachs, $separator);
        }
        return $coaches_to_string;
    }

    public static function get_coach_list_from_course_code_to_string($course_code, $session_id) {
        $tutor_data = '';
        if ($session_id != 0) {
            $coaches = self::get_email_of_tutor_to_session($session_id, $course_code);
            $coach_list = array();
            foreach ($coaches as $coach) {
                $coach_list[] = $coach['complete_name'];
            }
            if (!empty($coach_list)) {
                $tutor_data = implode(self::USER_SEPARATOR, $coach_list);
            }
        }
        return $tutor_data;
    }


    /**
     *    Return user info array of all users registered in the specified course
     *    this includes the users of the course itsel and the users of all linked courses.
     *
     *    @param array $course_info
     *    @return array with user info
     */
    public static function get_real_and_linked_user_list($course_code, $with_sessions = true, $session_id = 0, $order_by = null) {
        $complete_user_list = array();

        //get users from real course
        $user_list = self::get_user_list_from_course_code($course_code, $session_id, null, $order_by);
        foreach ($user_list as $this_user) {
            $complete_user_list[] = $this_user;
        }
        return $complete_user_list;
    }

    /**
     *    Return an array of arrays, listing course info of all courses in the list
     *    linked to the real course $real_course_code, to which the user $user_id is subscribed.
     *
     *    @param $user_id, the id (int) of the user
     *    @param $real_course_code, the id (char) of the real course
     *
     *    @return array of course info arrays
     */
    public static function get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $course_code) {
        $result_array = array();

        if ($user_id != strval(intval($user_id))) {
            return $result_array;
        }

        $course_code = Database::escape_string($course_code);
        $sql = "SELECT *
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
                LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
                ON course.code = course_user.course_code
                WHERE course.target_course_code = '$course_code' AND course_user.user_id = '$user_id' AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
        $sql_result = Database::query($sql);

        while ($result = Database::fetch_array($sql_result)) {
            $result_array[] = $result;
        }
        return $result_array;
    }

    /*
        GROUP FUNCTIONS
    */

    /**
     * Get the list of groups from the course
     * @param   string  Course code
     * @param   int     Session ID (optional)
     * @param   boolean get empty groups (optional)
     * @return  array   List of groups info
     */
    public static function get_group_list_of_course($course_code, $session_id = 0, $in_get_empty_group = 0) {
        $course_info = Database::get_course_info($course_code);
        $course_id = $course_info['real_id'];
        $group_list = array();
        $session_id != 0 ? $session_condition = ' WHERE g.session_id IN(1,'.intval($session_id).')' : $session_condition = ' WHERE g.session_id = 0';

        if ($in_get_empty_group == 0) {
            // get only groups that are not empty
            $sql = "SELECT DISTINCT g.id, g.name
                FROM ".Database::get_course_table(TABLE_GROUP)." AS g
                INNER JOIN ".Database::get_course_table(TABLE_GROUP_USER)." gu
                ON (g.id = gu.group_id AND g.c_id = $course_id AND gu.c_id = $course_id)
                $session_condition
                ORDER BY g.name";
                }
        else {
            // get all groups even if they are empty
            $sql = "SELECT g.id, g.name
                    FROM ".Database::get_course_table(TABLE_GROUP)." AS g
                    $session_condition
                    AND c_id = $course_id";
        }
        $result = Database::query($sql);

        while ($group_data = Database::fetch_array($result)) {
            $group_data['userNb'] = GroupManager::number_of_students($group_data['id'], $course_id);
            $group_list[$group_data['id']] = $group_data;
        }
        return $group_list;
    }

    /**
     * Checks all parameters needed to create a virtual course.
     * If they are all set, the virtual course creation procedure is called.
     *
     * Call this function instead of create_virtual_course
     * @param  string  Course code
     * @param  string  Course title
     * @param  string  Wanted course code
     * @param  string  Course language
     * @param  string  Course category
     * @return bool    True on success, false on error
     */
    public static function attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
        //better: create parameter list, check the entire list, when false display errormessage
        self::check_parameter_or_fail($real_course_code, 'Unspecified parameter: real course id.');
        self::check_parameter_or_fail($course_title, 'Unspecified parameter: course title.');
        self::check_parameter_or_fail($wanted_course_code, 'Unspecified parameter: wanted course code.');
        self::check_parameter_or_fail($course_language, 'Unspecified parameter: course language.');
        self::check_parameter_or_fail($course_category, 'Unspecified parameter: course category.');

        return self::create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
    }

    /**
     * This function creates a virtual course.
     * It assumes all parameters have been checked and are not empty.
     * It checks wether a course with the $wanted_course_code already exists.
     *
     * Users of this library should consider this function private,
     * please call attempt_create_virtual_course instead of this one.
     *
     * note: The virtual course 'owner' id (the first course admin) is set to the CURRENT user id.
     * @param  string  Course code
     * @param  string  Course title
     * @param  string  Wanted course code
     * @param  string  Course language
     * @param  string  Course category
     * @return true if the course creation succeeded, false otherwise
     * @deprecated this is deprecated?
     * @todo research: expiration date of a course
     */
    public static function create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
        global $firstExpirationDelay;

        $user_id = api_get_user_id();
        $real_course_info = Database::get_course_info($real_course_code);
        $real_course_code = $real_course_info['system_code'];

        //check: virtual course creation fails if another course has the same
        //code, real or fake.
        if (self::course_code_exists($wanted_course_code)) {
            Display::display_error_message($wanted_course_code.' - '.get_lang('CourseCodeAlreadyExists'));
            return false;
        }

        //add data to course table, course_rel_user
        $course_sys_code = $wanted_course_code;
        $course_screen_code = $wanted_course_code;
        $course_repository = $real_course_info['directory'];
        $course_db_name = $real_course_info['db_name'];
        $responsible_teacher = $real_course_info['tutor_name'];
        $faculty_shortname = $course_category;
        // $course_title = $course_title;
        // $course_language = $course_language;
        $teacher_id = $user_id;

        //HACK ----------------------------------------------------------------
        $expiration_date = time() + $firstExpirationDelay;
        //END HACK ------------------------------------------------------------

        self::register_course($course_sys_code, $course_screen_code, $course_repository, $course_db_name, $responsible_teacher, $faculty_shortname, $course_title, $course_language, $teacher_id, $expiration_date);

        //above was the normal course creation table update call,
        //now one more thing: fill in the target_course_code field
        Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE)." SET target_course_code = '$real_course_code'
                WHERE code = '".Database::escape_string($course_sys_code)."' LIMIT 1 ");

        return true;
    }

    /**
     * Delete a course
     * This function deletes a whole course-area from the platform. When the
     * given course is a virtual course, the database and directory will not be
     * deleted.
     * When the given course is a real course, also all virtual courses refering
     * to the given course will be deleted.
     * Considering the fact that we remove all traces of the course in the main
     * database, it makes sense to remove all tracking as well (if stats databases exist)
     * so that a new course created with this code would not use the remains of an older
     * course.
     *
     * @param string The code of the course to delete
     * @todo When deleting a virtual course: unsubscribe users from that virtual
     * course from the groups in the real course if they are not subscribed in
     * that real course.
     * @todo Remove globals
     */
    public static function delete_course($code) {

        $table_course                       = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_user                  = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_course_class                 = Database::get_main_table(TABLE_MAIN_COURSE_CLASS);

        $table_session_course               = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_session_course_user          = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_course_survey                = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);
        $table_course_survey_question       = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
        $table_course_survey_question_option= Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);

        $table_stats_hotpots        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $table_stats_attempt        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_stats_exercises      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $table_stats_access         = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $table_stats_lastaccess     = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $table_stats_course_access  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $table_stats_online         = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
        $table_stats_default        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $table_stats_downloads      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
        $table_stats_links          = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
        $table_stats_uploads        = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);

        $code = Database::escape_string($code);

        // Unsubscribe all classes from the course
        $sql = "DELETE FROM $table_course_class WHERE course_code='".$code."'";
        Database::query($sql);
        // Unsubscribe all users from the course
        $sql = "DELETE FROM $table_course_user WHERE course_code='".$code."'";
        Database::query($sql);
        // Delete the course from the sessions tables
        $sql = "DELETE FROM $table_session_course WHERE course_code='".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_session_course_user WHERE course_code='".$code."'";
        Database::query($sql);

        $sql = 'SELECT survey_id FROM '.$table_course_survey.' WHERE course_code="'.$code.'"';
        $result_surveys = Database::query($sql);
        while($surveys = Database::fetch_array($result_surveys)) {
            $survey_id = $surveys[0];
            $sql = 'DELETE FROM '.$table_course_survey_question.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
            $sql = 'DELETE FROM '.$table_course_survey_question_option.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
            $sql = 'DELETE FROM '.$table_course_survey.' WHERE survey_id="'.$survey_id.'"';
            Database::query($sql);
        }

        // Delete the course from the stats tables

        $sql = "DELETE FROM $table_stats_hotpots WHERE exe_cours_id = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_attempt WHERE course_code = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_exercises WHERE exe_cours_id = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_access WHERE access_cours_code = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_lastaccess WHERE access_cours_code = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_course_access WHERE course_code = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_online WHERE course = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_default WHERE default_cours_code = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_downloads WHERE down_cours_id = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_links WHERE links_cours_id = '".$code."'";
        Database::query($sql);
        $sql = "DELETE FROM $table_stats_uploads WHERE upload_cours_id = '".$code."'";
        Database::query($sql);

        if (api_is_multiple_url_enabled()) {
            $url_id = 1;
            if (api_get_current_access_url_id() != -1) {
                $url_id = api_get_current_access_url_id();
            }
            UrlManager::delete_url_rel_course($code, $url_id);
        }

        // Delete the course from the database
        $sql = "DELETE FROM $table_course WHERE code='".$code."'";
        Database::query($sql);

        // delete extra course fields
        $t_cf         = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $t_cfv         = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

        $sql = "SELECT distinct field_id FROM $t_cfv WHERE course_code = '$code'";
        $res_field_ids = @Database::query($sql);

        while($row_field_id = Database::fetch_row($res_field_ids)){
            $field_ids[] = $row_field_id[0];
        }

        //delete from table_course_field_value from a given course_code

        $sql_course_field_value = "DELETE FROM $t_cfv WHERE course_code = '$code'";
        @Database::query($sql_course_field_value);

        $sql = "SELECT distinct field_id FROM $t_cfv";
        $res_field_all_ids = @Database::query($sql);

        while($row_field_all_id = Database::fetch_row($res_field_all_ids)){
            $field_all_ids[] = $row_field_all_id[0];
        }

        if (is_array($field_ids) && count($field_ids) > 0) {
            foreach ($field_ids as $field_id) {
                // check if field id is used into table field value
                if (is_array($field_all_ids)) {
                    if (in_array($field_id, $field_all_ids)) {
                        continue;
                    } else {
                        $sql_course_field = "DELETE FROM $t_cf WHERE id = '$field_id'";
                        Database::query($sql_course_field);
                    }
                }
            }
        }

        // Add event to system log
        $user_id = api_get_user_id();
        event_system(LOG_COURSE_DELETE, LOG_COURSE_CODE, $code, api_get_utc_datetime(), $user_id, $code);

    }

    /**
     * Creates a file called mysql_dump.sql in the course folder
     * @param $course_code The code of the course
     * @todo Implementation for single database
     */
    public static function create_database_dump($course_code) {
        global $_configuration;

        if ($_configuration['single_database']) {
            return;
        }
        $sql_dump = '';
        $course_code    = Database::escape_string($course_code);
        $table_course   = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT * FROM $table_course WHERE code = '$course_code'";
        $res = Database::query($sql);
        $course = Database::fetch_array($res);

        $course_tables = self::get_course_tables();

        if (!empty($course['id'])) {
            //Cleaning c_x tables
            foreach($course_tables as $table) {
                $table = Database::get_course_table($table);
                $sql = "SELECT * FROM $table WHERE c_id = {$course['id']} ";
                $res_table = Database::query($sql);

                while ($row = Database::fetch_array($res_table, 'ASSOC')) {
                    $row_to_save = array();
                    foreach ($row as $key => $value) {
                        $row_to_save[$key] = $key."='".Database::escape_string($row[$key])."'";
                    }
                    $sql_dump .= "\nINSERT INTO $table SET ".implode(', ', $row_to_save).';';
                }
            }
        }

        if (is_dir(api_get_path(SYS_COURSE_PATH).$course['directory'])) {
            $file_name = api_get_path(SYS_COURSE_PATH).$course['directory'].'/mysql_dump.sql';
            $handle = fopen($file_name, 'a+');
            if ($handle !== false) {
                fwrite($handle, $sql_dump);
                fclose($handle);
            } else {
                //TODO trigger exception in a try-catch
            }
        }
    }

    /**
     * Sort courses for a specific user ??
     * @param   int     User ID
     * @param   string  Course code
     * @return  int     Minimum course order
     * @todo Review documentation
     */
    public static function userCourseSort($user_id, $course_code) {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);
        $TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $course_title = Database::result(Database::query('SELECT title FROM '.$TABLECOURSE.' WHERE code="'.$course_code.'"'), 0, 0);

        $sql = 'SELECT course.code as code, course.title as title, cu.sort as sort FROM '.$TABLECOURSUSER.' as cu, '.$TABLECOURSE.' as course
                WHERE   course.code = cu.course_code AND user_id = "'.$user_id.'" AND
                        cu.relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND
                        user_course_cat = 0
                ORDER BY cu.sort';
        $result = Database::query($sql);

        $course_title_precedent = '';
        $counter = 0;
        $course_found = false;
        $course_sort = 1;

        if (Database::num_rows($result) > 0) {
        while ($courses = Database::fetch_array($result)){

            if ($course_title_precedent == '') {
                $course_title_precedent = $courses['title'];
            }

            if (api_strcasecmp($course_title_precedent, $course_title) < 0) {

                $course_found = true;
                $course_sort = $courses['sort'];

                if ($counter == 0) {
                    $sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND user_course_cat="0" AND sort > "'.$course_sort.'"';
                    $course_sort++;
                } else {
                    $sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND user_course_cat="0" AND sort >= "'.$course_sort.'"';
                }

                Database::query($sql);
                break;

            } else {
                $course_title_precedent = $courses['title'];
            }

            $counter++;
        }

        // We must register the course in the beginning of the list
            if (!$course_found) {
                $course_sort = Database::result(Database::query('SELECT min(sort) as min_sort FROM '.$TABLECOURSUSER.' WHERE user_id="'.$user_id.'" AND user_course_cat="0"'), 0, 0);
                Database::query('UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND user_course_cat="0"');
        }

        }
        return $course_sort;
    }

    /**
     * create recursively all categories as option of the select passed in paramater.
     *
     * @param object $select_element the quickform select where the options will be added
     * @param string $category_selected_code the option value to select by default (used mainly for edition of courses)
     * @param string $parent_code the parent category of the categories added (default=null for root category)
     * @param string $padding the indent param (you shouldn't indicate something here)
     */
    public static function select_and_sort_categories($select_element, $category_selected_code = '', $parent_code = null , $padding = '') {

        $sql = "SELECT code, name, auth_course_child, auth_cat_child
                FROM ".Database::get_main_table(TABLE_MAIN_CATEGORY)."
                WHERE parent_id ".(is_null($parent_code) ? "IS NULL" : "='".Database::escape_string($parent_code)."'")."
                ORDER BY code";
        $res = Database::query($sql);

        while ($cat = Database::fetch_array($res)) {
            $params = $cat['auth_course_child'] == 'TRUE' ? '' : 'disabled';
            $params .= ($cat['code'] == $category_selected_code) ? ' selected' : '';
            $select_element->addOption($padding.'('.$cat['code'].') '.$cat['name'], $cat['code'], $params);
            if ($cat['auth_cat_child']) {
                self::select_and_sort_categories($select_element, $category_selected_code, $cat['code'], $padding.' - ');
            }
        }
    }

    /**
     * check if course exists
     * @param string course_code
     * @param string whether to accept virtual course codes or not
     * @return true if exists, false else
     */
    public static function course_exists($course_code, $accept_virtual = false) {
        if ($accept_virtual === true) {
            $sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'" OR visual_code="'.Database::escape_string($course_code).'"';
        } else {
            $sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'"';
        }
        return Database::num_rows(Database::query($sql));
    }

    /**
     * Send an email to tutor after the auth-suscription of a student in your course
     * @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
     * @param  int $user_id the id of the user
     * @param  string $course_code the course code
     * @param  string $send_to_tutor_also
     * @return string we return the message that is displayed when the action is succesfull
     */
    public static function email_to_tutor($user_id, $course_code, $send_to_tutor_also = false) {

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        $course_code = Database::escape_string($course_code);

        $student = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                WHERE user_id='".$user_id."'"));
        $information = self::get_course_information($course_code);
        $name_course = $information['title'];
        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." WHERE course_code='".$course_code."'";

        // TODO: Ivan: This is a mistake, please, have a look at it. Intention here is diffcult to be guessed.
        //if ($send_to_tutor_also = true)
        // Proposed change:
        if ($send_to_tutor_also) {
        //
            $sql .= " AND tutor_id=1";
        } else {
            $sql .= " AND status=1";
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $tutor = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                    WHERE user_id='".$row['user_id']."'"));
            $emailto         = $tutor['email'];
            $emailsubject     = get_lang('NewUserInTheCourse').': '.$name_course;
            $emailbody         = get_lang('Dear').': '. api_get_person_name($tutor['firstname'], $tutor['lastname'])."\n";
            $emailbody        .= get_lang('MessageNewUserInTheCourse').': '.$name_course."\n";
            $emailbody        .= get_lang('UserName').': '.$student['username']."\n";
            if (api_is_western_name_order()) {
                $emailbody    .= get_lang('FirstName').': '.$student['firstname']."\n";
                $emailbody    .= get_lang('LastName').': '.$student['lastname']."\n";
            } else {
                $emailbody    .= get_lang('LastName').': '.$student['lastname']."\n";
                $emailbody    .= get_lang('FirstName').': '.$student['firstname']."\n";
            }
            $emailbody        .= get_lang('Email').': '.$student['email']."\n\n";
            $recipient_name = api_get_person_name($tutor['firstname'], $tutor['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
            $email_admin = api_get_setting('emailAdministrator');
            @api_mail($recipient_name, $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
        }
    }

    public static function get_special_course_list() {
        $tbl_course_field           = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value     = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

          //we filter the courses from the URL
        $join_access_url = $where_access_url='';
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url= "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= tcfv.course_code ";
                $where_access_url =" AND access_url_id = $access_url_id ";
            }
        }

        // get course list auto-register
        $sql = "SELECT DISTINCT(tcfv.course_code) FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf
                ON tcfv.field_id =  tcf.id $join_access_url
                WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1  $where_access_url";
        $special_course_result = Database::query($sql);
        $special_course_list = array();

        if (Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                $special_course_list[] = $result_row['course_code'];
            }
        }
        return $special_course_list;
    }

    /**
     * Get list of courses for a given user
     * @param int       user ID
     * @param boolean   Whether to include courses from session or not
     * @return array    List of codes and db names
     * @author isaac flores paz
     */
    public static function get_courses_list_by_user_id($user_id, $include_sessions = false) {
        $user_id = intval($user_id);
        $course_list = array();
        $codes = array();
        $tbl_course                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user            = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_user_course_category   = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        $special_course_list         = self::get_special_course_list();

        $with_special_courses = $without_special_courses = '';
        if (!empty($special_course_list)) {
            $sc_string = '"'.implode('","',$special_course_list).'"';
            $with_special_courses = ' course.code IN ('.$sc_string.')';
            $without_special_courses = ' AND course.code NOT IN ('.$sc_string.')';
        }

        if (!empty($with_special_courses)) {
            $sql = "SELECT DISTINCT(course.code), course.id as real_id
                        FROM    ".$tbl_course_user." course_rel_user
                        LEFT JOIN ".$tbl_course." course
                        ON course.code = course_rel_user.course_code
                        LEFT JOIN ".$tbl_user_course_category." user_course_category
                        ON course_rel_user.user_course_cat = user_course_category.id
                        WHERE  $with_special_courses
                        GROUP BY course.code
                        ORDER BY user_course_category.sort,course.title,course_rel_user.sort ASC";
            $rs_special_course = Database::query($sql);
            if (Database::num_rows($rs_special_course) > 0) {
                while ($result_row = Database::fetch_array($rs_special_course)) {
                    $result_row['special_course'] = 1;
                    $course_list[] = $result_row;
                    $codes[] = $result_row['real_id'];
                }
            }
        }

        // get course list not auto-register. Use Distinct to avoid multiple
        // entries when a course is assigned to a HRD (DRH) as watcher
        $sql = "SELECT DISTINCT(course.code), course.id as real_id
                FROM $tbl_course course
                INNER JOIN $tbl_course_user cru ON course.code=cru.course_code
                WHERE cru.user_id='$user_id' $without_special_courses";

        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $course_list[] = $row;
                $codes[] = $row['real_id'];
            }
        }

        if ($include_sessions === true) {
            $sql = "SELECT DISTINCT(c.code), c.id as real_id
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." s, ".Database::get_main_table(TABLE_MAIN_COURSE)." c
                    WHERE id_user = $user_id AND s.course_code=c.code";
            $r = Database::query($sql);
            while ($row = Database::fetch_array($r, 'ASSOC')) {
                if (!in_array($row['real_id'], $codes)) {
                    $course_list[] = $row;
                }
            }
        }
        return $course_list;
    }

    /**
     * Get course ID from a given course directory name
     * @param   string  Course directory (without any slash)
     * @return  string  Course code, or false if not found
     */
    public static function get_course_id_from_path($path) {
        $path = Database::escape_string(str_replace('.', '', str_replace('/', '', $path)));
        $res = Database::query("SELECT code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE directory LIKE BINARY '$path'");
        if ($res === false) {
            return false;
        }
        if (Database::num_rows($res) != 1) {
            return false;
        }
        $row = Database::fetch_array($res);
        return $row['code'];
    }

    /**
     * Get course code(s) from visual code
     * @param   string  Visual code
     * @return  array   List of codes for the given visual code
     */
    public static function get_courses_info_from_visual_code($code) {
        $result = array();
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE visual_code = '".Database::escape_string($code)."'");
        while ($virtual_course = Database::fetch_array($sql_result)) {
            $result[] = $virtual_course;
        }
        return $result;
    }

    /**
     * Get emails of tutors to course
     * @param string Visual code
     * @return array List of emails of tutors to course
     * @author @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
     * */
    public static function get_emails_of_tutors_to_course($code) {
        $list = array();
        $res = Database::query("SELECT user_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE course_code='".Database::escape_string($code)."' AND status=1");
        while ($list_users = Database::fetch_array($res)) {
            $result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
                    WHERE user_id=".$list_users['user_id']);
            while ($row_user = Database::fetch_array($result)){
                $name_teacher = api_get_person_name($row_user['firstname'], $row_user['lastname']);
                $list[] = array($row_user['email'] => $name_teacher);
            }
        }
        return $list;
    }

    /**
     * Get coachs' emails by session
     * @param int session id
     * @param string course code
     * @return array  array(email => name_tutor)  by coach
     * @author Carlos Vargas <carlos.vargas@dokeos.com>
     */
    public static function get_email_of_tutor_to_session($session_id, $course_code) {

        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $coachs_emails = array();

        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);

        $sql = "SELECT id_user FROM $tbl_session_course_user WHERE id_session='$session_id' AND course_code='$course_code' AND status =2";
        $rs  = Database::query($sql);

        if (Database::num_rows($rs) > 0) {

            $user_ids = array();
            while ($row = Database::fetch_array($rs)) {
                $user_ids[] = $row['id_user'];
            }

            $sql = "SELECT firstname, lastname, email FROM $tbl_user WHERE user_id IN (".implode(",",$user_ids).")";
            $rs_user = Database::query($sql);

            while ($row_emails = Database::fetch_array($rs_user)) {
                //$name_tutor = api_get_person_name($row_emails['firstname'], $row_emails['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                $mail_tutor = array('email' => $row_emails['email'], 'complete_name' => api_get_person_name($row_emails['firstname'], $row_emails['lastname']));
                $coachs_emails[] = $mail_tutor;
            }
        }
        return $coachs_emails;
    }

    /**
     * Creates a new extra field for a given course
      * @param    string    Field's internal variable name
      * @param    int        Field's type
      * @param    string    Field's language var name
      * @return int     new extra field id
      */
    public static function create_course_extra_field($fieldvarname, $fieldtype, $fieldtitle) {
        // database table definition
        $t_cfv            = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $t_cf             = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $fieldvarname     = Database::escape_string($fieldvarname);
        $fieldtitle     = Database::escape_string($fieldtitle);
        $fieldtype = (int)$fieldtype;
        $time = time();
        $sql_field = "SELECT id FROM $t_cf WHERE field_variable = '$fieldvarname'";
        $res_field = Database::query($sql_field);

        $r_field = Database::fetch_row($res_field);

        if (Database::num_rows($res_field) > 0) {
            return $r_field[0];
        }

        // save new fieldlabel into course_field table
        $sql = "SELECT MAX(field_order) FROM $t_cf";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_row($res);
            $order = $row[0] + 1;
        }

        $sql = "INSERT INTO $t_cf
                                    SET field_type = '$fieldtype',
                                    field_variable = '$fieldvarname',
                                    field_display_text = '$fieldtitle',
                                    field_order = '$order',
                                    tms = FROM_UNIXTIME($time)";
        Database::query($sql);

        return Database::insert_id();
    }

    /**
     * Updates course attribute. Note that you need to check that your attribute is valid before you use this function
     *
     * @param int Course id
     * @param string Attribute name
     * @param string Attribute value
     * @return bool True if attribute was successfully updated, false if course was not found or attribute name is invalid
     */
    public static function update_attribute($id, $name, $value) {
        $id = (int)$id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "UPDATE $table SET $name = '".Database::escape_string($value)."' WHERE id = '$id';";
        return Database::query($sql);
    }

    /**
     * Update course attributes. Will only update attributes with a non-empty value. Note that you NEED to check that your attributes are valid before using this function
     *
     * @param int Course id
     * @param array Associative array with field names as keys and field values as values
     * @return bool True if update was successful, false otherwise
     */
    public static function update_attributes($id, $attributes) {
        $id = (int)$id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "UPDATE $table SET ";
        $i = 0;
        foreach($attributes as $name => $value) {
            if($value != '') {
                if($i > 0) {
                    $sql .= ", ";
                }
                $sql .= " $name = '".Database::escape_string($value)."'";
                $i++;
            }
        }
        $sql .= " WHERE id = '$id';";
        return Database::query($sql);
    }


    /**
     * Update an extra field value for a given course
     * @param    integer    Course ID
     * @param    string    Field variable name
     * @param    string    Field value
     * @return    boolean    true if field updated, false otherwise
     */
    public static function update_course_extra_field_value($course_code, $fname, $fvalue = '') {

        $t_cfv            = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $t_cf             = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $fname = Database::escape_string($fname);
        $course_code = Database::escape_string($course_code);
        $fvalues = '';
        if (is_array($fvalue)) {
            foreach ($fvalue as $val) {
                $fvalues .= Database::escape_string($val).';';
            }
            if (!empty($fvalues)) {
                $fvalues = substr($fvalues, 0, -1);
            }
        } else {
            $fvalues = Database::escape_string($fvalue);
        }

        $sqlcf = "SELECT * FROM $t_cf WHERE field_variable='$fname'";
        $rescf = Database::query($sqlcf);
        if (Database::num_rows($rescf) == 1) {
            // Ok, the field exists
            // Check if enumerated field, if the option is available
            $rowcf = Database::fetch_array($rescf);

            $tms = time();
            $sqlcfv = "SELECT * FROM $t_cfv WHERE course_code = '$course_code' AND field_id = '".$rowcf['id']."' ORDER BY id";
            $rescfv = Database::query($sqlcfv);
            $n = Database::num_rows($rescfv);
            if ($n > 1) {
                //problem, we already have to values for this field and user combination - keep last one
                while ($rowcfv = Database::fetch_array($rescfv)) { // See the TODO note below.
                    if ($n > 1) {
                        $sqld = "DELETE FROM $t_cfv WHERE id = ".$rowcfv['id'];
                        $resd = Database::query($sqld);
                        $n--;
                    }
                    $rowcfv = Database::fetch_array($rescfv);
                    if ($rowcfv['field_value'] != $fvalues) {
                        $sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
                        $resu = Database::query($sqlu);
                        return ($resu ? true : false);
                    }
                    return true; // TODO: Sure exit from the function occures in this "while" cycle. Logic should checked. Maybe "if" instead of "while"? It is not clear...
                }
            } elseif ($n == 1) {
                //we need to update the current record
                $rowcfv = Database::fetch_array($rescfv);
                if ($rowcfv['field_value'] != $fvalues) {
                    $sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
                    //error_log('UM::update_extra_field_value: '.$sqlu);
                    $resu = Database::query($sqlu);
                    return ($resu ? true : false);
                }
                return true;
            } else {
                $sqli = "INSERT INTO $t_cfv (course_code,field_id,field_value,tms) " .
                    "VALUES ('$course_code',".$rowcf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
                //error_log('UM::update_extra_field_value: '.$sqli);
                $resi = Database::query($sqli);
                return ($resi ? true : false);
            }
        } else {
            return false; //field not found
        }
    }

    /**
     * Get the course id of an course by the database name
     * @param string The database name
     * @return string The course id
     */
    public static function get_course_id_by_database_name($db_name) {
        return Database::result(Database::query('SELECT code FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
                ' WHERE db_name="'.Database::escape_string($db_name).'"'), 0, 'code');
    }

    public static function get_session_category_id_by_session_id($session_id) {
        return Database::result(Database::query('SELECT  sc.id session_category
                FROM '.Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY).' sc
                INNER JOIN '.Database::get_main_table(TABLE_MAIN_SESSION).' s
                ON sc.id=s.session_category_id WHERE s.id="'.Database::escape_string($session_id).'"'),
            0, 'session_category');
    }

    /**
     * Get the course id of an course by the database name
     * @param string The database name
     * @return string The course id
     */
    public static function get_course_extra_field_list($code) {
        $tbl_course_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value    = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $sql_field = "SELECT id, field_type, field_variable, field_display_text, field_default_value
            FROM $tbl_course_field  WHERE field_visible = '1' ";
        $res_field = Database::query($sql_field);
        $extra_fields = array();
        while($rowcf = Database::fetch_array($res_field)) {
            $extra_field_id = $rowcf['id'];
            $sql_field_value = "SELECT field_value FROM $tbl_course_field_value WHERE course_code = '$code' AND field_id = '$extra_field_id' ";
            $res_field_value = Database::query($sql_field_value);
            if(Database::num_rows($res_field_value) > 0 ) {
                $r_field_value = Database::fetch_row($res_field_value);
                $rowcf['extra_field_value'] = $r_field_value[0];
            }
            $extra_fields[] = $rowcf;
        }
        return $extra_fields;
    }

    /**
     * Gets the value of a course extra field. Returns null if it was not found
     *
     * @param string Name of the extra field
     * @param string Course code
     * @return string Value
     */
    public static function get_course_extra_field_value($field_name, $code, $likeVisible = 1) {
        $tbl_course_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value    = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $sql = "SELECT id FROM $tbl_course_field WHERE field_visible like '$likeVisible' AND field_variable = '$field_name';";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if(!$row) {
            return null;
        } else {
            $sql_field_value = "SELECT field_value FROM $tbl_course_field_value WHERE course_code = '$code' AND field_id = '{$row->id}';";
            $res_field_value = Database::query($sql_field_value);
            $row_field_value = Database::fetch_object($res_field_value);
            if(!$row_field_value) {
                return null;
            } else {
                $row_field_value = (array)$row_field_value; //Parse to array because it is an stdClass
                return $row_field_value['field_value'];
            }
        }
    }


    /**
     * Get the database name of a course by the code
     * @param string The course code
     * @return string The database name
     */
    public static function get_name_database_course($course_code) {
        return Database::result(Database::query('SELECT db_name FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
                ' WHERE code="'.Database::escape_string($course_code).'"'), 0, 'db_name');
    }

    /**
     * Lists details of the course description
     * @param array        The course description
     * @param string    The encoding
     * @param bool        If true is displayed if false is hidden
     * @return string     The course description in html
     */
    public static function get_details_course_description_html($descriptions, $charset, $action_show = true) {
        if (isset($descriptions) && count($descriptions) > 0) {
            $data = '';
            foreach ($descriptions as $id => $description) {
                $data .= '<div class="sectiontitle">';
                if (api_is_allowed_to_edit() && $action_show) {
                    //delete
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;">';
                    $data .= Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
                    $data .= '</a> ';
                    //edit
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;description_id='.$description->id.'">';
                    $data .= Display::return_icon('edit.png', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'), ICON_SIZE_SMALL);
                    $data .= '</a> ';
                }
                $data .= $description->title;
                $data .= '</div>';
                $data .= '<div class="sectioncomment">';
                $data .= $description->content;
                $data .= '</div>';
            }
        } else {
            $data .= '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
        }

        return $data;
    }

    /**
     * Returns the details of a course category
     *
     * @param string Category code
     * @return array Course category
     */
    public static function get_course_category($code) {
        $table_categories = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT * FROM $table_categories WHERE code = '$code';";
        return Database::fetch_array(Database::query($sql));
    }

    /*
    ==============================================================================
        DEPRECATED METHODS
    ==============================================================================
    */

    /**
     *    This code creates a select form element to let the user
     *    choose a real course to link to.
     *
     *    A good non-display library should not use echo statements, but just return text/html
     *    so users of the library can choose when to display.
     *
     *    We display the course code, but internally store the course id.
     *
     *    @param boolean $has_size, true the select tag gets a size element, false it stays a dropdownmenu
     *    @param boolean $only_current_user_courses, true only the real courses of which the
     *    current user is course admin are displayed, false all real courses are shown.
     *    @param string $element_name the name of the select element
     *    @return a string containing html code for a form select element.
     * @deprecated Function not in use
     */
    public static function get_real_course_code_select_html($element_name, $has_size = true, $only_current_user_courses = true, $user_id) {
        if ($only_current_user_courses) {
            $real_course_list = self::get_real_course_list_of_user_as_course_admin($user_id);
        } else {
            $real_course_list = self::get_real_course_list();
        }

        if ($has_size) {
            $size_element = "size=\"".SELECT_BOX_SIZE."\"";
        } else {
            $size_element = "";
        }
        $html_code = "<select name=\"$element_name\" $size_element >\n";
        foreach ($real_course_list as $real_course) {
            $course_code = $real_course["code"];
            $html_code .= "<option value=\"".$course_code."\">";
            $html_code .= $course_code;
            $html_code .= "</option>\n";
        }
        $html_code .= "</select>\n";

        return $html_code;
    }

    /**
     *     Get count rows of a table inside a course database
     *  @param  string    The table of which the rows should be counted
     *  @param  int        optionally count rows by session id
     *  @return int     The number of rows in the given table.
     */
    public static function count_rows_course_table($table, $session_id = '', $course_id = null) {
        $condition_session = '';
        if ($session_id !== '') {
            $session_id = intval($session_id);
            $condition_session = " AND session_id = '$session_id' ";
        }
        if (!empty($course_id)) {
            $course_id = intval($course_id);
        } else {
            $course_id = api_get_course_int_id();
        }
        $condition_session .= " AND c_id = '$course_id' ";

        $sql    = "SELECT COUNT(*) AS n FROM $table WHERE 1=1 $condition_session ";
        $rs     = Database::query($sql);
        $row     = Database::fetch_row($rs);
        return $row[0];
    }

    /**
      * Subscribes courses to human resource manager (Dashboard feature)
      *    @param    int         Human Resource Manager id
      * @param    array        Courses code
      * @param    int            Relation type
      **/
    public static function suscribe_courses_to_hr_manager($hr_manager_id,$courses_list) {
        global $_configuration;

        // Database Table Definitions
        $tbl_course             =   Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_user    =   Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url     =   Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $hr_manager_id = intval($hr_manager_id);
        $affected_rows = 0;

        //Deleting assigned courses to hrm_id
        if ($_configuration['multiple_access_urls']) {
            $sql = "SELECT s.course_code FROM $tbl_course_rel_user s INNER JOIN $tbl_course_rel_access_url a ON (a.course_code = s.course_code) WHERE user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." AND access_url_id = ".api_get_current_access_url_id()."";
        } else {
            $sql = "SELECT course_code FROM $tbl_course_rel_user WHERE user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." ";
        }
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result))   {
                $sql = "DELETE FROM $tbl_course_rel_user WHERE course_code = '{$row['course_code']}' AND user_id = $hr_manager_id AND relation_type=".COURSE_RELATION_TYPE_RRHH." ";
                Database::query($sql);
            }
        }

        // inserting new courses list
        if (is_array($courses_list)) {
            foreach ($courses_list as $course_code) {
                $course_code = Database::escape_string($course_code);
                $insert_sql = "INSERT IGNORE INTO $tbl_course_rel_user(course_code, user_id, status, relation_type) VALUES('$course_code', $hr_manager_id, '".DRH."', '".COURSE_RELATION_TYPE_RRHH."')";
                Database::query($insert_sql);
                if (Database::affected_rows()) {
                    $affected_rows++;
                }
            }
        }
        return $affected_rows;

    }

    /**
     * get courses followed by human resources manager
     * @param int         human resources manager id
     * @return array    courses
     */
    public static function get_courses_followed_by_drh($user_id) {
        // Database Table Definitions
        $tbl_course             =     Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_user     =     Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url =   Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $user_id = intval($user_id);
        $assigned_courses_to_hrm = array();

        if (api_get_multiple_access_url()) {
           $sql = "SELECT *, id as real_id FROM $tbl_course c
                    INNER JOIN $tbl_course_rel_user cru ON (cru.course_code = c.code) LEFT JOIN $tbl_course_rel_access_url a  ON (a.course_code = c.code) WHERE cru.user_id = '$user_id' AND status = ".DRH." AND relation_type = '".COURSE_RELATION_TYPE_RRHH."' AND access_url_id = ".api_get_current_access_url_id()."";
        } else {
            $sql = "SELECT *, id as real_id FROM $tbl_course c
                    INNER JOIN $tbl_course_rel_user cru ON cru.course_code = c.code AND cru.user_id = '$user_id' AND status = ".DRH." AND relation_type = '".COURSE_RELATION_TYPE_RRHH."' ";
        }
        $rs_assigned_courses = Database::query($sql);
        if (Database::num_rows($rs_assigned_courses) > 0) {
            while ($row_assigned_courses = Database::fetch_array($rs_assigned_courses))    {
                $assigned_courses_to_hrm[$row_assigned_courses['code']] = $row_assigned_courses;
            }
        }
        return $assigned_courses_to_hrm;
    }

    /**
     * check if a course is special (autoregister)
     * @param string course code
     */
    public static function is_special_course($course_code){
        $tbl_course_field_value        = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $tbl_course_field             = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $is_special = false;
        $sql = "SELECT course_code FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf ON " .
                " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 AND course_code='$course_code'";
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);
        if ($num_rows > 0){
            $is_special = true;

        }
        return $is_special;
    }

    /**
     * Update course picture
     * @param   string  Course code
     * @param   string  File name
     * @param   string  The full system name of the image from which course picture will be created.
     * @return  bool    Returns the resulting. In case of internal error or negative validation returns FALSE.
     */
    public static function update_course_picture($course_code, $filename, $source_file = null) {

        $course_info          = api_get_course_info($course_code);
        $store_path           = api_get_path(SYS_COURSE_PATH).$course_info['path'];   // course path
        $course_image         = $store_path.'/course-pic.png';                      // image name for courses
        $course_medium_image  = $store_path.'/course-pic85x85.png';
        //$extension            = strtolower(substr(strrchr($filename, '.'), 1));

        if (file_exists($course_image)) {
            @unlink($course_image);
        }
        if (file_exists($course_medium_image)) {
            @unlink($course_medium_image);
        }

        $my_course_image = new Image($source_file);
        $result = $my_course_image->send_image($course_image, -1, 'png');
        //Redimension image to 100x85
        if ($result) {
            $medium = new Image($course_image);
            //$picture_infos = $medium->get_image_size();
            $medium->resize(100, 85, 0, false);
            $medium->send_image($store_path.'/course-pic85x85.png', -1, 'png');
        }
        return $result;
    }

    /**
     *  @deprecated See CourseManager::course_code_exists()
     */
    public static function is_existing_course_code($wanted_course_code) {
        return self::course_code_exists($wanted_course_code);
    }

    /**
     * Builds the course block in user_portal.php
     * @todo use Twig
     */
    public static function course_item_html($params, $is_sub_content = false) {
        $html = '';
        $class = "well course-box";
        if ($is_sub_content) {
            $class = "course_item";
        }
        if (!empty($params['is_session'])) {
            $class .= " session-item";
        }
        $html .= '<div class="'.$class.'">';
            $html .= '<div class="row">';
            $html .= '<div class="span7">';
                $html .= ' <div class="row">';
                    $html .= '<div class="span1 course-box-thumbnail-box">';
                    if (!empty($params['link'])) {
                        $html .= '<a class="thumbnail" href="'.$params['link'].'">';
                        $html .= $params['icon'];
                        $html .= '</a>';
                    } else {
                        $html .= '<div class="thumbnail">';
                        $html .= $params['icon'];
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                    $notifications = isset($params['notifications']) ? $params['notifications'] : null;
                    $param_class = isset($params['class']) ? $params['class'] : '';
                    if (!empty($params['is_session'])) {
                        $param_class .= ' session-box-text';
                    } else {
                        $param_class .= ' course-box-text';
                    }

                    $html .= '<div class="span6 '.$param_class.'">';
                        $html .='<h3>'.$params['title'].$notifications.'</h3> ';

                        if (!empty($params['subtitle'])) {
                            $html .= '<small>'.$params['subtitle'].'</small>';
                        }
                        if (!empty($params['teachers'])) {
                            $html .= '<h5>'.Display::return_icon('teacher.png', get_lang('Teacher'), array(), ICON_SIZE_TINY).$params['teachers'].'</h5>';
                        }
                        if (!empty($params['coaches'])) {
                            $html .= '<h5>'.Display::return_icon('teacher.png', get_lang('Coach'), array(), ICON_SIZE_TINY).$params['coaches'].'</h5>';
                        }

                    $html .= '</div>';
                $html .= '</div>';

            $html .= '</div>';
            $params['right_actions'] = isset($params['right_actions']) ? $params['right_actions'] : null;
            $html .= '<div class="span1 pull-right course-box-actions">'.$params['right_actions'].'</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }


    public static function course_item_parent($main_content, $sub_content, $sub_sub_content = null) {
        return '<div class="well">'.$main_content.$sub_content.$sub_sub_content.'</div>';
    }

    /**
     * Display special courses (and only these) as several HTML divs of class userportal-course-item
     *
     * Special courses are courses that stick on top of the list and are "auto-registerable"
     * in the sense that any user clicking them is registered as a student
     * @param int       User id
     * @param bool      Whether to show the document quick-loader or not
     * @return void
     */
    public static function display_special_courses($user_id, $load_dirs = false) {
        $user_id = intval($user_id);
        $tbl_course                 = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user            = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $special_course_list        = self::get_special_course_list();
        $html = null;
        $with_special_courses = $without_special_courses = '';
        if (!empty($special_course_list)) {
            $with_special_courses = ' course.code IN ("'.implode('","',$special_course_list).'")';
        }

        //$course_items = array();

        if (!empty($with_special_courses)) {
            $sql = "SELECT course.id, course.code, course.subscribe subscr, course.unsubscribe unsubscr, course_rel_user.status status,
                           course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat, course_rel_user.user_id
                    FROM $tbl_course course
                    LEFT JOIN $tbl_course_user course_rel_user ON course.code = course_rel_user.course_code AND course_rel_user.user_id = '$user_id'
                    WHERE $with_special_courses group by course.code";

            $rs_special_course = Database::query($sql);
            $number_of_courses = Database::num_rows($rs_special_course);
            $key = 0;

            if ($number_of_courses > 0) {
                while ($course = Database::fetch_array($rs_special_course)) {
                    $course_info = api_get_course_info($course['code']);
                    $params = array();
                    // Get notifications.
                    //$course['id_session']   = null;
                    //$course['status']       = $course['status'];

                    $course_info['id_session']  = null;
                    $course_info['status']      = $course['status'];
                    $show_notification = Display::show_notification($course_info);

                    if (empty($course['user_id'])) {
                        $course['status'] = STUDENT;
                    }

                    $params['icon'] = Display::return_icon('blackboard.png', $course_info['title'], array(), ICON_SIZE_LARGE);

                    $params['right_actions'] = '';
                    if (api_is_platform_admin()) {
                        if ($load_dirs) {
                            $params['right_actions'] .= '<a id="document_preview_'.$course['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                            $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                            $params['right_actions'] .= Display::div('', array('id' => 'document_result_'.$course['real_id'].'_0', 'class'=>'document_preview_container'));
                        } else {
                            $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                        }
                        if ($course['status'] == COURSEMANAGER) {
                            //echo Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style'=>'width: 11px; height: 11px;'));
                        }
                    } else {
                        if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
                            if ($load_dirs) {
                                $params['right_actions'] .=  '<a id="document_preview_'.$course['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                                $params['right_actions'] .=  Display::div('', array('id' => 'document_result_'.$course['real_id'].'_0', 'class'=>'document_preview_container'));
                            }
                        }
                    }

                    if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {
                        $course_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session=0&amp;autoreg=1">'.$course_info['title'].'</a>';
                    } else {
                        $course_title = $course_info['title']." ".Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));
                    }

                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        $course_title .= ' ('.$course_info['visual_code'].') ';
                    }
                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
                        $params['teachers'] = CourseManager::get_teacher_list_from_course_code_to_string($course['code'], self::USER_SEPARATOR, true);
                    }
                    $course_title .= '&nbsp;';
                    $course_title .= Display::return_icon('klipper.png', get_lang('CourseAutoRegister'));

                    $params['title'] = $course_title;
                    $params['link'] = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session=0&amp;autoreg=1';

                    if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
                        $params['notifications'] = $show_notification;
                    }

                    $html .= self::course_item_html($params, false);
                    //$course_items[] = $params;
                    $key++;
                }
            }
        }
        return $html;
    }

    /**
     * Display courses (without special courses) as several HTML divs
     * of course categories, as class userportal-catalog-item.
     * @uses display_courses_in_category() to display the courses themselves
     * @param int        user id
     * @param bool      Whether to show the document quick-loader or not
     * @return void
     */
    public static function display_courses($user_id, $load_dirs = false) {
        $user_id = intval($user_id);
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $html = null;

        // Step 1: We get all the categories of the user
        $tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT id, title FROM $tucc WHERE user_id='".$user_id."' ORDER BY sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $params = array();
            // We simply display the title of the category.
            $params['icon'] = Display::return_icon('folder_yellow.png', $row['title'], array(), ICON_SIZE_LARGE);
            $params['title'] = $row['title'];
            $params['class'] = 'table_user_course_category';
            $html .= self::course_item_parent(self::course_item_html($params, true), self :: display_courses_in_category($row['id'], $load_dirs));
        }

        // Step 2: We display the course without a user category.
        $html .= self :: display_courses_in_category(0, $load_dirs);
        return $html;
    }

    /**
     *  Display courses inside a category (without special courses) as HTML dics of
     *  class userportal-course-item.
     *  @param int      User category id
     * @param bool      Whether to show the document quick-loader or not
     *  @return void
     */
    public static function display_courses_in_category($user_category_id, $load_dirs = false) {
        $user_id = api_get_user_id();
        // Table definitions
        $TABLECOURS                     = Database :: get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER                 = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_ACCESS_URL_REL_COURSE    = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id                 = api_get_current_access_url_id();

        // Get course list auto-register
        $special_course_list            = self::get_special_course_list();

        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.code NOT IN ("'.implode('","',$special_course_list).'")';
        }

        //AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
        $sql = "SELECT course.id, course.title, course.code, course.subscribe subscr, course.unsubscribe unsubscr, course_rel_user.status status,
                                        course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                                        FROM    $TABLECOURS      course,
                                                $TABLECOURSUSER  course_rel_user, ".$TABLE_ACCESS_URL_REL_COURSE." url
                                        WHERE   course.code = course_rel_user.course_code AND url.course_code = course.code AND
                                                course_rel_user.user_id = '".$user_id."' AND
                                                course_rel_user.user_course_cat='".$user_category_id."' $without_special_courses ";
        // If multiple URL access mode is enabled, only fetch courses
        // corresponding to the current URL.
        if (api_get_multiple_access_url() && $current_url_id != -1) {
            $sql .= " AND url.course_code=course.code AND access_url_id='".$current_url_id."'";
        }
        // Use user's classification for courses (if any).
        $sql .= " ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";

        $result = Database::query($sql);
        $status_icon = '';
        $html = '';

        $course_list = array();

        // Browse through all courses.
        while ($course = Database::fetch_array($result)) {
            $course_info = api_get_course_info($course['code']);
            //$course['id_session'] = null;
            $course_info['id_session'] = null;
            $course_info['status'] = $course['status'];

            //In order to avoid doubles
            if (in_array($course_info['real_id'], $course_list)) {
                continue;
            } else {
                $course_list[] = $course_info['real_id'];
            }

            // For each course, get if there is any notification icon to show
            // (something that would have changed since the user's last visit).
            $show_notification = Display :: show_notification($course_info);

            // New code displaying the user's status in respect to this course.
            $status_icon = Display::return_icon('blackboard.png', $course_info['title'], array(), ICON_SIZE_LARGE);

            $params = array();
            $params['right_actions'] = '';

            if (api_is_platform_admin()) {
                if ($load_dirs) {
                    $params['right_actions'] .= '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                    $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                    $params['right_actions'] .= Display::div('', array('id' => 'document_result_'.$course_info['real_id'].'_0', 'class'=>'document_preview_container'));
                } else {
                    $params['right_actions'].= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                }

                if ($course_info['status'] == COURSEMANAGER) {
                    //echo Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style'=>'width: 11px; height: 11px;'));
                }
            } else {
                if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
                    if ($load_dirs) {
                        $params['right_actions'] .= '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                        $params['right_actions'] .= Display::div('', array('id' => 'document_result_'.$course_info['real_id'].'_0', 'class'=>'document_preview_container'));
                    } else {
                        if ($course_info['status'] == COURSEMANAGER) {
                            $params['right_actions'].= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course['code'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                        }
                    }
                }
            }

            $course_title = $course_info['title'];

            $course_title_url = '';
            if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {
                $course_title_url = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session=0';
                $course_title = Display::url($course_info['title'], $course_title_url);
            } else {
                $course_title = $course_info['title']." ".Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));
            }

            // Start displaying the course block itself
            if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                $course_title .= ' ('.$course_info['visual_code'].') ';
            }
            if (api_get_setting('display_teacher_in_courselist') == 'true') {
                $teachers = CourseManager::get_teacher_list_from_course_code_to_string($course['code'], self::USER_SEPARATOR, true);
            }

            $params['link'] = $course_title_url;
            $params['icon'] = $status_icon;
            $params['title'] = $course_title;
            $params['teachers'] = $teachers;

            if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
                $params['notifications'] = $show_notification;
            }

            $is_subcontent = true;
            if (empty($user_category_id)) {
                $is_subcontent = false;
            }
            $html .= self::course_item_html($params, $is_subcontent);
        }
        return $html;
    }

    /**
     * Retrieves the user defined course categories
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @return array containing all the titles of the user defined courses with the id as key of the array
     */
    function get_user_course_categories() {
        global $_user;
        $output = array();
        $table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "SELECT * FROM ".$table_category." WHERE user_id='".Database::escape_string($_user['user_id'])."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $output[$row['id']] = $row['title'];
        }
        return $output;
    }

    /**
     * Get the course id based on the original id and field name in the extra fields. Returns 0 if course was not found
     *
     * @param string Original course id
     * @param string Original field name
     * @return int Course id
     */
    public static function get_course_id_from_original_id($original_course_id_value, $original_course_id_name) {
        $t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $table_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
        $sql_course = "SELECT id FROM $table_field cf INNER JOIN $t_cfv cfv ON cfv.field_id=cf.id WHERE field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
        $res = Database::query($sql_course);
        $row = Database::fetch_object($res);
        if($row != false) {
            return $row->id;
        } else {
            return 0;
        }
    }

    /**
     * Display code for one specific course a logged in user is subscribed to.
     * Shows a link to the course, what's new icons...
     *
     * $my_course['d'] - course directory
     * $my_course['i'] - course title
     * $my_course['c'] - visual course code
     * $my_course['k']  - system course code
     *
     * @param   array       Course details
     * @param   integer     Session ID
     * @param   string      CSS class to apply to course entry
     * @param   boolean     Whether the session is supposedly accessible now (not in the case it has passed and is in invisible/unaccessible mode)
     * @param bool      Whether to show the document quick-loader or not
     * @return  string      The HTML to be printed for the course entry
     *
     * @version 1.0.3
     * @todo refactor into different functions for database calls | logic | display
     * @todo replace single-character $my_course['d'] indices
     * @todo move code for what's new icons to a separate function to clear things up
     * @todo add a parameter user_id so that it is possible to show the courselist of other users (=generalisation). This will prevent having to write a new function for this.
     */
    public static function get_logged_user_course_html($course, $session_id = 0, $load_dirs = false) {
        $course_info = api_get_course_info($course['code']);
        $course_info['id_session'] = $session_id;

        // Display course entry.
        // Show a link to the course, unless the course is closed and user is not course admin.
        $session_url = '';
        $session_title = $course_info['name'];

        if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED || $course_info['user_status_in_course'] == COURSEMANAGER) {
            //if ($course_info['user_status_in_course'] == COURSEMANAGER) {
                $session_url = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session='.$course_info['id_session'];
                $session_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session='.$course_info['id_session'].'">'.$course_info['name'].'</a>';
            //}
        } else {
            $session_title = $course_info['name'].' '.Display::tag('span',get_lang('CourseClosed'), array('class'=>'item_closed'));
        }


        $params = array();
        $params['icon'] = Display::return_icon('blackboard_blue.png', $course_info['name'], array(), ICON_SIZE_LARGE);
        $params['link'] = $session_url;
        $params['title'] = $session_title;


        $params['right_actions'] = '';

        if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
            if ($load_dirs) {
                $params['right_actions'] .= '<a id="document_preview_'.$course_info['real_id'].'_'.$course_info['id_session'].'" class="document_preview" href="javascript:void(0);">'.
                                            Display::return_icon('folder.png', get_lang('Documents'), array('align' => 'absmiddle'),ICON_SIZE_SMALL).'</a>';
                $params['right_actions'] .= Display::div('', array('id' => 'document_result_'.$course_info['real_id'].'_'.$course_info['id_session'], 'class'=>'document_preview_container'));
            }
        }

        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
            $session_title .= ' ('.$course_info['visual_code'].') ';
        }

        if (api_get_setting('display_teacher_in_courselist') == 'true') {
            $teacher_list = CourseManager::get_teacher_list_from_course_code_to_string($course_info['code'], self::USER_SEPARATOR, false);
            $course_coachs = CourseManager::get_coachs_from_course_to_string($course_info['id_session'], $course['code'], self::USER_SEPARATOR, false);
            $params['teachers'] = $teacher_list;
            $params['coaches'] = $course_coachs;
        }
        $session_title .= isset($course['special_course']) ? ' '.Display::return_icon('klipper.png', get_lang('CourseAutoRegister')) : '';

        // Display the "what's new" icons
        if ($course_info['visibility'] != COURSE_VISIBILITY_CLOSED) {
            $session_title .= Display :: show_notification($course_info);
        }
        $params['title'] = $session_title;
        $params['extra'] = '';
        $html = self::course_item_html($params, true);
        return $html;
    }

    /**
     *
     * @param    string    source course code
     * @param     int        source session id
     * @param    string    destination course code
     * @param     int        destination session id
     * @return  bool
     */
    public static function copy_course($source_course_code, $source_session_id, $destination_course_code, $destination_session_id, $params = array()) {
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseBuilder.class.php';
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseRestorer.class.php';
        require_once api_get_path(SYS_CODE_PATH).'coursecopy/classes/CourseSelectForm.class.php';

        $course_info = api_get_course_info($source_course_code);

        if (!empty($course_info)) {
            $cb = new CourseBuilder('', $course_info);
            $course = $cb->build($source_session_id, $source_course_code, true);
            $course_restorer = new CourseRestorer($course);
            $course_restorer->skip_content = $params;
            $course_restorer->restore($destination_course_code, $destination_session_id, true, true);
            return true;
        }
        return false;
    }


    /**
     * A simpler version of the copy_course, the function creates an empty course with an autogenerated course code
     *
     * @param    string    new course title
     * @param    string    source course code
     * @param     int        source session id
     * @param     int        destination session id
     * @param    bool    new copied tools (Exercises and LPs)will be set to invisible by default?
     *
     * @return     array
     */
    public static function copy_course_simple($new_title, $source_course_code, $source_session_id = 0, $destination_session_id = 0, $params = array()) {
        $source_course_info = api_get_course_info($source_course_code);
        if (!empty($source_course_info)) {
            $new_course_code = self::generate_nice_next_course_code($source_course_code);
            if ($new_course_code) {
                $new_course_info = self::create_course($new_title, $new_course_code, false);
                if (!empty($new_course_info['code'])) {
                    $result = self::copy_course($source_course_code, $source_session_id, $new_course_info['code'], $destination_session_id, $params);
                    if ($result) {
                        return $new_course_info;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Creates a new course code based in a given code
     *
     * @param string    wanted code
     * <code>    $wanted_code = 'curse' if there are in the DB codes like curse1 curse2 the function will return: course3</code>
     * if the course code doest not exist in the DB the same course code will be returned
     * @return string    wanted unused code
     */
    public static function generate_nice_next_course_code($wanted_code) {
        require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
        $course_code_ok = !self::course_code_exists($wanted_code);
        if (!$course_code_ok) {
           $wanted_code = self::generate_course_code($wanted_code);
           $table = Database::get_main_table(TABLE_MAIN_COURSE);
           $wanted_code = Database::escape_string($wanted_code);
           $sql = "SELECT count(*) as count FROM $table WHERE code LIKE '$wanted_code%'";
           $result = Database::query($sql);
           if (Database::num_rows($result) > 0 ) {
               $row = Database::fetch_array($result);
               $count = $row['count'] + 1;
               $wanted_code = $wanted_code.'_'.$count;
               $result = api_get_course_info($wanted_code);
               if (empty($result)) {
                   return $wanted_code;
               }
           }
           return false;
        }
        return $wanted_code;
    }


    /**
     * Gets the status of the users agreement in a course course-session
     *
     * @param int user id
     * @param string course code
     * @param int session id
     * @return boolean
     */
    public static function is_user_accepted_legal($user_id, $course_code, $session_id = null) {
        $user_id    = intval($user_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);
        if (empty($session_id)) {
            $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql = "SELECT legal_agreement FROM $table
                    WHERE user_id =  $user_id AND course_code  ='$course_code' ";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0 ) {
                $result = Database::fetch_array($result);
                if ($result['legal_agreement'] == 1 ) {
                    return true;
                }
            }
            return false;
        } else {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $sql = "SELECT legal_agreement FROM $table
                    WHERE id_user =  $user_id AND course_code  ='$course_code' AND id_session = $session_id";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0 ) {
                $result = Database::fetch_array($result);
                if ($result['legal_agreement'] == 1 ) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Saves the user-course legal agreement
     * @param   int user id
     * @param   string course code
     * @param   int session id
     */
    function save_user_legal($user_id, $course_code, $session_id = null) {

        $user_id    = intval($user_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);
        if (empty($session_id)) {
            $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql = "UPDATE $table SET legal_agreement = '1'
                    WHERE user_id =  $user_id AND course_code  ='$course_code' ";
            $result = Database::query($sql);
        } else {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $sql = "UPDATE  $table SET legal_agreement = '1'
                    WHERE id_user =  $user_id AND course_code  = '$course_code' AND id_session = $session_id";
            $result = Database::query($sql);
        }
    }

    public static function get_user_course_vote($user_id, $course_id, $session_id = null, $url_id = null) {
        $table_user_course_vote     = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);

        $session_id = !isset($session_id)   ? api_get_session_id()                : intval($session_id);
        $url_id     = empty($url_id)        ? api_get_current_access_url_id()    : intval($url_id);

        $user_id = intval($user_id);

        if (empty($user_id)) {
            return false;
        }

        $params = array(
            'user_id'        => $user_id,
            'c_id'          => $course_id,
            'session_id'    => $session_id,
            'url_id'        => $url_id
        );

        $result = Database::select('vote', $table_user_course_vote, array('where' => array('user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params)), 'first');
        if (!empty($result)) {
            return $result['vote'];
        }
        return false;
    }

    public static function get_course_ranking($course_id, $session_id = null, $url_id = null) {
        $table_course_ranking = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);

        $session_id = !isset($session_id)   ? api_get_session_id() : intval($session_id);
        $url_id     = empty($url_id)        ? api_get_current_access_url_id() : intval($url_id);

        $params = array(
            'c_id'          => $course_id,
            'session_id'    => $session_id,
            'url_id'        => $url_id
        );

        $result = Database::select('c_id, accesses, total_score, users', $table_course_ranking, array('where' => array('c_id = ? AND session_id = ? AND url_id = ?' => $params)), 'first');

        $point_average_in_percentage = 0;
        $point_average_in_star = 0;
        $users_who_voted = 0;

        if (!empty($result['users'])) {
            $users_who_voted                = $result['users'];
            $point_average_in_percentage    = round($result['total_score']/$result['users'] * 100 / 5, 2);
            $point_average_in_star            = round($result['total_score']/$result['users'], 1);
        }

        $result['user_vote'] = false;

        if (!api_is_anonymous()) {
            $result['user_vote'] = self::get_user_course_vote(api_get_user_id(), $course_id, $session_id,$url_id);
        }

        $result['point_average']        = $point_average_in_percentage;
        $result['point_average_star']   = $point_average_in_star;
        $result['users_who_voted']        = $users_who_voted;

        return $result;
    }

    /**
     *
     * Updates the course ranking
     * @param int   course id
     * @param int   session id
     * @param id    url id
     *
     **/
    public static function update_course_ranking($course_id = null, $session_id = null, $url_id = null, $points_to_add = null, $add_access = true, $add_user = true) {
        //Course catalog stats modifications see #4191
        $table_course_ranking       = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);

        $now = api_get_utc_datetime();
        $course_id  = empty($course_id)     ? api_get_course_int_id() : intval($course_id);
        $session_id = !isset($session_id)   ? api_get_session_id() : intval($session_id);
        $url_id     = empty($url_id)        ? api_get_current_access_url_id() : intval($url_id);

        $params = array(
            'c_id'          => $course_id,
            'session_id'    => $session_id,
            'url_id'        => $url_id,
            'creation_date' => $now,
        );

        $result = Database::select('id, accesses, total_score, users', $table_course_ranking, array('where' => array('c_id = ? AND session_id = ? AND url_id = ?' => $params)), 'first');

        // Problem here every thime we load the courses/XXXX/index.php course home page we update the access

        if (empty($result)) {
            if ($add_access) {
                $params['accesses'] = 1;
            }
            //The votes and users are empty
            if (isset($points_to_add) && !empty($points_to_add)) {
               $params['total_score'] = intval($points_to_add);
            }
            if ($add_user) {
                $params['users'] = 1;
            }
            $result = Database::insert($table_course_ranking, $params);
        } else {
            $my_params = array();

            if ($add_access) {
                $my_params['accesses'] = intval($result['accesses']) + 1;
            }
            if (isset($points_to_add) && !empty($points_to_add)) {
               $my_params['total_score'] = $result['total_score'] + $points_to_add;
            }
            if ($add_user) {
                $my_params['users']  = $result['users'] + 1;
            }

            if (!empty($my_params)) {
                $result = Database::update($table_course_ranking, $my_params, array('c_id = ? AND session_id = ? AND url_id = ?' => $params));
            }
        }
        return $result;
    }


    /**
     * Add user vote to a course
     *
     * @param   int user id
     * @param   int vote [1..5]
     * @param   int course id
     * @param   int session id
     * @param   int url id (access_url_id)
     * @return    mixed 'added', 'updated' or 'nothing'
     *
     */

    public static function add_course_vote($user_id, $vote, $course_id, $session_id = null, $url_id = null) {
        $table_user_course_vote     = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);
        $course_id  = empty($course_id) ? api_get_course_int_id() : intval($course_id);

        if (empty($course_id) || empty($user_id)) {
            return false;
        }

        if (!in_array($vote, array(1,2,3,4,5))) {
            return false;
        }

        $session_id = !isset($session_id)   ? api_get_session_id() : intval($session_id);
        $url_id     = empty($url_id)        ? api_get_current_access_url_id() : intval($url_id);
        $vote       = intval($vote);

        $params = array(
            'user_id'       => intval($user_id),
            'c_id'          => $course_id,
            'session_id'    => $session_id,
            'url_id'        => $url_id,
            'vote'          => $vote
        );

        $action_done = 'nothing';

        $result = Database::select('id, vote', $table_user_course_vote, array('where' => array('user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params)), 'first');

        if (empty($result)) {
            $result = Database::insert($table_user_course_vote, $params);
            $points_to_add = $vote;
            $add_user = true;
            $action_done = 'added';
        } else {
            $my_params = array('vote' => $vote);
            $points_to_add = $vote - $result['vote'];
            $add_user = false;

            $result = Database::update($table_user_course_vote, $my_params, array('user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params));
            $action_done = 'updated';
        }

        //Current points
        if (!empty($points_to_add)) {
            self::update_course_ranking($course_id, $session_id, $url_id, $points_to_add, false, $add_user);
        }
        return $action_done;
    }

    /**
     * Remove course ranking + user votes
     *
     * @param   int course id
     * @param   int session id
     * @param   int url id
     *
     */
    public function remove_course_ranking($course_id, $session_id, $url_id = null) {
        $table_course_ranking       = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);
        $table_user_course_vote     = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);
        if (!empty($course_id) && isset($session_id)) {

            $url_id     = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);
            $params = array(
                'c_id'          => $course_id,
                'session_id'    => $session_id,
                'url_id'        => $url_id,
            );
            Database::delete($table_course_ranking,   array('c_id = ? AND session_id = ? AND url_id = ?' => $params));
            Database::delete($table_user_course_vote, array('c_id = ? AND session_id = ? AND url_id = ?' => $params));
        }
    }

    /**
     * Returns an array with the hottest courses
     * @param   int number of days
     * @param   int number of hottest courses
     */
    public static function return_hot_courses($days = 30, $limit = 5) {
        global $_configuration;
        $limit  = intval($limit);

        //Getting my courses
        $my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id());

        $my_course_code_list = array();
        foreach ($my_course_list as $course) {
            $my_course_code_list[$course['real_id']] = $course['real_id'];
        }

        if (api_is_drh()) {
            $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
            foreach ($courses as $course) {
                $my_course_code_list[$course['real_id']] = $course['real_id'];
            }
        }

        $table_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        //@todo all dates in the tracking_course_access, last_access are in the DB time (NOW) not UTC
        /*
        $today                    = api_get_utc_datetime();
        $today_diff                = time() -intval($days)*24*60*60;
        $today_diff                = api_get_utc_datetime($today_diff);
         * */

        //WHERE login_course_date <= '$today' AND login_course_date >= '$today_diff'

        //$table_course_access table uses the now() and interval ...

       $sql = "SELECT COUNT(course_access_id) course_count, a.course_code, visibility ".
              "FROM $table_course c INNER JOIN $table_course_access a ".
              "  ON (c.code = a.course_code) INNER JOIN $table_course_url u ON u.course_code = a.course_code ".
              "  WHERE   u.access_url_id = ".$_configuration['access_url']." AND".
              "          login_course_date <= now() AND ".
              "          login_course_date > DATE_SUB(now(), INTERVAL $days DAY) AND".
              "          visibility <> '".COURSE_VISIBILITY_CLOSED."'".
              "  GROUP BY course_code".
              "  ORDER BY course_count DESC".
              "  LIMIT $limit";

        $result = Database::query($sql);
        $courses = array();
        if (Database::num_rows($result)) {
            $courses = Database::store_result($result, 'ASSOC');
            $courses = self::process_hot_course_item($courses, $my_course_code_list);
        }
        return $courses;
    }

    public static function process_hot_course_item($courses, $my_course_code_list = array()) {
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';

        foreach ($courses as &$my_course) {
            $course_info = api_get_course_info($my_course['course_code']);
            $my_course['extra_info'] = $course_info;
            $my_course['extra_info']['go_to_course_button'] = '';
            $my_course['extra_info']['register_button'] = '';
            $access_link = self::get_access_link_by_user(api_get_user_id(), $course_info, $my_course_code_list);

            //Course visibility
            if ($access_link && in_array('register', $access_link)) {
                $stok = Security::get_token();
                $my_course['extra_info']['register_button'] = Display::url(get_lang('Subscribe'), api_get_path(WEB_COURSE_PATH).$course_info['path'].'/index.php?action=subscribe&amp;sec_token='.$stok, array('class' => 'btn btn-primary'));
            }

            if ($access_link && in_array('enter', $access_link)) {
                $my_course['extra_info']['go_to_course_button'] = Display::url(get_lang('GoToCourse'), api_get_path(WEB_COURSE_PATH).$course_info['path'].'/index.php', array('class' => 'btn btn-primary'));
            }

            //Description
            $my_course['extra_info']['description_button'] = '';
            if ($course_info['visibility'] == COURSE_VISIBILITY_OPEN_WORLD || in_array($course_info['real_id'], $my_course_code_list) ) {
                $my_course['extra_info']['description_button'] = Display::url(get_lang('Description'), api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=show_course_information&amp;code='.$course_info['code'], array('class' => 'ajax btn'));
            }

            $my_course['extra_info']['teachers'] = CourseManager::get_teacher_list_from_course_code_to_string($course_info['code']);
            $point_info = self::get_course_ranking($course_info['real_id'], 0);
            $my_course['extra_info']['rating_html'] = Display::return_rating_system('star_'.$course_info['real_id'], $ajax_url.'&amp;course_id='.$course_info['real_id'], $point_info);
        }
        return $courses;
    }

    public static function return_most_accessed_courses($limit = 5) {
        $table_course_ranking    = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);
        $params['url_id']        = api_get_current_access_url_id();

        $result = Database::select('c_id, accesses, total_score, users', $table_course_ranking, array('where' => array('url_id = ?' => $params), 'order' => 'accesses DESC', 'limit' => $limit), 'all', true);
        return $result;
    }

    /**
     *
     *
     * @return ResultSet
     */
    static function list_inactive_courses($ceiling, $visibility_level = COURSE_VISIBILITY_REGISTERED) {
        $ceiling = is_numeric($ceiling) ? (int) $ceiling : strtotime($ceiling);
        $ceiling = date('Y-m-d H:i:s', $ceiling);
        $visibility_level = $visibility_level ? $visibility_level : '0';

        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_category = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT
                    c.*,
                    cat.name AS category
                FROM
                    $table_course AS c
                LEFT JOIN
                    $table_category AS cat
                ON
                    c.category_code = cat.code
                WHERE
                    c.visibility >= $visibility_level AND
                    c.last_visit<='$ceiling'
        ";

        return ResultSet::create($sql);
    }

    /**
     * Get courses count
     * @param int Access URL ID (optional)
     * @return int Number of courses
     */
    public static function count_courses($access_url_id = null) {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "SELECT count(id) FROM $table_course c";
        if (!empty($access_url_id) && $access_url_id == intval($access_url_id)) {
            $sql .= ", $table_course_rel_access_url u WHERE c.code = u.course_code AND u.access_url_id = $access_url_id";
        }
        $res = Database::query($sql);
        $row = Database::fetch_row($res);
        return $row[0];
    }

    /**
     * Return a link to go to the course, validating the visibility of the
     * course and the user status
     * @param int User ID
     * @param array Course details array
     * @param array  List of courses to which the user is subscribed (if not provided, will be generated)
     * @return mixed 'enter' for a link to go to the course or 'register' for a link to subscribe, or false if no access
     */
    static function get_access_link_by_user($uid, $course, $user_courses = array()) {
        if (empty($uid) or empty($course)) { return false; }
        if (empty($user_courses)) {
            // get the array of courses to which the user is subscribed
            $user_courses = CourseManager::get_courses_list_by_user_id($uid);
            foreach ($user_courses as $k => $v) {
                $user_courses[$k] = $v['real_id'];
            }
        }

        if (!isset($course['real_id']) && empty($course['real_id'])) {
            $course = api_get_course_info($course['code']);
        }

        $is_admin = api_is_platform_admin_by_id($uid);
        $options =  array();

        // Register button
        if (!api_is_anonymous($uid) &&
            !$is_admin &&
            (
                ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD || $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) ||
                $course['visibility'] == COURSE_VISIBILITY_REGISTERED && $course['subscribe'] == SUBSCRIBE_ALLOWED
            ) &&
            $course['subscribe'] == SUBSCRIBE_ALLOWED &&
            (!in_array($course['real_id'], $user_courses) || empty($user_courses))
          ) {
            $options[]= 'register';
        }

         // Go To Course button (only if admin, if course public or if student already subscribed)
        if ($is_admin ||
            $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD && empty($course['registration_code']) ||
            (api_user_is_login($uid) && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM && empty($course['registration_code']) ) ||
            (in_array($course['real_id'], $user_courses) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
        ) {
            $options[]=  'enter';
        }
        return $options;
    }

    // TODO: Such a function might be useful in other places too. It might be moved in the CourseManager class.
    // Also, the function might be upgraded for avoiding code duplications.
    static function generate_course_code($course_title, $encoding = null) {
        if (empty($encoding)) {
            $encoding = api_get_system_encoding();
        }
        return substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(api_transliterate($course_title, 'X', $encoding))), 0, CourseManager::MAX_COURSE_LENGTH_CODE);
    }


    /**
     * Defines the four needed keys to create a course based on several parameters.
     * @param string    The code you want for this course
     * @param string    Prefix added for ALL keys
     * @param string    Prefix added for databases only
     * @param string    Prefix added for paths only
     * @param bool      Add unique prefix
     * @param bool      Use code-independent keys
     * @return array    An array with the needed keys ['currentCourseCode'], ['currentCourseId'], ['currentCourseDbName'], ['currentCourseRepository']
     * @todo Eliminate the global variables.
     */
    static function define_course_keys($wanted_code, $prefix_for_all = '', $prefix_for_base_name = '', $prefix_for_path = '', $add_unique_prefix = false, $use_code_indepedent_keys = true) {
        global $prefixAntiNumber, $_configuration;
        $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $wanted_code = self::generate_course_code($wanted_code);
        $keys_course_code = $wanted_code;
        if (!$use_code_indepedent_keys) {
            $wanted_code = '';
        }

        if ($add_unique_prefix) {
            $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
        } else {
            $unique_prefix = '';
        }

        $keys = array();
        $final_suffix = array('CourseId' => '', 'CourseDb' => '', 'CourseDir' => '');
        $limit_numb_try = 100;
        $keys_are_unique = false;
        $try_new_fsc_id = $try_new_fsc_db = $try_new_fsc_dir = 0;

        while (!$keys_are_unique) {

            $keys_course_id = $prefix_for_all . $unique_prefix . $wanted_code . $final_suffix['CourseId'];
            //$keys_course_db_name = $prefix_for_base_name . $unique_prefix . strtoupper($keys_course_id) . $final_suffix['CourseDb'];
            $keys_course_repository = $prefix_for_path . $unique_prefix . $wanted_code . $final_suffix['CourseDir'];
            $keys_are_unique = true;

            // Check whether they are unique.
            $query = "SELECT 1 FROM ".$course_table." WHERE code='".$keys_course_id."' LIMIT 0,1";
            $result = Database::query($query);

            //if ($keys_course_id == DEFAULT_COURSE || Database::num_rows($result)) {
            if (Database::num_rows($result)) {
                $keys_are_unique = false;
                $try_new_fsc_id ++;
                $final_suffix['CourseId'] = substr(md5(uniqid(rand())), 0, 4);
            }
            if (file_exists(api_get_path(SYS_COURSE_PATH).$keys_course_repository)) {
                $keys_are_unique = false;
                $try_new_fsc_dir ++;
                $final_suffix['CourseDir'] = substr(md5(uniqid(rand())), 0, 4);
            }

            if (($try_new_fsc_id + $try_new_fsc_db + $try_new_fsc_dir) > $limit_numb_try) {
                return $keys;
            }
        }

        $keys['currentCourseCode'] = $keys_course_code;
        $keys['currentCourseId'] = $keys_course_id;
        $keys['currentCourseRepository'] = $keys_course_repository;

        return $keys;
    }

    /**
     * Initializes a file repository for a newly created course.
     */
    static function prepare_course_repository($course_repository, $course_code) {

        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();
        $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n  <head>\n    <meta charset=\"utf-8\">\n    <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";
        $cp = api_get_path(SYS_COURSE_PATH).$course_repository;

        //Creating document folder
        mkdir($cp, $perm);
        mkdir($cp . '/document', $perm);
        $cpt = $cp.'/document/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);

        /*
        @chmod($cpt, $perm_file);
        @copy($cpt, $cp . '/document/index.html');
        mkdir($cp . '/document/images', $perm);
        @copy($cpt, $cp . '/document/images/index.html');
        mkdir($cp . '/document/images/gallery/', $perm);
        @copy($cpt, $cp . '/document/images/gallery/index.html');
        mkdir($cp . '/document/shared_folder/', $perm);
        @copy($cpt, $cp . '/document/shared_folder/index.html');
        mkdir($cp . '/document/audio', $perm);
        @copy($cpt, $cp . '/document/audio/index.html');
        mkdir($cp . '/document/flash', $perm);
        @copy($cpt, $cp . '/document/flash/index.html');
        mkdir($cp . '/document/video', $perm);
        @copy($cpt, $cp . '/document/video/index.html');    */

        //Creatind dropbox folder
        mkdir($cp . '/dropbox', $perm);
        $cpt = $cp.'/dropbox/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);
        @chmod($cpt, $perm_file);
        mkdir($cp . '/group', $perm);
        @copy($cpt, $cp . '/group/index.html');
        mkdir($cp . '/page', $perm);
        @copy($cpt, $cp . '/page/index.html');
        mkdir($cp . '/scorm', $perm);
        @copy($cpt, $cp . '/scorm/index.html');
        mkdir($cp . '/upload', $perm);
        @copy($cpt, $cp . '/upload/index.html');
        mkdir($cp . '/upload/forum', $perm);
        @copy($cpt, $cp . '/upload/forum/index.html');
        mkdir($cp . '/upload/forum/images', $perm);
        @copy($cpt, $cp . '/upload/forum/images/index.html');
        mkdir($cp . '/upload/test', $perm);
        @copy($cpt, $cp . '/upload/test/index.html');
        mkdir($cp . '/upload/blog', $perm);
        @copy($cpt, $cp . '/upload/blog/index.html');
        mkdir($cp . '/upload/learning_path', $perm);
        @copy($cpt, $cp . '/upload/learning_path/index.html');
        mkdir($cp . '/upload/learning_path/images', $perm);
        @copy($cpt, $cp . '/upload/learning_path/images/index.html');
        mkdir($cp . '/upload/calendar', $perm);
        @copy($cpt, $cp . '/upload/calendar/index.html');
        mkdir($cp . '/upload/calendar/images', $perm);
        @copy($cpt, $cp . '/upload/calendar/images/index.html');
        mkdir($cp . '/work', $perm);
        @copy($cpt, $cp . '/work/index.html');
        mkdir($cp . '/upload/announcements', $perm);
        @copy($cpt, $cp . '/upload/announcements/index.html');
        mkdir($cp . '/upload/announcements/images', $perm);
        @copy($cpt, $cp . '/upload/announcements/images/index.html');

        //Oral expression question type
        mkdir($cp . '/exercises', $perm);
        @copy($cpt, $cp . '/exercises/index.html');

        // Create .htaccess in the dropbox directory.
        $fp = fopen($cp . '/dropbox/.htaccess', 'w');
        fwrite($fp, "AuthName AllowLocalAccess
                       AuthType Basic

                       order deny,allow
                       deny from all

                       php_flag zlib.output_compression off");
        fclose($fp);

        // Build index.php of the course.
        $fd = fopen($cp . '/index.php', 'w');

        // str_replace() removes \r that cause squares to appear at the end of each line
        //@todo fix the harcoded include
        $string = str_replace("\r", "", "<?" . "php
        \$cidReq = \"$course_code\";
        \$dbname = \"$course_code\";

        include(\"".api_get_path(SYS_CODE_PATH)."course_home/course_home.php\");
        ?>");
        fwrite($fd, $string);
        @chmod($cp . '/index.php',$perm_file);
        return 0;
    }

    static function get_course_tables() {
        $tables = array();

        $tables[]= 'tool';
        $tables[]= 'tool_intro';
        $tables[]= 'group_info';
        $tables[]= 'group_category';
        $tables[]= 'group_rel_user';
        $tables[]= 'group_rel_tutor';
        $tables[]= 'item_property';
        $tables[]= 'userinfo_content';
        $tables[]= 'userinfo_def';
        $tables[]= 'course_description';
        $tables[]= 'calendar_event';
        $tables[]= 'calendar_event_repeat';
        $tables[]= 'calendar_event_repeat_not';
        $tables[]= 'calendar_event_attachment';
        $tables[]= 'announcement';
        $tables[]= 'announcement_attachment';
        $tables[]= 'resource';
        $tables[]= 'student_publication';
        $tables[]= 'student_publication_assignment';
        $tables[]= 'document';
        $tables[]= 'forum_category';
        $tables[]= 'forum_forum';
        $tables[]= 'forum_thread';
        $tables[]= 'forum_post';
        $tables[]= 'forum_mailcue';
        $tables[]= 'forum_attachment';
        $tables[]= 'forum_notification';
        $tables[]= 'forum_thread_qualify';
        $tables[]= 'forum_thread_qualify_log';
        $tables[]= 'link';
        $tables[]= 'link_category';
        $tables[]= 'online_connected';
        $tables[]= 'online_link';
        $tables[]= 'chat_connected';
        $tables[]= 'quiz';
        $tables[]= 'quiz_rel_question';
        $tables[]= 'quiz_question';
        $tables[]= 'quiz_answer';
        $tables[]= 'quiz_question_option';
        $tables[]= 'quiz_question_category';
        $tables[]= 'quiz_question_rel_category';
        $tables[]= 'dropbox_post';
        $tables[]= 'dropbox_file';
        $tables[]= 'dropbox_person';
        $tables[]= 'dropbox_category';
        $tables[]= 'dropbox_feedback';
        $tables[]= 'lp';
        $tables[]= 'lp_item';
        $tables[]= 'lp_view';
        $tables[]= 'lp_item_view';
        $tables[]= 'lp_iv_interaction';
        $tables[]= 'lp_iv_objective';
        $tables[]= 'blog';
        $tables[]= 'blog_comment';
        $tables[]= 'blog_post';
        $tables[]= 'blog_rating';
        $tables[]= 'blog_rel_user';
        $tables[]= 'blog_task';
        $tables[]= 'blog_task_rel_user';
        $tables[]= 'blog_attachment';
        $tables[]= 'permission_group';
        $tables[]= 'permission_user';
        $tables[]= 'permission_task';
        $tables[]= 'role';
        $tables[]= 'role_group';
        $tables[]= 'role_permissions';
        $tables[]= 'role_user';
        $tables[]= 'survey';
        $tables[]= 'survey_question';
        $tables[]= 'survey_question_option';
        $tables[]= 'survey_invitation';
        $tables[]= 'survey_answer';
        $tables[]= 'survey_group';
        $tables[]= 'wiki';
        $tables[]= 'wiki_conf';
        $tables[]= 'wiki_discuss';
        $tables[]= 'wiki_mailcue';
        $tables[]= 'course_setting';
        $tables[]= 'glossary';
        $tables[]= 'notebook';
        $tables[]= 'attendance';
        $tables[]= 'attendance_sheet';
        $tables[]= 'attendance_calendar';
        $tables[]= 'attendance_result';
        $tables[]= 'attendance_sheet_log';
        $tables[]= 'thematic';
        $tables[]= 'thematic_plan';
        $tables[]= 'thematic_advance';
        $tables[]= 'metadata';
        return $tables;
    }

    static function browse_folders($path, $files, $media) {
        if ($media == 'images') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        }
        if ($media == 'audio') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/audio/';
        }
        if ($media == 'flash') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/flash/';
        }
        if ($media == 'video') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/video/';
        }
        if ($media == 'certificates') {
            $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/certificates/';
        }
        if (is_dir($path)) {
            $handle = opendir($path);
            while (false !== ($file = readdir($handle))) {
                if (is_dir($path.$file) && strpos($file, '.') !== 0) {
                    $files[]['dir'] = str_replace($code_path, '', $path.$file.'/');
                    $files = self::browse_folders($path.$file.'/', $files, $media);
                } elseif (is_file($path.$file) && strpos($file, '.') !== 0) {
                    $files[]['file'] = str_replace($code_path, '', $path.$file);
                }
            }
        }
        return $files;
    }

    static function sort_pictures($files, $type) {
        $pictures = array();
        foreach ($files as $key => $value){
            if ($value[$type] != '') {
                $pictures[][$type] = $value[$type];
            }
        }
        return $pictures;
    }

    /**
     * Fills the course repository with some example content.
     * @version	 1.2
     * @deprecated this function has been merged into the fill_db_course
     */
    static function fill_course_repository($course_repository, $fill_with_exemplary_content = null) {

        if (is_null($fill_with_exemplary_content)) {
            $fill_with_exemplary_content = api_get_setting('example_material_course_creation') != 'false';
        }

        $default_document_array = array();

        if ($fill_with_exemplary_content) {

            $sys_course_path = api_get_path(SYS_COURSE_PATH);

            $perm = api_get_permissions_for_new_directories();
            $perm_file = api_get_permissions_for_new_files();

            $img_code_path   = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
            $audio_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/audio/';
            $flash_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/flash/';
            $video_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/video/';
            $cert_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/certificates/';

            $course_documents_folder_images = $sys_course_path.$course_repository.'/document/images/gallery/';
            $course_documents_folder_audio = $sys_course_path.$course_repository.'/document/audio/';
            $course_documents_folder_flash = $sys_course_path.$course_repository.'/document/flash/';
            $course_documents_folder_video = $sys_course_path.$course_repository.'/document/video/';
            $course_documents_folder_cert = $sys_course_path.$course_repository.'/document/certificates/';

            /* Images */
            $files = array();

            $files = self::browse_folders($img_code_path, $files, 'images');

            $pictures_array = self::sort_pictures($files, 'dir');
            $pictures_array = array_merge($pictures_array, self::sort_pictures($files, 'file'));

            if (!is_dir($course_documents_folder_images)) {
                mkdir($course_documents_folder_images,$perm);
            }

            $handle = opendir($img_code_path);

            foreach ($pictures_array as $key => $value) {
                if ($value['dir'] != '') {
                    mkdir($course_documents_folder_images.$value['dir'], $perm);
                }
                if ($value['file'] != '') {
                    copy($img_code_path.$value['file'], $course_documents_folder_images.$value['file']);
                    chmod($course_documents_folder_images.$value['file'], $perm_file);
                }
            }

            // Trainer thumbnails fix.

            $path_thumb = mkdir($course_documents_folder_images.'trainer/.thumbs', $perm);
            $handle = opendir($img_code_path.'trainer/.thumbs/');

            while (false !== ($file = readdir($handle))) {
                if (is_file($img_code_path.'trainer/.thumbs/'.$file)) {
                    copy($img_code_path.'trainer/.thumbs/'.$file, $course_documents_folder_images.'trainer/.thumbs/'.$file);
                    chmod($course_documents_folder_images.'trainer/.thumbs/'.$file, $perm_file);
                }
            }

            $default_document_array['images'] = $pictures_array;

            /* Audio */
            $files = array();

            $files = self::browse_folders($audio_code_path, $files, 'audio');

            $audio_array = self::sort_pictures($files, 'dir');
            $audio_array = array_merge($audio_array, self::sort_pictures($files, 'file'));

            if (!is_dir($course_documents_folder_audio)) {
                mkdir($course_documents_folder_audio, $perm);
            }

            $handle = opendir($audio_code_path);

            foreach ($audio_array as $key => $value){

                if ($value['dir'] != '') {
                    mkdir($course_documents_folder_audio.$value['dir'], $perm);
                }
                if ($value['file'] != '') {
                    copy($audio_code_path.$value['file'], $course_documents_folder_audio.$value['file']);
                    chmod($course_documents_folder_audio.$value['file'], $perm_file);
                }

            }
            $default_document_array['audio'] = $audio_array;

            /*
             * Flash
             */
            $files = array();

            $files = self::browse_folders($flash_code_path, $files, 'flash');

            $flash_array = self::sort_pictures($files, 'dir');
            $flash_array = array_merge($flash_array, self::sort_pictures($files, 'file'));

            if (!is_dir($course_documents_folder_flash)) {
                mkdir($course_documents_folder_flash, $perm);
            }

            $handle = opendir($flash_code_path);

            foreach ($flash_array as $key => $value) {

                if ($value['dir'] != '') {
                    mkdir($course_documents_folder_flash.$value['dir'], $perm);
                }
                if ($value['file'] != '') {
                    copy($flash_code_path.$value['file'], $course_documents_folder_flash.$value['file']);
                    chmod($course_documents_folder_flash.$value['file'], $perm_file);
                }

            }
            $default_document_array['flash'] = $flash_array;

            /* Video */
            $files = self::browse_folders($video_code_path, $files, 'video');

            $video_array = self::sort_pictures($files, 'dir');
            $video_array = array_merge($video_array, self::sort_pictures($files, 'file'));

            if (!is_dir($course_documents_folder_video)) {
                mkdir($course_documents_folder_video, $perm);
            }

            $handle = opendir($video_code_path);

            foreach ($video_array as $key => $value) {

                if ($value['dir'] != '') {
                    @mkdir($course_documents_folder_video.$value['dir'], $perm);
                }
                if ($value['file'] != '') {
                    copy($video_code_path.$value['file'], $course_documents_folder_video.$value['file']);
                    chmod($course_documents_folder_video.$value['file'], $perm_file);
                }

            }
            $default_document_array['video'] = $video_array;

            /* Certificates */
            $files = self::browse_folders($cert_code_path, $files, 'certificates');
            $cert_array = self::sort_pictures($files, 'dir'); //only one file so far
            $cert_array = array_merge($cert_array, self::sort_pictures($files, 'file'));
            if (!is_dir($course_documents_folder_cert)) {
                mkdir($course_documents_folder_cert, $perm);
            }
            $handle = opendir($cert_code_path);
            foreach ($cert_array as $key => $value) {

                if ($value['dir'] != '') {
                    @mkdir($course_documents_folder_cert.$value['dir'], $perm);
                }
                if ($value['file'] != '') {
                    copy($cert_code_path.$value['file'], $course_documents_folder_cert.$value['file']);
                    chmod($course_documents_folder_cert.$value['file'], $perm_file);
                }

            }
            $default_document_array['cert'] = $cert_array;

        }
        return $default_document_array;
    }


    /**
     * Fills the course database with some required content and example content.
     * @version 1.2
     */
    static function fill_db_course($course_id, $course_repository, $language, $fill_with_exemplary_content = null) {
        if (is_null($fill_with_exemplary_content)) {
            $fill_with_exemplary_content = api_get_setting('example_material_course_creation') != 'false';
        }
        $course_id = intval($course_id);

        if (empty($course_id)) {
            return false;
        }
        $now = api_get_utc_datetime(time());

        $tbl_course_homepage 	= Database::get_course_table(TABLE_TOOL_LIST);
        $TABLEINTROS 			= Database::get_course_table(TABLE_TOOL_INTRO);
        $TABLEGROUPCATEGORIES 	= Database::get_course_table(TABLE_GROUP_CATEGORY);
        $TABLEITEMPROPERTY 		= Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLETOOLAGENDA 		= Database::get_course_table(TABLE_AGENDA);
        $TABLETOOLANNOUNCEMENTS = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $TABLETOOLDOCUMENT 		= Database::get_course_table(TABLE_DOCUMENT);
        $TABLETOOLLINK 			= Database::get_course_table(TABLE_LINK);
        $TABLEQUIZ 				= Database::get_course_table(TABLE_QUIZ_TEST);
        $TABLEQUIZQUESTION 		= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TABLEQUIZQUESTIONLIST 	= Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TABLEQUIZANSWERSLIST 	= Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TABLESETTING 			= Database::get_course_table(TABLE_COURSE_SETTING);

        $TABLEFORUMCATEGORIES 	= Database::get_course_table(TABLE_FORUM_CATEGORY);
        $TABLEFORUMS 			= Database::get_course_table(TABLE_FORUM);
        $TABLEFORUMTHREADS 		= Database::get_course_table(TABLE_FORUM_THREAD);
        $TABLEFORUMPOSTS 		= Database::get_course_table(TABLE_FORUM_POST);
        $TABLEGRADEBOOK 		= Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $TABLEGRADEBOOKLINK		= Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $TABLEGRADEBOOKCERT		= Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);

        include api_get_path(SYS_CODE_PATH).'lang/english/create_course.inc.php';
        $file_to_include = api_get_path(SYS_CODE_PATH).'lang/'.$language.'/create_course.inc.php';

        if (file_exists($file_to_include)) {
            include $file_to_include;
        }

        $visible_for_all = 1;
        $visible_for_course_admin = 0;
        $visible_for_platform_admin = 2;

        /*    Course tools  */

        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_COURSE_DESCRIPTION . "','course_description/','info.gif','".string2binary(api_get_setting('course_create_active_tools', 'course_description')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_CALENDAR_EVENT . "','calendar/agenda.php','agenda.gif','".string2binary(api_get_setting('course_create_active_tools', 'agenda')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_DOCUMENT . "','document/document.php','folder_document.gif','".string2binary(api_get_setting('course_create_active_tools', 'documents')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_LEARNPATH . "','newscorm/lp_controller.php','scorms.gif','".string2binary(api_get_setting('course_create_active_tools', 'learning_path')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_LINK . "','link/link.php','links.gif','".string2binary(api_get_setting('course_create_active_tools', 'links')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_QUIZ . "','exercice/exercice.php','quiz.gif','".string2binary(api_get_setting('course_create_active_tools', 'quiz')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_ANNOUNCEMENT . "','announcements/announcements.php','valves.gif','".string2binary(api_get_setting('course_create_active_tools', 'announcements')) . "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_FORUM . "','forum/index.php','forum.gif','".string2binary(api_get_setting('course_create_active_tools', 'forums')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_DROPBOX . "','dropbox/index.php','dropbox.gif','".string2binary(api_get_setting('course_create_active_tools', 'dropbox')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_USER . "','user/user.php','members.gif','".string2binary(api_get_setting('course_create_active_tools', 'users')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GROUP . "','group/group.php','group.gif','".string2binary(api_get_setting('course_create_active_tools', 'groups')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_CHAT . "','chat/chat.php','chat.gif','".string2binary(api_get_setting('course_create_active_tools', 'chat')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_STUDENTPUBLICATION . "','work/work.php','works.gif','".string2binary(api_get_setting('course_create_active_tools', 'student_publications')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_SURVEY."','survey/survey_list.php','survey.gif','".string2binary(api_get_setting('course_create_active_tools', 'survey')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_WIKI ."','wiki/index.php','wiki.gif','".string2binary(api_get_setting('course_create_active_tools', 'wiki')) . "','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GRADEBOOK."','gradebook/index.php','gradebook.gif','".string2binary(api_get_setting('course_create_active_tools', 'gradebook')). "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GLOSSARY."','glossary/index.php','glossary.gif','".string2binary(api_get_setting('course_create_active_tools', 'glossary')). "','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_NOTEBOOK."','notebook/index.php','notebook.gif','".string2binary(api_get_setting('course_create_active_tools', 'notebook'))."','0','squaregrey.gif','NO','_self','interaction','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_ATTENDANCE."','attendance/index.php','attendance.gif','".string2binary(api_get_setting('course_create_active_tools', 'attendances'))."','0','squaregrey.gif','NO','_self','authoring','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_COURSE_PROGRESS."','course_progress/index.php','course_progress.gif','".string2binary(api_get_setting('course_create_active_tools', 'course_progress'))."','0','squaregrey.gif','NO','_self','authoring','0','','')");

        if (api_get_setting('service_visio', 'active') == 'true') {
            $mycheck = api_get_setting('service_visio', 'visio_host');
            if (!empty($mycheck)) {
                Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_VISIO_CONFERENCE . "','conference/index.php?type=conference','visio_meeting.gif','1','0','squaregrey.gif','NO','_self','interaction','0','','')");
                Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_VISIO_CLASSROOM . "','conference/index.php?type=classroom','visio.gif','1','0','squaregrey.gif','NO','_self','authoring','0','','')");
            }
        }

        if (api_get_setting('search_enabled') == 'true') {
            Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_SEARCH. "','search/','info.gif','".string2binary(api_get_setting('course_create_active_tools', 'enable_search')) . "','0','search.gif','NO','_self','authoring','0','','')");
        }

        // Smartblogs (Kevin Van Den Haute :: kevin@develop-it.be)
        $sql = "INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL,'" . TOOL_BLOGS . "','blog/blog_admin.php','blog_admin.gif','" . string2binary(api_get_setting('course_create_active_tools', 'blogs')) . "','1','squaregrey.gif','NO','_self','admin','0','','')";
        Database::query($sql);
        // end of Smartblogs

        /*  Course homepage tools for course admin only    */

        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '".TOOL_TRACKING . "','tracking/courseLog.php','statistics.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '".TOOL_COURSE_SETTING . "','course_info/infocours.php','reference.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0','','')");
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '".TOOL_COURSE_MAINTENANCE."','course_info/maintenance.php','backup.gif','$visible_for_course_admin','1','','NO','_self', 'admin','0','','')");

        /*    course_setting table (courseinfo tool)   */

        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_manager_on_new_doc',0,'work')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_on_new_doc_dropbox',0,'dropbox')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_edit_agenda',0,'agenda')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_edit_announcement',0,'announcement')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_manager_on_new_quiz',1,'quiz')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_image_forum',1,'forum')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'course_theme','','theme')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_learning_path_theme','1','theme')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_open_chat_window',1,'chat')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_to_teacher_on_new_user_in_course',0,'registration')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_view_user_list',1,'user')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'display_info_advance_inside_homecourse',1,'thematic_advance')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_students_on_new_homework',0,'work')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'enable_lp_auto_launch',0,'learning_path')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'pdf_export_watermark_text','','learning_path')");
        Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_public_certificates','','certificates')");


        /* Course homepage tools for platform admin only */

        /* Group tool */

        Database::query("INSERT INTO $TABLEGROUPCATEGORIES  (c_id,  id , title , description , max_student , self_reg_allowed , self_unreg_allowed , groups_per_user , display_order )
                VALUES ($course_id, '2', '".lang2db(get_lang('DefaultGroupCategory')) . "', '', '8', '0', '0', '0', '0');");

        /*    Example Material  */
        global $language_interface;
        $language_interface = !empty($language_interface) ? $language_interface : api_get_setting('platformLanguage');


        // Example material should be in the same language as the course is.
        $language_interface_original = $language_interface;
        $language_interface          = $language;

        //Share folder
        Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/shared_folder','".get_lang('UserFolders')."','folder','0')");
        $example_doc_id = Database :: insert_id();
        Database::query("INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

        //Chat folder
        Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/chat_files','".get_lang('ChatFiles')."','folder','0')");
        $example_doc_id = Database :: insert_id();
        Database::query("INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();

        $chat_path = $sys_course_path.$course_repository.'/document/chat_files';

        if (!is_dir($chat_path)) {
            @mkdir($chat_path, api_get_permissions_for_new_directories());
        }

        /*    Documents   */
        if ($fill_with_exemplary_content) {

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id,path,title,filetype,size) VALUES ($course_id,'/images','".get_lang('Images')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/images/gallery','".get_lang('DefaultCourseImages')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/audio','".get_lang('Audio')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/flash','".get_lang('Flash')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/video','".get_lang('Video')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/certificates','".get_lang('Certificates')."','folder','0')");
            $example_doc_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

            // FILL THE COURSE DOCUMENT WITH DEFAULT COURSE PICTURES

            $folders_to_copy_from_default_course =  array(
                'images',
                'audio',
                'flash',
                'video',
                'certificates',
            );

            $default_course_path = api_get_path(SYS_CODE_PATH).'default_course_document/';

            $default_document_array = array();
            foreach ($folders_to_copy_from_default_course as $folder) {
                $default_course_folder_path = $default_course_path.$folder.'/';
                $files = browse_folders($default_course_folder_path, array(), $folder);
                $sorted_array = sort_pictures($files, 'dir');
                $sorted_array = array_merge($sorted_array, sort_pictures($files, 'file'));
                $default_document_array[$folder] = $sorted_array;
            }

            //echo '<pre>'; print_r($default_document_array);exit;

            //Light protection (adding index.html in every document folder)
            $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n <head>\n <meta charset=\"utf-8\">\n <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";

            $example_cert_id = 0;
            if (is_array($default_document_array) && count($default_document_array) > 0) {
                foreach ($default_document_array as $media_type => $array_media) {
                    $path_documents = "/$media_type/";

                    //hack until feature #5242 is implemented
                    if ($media_type == 'images') {
                        $media_type = 'images/gallery';
                        $images_folder = $sys_course_path.$course_repository."/document/images/";

                        if (!is_dir($images_folder)) {
                            //Creating index.html
                            mkdir($images_folder, $perm);
                            $fd = fopen($images_folder.'index.html', 'w');
                            fwrite($fd, $htmlpage);
                            @chmod($images_folder.'index.html', $perm_file);
                        }
                    }

                    $course_documents_folder = $sys_course_path.$course_repository."/document/$media_type/";
                    $default_course_path = api_get_path(SYS_CODE_PATH).'default_course_document'.$path_documents;

                    //echo 'try '.$course_documents_folder; echo '<br />';

                    if (!is_dir($course_documents_folder)) {
                        //Creating index.html
                        mkdir($course_documents_folder, $perm);
                        $fd = fopen($course_documents_folder.'index.html', 'w');
                        fwrite($fd, $htmlpage);
                        @chmod($course_documents_folder.'index.html', $perm_file);
                    }

                    if (is_array($array_media) && count($array_media)>0) {
                        foreach ($array_media as $key => $value) {
                            if (isset($value['dir']) && !empty($value['dir'])) {

                                if (!is_dir($course_documents_folder.$value['dir'])) {
                                    //Creating folder
                                    mkdir($course_documents_folder.$value['dir'], $perm);

                                    //Creating index.html (for light protection)
                                    $index_html = $course_documents_folder.$value['dir'].'/index.html';
                                    $fd = fopen($index_html, 'w');
                                    fwrite($fd, $htmlpage);
                                    @chmod($index_html, $perm_file);

                                    //Inserting folder in the DB
                                    $folder_path = substr($value['dir'], 0, strlen($value['dir']) - 1);
                                    $temp = explode('/', $folder_path);
                                    $title = $temp[count($temp)-1];

                                    //hack until feature #5242 is implemented
                                    if ($title == 'gallery') {
                                        $title = get_lang('DefaultCourseImages');
                                    }

                                    if ($media_type == 'images/gallery') {
                                        $folder_path = 'gallery/'.$folder_path;
                                    }

                                    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'$path_documents".$folder_path."','".$title."','folder','0')");
                                    $image_id = Database :: insert_id();
                                    Database::query("INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,0)");
                                }
                            }

                            if (isset($value['file']) && !empty($value['file'])) {
                                if (!file_exists($course_documents_folder.$value['file'])) {
                                    //Copying file
                                    copy($default_course_path.$value['file'], $course_documents_folder.$value['file']);
                                    chmod($course_documents_folder.$value['file'], $perm_file);
                                    //echo $default_course_path.$value['file']; echo ' - '; echo $course_documents_folder.$value['file']; echo '<br />';
                                    $temp = explode('/', $value['file']);
                                    $file_size = filesize($course_documents_folder.$value['file']);

                                    //hack until feature #5242 is implemented
                                    if ($media_type == 'images/gallery') {
                                        $value["file"] = 'gallery/'.$value["file"];
                                    }

                                    //Inserting file in the DB
                                    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'$path_documents".$value["file"]."','".$temp[count($temp)-1]."','file','$file_size')");
                                    $image_id = Database :: insert_id();
                                    if ($path_documents.$value['file'] == '/certificates/default.html') {
                                      $example_cert_id = $image_id;
                                    }
                                    Database::query("INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,1)");
                                }
                            }
                        }
                    }
                }
            }

            /* Agenda tool */

            Database::query("INSERT INTO $TABLETOOLAGENDA  VALUES ($course_id, NULL, '".lang2db(get_lang('AgendaCreationTitle')) . "', '".lang2db(get_lang('AgendaCreationContenu')) . "', now(), now(), NULL, 0, 0)");
            // We need to add the item properties too!
            $insert_id = Database :: insert_id();
            $sql = "INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id, '" . TOOL_CALENDAR_EVENT . "',1,NOW(),NOW(),$insert_id,'AgendaAdded',1,0,NULL,1)";
            Database::query($sql);

            /*  Links tool */

            $add_google_link_sql = "INSERT INTO $TABLETOOLLINK  (c_id, url, title, description, category_id, display_order, on_homepage, target)
                    VALUES ($course_id, 'http://www.google.com','Google','".lang2db(get_lang('Google')) . "','0','0','0','_self')";
            Database::query($add_google_link_sql);

            // We need to add the item properties too!
            $insert_id = Database :: insert_id();
            $sql = "INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                    VALUES ($course_id, '" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
            Database::query($sql);

            $add_wikipedia_link_sql = "INSERT INTO $TABLETOOLLINK  (c_id, url, title, description, category_id, display_order, on_homepage, target)
                    VALUES ($course_id, 'http://www.wikipedia.org','Wikipedia','".lang2db(get_lang('Wikipedia')) . "','0','1','0','_self')";
            Database::query($add_wikipedia_link_sql);

            // We need to add the item properties too!
            $insert_id = Database :: insert_id();
            $sql = "INSERT INTO $TABLEITEMPROPERTY  (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
            Database::query($sql);

            /* Annoucement tool */

            $sql = "INSERT INTO $TABLETOOLANNOUNCEMENTS  (c_id, title,content,end_date,display_order,email_sent)
                    VALUES ($course_id, '".lang2db(get_lang('AnnouncementExampleTitle')) . "', '".lang2db(get_lang('AnnouncementEx')) . "', NOW(), '1','0')";
            Database::query($sql);

            // We need to add the item properties too!
            $insert_id = Database :: insert_id();
            $sql = "INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                    VALUES ($course_id, '" . TOOL_ANNOUNCEMENT . "',1,NOW(),NOW(),$insert_id,'AnnouncementAdded',1,0,NULL,1)";
            Database::query($sql);

            /* Introduction text */

            $intro_text='<p style="text-align: center;">
                            <img src="'.api_get_path(REL_CODE_PATH).'img/mascot.png" alt="Mr. Chamilo" title="Mr. Chamilo" />
                            <h2>'.lang2db(get_lang('IntroductionText')).'</h2>
                         </p>';
            Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_COURSE_HOMEPAGE . "','".$intro_text."', 0)");
            Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_STUDENTPUBLICATION . "','".lang2db(get_lang('IntroductionTwo')) . "', 0)");

            // Wiki intro
            $intro_wiki='<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="110" valign="top" align="left"></td><td valign="top" align="left">'.lang2db(get_lang('IntroductionWiki')).'</td></tr></table>';
            Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_WIKI . "','".$intro_wiki. "', 0)");

            /*  Exercise tool */

            Database::query("INSERT INTO $TABLEQUIZANSWERSLIST (c_id, id, question_id, answer, correct, comment, ponderation, position) VALUES ($course_id, '1', '1', '".lang2db(get_lang('Ridiculise')) . "', '0', '".lang2db(get_lang('NoPsychology')) . "', '-5', '1')");
            Database::query("INSERT INTO $TABLEQUIZANSWERSLIST (c_id, id, question_id, answer, correct, comment, ponderation, position) VALUES ($course_id,  '2', '1', '".lang2db(get_lang('AdmitError')) . "', '0', '".lang2db(get_lang('NoSeduction')) . "', '-5', '2')");
            Database::query("INSERT INTO $TABLEQUIZANSWERSLIST (c_id, id, question_id, answer, correct, comment, ponderation, position) VALUES ($course_id,  '3', '1', '".lang2db(get_lang('Force')) . "', '1', '".lang2db(get_lang('Indeed')) . "', '5', '3')");
            Database::query("INSERT INTO $TABLEQUIZANSWERSLIST (c_id, id, question_id, answer, correct, comment, ponderation, position) VALUES ($course_id,  '4', '1', '".lang2db(get_lang('Contradiction')) . "', '1', '".lang2db(get_lang('NotFalse')) . "', '5', '4')");

            $html=Database::escape_string('<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="110" valign="top" align="left"><img src="'.api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_dokeos/thinking.jpg"></td><td valign="top" align="left">'.get_lang('Antique').'</td></tr></table>');

            Database::query('INSERT INTO '.$TABLEQUIZ .
              ' (c_id, title, description, type, random, random_answers, active, results_disabled ) ' .
              ' VALUES ('.$course_id.', "'.lang2db(get_lang('ExerciceEx')) . '",' .
              ' "'.$html.'", "1", "0", "0", "1", "0")');
            $exercise_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEQUIZQUESTIONLIST  (c_id, id, question, description, ponderation, position, type, picture, level)
                            VALUES ( '.$course_id.', '1', '".lang2db(get_lang('SocraticIrony')) . "', '".lang2db(get_lang('ManyAnswers')) . "', '10', '1', '2','',1)");
            Database::query("INSERT INTO $TABLEQUIZQUESTION  (c_id, question_id, exercice_id, question_order) VALUES ('.$course_id.', 1,1,1)");

            /* Forum tool */

            Database::query("INSERT INTO $TABLEFORUMCATEGORIES VALUES ($course_id, 1,'".lang2db(get_lang('ExampleForumCategory'))."', '', 1, 0, 0)");
            $insert_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                            VALUES ($course_id, 'forum_category',1,NOW(),NOW(),$insert_id,'ForumCategoryAdded',1,0,NULL,1)");

            Database::query("INSERT INTO $TABLEFORUMS (c_id, forum_title, forum_comment, forum_threads,forum_posts,forum_last_post,forum_category, allow_anonymous, allow_edit,allow_attachments, allow_new_threads,default_view,forum_of_group,forum_group_public_private, forum_order,locked,session_id )
                            VALUES ($course_id, '".lang2db(get_lang('ExampleForum'))."', '', 0, 0, 0, 1, 0, 1, '0', 1, 'flat','0', 'public', 1, 0,0)");
            $insert_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                             VALUES ($course_id, '".TOOL_FORUM."', 1,NOW(),NOW(),$insert_id,'ForumAdded',1,0,NULL,1)");

            Database::query("INSERT INTO $TABLEFORUMTHREADS (c_id, thread_id, thread_title, forum_id, thread_replies, thread_poster_id, thread_poster_name, thread_views, thread_last_post, thread_date, locked, thread_qualify_max, session_id)
                            VALUES ($course_id, 1, '".lang2db(get_lang('ExampleThread'))."', 1, 0, 1, '', 0, 1, NOW(), 0, 10, 0)");
            $insert_id = Database :: insert_id();
            Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                            VALUES ($course_id, 'forum_thread',1,NOW(),NOW(),$insert_id,'ForumThreadAdded',1,0,NULL,1)");

            Database::query("INSERT INTO $TABLEFORUMPOSTS VALUES ($course_id, 1, '".lang2db(get_lang('ExampleThread'))."', '".lang2db(get_lang('ExampleThreadContent'))."', 1, 1, 1, '', NOW(), 0, 0, 1)");

            /* Gradebook tool */
            $course = api_get_course_info_by_id($course_id);
            $course_code = $course['code'];
            // father gradebook
            Database::query("INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id) VALUES ('$course_code','',1,'$course_code',0,100,0,NULL,NULL,$example_cert_id)");
            $gbid = Database :: insert_id();
            Database::query("INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id) VALUES ('$course_code','',1,'$course_code',$gbid,100,1,75,NULL,$example_cert_id)");
            $gbid = Database :: insert_id();
            Database::query("INSERT INTO $TABLEGRADEBOOKLINK (type, ref_id, user_id, course_code, category_id, created_at, weight, visible, locked) VALUES (1,$exercise_id,1,'$course_code',$gbid,'$now',100,1,0)");
        }

        //Installing plugins in course
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($course_id);

        $language_interface = $language_interface_original;
        return true;
    }


    /**
     * function register_course to create a record in the course table of the main database
     * @param string    $course_sys_code
     * @param string    $course_screen_code
     * @param string    $course_repository
     * @param string    $course_db_name
     * @param string    $tutor_name
     * @param string    $category
     * @param string    $title              complete name of course
     * @param string    $course_language    lang for this course
     * @param string    $uid                uid of owner
     * @param integer                       Expiration date in unix time representation
     * @param array                         Optional array of teachers' user ID
     * @return int      0
     * @todo use an array called $params instead of lots of params
     */
    static function register_course($params) {
        global $error_msg, $firstExpirationDelay;

        $title              = $params['title'];
        $code               = $params['code'];
        $visual_code        = $params['visual_code'];
        $directory          = isset($params['directory']) ? $params['directory'] : null;
        $tutor_name         = isset($params['tutor_name']) ? $params['tutor_name'] : null;
        //$description        = $params['description'];

        $category_code      = isset($params['category_code']) ? $params['category_code'] : null;
        $course_language    = isset($params['course_language']) && !empty($params['course_language']) ? $params['course_language'] : api_get_setting('platformLanguage');
        $user_id            = empty($params['user_id']) ? api_get_user_id() : intval($params['user_id']);
        $department_name    = isset($params['department_name']) ? $params['department_name'] : null;
        $department_url     = isset($params['department_url']) ? $params['department_url'] : null;
        $disk_quota         = isset($params['disk_quota']) ? $params['disk_quota'] : null;

        if (!isset($params['visibility'])) {
            $default_course_visibility = api_get_setting('courses_default_creation_visibility');
            if (isset($default_course_visibility)) {
                $visibility         = $default_course_visibility;
            } else {
                $visibility         = COURSE_VISIBILITY_OPEN_PLATFORM;
            }
        } else {
            $visibility         = $params['visibility'];
        }
        $subscribe          = isset($params['subscribe']) ? intval($params['subscribe']) : ($visibility == COURSE_VISIBILITY_OPEN_PLATFORM ? 1 : 0);
        $unsubscribe        = isset($params['unsubscribe']) ? intval($params['unsubscribe']) : 0;
        $expiration_date    = isset($params['expiration_date']) ? $params['expiration_date'] : null;
        $teachers           = isset($params['teachers']) ? $params['teachers'] : null;
        $status             = isset($params['status']) ? $params['status'] : null;

        $TABLECOURSE		 	= Database :: get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER 		= Database :: get_main_table(TABLE_MAIN_COURSE_USER);

        $ok_to_register_course = true;

        // Check whether all the needed parameters are present.
        if (empty($code)) {
            $error_msg[] = 'courseSysCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($visual_code)) {
            $error_msg[] = 'courseScreenCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($directory)) {
            $error_msg[] = 'courseRepository is missing';
            $ok_to_register_course = false;
        }

        if (empty($title)) {
            $error_msg[] = 'title is missing';
            $ok_to_register_course = false;
        }

        if (empty($expiration_date)) {
            $expiration_date = api_get_utc_datetime(time() + $firstExpirationDelay);
        } else {
            $expiration_date = api_get_utc_datetime($expiration_date);
        }

        if ($visibility < 0 || $visibility > 3) {
            $error_msg[] = 'visibility is invalid';
            $ok_to_register_course = false;
        }

        if (empty($disk_quota)) {
            $disk_quota = api_get_setting('default_document_quotum');
        }

        $time = api_get_utc_datetime();

        if (stripos($department_url, 'http://') === false && stripos($department_url, 'https://') === false) {
            $department_url = 'http://'.$department_url;
        }
        //just in case
        if ($department_url == 'http://') {
            $department_url = '';
        }
        $course_id = 0;

        if ($ok_to_register_course) {

           // Here we must add 2 fields.
          $sql = "INSERT INTO ".$TABLECOURSE . " SET
                        code            = '".Database :: escape_string($code) . "',
                        directory       = '".Database :: escape_string($directory) . "',
                        course_language = '".Database :: escape_string($course_language) . "',
                        title           = '".Database :: escape_string($title) . "',
                        description     = '".lang2db(get_lang('CourseDescription')) . "',
                        category_code   = '".Database :: escape_string($category_code) . "',
                        visibility      = '".$visibility . "',
                        show_score      = '1',
                        disk_quota      = '".intval($disk_quota) . "',
                        creation_date   = '$time',
                        expiration_date = '".$expiration_date . "',
                        last_edit       = '$time',
                        last_visit      = NULL,
                        tutor_name      = '".Database :: escape_string($tutor_name) . "',
                        department_name = '".Database :: escape_string($department_name) . "',
                        department_url  = '".Database :: escape_string($department_url) . "',
                        subscribe       = '".intval($subscribe) . "',
                        unsubscribe     = '".intval($unsubscribe) . "',
                        visual_code     = '".Database :: escape_string($visual_code) . "'";
            Database::query($sql);
            //error_log($sql);

            $course_id  = Database::get_last_insert_id();

            if ($course_id) {

                $sort = api_max_sort_value('0', api_get_user_id());

                $i_course_sort = CourseManager :: userCourseSort($user_id, $code);
                if (!empty($user_id)) {
                    $sql = "INSERT INTO ".$TABLECOURSUSER . " SET
                                course_code     = '".Database :: escape_string($code). "',
                                user_id         = '".intval($user_id) . "',
                                status          = '1',
                                role            = '".lang2db(get_lang('Professor')) . "',
                                tutor_id        = '0',
                                sort            = '". ($i_course_sort) . "',
                                user_course_cat = '0'";
                    Database::query($sql);
                }
                if (!empty($teachers)) {
                    if (!is_array($teachers)) {
                        $teachers = array($teachers);
                    }
                    foreach ($teachers as $key) {
                        //just in case
                        if ($key == $user_id) {
                            continue;
                        }
                        if (empty($key)) {
                            continue;
                        }
                        $sql = "INSERT INTO ".$TABLECOURSUSER . " SET
                            course_code     = '".Database::escape_string($code) . "',
                            user_id         = '".Database::escape_string($key) . "',
                            status          = '1',
                            role            = '',
                            tutor_id        = '0',
                            sort            = '". ($sort +1) . "',
                            user_course_cat = '0'";
                        Database::query($sql);
                    }
                }

                // Adding the course to an URL
                if (api_is_multiple_url_enabled()) {
                    $url_id = 1;
                    if (api_get_current_access_url_id() != -1) {
                        $url_id = api_get_current_access_url_id();
                    }
                    UrlManager::add_course_to_url($code, $url_id);
                } else {
                    UrlManager::add_course_to_url($code, 1);
                }

                // Add event to the system log.
                $user_id = api_get_user_id();
                event_system(LOG_COURSE_CREATE, LOG_COURSE_CODE, $code, api_get_utc_datetime(), $user_id, $code);

                $send_mail_to_admin = api_get_setting('send_email_to_admin_when_create_course');

                // @todo Improve code to send to all current portal administrators.
                if ($send_mail_to_admin == 'true') {
                    $siteName = api_get_setting('siteName');
                    $recipient_email = api_get_setting('emailAdministrator');
                    $recipient_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'));
                    $iname = api_get_setting('Institution');
                    $subject = get_lang('NewCourseCreatedIn').' '.$siteName.' - '.$iname;
                    $message =  get_lang('Dear').' '.$recipient_name.",\n\n".get_lang('MessageOfNewCourseToAdmin').' '.$siteName.' - '.$iname."\n";
                    $message .= get_lang('CourseName').' '.$title."\n";
                    $message .= get_lang('Category').' '.$category_code."\n";
                    $message .= get_lang('Tutor').' '.$tutor_name."\n";
                    $message .= get_lang('Language').' '.$course_language;

                    @api_mail($recipient_name, $recipient_email, $subject, $message, $siteName, $recipient_email);
                }
            }
        }

        return $course_id;
    }


    /**
     * Extract properties of the files from a ZIP package, write them to disk and
     * return them as an array.
     * @todo this function seems not to be used
     * @param string        Absolute path to the ZIP file
     * @param bool          Whether the ZIP file is compressed (not implemented). Defaults to TRUE.
     * @deprecated seems not to be used
     * @return array        List of files properties from the ZIP package
     */
    static function readPropertiesInArchive($archive, $is_compressed = true) {
        include api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php';
        debug::printVar(dirname($archive), 'Zip : ');
        $uid = api_get_user_id();
        /*
        string tempnam (string dir, string prefix)
        tempnam() creates a unique temporary file in the dir directory. If the
        directory doesn't existm tempnam() will generate a filename in the system's
        temporary directory.
        Before PHP 4.0.6, the behaviour of tempnam() depended of the underlying OS.
        Under Windows, the "TMP" environment variable replaces the dir parameter;
        under Linux, the "TMPDIR" environment variable has priority, while for the
        OSes based on system V R4, the dir parameter will always be used if the
        directory which it represents exists. Consult your documentation for more
        details.
        tempnam() returns the temporary filename, or the string NULL upon failure.
        */
        $zip_file = new PclZip($archive);
        $tmp_dir_name = dirname($archive) . '/tmp'.$uid.uniqid($uid);
        if (mkdir($tmp_dir_name, api_get_permissions_for_new_directories(), true)) {
            $unzipping_state = $zip_file->extract($tmp_dir_name);
        } else {
            die ('mkdir failed');
        }
        $path_to_archive_ini = dirname($tmp_dir_name) . '/archive.ini';
        //echo $path_to_archive_ini;
        $course_properties = parse_ini_file($path_to_archive_ini);
        rmdir($tmp_dir_name);
        return $course_properties;
    }
    
    static function isAdultPlexExam($courseCode)
    {
        // Verify if it is a PLEX for adults!
        $oriCoursCode = CourseManager::get_course_extra_field_value('cs_course_id', $courseCode, '%');
        $isAdultPlex = $oriCoursCode == '50' ? true : false;
        return $isAdultPlex;
    }
} //end class CourseManager
