<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains the MigrationCustom class, which defines methods to
 * alter the data from the original database when importing it to the Chamilo
 * database
 */

/**
 * The custom migration class allows you to define rules to process data
 * during the migration
 */
class MigrationCustom
{

    const defaultAdminId = 1;
    const TRANSACTION_STATUS_TO_BE_EXECUTED = 1;
    const TRANSACTION_STATUS_SUCCESSFUL = 2;
    const TRANSACTION_STATUS_DEPRECATED = 3; //??
    const TRANSACTION_STATUS_FAILED = 4;
    const TRANSACTION_STATUS_ABANDONNED = 5;
    /**
     * Types of transaction operations read from the external databases
     */
    const TRANSACTION_TYPE_ADD_USER = 1;
    const TRANSACTION_TYPE_DEL_USER = 2;
    const TRANSACTION_TYPE_EDIT_USER = 3;
    const TRANSACTION_TYPE_SUB_USER = 4; //subscribe user to a session
    const TRANSACTION_TYPE_ADD_COURSE = 5;
    const TRANSACTION_TYPE_DEL_COURSE = 6;
    const TRANSACTION_TYPE_EDIT_COURSE = 7;
    const TRANSACTION_TYPE_ADD_SESS = 8;
    const TRANSACTION_TYPE_DEL_SESS = 9;
    const TRANSACTION_TYPE_EDIT_SESS = 10;
    const TRANSACTION_TYPE_UPD_ROOM = 11;
    const TRANSACTION_TYPE_UPD_SCHED = 12;
    const TRANSACTION_TYPE_ADD_SCHED = 13;
    const TRANSACTION_TYPE_DEL_SCHED = 14;
    const TRANSACTION_TYPE_EDIT_SCHED = 15;
    const TRANSACTION_TYPE_ADD_ROOM = 16;
    const TRANSACTION_TYPE_DEL_ROOM = 17;
    const TRANSACTION_TYPE_EDIT_ROOM = 18;
    const TRANSACTION_TYPE_ADD_BRANCH = 19;
    const TRANSACTION_TYPE_DEL_BRANCH = 20;
    const TRANSACTION_TYPE_EDIT_BRANCH = 21;
    const TRANSACTION_TYPE_ADD_FREQ = 22;
    const TRANSACTION_TYPE_DEL_FREQ = 23;
    const TRANSACTION_TYPE_EDIT_FREQ = 24;
    const TRANSACTION_TYPE_ADD_INTENS = 25;
    const TRANSACTION_TYPE_DEL_INTENS = 26;
    const TRANSACTION_TYPE_EDIT_INTENS = 27;
    const TRANSACTION_TYPE_ADD_FASE = 28;
    const TRANSACTION_TYPE_DEL_FASE = 29;
    const TRANSACTION_TYPE_EDIT_FASE = 30;

    //Gradebook
    const TRANSACTION_TYPE_ADD_NOTA = 31;
    const TRANSACTION_TYPE_DEL_NOTA = 32;
    const TRANSACTION_TYPE_EDIT_NOTA = 33;
    //Attendances
    const TRANSACTION_TYPE_ADD_ASSIST = 34;
    const TRANSACTION_TYPE_DEL_ASSIST = 35;
    const TRANSACTION_TYPE_EDIT_ASSIST = 36;

    //Teacher
    const TRANSACTION_TYPE_SEND_TEACHER_ATTENDANCE = 534;

    public static $attendStatus = array(
        'DEF' => 4,
        'AUS' => 0,
        'PRE' => 1,
        'TAR' => 2,
        'T45' => 3,
    );

    // Simple repeat operations check (in particular, subscriptions cannot
    // be considered cleanable)
    public static $cleanableActions = array(
        self::TRANSACTION_TYPE_ADD_USER,
        self::TRANSACTION_TYPE_DEL_USER,
        self::TRANSACTION_TYPE_EDIT_USER,
        self::TRANSACTION_TYPE_ADD_COURSE,
        self::TRANSACTION_TYPE_DEL_COURSE,
        self::TRANSACTION_TYPE_EDIT_COURSE,
        self::TRANSACTION_TYPE_ADD_SESS,
        self::TRANSACTION_TYPE_DEL_SESS,
        self::TRANSACTION_TYPE_EDIT_SESS,
        self::TRANSACTION_TYPE_ADD_SCHED,
        self::TRANSACTION_TYPE_DEL_SCHED,
        self::TRANSACTION_TYPE_ADD_ROOM,
        self::TRANSACTION_TYPE_DEL_ROOM,
        self::TRANSACTION_TYPE_EDIT_ROOM,
        self::TRANSACTION_TYPE_ADD_BRANCH,
        self::TRANSACTION_TYPE_DEL_BRANCH,
        self::TRANSACTION_TYPE_EDIT_BRANCH,
        self::TRANSACTION_TYPE_ADD_FREQ,
        self::TRANSACTION_TYPE_DEL_FREQ,
        self::TRANSACTION_TYPE_EDIT_FREQ,
        self::TRANSACTION_TYPE_ADD_INTENS,
        self::TRANSACTION_TYPE_DEL_INTENS,
        self::TRANSACTION_TYPE_EDIT_INTENS,
        self::TRANSACTION_TYPE_ADD_FASE,
        self::TRANSACTION_TYPE_DEL_FASE,
        self::TRANSACTION_TYPE_EDIT_FASE,
    );

    /**
     * Gets all the possible transaction statutes
     * @return array    List of data from the branch_transaction_status table
     */
    static function getTransactionStatusList()
    {
        $table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_STATUS);
        return Database::select("*", $table);
    }

    /**
     * The only required method is the 'none' method, which will not trigger
     * any process at all
     * @param mixed Data
     * @return mixed The unaltered data
     */
    static function none($data)
    {
        return $data;
    }

    /**
     * Join elements of a string to form the final display of the 'horario' field
     * @param mixed $data
     * @param array $omigrate
     * @param array $row_data Contains the 'horario' field details
     * @return string A well formed schedule string ready to be displayed
     */
    static function joinScheduleString($data, &$omigrate, $row_data)
    {
        return '('.$row_data['chrIdHorario'].') '.$row_data['chrHoraInicial'].' '.$row_data['chrHoraFinal'];
    }

    /**
     * Converts 2008-02-01 12:20:20.540 to  2008-02-01 12:20:20
     * @param   string $date A date, in 'yyyy-mm-dd hh:mm:ss.mmm' to be cut after the seconds value
     * @return string A string in the 'yyyy-mm-dd hh:mm:ss' format
     */
    static function cleanDateTime($date)
    {
        return substr($date, 0, 19);
    }

    /**
     * Converts 2009-09-30T00:00:00-05:00 to 2009-09-30 00:00:00
     * @param string $date Date
     * @return string A strin in the 'yyyy-mm-dd hh:mm:ss' format
     */
    static function cleanDateTimeFromWS($date)
    {
        $pre_clean = self::cleanDateTime($date, 0, 19);
        return str_replace('T', ' ', $pre_clean);
    }

    /**
     * Transforms the uid identifiers recovered from MSSQL to a string
     * @param string Field name
     * @return string SQL select string to include in the final select
     */
    static function sqlAlterUnhash50($field)
    {
        $as_field = explode('.', $field);
        if (isset($as_field[1])) {
            $as_field = $as_field[1];
        } else {
            $as_field = $field;
        }
        return " cast( $field  as varchar(50)) as $as_field ";
    }

    /**
     * Proxy for utf8_encode() function
     * @param string $value
     * @return string
     */
    static function cleanUTF8($value)
    {
        return utf8_encode($value);
    }

    /**
     * Proxy for the sha1() function
     * @param string $value
     * @return string
     */
    static function makeSHA1($value)
    {
        return sha1($value);
    }

    /**
     * Removes any weird UTF-8 character from the session name
     * @param mixed $value
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @param array $row_data
     * @return string
     */
    static function cleanSessionName($value, &$omigrate, $row_data)
    {
        return self::cleanUTF8($row_data['session_name']);
    }

    /**
     * Gets the real course (int) ID from an external uidIdCurso param
     * @param string $data
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return mixed Course ID
     */
    static function getRealCourseCode($data, &$omigrate = null)
    {
        if (is_array($omigrate) && $omigrate['boost_courses']) {
            if (isset($omigrate['courses'][$data])) {
                return $omigrate['courses'][$data];
            }
        }
        $extra_field = new ExtraFieldValue('course');
        $data = strtoupper($data);
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdCurso', $data);
        if ($values) {
            return $values['item_id'];
        } else {
            error_log("Course ".print_r($data, 1)." not found in DB");
            return false;
        }
    }

    /**
     * Gets the session ID from a given (external) uidIdPrograma
     * @param string $uidIdPrograma
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return mixed
     */
    static function getSessionIDByProgramID($uidIdPrograma, &$omigrate = null)
    {
        if (is_array($omigrate) && $omigrate['boost_sessions']) {
            if (isset($omigrate['sessions'][$uidIdPrograma])) {
                return $omigrate['sessions'][$uidIdPrograma];
            }
        }
        $extra_field = new ExtraFieldValue('session');
        $uidIdPrograma = strtoupper($uidIdPrograma);
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdPrograma', $uidIdPrograma);
        if ($values) {
            return $values['item_id'];
        } else {
            //error_log("session id not found in DB");
        }
    }

    /**
     * Gets the user ID from the external uidIdPersona param
     * @param string $uidIdPersona
     * @param array $omigrate
     * @return int  0 if not found
     */
    static function getUserIDByPersonaID($uidIdPersona, &$omigrate = null)
    {
        if (is_array($omigrate) && $omigrate['boost_users']) {
            if (isset($omigrate['users'][$uidIdPersona])) {
                error_log('Get user ID by persona ID -- omigrate => '.$omigrate['users'][$uidIdPersona]);
                return $omigrate['users'][$uidIdPersona];
            }
        }
        //error_log('getUserIDByPersonaID');
        $extra_field = new ExtraFieldValue('user');
        $uidIdPersona = strtoupper($uidIdPersona);
        $values = $extra_field->get_item_id_from_field_variable_and_field_value(
            'uididpersona',
            $uidIdPersona
        );
        error_log('Get user ID by persona ID -- extrafieldvalue => '.print_r($values, true));
        if ($values) {
            return $values['item_id'];
        } else {
            return 0;
        }
    }

    /**
     * Gets the user id from the uidIdPersona of a teacher
     * @param $uidIdPersona
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return int
     */
    static function getRealTeacherID($uidIdPersona, &$omigrate = null)
    {
        $default_teacher_id = self::defaultAdminId;
        if (empty($uidIdPersona)) {
            //error_log('No teacher provided');
            return $default_teacher_id;
        }
        if (is_array($omigrate) && $omigrate['boost_users']) {
            if (isset($omigrate['users'][$uidIdPersona])) {
                return $omigrate['users'][$uidIdPersona];
            }
        }

        $extra_field = new ExtraFieldValue('user');
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uididpersona', $uidIdPersona);

        if ($values) {
            return $values['item_id'];
        } else {
            return $default_teacher_id;
        }
    }

    /**
     * Manage the user creation, including checking if the user hasn't been
     * created previously
     * @param array $data User data
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return mixed User info (from Chamilo DB) or false on error
     */
    static function createUser($data, &$omigrate = null)
    {
        //error_log('In createUser, receiving '.print_r($data,1));
        if (empty($data['uididpersona'])) {
            error_log('User does not have a uidIdPersona');
            error_log(print_r($data, 1));
            return false;
        }
        if (empty($data['email'])) {
            error_log('User does not have an email');
            error_log(print_r($data, 1));
            return false;
        }

        $extra = [];

        $data['uididpersona'] = strtoupper($data['uididpersona']);
        $extra['uididpersona'] = $data['uididpersona'];

        $data['status'] = STUDENT;
        if (isset($data['uidIdEmpleado'])) {
            $data['status'] = COURSEMANAGER;
        }

        if (!isset($data['username']) || empty($data['username'])) {
            $data['firstname'] = (string)trim($data['firstname']);
            $data['lastname'] = (string)trim($data['lastname']);

            if (empty($data['firstname']) && empty($data['lastname'])) {
                $wanted_user_name = UserManager::purify_username($data['uididpersona']);
                //$wanted_user_name = UserManager::create_unique_username(null, null);
            } else {
                $wanted_user_name = UserManager::create_username($data['firstname'], $data['lastname']);
            }

            $extra_data = UserManager::get_extra_user_data_by_value('uididpersona', $data['uididpersona']);

            if ($extra_data) {
                $user_info = api_get_user_info($extra_data[0]);
                //print_r($extra_data);
                //error_log("User_already_added - {$user_info['user_id']}  - {$user_info['username']} - {$user_info['firstname']} - {$user_info['lastname']}");
                return $user_info;
            }

            if (UserManager::is_username_available($wanted_user_name)) {
                $data['username'] = $wanted_user_name;
                //error_log("username available  $wanted_user_name");
            } else {
                //the user already exists?
                $user_info = api_get_user_info_from_username($wanted_user_name);
                $user_persona = UserManager::get_extra_user_data_by_field($user_info['user_id'], 'uididpersona');

                if (isset($user_persona['uididpersona']) && $data['uididpersona'] == $user_persona['uididpersona']) {
                    error_log("Skip user already added: {$user_info['username']}");
                    return $user_info;
                } else {
                    error_log("Homonym - wanted_username: $wanted_user_name - uididpersona: {$user_persona['uididpersona']} - username: {$user_info['username']}");
                    //print_r($data);
                    //The user has the same firstname and lastname but it has another uiIdPersona could by an homonym
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
                    error_log("homonym username created ".$data['username']);
                }
            }

            if (empty($data['username'])) {
                //Last chance to have a nice username
                if (empty($data['firstname']) && empty($data['lastname'])) {
                    $data['username'] = UserManager::create_unique_username(uniqid());
                    error_log("username empty 1".$data['username']);
                } else {
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
                    error_log("username empty 2".$data['username']);
                }
            }
        } else {
            if (UserManager::is_username_available($data['username'])) {
                //error_log("username available {$data['username']} ");
            } else {
                //the user already exists?
                $user_info = api_get_user_info_from_username($data['username']);
                $user_persona = UserManager::get_extra_user_data_by_field($user_info['user_id'], 'uididpersona');


                if (isset($user_persona['uididpersona']) && (string)$data['uididpersona'] == (string)$user_persona['uididpersona']) {
                    //error_log("2 Skip user already added: {$user_info['username']}");
                    return $user_info;
                } else {
                    //print_r($user_persona);
                    //error_log("2 homonym - wanted_username: {$data['username']} - uididpersona: {$user_persona['uididpersona']} - username: {$user_info['username']}");
                    //print_r($data);
                    //The user has the same firstname and lastname but it has another uiIdPersona could by an homonym
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
                    //error_log("2 homonym username created ". $data['username']);
                }
            }
        }

        if (empty($data['username'])) {
            error_log('No Username provided');
            error_log(print_r($data, 1));
            return false;
            //exit;
        }
        $id_persona = $data['uididpersona'];
        unset($data['uididpersona']);
        unset($data['uidIdAlumno']);
        unset($data['uidIdEmpleado']);
        $data['encrypt_method'] = 'sha1';

        global $api_failureList;
        $api_failureList = array();
        $userId = UserManager::create_user(
            $data['firstname'],
            $data['lastname'],
            $data['status'],
            $data['email'],
            $data['username'],
            $data['password']
        );
        if (!$userId) {
            error_log('User '.$id_persona.' could not be inserted (maybe duplicate?)');
        } else {
            //error_log('User '.$id_persona.' was created as user '.$user_info['user_id']);
        }
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_users']) {
            $omigrate['users'][$id_persona] = $userId;
        }
        UserManager::update_extra_field_value($userId, 'uididpersona', $id_persona);
        $user_info = api_get_user_info($userId);
        return $user_info;
    }

    /**
     * Manages the course creation based on the rules in the db_matches.php file
     * @param   array $data
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return  array   Return value of CourseManager::createCourse();
     */
    static function createCourse($data, &$omigrate = null)
    {
        //error_log('In createCourse, received '.print_r($data,1));
        //Fixes wrong wanted codes
        $data['wanted_code'] = str_replace(array('-', '_'), '000', $data['wanted_code']);

        //Specific to customer, set the default language to English
        $data['language'] = 'english';
        $data['visibility'] = COURSE_VISIBILITY_REGISTERED;

        $courseId = null;
        // check if this course doesn't exist already
        if (!empty($data['uidIdCurso'])) {
            if (is_array($omigrate) && $omigrate['boost_courses']) {
                if (isset($omigrate['courses'][$data['uidIdCurso']])) {
                    $courseId = $omigrate['courses'][$data['uidIdCurso']];
                }
            } else {
                $extra_field = new ExtraFieldValue('course');
                $data = strtoupper($data);
                $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdCurso', $data);
                $courseId = $values['item_id'];
            }
            if (!empty($courseId)) {
                // A course with this uidIdCurso already exists. Return it instead.
                $courseInfo = api_get_course_info_by_id($courseId);
                return $courseInfo;
            }

        }

        //Creates an evaluation
        $data['create_gradebook_evaluation'] = false;
        /*
        $data['gradebook_params'] = array(
            'name'      => 'General evaluation',
            'user_id'   => self::defaultAdminId,
            'weight'    => '20',
            'max'       => '20'
        );*/
        $course_data = CourseManager::create_course($data);
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_courses']) {
            $omigrate['courses'][$data['uidIdCurso']] = $course_data['real_id'];
        }
        return $course_data;
    }

    /**
     * Manages the session creation, based on data provided by the rules
     * in db_matches.php
     * @param   array $data
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return int The created (or existing) session ID
     */
    static function createSession($data, &$omigrate)
    {
        $sessionId = null;
        // check if this course doesn't exist already
        if (!empty($data['uidIdPrograma'])) {
            if (is_array($omigrate) && $omigrate['boost_sessions']) {
                if (isset($omigrate['sessions'][$data['uidIdPrograma']])) {
                    $sessionId = $omigrate['sessions'][$data['uidIdPrograma']];
                }
            } else {
                $extra_field = new ExtraFieldValue('session');
                $data = strtoupper($data);
                $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdPrograma', $data);
                $sessionId = $values['item_id'];
            }
            if (!empty($sessionId)) {
                // A course with this uidIdCurso already exists. Return it instead.
                $sessionInfo = api_get_session_info($sessionId);
                return $sessionInfo;
            }

        }

        self::fixAccessDates($data);
        $extra = [];
        if (!empty($session_info['extra_uidIdPrograma'])) {
            $extra['extra_uididprograma'] = $session_info['extra_uidIdPrograma'];
        }
        if (!empty($session_info['estado'])) {
            $extra['extra_estado'] = $session_info['estado'];
        }
        if (!empty($session_info['extra_sede'])) {
            $extra['extra_sede'] = $session_info['extra_sede'];
        }
        if (!empty($session_info['extra_horario'])) {
            $extra['extra_horario'] = $session_info['extra_horario'];
        }
        if (!empty($session_info['extra_periodo'])) {
            $extra['extra_periodo'] = $session_info['extra_periodo'];
        }
        if (!empty($session_info['extra_aula'])) {
            $extra['extra_aula'] = $session_info['extra_aula'];
        }
        $session_id = SessionManager::create_session(
            $data['name'],
            $data['access_start_date'],
            $data['access_end_date'],
            $data['display_start_date'],
            $data['display_end_date'],
            $data['coach_access_start_date'],
            $data['coach_access_end_date'],
            null,
            null,
            SESSION_VISIBLE_READ_ONLY,
            null,
            null,
            null,
            null,
            $extra,
            null,
            null
        );
        if (!empty($data['c_id'])) {
            SessionManager::add_courses_to_session($session_id, $data['c_id']);
        }
        if (!$session_id) {
            error_log('Error: Failed to createSession '.$data['name']);
        } else {
            $c = SessionManager::set_coach_to_course_session($data['id_coach'], $session_id, $data['c_id']);
            if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_sessions']) {
                $omigrate['sessions'][$data['uidIdPrograma']] = $session_id;
            }
        }
        return $session_id;
    }

    /**
     * Assigns a user to a session based on rules in db_matches.php
     * @param   array $data
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return void
     */
    static function addUserToSession($data, &$omigrate = null)
    {
        $session_id = null;
        $user_id = null;
        if (is_array($omigrate) && $omigrate['boost_sessions']) {
            if (isset($omigrate['sessions'][$data['uidIdPrograma']])) {
                $session_id = $omigrate['sessions'][$data['uidIdPrograma']];
            }
        }
        if (empty($session_id)) {
            $extra_field_value = new ExtraFieldValue('session');
            $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uidIdPrograma',
                $data['uidIdPrograma']);
            //error_log('data[uidIdPrograma] '.$data['uidIdPrograma'].' returned $result[session_id]: '.$result['session_id']);
            if ($result && $result['session_id']) {
                $session_id = $result['session_id'];
            }
        }

        if (is_array($omigrate) && $omigrate['boost_users']) {
            if (isset($omigrate['users'][$data['uididpersona']])) {
                $user_id = $omigrate['users'][$data['uididpersona']];
            }
        }
        if (empty($user_id)) {
            $extra_field_value = new ExtraFieldValue('user');
            $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uididpersona',
                $data['uididpersona']);
            //error_log('data[uididpersona] '.$data['uididpersona'].' returned $result[user_id]: '.$result['user_id']);
            if ($result && $result['user_id']) {
                $user_id = $result['user_id'];
            }
        }

        if (!empty($session_id) && !empty($user_id)) {
            //error_log('Called: addUserToSession - Subscribing: session_id: '.$session_id. '  user_id: '.$user_id);
            SessionManager::subscribeUsersToSession($session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false);
            //exit;
        } else {
            //error_log('Called: addUserToSession - No idPrograma: '.$data['uidIdPrograma'].' - No uididpersona: '.$data['uididpersona']);
        }
    }

    /**
     * Create an attendance record based on a transaction record
     * @param array $data
     */
    static function createAttendance($data)
    {
        //error_log('createAttendance');
        $session_id = $data['session_id'];
        $user_id = $data['user_id'];
        $time = self::getScheduleValue($session_id);
        $fecha = $data['fecha']." $time:00";

        if (!empty($session_id) && !empty($user_id)) {
            $attendance = new Attendance();
            $course_list = false;
            global $data_list;
            if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions']) {
                $course_list = array(0 => array('code' => $data_list['session_course'][$session_id]));
            } else {
                $course_list = SessionManager::get_course_list_by_session_id($session_id);
            }

            $attendance_sheet_id = null;

            if (!empty($course_list)) {
                //We know there's only one course by session. Take it.
                $course = current($course_list);

                //Creating attendance
                if (isset($course['code'])) {
                    if (is_array($data_list) && isset($data_list) && $data_list['boost_courses']) {
                        $course_info = array('real_id' => $data_list['course_ids'][$course['code']]);
                    } else {
                        $course_info = api_get_course_info($course['code']);
                    }

                    $attendance->set_course_id($course_info['real_id']);
                    $attendance->set_session_id($session_id);

                    if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions'] && isset($data_list['sessions_attendances'][$course_info['real_id']][$session_id])) {
                        $list = $data_list['sessions_attendances'][$course_info['real_id']][$session_id];
                        foreach ($list as $at_id) {
                            $attendance_list[] = array('id' => $at_id);
                        }
                    } else {
                        $attendance_list = $attendance->get_attendances_list($course_info['real_id'], $session_id);
                    }
                    if (empty($attendance_list)) {
                        $attendance->set_name('Asistencia');
                        $attendance->set_description('');
                        //$attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
                        //$attendance->set_attendance_weight($_POST['attendance_weight']);
                        $link_to_gradebook = false;
                        //$attendance->category_id = $_POST['category_id'];
                        $attendance_sheet_id = $attendance->attendance_add($link_to_gradebook);
                        if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions']) {
                            $data_list['sessions_attendances'][$course_info['real_id']][$session_id][] = $attendance_sheet_id;
                        }
                        //error_log("Attendance added course code: {$course['code']} - session_id: $session_id");
                        //only 1 course per session
                    } else {
                        // Only one attendance sheet by course and session (at least during migration)
                        $attendance_data = current($attendance_list);
                        $attendance_sheet_id = $attendance_data['id'];
                        //error_log("Attendance found in attendance_sheet_id = $attendance_sheet_id - course code: {$course['code']} - session_id: $session_id");
                    }
                    // Now the attendance_sheet has been found or created, check the date
                    if ($attendance_sheet_id) {
                        error_log('Processing attendance sheet '.$attendance_sheet_id.' for session '.$session_id.', course '.$course_info['real_id'].', date '.$fecha);
                        //Attendance date exists?
                        $cal_info = array();
                        $cal_id = null;
                        if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions'] && $data_list['sessions_attendance_dates'][$attendance_sheet_id][$fecha]) {
                            $cal_info['id'] = $data_list['sessions_attendance_dates'][$attendance_sheet_id][$fecha];
                        } else {
                            $cal_info = $attendance->get_attendance_calendar_data_by_date($attendance_sheet_id, $fecha);
                        }
                        // Get (or create) the attendance date ID
                        if (isset($cal_info['id'])) {
                            $cal_id = $cal_info['id'];
                        } else {
                            $attendance->set_date_time($fecha);
                            //Creating the attendance date and get ID
                            $cal_id = $attendance->attendance_calendar_add($attendance_sheet_id, true);
                            if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions']) {
                                $data_list['sessions_attendance_dates'][$attendance_sheet_id][$fecha] = $cal_id;
                            }
                            //error_log("Creating attendance calendar $cal_id");
                        }
                        //Adding presence for the user (by default everybody is present)
                        $users_present = array($user_id => $data['status']);
                        if (is_array($data_list['createAttendance'])) {
                            $data_list['createAttendance'][] = array(
                                $cal_id,
                                $user_id,
                                $data['status'],
                                $attendance_sheet_id,
                                $course_info['real_id'],
                                $fecha
                            );
                            $limit = 100;
                            if (count($data_list['createAttendance']) == $limit) {
                                error_log('Flushing attendances list because reached '.$limit.": \n".print_r($data_list['createAttendance'],
                                        1));
                                $attendance->attendance_sheet_group_add($data_list['createAttendance'], false, true);
                                $data_list['createAttendance'] = array();
                            }
                        } else {
                            $attendance->attendance_sheet_add($cal_id, $users_present, $attendance_sheet_id, true);
                        }
                        //error_log("Adding calendar to user: $user_id to calendar: $cal_id");
                    } else {
                        // We should never get here
                        error_log('No attendance_sheet_id created');
                    }
                } else {
                    error_log("Course not found for session: $session_id");
                }
            }
        } else {
            error_log("Missing data: session: $session_id - user_id: $user_id");
        }
    }

    /**
     * Convert attendance status from the external system to a correct internal value
     * @param string $status
     * @return mixed|null
     */
    static function convertAttendanceStatus($status)
    {
        if (!in_array($status, array_keys(self::$attendStatus))) {
            return null;
        }
        return self::$attendStatus[$status];
    }

    /**
     * Create a thematic progress record based on external data
     * @param array $data
     * @return void
     */
    static function createThematic($data)
    {
        //error_log('createThematic');
        $session_id = $data['session_id'];

        if (!empty($session_id)) {
            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);

                if (!empty($course_data)) {
                    $thematic = new Thematic();
                    $thematic->set_course_int_id($course_data['real_id']);
                    $thematic->set_session_id($session_id);
                    $thematic_info = $thematic->get_thematic_by_title($data['thematic']);

                    if (empty($thematic_info)) {
                        $thematic->set_thematic_attributes(null, $data['thematic'], null, $session_id);
                        $thematic_id = $thematic->thematic_save();
                        error_log("Thematic added to course code: {$course_data['code']} - session_id: $session_id");
                    } else {
                        $thematic_id = isset($thematic_info['id']) ? $thematic_info['id'] : null;
                        error_log("Thematic id #$thematic_id found in course: {$course_data['code']} - session_id: $session_id");
                    }

                    if ($thematic_id) {
                        $thematic->set_thematic_plan_attributes($thematic_id, $data['thematic_plan'], null, 6);
                        $thematic->thematic_plan_save();
                        error_log("Saving plan attributes: {$data['thematic_plan']}");
                    }
                    error_log("Adding thematic id : $thematic_id to session: $session_id to course: {$course_data['code']} real_id: {$course_data['real_id']}");

                    if ($thematic_id) {
                        error_log("Thematic saved: $thematic_id");
                    } else {
                        error_log("Thematic NOT saved");
                    }
                }

                //if ($course_info['code'] != 'B05') {
                //exit;
                //}
            } else {
                error_log("No courses in session $session_id ");
            }
        }
    }

    /**
     * Create a gradebook evaluation from given data using Chamilo methods
     * @param array $data
     */
    static function createGradebookEvaluation($data)
    {
        //error_log('createGradebookEvaluation() function called');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;

        if (!empty($session_id)) {
            global $data_list;
            $course_list = array(0 => array('code' => $data_list['session_course'][$session_id]));
            //$course_list = SessionManager::get_course_list_by_session_id($session_id);
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Get gradebook (if does not exist, create it)
                    $gradebook = null;
                    if (!empty($data_list['session_course_gradebook'][$course_data['code']][$session_id])) {
                        $gradebook = array('id' => $data_list['session_course_gradebook'][$course_data['code']][$session_id]);
                    } else {
                        // This situation should not happen anymore. Default gradebooks are created in all courses and sessions upon creation.
                        //require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                        //$gradebook = array('id' => create_default_course_gradebook($course_data['code'], false, $session_id));
                        error_log('Gradebook not found.');
                        //$data_list['session_course_gradebook'][$course_data['code']][$session_id] = $gradebook['id'];
                    }
                    if (!empty($gradebook)) {
                        //Check if gradebook exists
                        $eval = 0;
                        $evals_found = $data_list['session_course_gradebook_eval'][$course_data['code']][$data['gradebook_description']];
                        if (!empty($evals_found)) {
                            return null;
                        }
                        $eval = new Evaluation();
                        $evals_found = $eval->load(null, null, $course_data['code'], $gradebook['id'], null, null,
                            $data['gradebook_description']);

                        if (empty($evals_found)) {
                            $eval->set_name($data['gradebook_description']);
                            $eval->set_description($data['gradebook_description']);
                            $eval->set_user_id(self::defaultAdminId);
                            $eval->set_course_code($course_data['code']);
                            $eval->set_category_id($gradebook['id']);

                            //harcoded values
                            $eval->set_weight(100);
                            $eval->set_max(100);
                            $eval->set_visible(1);
                            $eval_id = $eval->add();
                            $data_list['session_course_gradebook_eval'][$course_data['code']][$data['gradebook_description']] = $eval_id;
                            error_log("Gradebook evaluation ID $eval_id created!!");
                        } else {
                            error_log("Gradebook evaluation already exists - skipping insert :/ ");
                        }
                    } else {
                        error_log("Gradebook does not exists");
                    }
                } else {
                    error_log("Something is wrong with the course ");
                }
            } else {
                error_log("NO course found for session id: $session_id");
            }

        } else {
            error_log("NO session id found: $session_id");
        }
    }

    /**
     * Add gradebook results from external data
     * @param array $data
     */
    static function addGradebookResult($data)
    {
        error_log('addGradebookResult');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;

        if (!empty($session_id) && !empty($user_id)) {
            global $data_list, $utc_datetime;
            $course_list = array(0 => array('code' => $data_list['session_course'][$session_id]));
            //$course_list = SessionManager::get_course_list_by_session_id($session_id);
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Get gradebook
                    $gradebook = array('id' => $data_list['session_course_gradebook'][$course_data['code']][$session_id]);
                    //$gradebook = new Gradebook();
                    //$gradebook = $gradebook->get_first(array('where' => array('course_code = ? AND session_id = ?' => array($course_data['code'], $session_id))));
                    //error_log("Looking for gradebook in course code:  {$course_data['code']} - session_id: $session_id, user_id: $user_id");
                    if (!empty($gradebook)) {
                        error_log("Gradebook exists: {$gradebook['id']}");

                        //Check if gradebook exists
                        $eval = 0;
                        $eval_id = $data_list['session_course_gradebook_eval'][$course_data['code']][$data['gradebook_description']];
                        if (empty($eval_id)) {
                            $eval = new Evaluation();
                            $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null);
                            if (!empty($evals_found)) {
                                $evaluation = current($evals_found);
                                $eval_id = $evaluation->get_id();
                            }
                        }
                        if (!empty($eval_id)) {
                            //$evaluation = current($evals_found);
                            //$eval_id = $evaluation->get_id();

                            //Eval found
                            $check_result = $data_list['course_eval_results'][$eval_id][$user_id];
                            //$res = new Result();
                            //$check_result = Result :: load (null, $user_id, $eval_id);
                            if (empty($check_result)) {
                                $res = new Result();
                                $eval_data = array(
                                    'user_id' => $user_id,
                                    'evaluation_id' => $eval_id,
                                    //'created_at' => api_get_utc_datetime(),
                                    'created_at' => $utc_datetime,
                                    'score' => $data['nota'],
                                );
                                //$res->set_evaluation_id($eval_id);
                                //$res->set_user_id($user_id);
                                //if no scores are given, don't set the score
                                //$res->set_score($data['nota']);
                                //$res->add();
                                $limit = 250;
                                if (count($data_list['create_eval_results']) > $limit) {
                                    $data_list['create_eval_results'][] = $eval_data;
                                    $res->group_add($data_list['create_eval_results']);
                                    $data_list['create_eval_results'] = array();
                                } else {
                                    $data_list['create_eval_results'][] = $eval_data;
                                }
                                error_log("Result saved :)");
                            } else {
                                error_log("Result already added ");
                            }
                        } else {
                            error_log("Evaluation not found ");
                        }
                    } else {
                        error_log("Gradebook does not exists");
                    }
                } else {
                    error_log("Something is wrong with the course ");
                }
            } else {
                error_log("NO course found for session id: $session_id");
            }

        } else {
            error_log("NO session id found: $session_id");
        }
    }

    /**
     * Add gradebook result and an generic evaluation to hold it
     * @param array $data
     */
    static function addGradebookResultWithEvaluation($data)
    {
        error_log('addGradebookResultWithEvaluation');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;

        //Default evaluation title
        $title = 'Evaluación General';

        if (!empty($session_id) && !empty($user_id)) {
            global $data_list, $utc_datetime;
            //$course_list = SessionManager::get_course_list_by_session_id($session_id);
            $course_list = array(0 => array('code' => $data_list['session_course'][$session_id]));
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    $gradebook = array('id' => $data_list['session_course_gradebook'][$course_data['code']][$session_id]);
                    //Get gradebook
                    //$gradebook = new Gradebook();
                    //$gradebook = $gradebook->get_first(array('where' => array('course_code = ? AND session_id = ?' => array($course_data['code'], $session_id))));
                    //error_log("Looking gradebook in course code:  {$course_data['code']} - session_id: $session_id, user_id: $user_id");
                    if (!empty($gradebook)) {
                        error_log("Gradebook exists: {$gradebook['id']}");

                        //Creates
                        $eval = new Evaluation();
                        $evals_found = false;
                        if (isset($data_list['course_evals'][$course_data['code']][$gradebook['id']][$title])) {
                            $evals_found = $data_list['course_evals'][$course_data['code']][$gradebook['id']][$title];
                        }
                        if (empty($evals_found)) {
                            $eval->set_name($title);
                            //$eval->set_evaluation_type_id($data['gradebook_evaluation_type_id']);
                            $eval->set_user_id(self::defaultAdminId);
                            $eval->set_course_code($course_data['code']);
                            $eval->set_category_id($gradebook['id']);

                            //harcoded values
                            $eval->set_weight(100);
                            $eval->set_max(100); //score of tinNota is over 100
                            $eval->set_visible(1);
                            $eval->add();
                            $eval_id = $eval->get_id();
                            $data_list['course_evals'][$course_data['code']][$gradebook['id']][$title] = $eval_id;
                        } else {
                            $eval_id = $evals_found;
                        }

                        if ($eval_id) {
                            //Check if already exists
                            //$check_result = Result :: load (null, $user_id, $eval_id);
                            $check_result = $data_list['course_eval_results'][$eval_id][$user_id];
                            if (empty($check_result)) {
                                //$res = new Result();
                                //$res->set_evaluation_id($eval_id);
                                //$res->set_user_id($user_id);
                                ////if no scores are given, don't set the score
                                //$res->set_score($data['nota']);
                                //$res_id = $res->add();
                                $eval_data = array(
                                    'user_id' => $user_id,
                                    'evaluation_id' => $eval_id,
                                    //'created_at' => api_get_utc_datetime(),
                                    'created_at' => $utc_datetime,
                                    'score' => $data['nota'],
                                );
                                //$data_list['course_eval_results'][$eval_id][$user_id] = $res_id;
                                $limit = $data_list['create_eval_results_limit'];
                                if (count($data_list['create_eval_results']) > $limit) {
                                    $data_list['create_eval_results'][] = $eval_data;
                                    $res = new Result();
                                    $res->group_add($data_list['create_eval_results']);
                                    $data_list['create_eval_results'] = array();
                                } else {
                                    $data_list['create_eval_results'][] = $eval_data;
                                }
                            } else {
                                error_log("Result already added ");
                            }
                        } else {
                            error_log("Evaluation not detected");
                        }
                    } else {
                        error_log("Gradebook does not exists");
                    }
                } else {
                    error_log("Something is wrong with the course ");
                }
            } else {
                error_log("NO course found for session id: $session_id");
            }
        } else {
            error_log("NO session id found: $session_id");
        }
    }


    /* Transaction methods */

    /**
     * añadir usuario: usuario_agregar UID
     * const TRANSACTION_TYPE_ADD_USER    =  1;
     * @param array $data
     * @param array $data
     * @return array
     */
    static function transaction_1($data, $web_service_details)
    {
        global $data_list;
        $uidIdPersonaId = $data['item_id'];
        //Add user call the webservice
        $user_info = Migration::soap_call(
            $web_service_details,
            'usuarioDetalles',
            array(
                'intIdSede' => $data['branch_id'],
                'uididpersona' => $uidIdPersonaId
            )
        );

        if ($user_info['error'] == false) {
            global $api_failureList;
            unset($user_info['error']);

            // ICPNA users are accepted without e-mail
            if (empty($user_info['email'])) {
                $user_info['email'] = 'NO TIENE';
            }

            $chamiloUserInfo = api_get_user_info_from_username($user_info['username']);

            if ($chamiloUserInfo !== false) {
                if (!empty($user_info['extra_uididpersona'])) {
                    $extraFieldValue = new ExtraFieldValue('user');
                    $params = [
                        'item_id' => $chamiloUserInfo['id'],
                        'variable' => 'uididpersona',
                        'value' => $user_info['extra_uididpersona']
                    ];
                    $extraFieldValue->save($params);
                }

                /*return [
                    'entity' => 'user',
                    'before' => null,
                    'after' => $chamiloUserInfo,
                    'message' => "Existing user $uidIdPersonaId: ".print_r($chamiloUserInfo, true),
                    'status_id' => self::TRANSACTION_STATUS_DEPRECATED
                ];*/
                return self::transaction_3($data, $web_service_details);
            }

            $chamilo_user_id = self::create_user_custom_ws(
                $user_info['firstname'],
                $user_info['lastname'],
                $user_info['status'],
                $user_info['email'],
                $user_info['username'],
                $user_info['password'],
                $uidIdPersonaId

            );
            if (!empty($user_info['extra_uididpersona'])) {
                $extraFieldValue = new ExtraFieldValue('user');
                $params = [
                    'item_id' => $chamilo_user_id,
                    'variable' => 'uididpersona',
                    'value' => $user_info['extra_uididpersona']
                ];
                $extraFieldValue->save($params);
            }
            if (!empty($user_info['extra_birthdate'])) {
                UserManager::update_extra_field_value($chamilo_user_id, 'birthdate', $user_info['extra_birthdate']);
            }
            $chamilo_user_info = api_get_user_info($chamilo_user_id);

            if ($chamilo_user_info && $chamilo_user_info['id']) {
                $chamilo_user_info = api_get_user_info($chamilo_user_info['id'], false, false, true);

                // We extra-update the user to set a password that works with the salt
                $sql = "UPDATE user
                    SET password = SHA1(CONCAT('".$user_info['password']."', '{', salt, '}')),
                        auth_source = 'platform'
                    WHERE id = ".$chamilo_user_info['id']." AND salt IS NOT NULL AND salt != ''";
                $res = Database::query($sql);
                $msg = '';
                if ($res === false) {
                    $msg = ' - Issue updating password: '.$sql;
                }

                $data_list['users'][$uidIdPersonaId] = $chamilo_user_info['id'];
                return array(
                    'entity' => 'user',
                    'before' => null,
                    'after' => $chamilo_user_info,
                    'message' => "User was created - user_id: {$chamilo_user_info['id']} - firstname: {$chamilo_user_info['firstname']} - lastname:{$chamilo_user_info['lastname']}".$msg,
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "User was not created : $uidIdPersonaId \n UserManager::add() params: ".print_r($user_info,
                            1)." \nResponse: \n ".print_r($api_failureList, 1),
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return $user_info;
        }
    }

    /**
     * eliminar usuario usuario_eliminar UID
     * const TRANSACTION_TYPE_DEL_USER    =  2;
     * @param array $data
     * @param array $data
     * @return array
     */
    static function transaction_2($data)
    {
        $uidIdPersonaId = strtoupper($data['item_id']);
        global $data_list;
        $user_id = self::getUserIDByPersonaID($uidIdPersonaId, $data_list);
        if ($user_id) {
            $chamilo_user_info_before = api_get_user_info($user_id, false, false, true);
            $result = UserManager::delete_user($user_id);
            $chamilo_user_info = api_get_user_info($user_id, false, false, true);
            if ($result) {
                $data_list['users'][$uidIdPersonaId] = null;
                return array(
                    'entity' => 'user',
                    'before' => $chamilo_user_info_before,
                    'after' => $chamilo_user_info,
                    'message' => "User was deleted : $user_id",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "User was NOT deleted : $user_id error while calling function UserManager::delete_user",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            }
        } else {
            return array(
                'message' => "User was not found with uididpersona: $uidIdPersonaId",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    /**
     * Editar detalles de usuario (nombre/correo/contraseña) usuario_editar UID
     * const TRANSACTION_TYPE_EDIT_USER   =  3;
     * @param array $data
     * @param array $data
     * @return array
     */
    static function transaction_3($data, $web_service_details)
    {
        $uidIdPersonaId = strtoupper($data['item_id']);
        global $data_list;
        $user_id = self::getUserIDByPersonaID($uidIdPersonaId, $data_list);
        if ($user_id) {
            $user_info = Migration::soap_call(
                $web_service_details,
                'usuarioDetalles',
                ['intIdSede' => $data['branch_id'], 'uididpersona' => $uidIdPersonaId]
            );
            if ($user_info['error'] == false) {
                unset($user_info['error']);
                //Edit user
                $user_info['user_id'] = $user_id;
                $chamilo_user_info_before = api_get_user_info($user_id, false, false, true);
                UserManager::update_user(
                    $user_info['user_id'],
                    $user_info['firstname'],
                    $user_info['lastname'],
                    $user_info['username'],
                    $user_info['password'],
                    $chamilo_user_info_before['auth_source'],
                    $user_info['email'],
                    $chamilo_user_info_before['status'],
                    $chamilo_user_info_before['official_code'],
                    $chamilo_user_info_before['phone'],
                    $chamilo_user_info_before['picture_uri'],
                    $chamilo_user_info_before['expiration_date'],
                    $chamilo_user_info_before['active'],
                    $chamilo_user_info_before['creator_id'],
                    $chamilo_user_info_before['hr_dept_id'],
                    [],
                    $chamilo_user_info_before['language']
                );
                if (!empty($user_info['extra_birthdate'])) {
                    UserManager::update_extra_field_value($user_id, 'birthdate', $user_info['extra_birthdate']);
                }
                $chamilo_user_info = api_get_user_info($user_id, false, false, true);
                // We extra-update the user to set a password that works with the salt
                UserManager::updatePassword($chamilo_user_info['id'], $user_info['password']);

                $sql = "UPDATE user SET auth_source = 'platform' WHERE id = ".$chamilo_user_info['id'];
                $res = Database::query($sql);
                $msg = '';
                if ($res === false) {
                    $msg = ' - Issue updating password: '.$sql;
                }

                return array(
                    'entity' => 'user',
                    'before' => $chamilo_user_info_before,
                    'after' => $chamilo_user_info,
                    'message' => "User id $user_id was updated with data: ".print_r($user_info, 1).$msg,
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $user_info;
            }
        } else {
            return array(
                'message' => "User was not found with uididpersona: $uidIdPersonaId",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    /**
     * Cambiar usuario de progr. académ. (de A a B, de A a nada, de nada a A) (como estudiante o profesor) usuario_matricula UID ORIG DEST
     * const TRANSACTION_TYPE_SUB_USER    =  4; //subscribe user to a session
     * @param array $data
     * @param array $data
     * @return array
     */
    static function transaction_4($data)
    {
        $uidIdPersona = $data['item_id'];
        $uidIdPrograma = $data['origin'];
        $uidIdProgramaDestination = $data['dest_id'];
        $status = (!empty($data['external_info']) ? $data['external_info'] : null);
        global $data_list;
        $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

        if (empty($user_id)) {
            error_log('YYYY - User '.$uidIdPersona.' could not be found');
            return array(
                'message' => "User does not exists in DB: $uidIdPersona",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
        //error_log('YYYY - User ID '.$user_id. '('.$uidIdPersona.')');

        //Move A to B
        if (!empty($uidIdPrograma) && !empty($uidIdProgramaDestination)) {
            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $destination_session_id = self::getSessionIDByProgramID($uidIdProgramaDestination, $data_list);

            if (!empty($session_id) && !empty($destination_session_id)) {

                $before1 = self::getUserStatusInSession($session_id, $user_id);
                $before2 = self::getUserStatusInSession($destination_session_id, $user_id);

                //$reason_id = SESSION_CHANGE_USER_REASON_SCHEDULE;
                //change schedule - see cases CONST in sessionmanager
                $reason_id = 1;
                error_log('YYYY - Moving '.$user_id.' from one session to another');
                SessionManager::changeUserSession($user_id, $session_id, $destination_session_id, $reason_id);
                //error_log('YYYY - change_user_session called');

                $befores = array($before1, $before2);
                //error_log('YYYY - Befores: '.print_r($before1,true).' - '.print_r($before2,true));
                $message = "Move Session A to Session B";
                $c = self::isUserSubscribedToSession($user_id, $destination_session_id, $message, $befores);
                //error_log('YYYY --- check :'.print_r($c,true));
                return $c;
            } else {
                ;
                error_log('YYYY - Move user '.$user_id.' from session '.$session_id.' to '.$destination_session_id);
                return array(
                    'message' => "Session ids were not correctly setup session_id 1: $session_id Session id 2 $uidIdProgramaDestination - Move Session A to Session B",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }

        //Move A to empty
        if (!empty($uidIdPrograma) && empty($uidIdProgramaDestination)) {
            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            if (!empty($session_id)) {
                $before = self::getUserStatusInSession($session_id, $user_id);
                //SessionManager::subscribe_users_to_session($session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false);
                SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                $message = "Move Session to empty";
                return self::isUserSubscribedToSession($user_id, $session_id, $message, $before);
            } else {
                return array(
                    'message' => "Session does not exists in DB $uidIdPrograma  - Move Session to empty",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }

        //Move empty to A
        if (empty($uidIdPrograma) && !empty($uidIdProgramaDestination)) {
            $session_id = self::getSessionIDByProgramID($uidIdProgramaDestination, $data_list);
            if (!empty($session_id)) {
                $before = self::getUserStatusInSession($session_id, $user_id);
                if (isset($status) && $status == 1) {
                    $course_list = SessionManager::get_course_list_by_session_id($session_id);
                    if (!empty($course_list)) {
                        $course_data = current($course_list);
                        SessionManager::set_coach_to_course_session($user_id, $session_id, $course_data['id']);
                        return self::isCoachInSessionCourse($user_id, $session_id, $course_data['id']);
                    } else {
                        return array(
                            'message' => 'Could not subscribe to course: no course in session '.$uidIdProgramaDestination,
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }
                } else {
                    SessionManager::subscribeUsersToSession(
                        $session_id,
                        array($user_id),
                        SESSION_VISIBLE_READ_ONLY,
                        false
                    );
                }
                $message = 'Move empty to Session';
                return self::isUserSubscribedToSession($user_id, $session_id, $message, $before);
            } else {
                return array(
                    'message' => "Session does not exists in DB $uidIdProgramaDestination - Move empty to Session",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }
        return false;
    }

    /**
     * Check if a user is subscribed to a session
     * @param int $user_id
     * @param int $session_id
     * @param string $message
     * @param array $before
     * @return array
     */
    static function isUserSubscribedToSession($user_id, $session_id, $message = null, $before = array())
    {
        $user_session_status = self::getUserStatusInSession($session_id, $user_id);
        //error_log('YYYY - User status in session '.$session_id.' is '.print_r($user_session_status,1));
        if (!empty($user_session_status)) {
            return array(
                'entity' => 'session_rel_user',
                'before' => $before,
                'after' => $user_session_status,
                'message' => "User $user_id added to Session $session_id  - user relation_type in session: {$user_session_status['relation_type']}- $message ",
                'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                'message' => "User $user_id was NOT added to Session $session_id  - $message",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    /**
     * Check if a coach user is subscribed to a session course
     * @param int $user_id
     * @param int $session_id
     * @param int $course_id
     * @param string $message
     * @param array $before
     * @return array
     */
    static function isCoachInSessionCourse($user_id, $session_id, $course_id, $message = null, $before = array())
    {
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $session_id = intval($session_id);
        $course_id = intval($course_id);
        $user_id = intval($user_id);
        $sql = "SELECT * FROM $tbl_session_course_user
            WHERE user_id = $user_id
            AND session_id = $session_id
            AND c_id = $course_id
            AND status = 2";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return array(
                'entity' => 'session_rel_course_rel_user',
                'before' => $before,
                'after' => 2,
                'message' => "User $user_id added to session $session_id course $course_id - user relation_type in session: 2 - $message ",
                'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                'message' => "User $user_id was NOT added to session $session_id course $course_id - $message",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    //Cursos

    /**
     * añadir curso curso_agregar CID
     * const TRANSACTION_TYPE_ADD_COURSE  =  5;
     * @param array $data
     * @param array $web_service_details
     * @return array|mixed
     */
    static function transaction_5($data, $web_service_details)
    {
        global $data_list, $matches;
        $uidCursoId = $data['item_id'];
        $course_info = Migration::soap_call($web_service_details, 'cursoDetalles',
            array('intIdSede' => $data['branch_id'], 'uididcurso' => $uidCursoId));
        if ($course_info['error'] == false) {
            unset($course_info['error']);
            $course_info = CourseManager::create_course(
                $course_info,
                0,
                isset($matches['web_service_calls']['accessUrlId']) ? (int) $matches['web_service_calls']['accessUrlId'] : 1
            );
            $course_info = api_get_course_info($course_info['code'], true);
            if (!empty($course_info)) {
                $data_list['courses'][$uidCursoId] = $course_info['code'];
                return array(
                    'entity' => 'course',
                    'before' => null,
                    'after' => $course_info,
                    'message' => "Course was created code: {$course_info['code']} ",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "Course was NOT created",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return $course_info;
        }
    }

    /**
     * Eliminar curso curso_eliminar CID
     * const TRANSACTION_TYPE_DEL_COURSE  =  6;
     * @param array $data
     * @return array
     */
    static function transaction_6($data)
    {
        global $data_list;
        $course_code = self::getRealCourseCode($data['item_id']);
        if (!empty($course_code)) {
            $course_info_before = api_get_course_info($course_code, true);
            CourseManager::delete_course($course_code);
            $course_info = api_get_course_info($course_code, true);
            $data_list['courses'][$data['item_id']] = null;
            return array(
                'entity' => 'course',
                'before' => $course_info_before,
                'after' => $course_info,
                'message' => "Course was deleted $course_code ",
                'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                'message' => "Coursecode does not exists in DB $course_code ",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }

    }

    /**
     * Editar detalles de curso curso_editar CID
     * const TRANSACTION_TYPE_EDIT_COURSE =  7;
     * @param array $data The data identifying the course and other transaction details
     * @param array $web_service_details The web service information allowing the connection to get more item update data
     * @return array
     */
    static function transaction_7($data, $web_service_details)
    {
        $uidCursoId = $data['item_id'];
        $course_code = self::getRealCourseCode($uidCursoId);
        if (!empty($course_code)) {
            $course_info = api_get_course_info($course_code, true);
            $data_to_update = Migration::soap_call($web_service_details, 'cursoDetalles',
                array('intIdSede' => $data['branch_id'], 'uididcurso' => $uidCursoId));

            if ($data_to_update['error'] == false) {
                //do some cleaning
                $data_to_update['code'] = $course_info['code'];
                unset($data_to_update['error']);
                //CourseManager::update($data_to_update); //deprecated as it tries to update all fields and ends up blanking most - see BT#6282
                $errmsg = '';
                foreach ($data_to_update as $field => $value) {
                    if (substr($field, 0, 6) == 'extra') {
                        continue;
                    } //don't deal with extra fields for now
                    $res = CourseManager::update_attribute($course_info['id'], $field, $value);
                    if ($res === false) {
                        $errmsg .= ' [note: field '.$field.' could not be updated with value '.$value.' due to a database error]';
                    }
                }
                // Now edit extra fields
                $field_value = new ExtraFieldValue('course');
                $data_to_update['course_code'] = $data_to_update['code'];
                $field_value->saveFieldValues($data_to_update);
                // Get course info
                $course_info_after = api_get_course_info($course_code, true);

                return array(
                    'entity' => 'course',
                    'before' => $course_info,
                    'after' => $course_info_after,
                    'message' => "Course with code: $course_code was updated with this data:  ".print_r($data_to_update,
                            1),
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $data_to_update;
            }
        } else {
            return array(
                'message' => "couCoursese_code does not exists $course_code ",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    //Programas académicos

    /**
     * añade p.a. pa_agregar PID
     * const TRANSACTION_TYPE_ADD_SESS    =  8;
     * @param array $data
     * @param array $web_service_details
     * @return array
     */
    static function transaction_8($data, $web_service_details)
    {
        global $data_list, $matches;
        $session_info = Migration::soap_call($web_service_details, 'programaDetalles',
            array('intIdSede' => $data['branch_id'], 'uididprograma' => $data['item_id']));

        if ($session_info['error'] == false) {
            unset($session_info['error']);
            // check dates (only do this at session creation)
            self::fixAccessDates($session_info);
            $session_info['id_coach'] = 0;
            $cid = $session_info['c_id'];
            $extra = [];
            if (!empty($session_info['extra_uidIdPrograma'])) {
                $extra['extra_uididprograma'] = $session_info['extra_uidIdPrograma'];
            }
            if (!empty($session_info['estado'])) {
                $extra['extra_estado'] = $session_info['estado'];
            }
            if (!empty($session_info['extra_sede'])) {
                $extra['extra_sede'] = $session_info['extra_sede'];
            }
            if (!empty($session_info['extra_horario'])) {
                $extra['extra_horario'] = $session_info['extra_horario'];
            }
            if (!empty($session_info['extra_periodo'])) {
                $extra['extra_periodo'] = $session_info['extra_periodo'];
            }
            if (!empty($session_info['extra_aula'])) {
                $extra['extra_aula'] = $session_info['extra_aula'];
            }
            $session_id = SessionManager::create_session(
                $session_info['name'],
                $session_info['access_start_date'],
                $session_info['access_end_date'],
                $session_info['display_start_date'],
                $session_info['display_end_date'],
                $session_info['coach_access_start_date'],
                $session_info['coach_access_end_date'],
                $session_info['id_coach'] ?: null,
                null,
                SESSION_VISIBLE_READ_ONLY,
                null,
                null,
                null,
                null,
                $extra,
                isset($matches['web_service_calls']['sessionAdmin']) ? $matches['web_service_calls']['sessionAdmin'] : 0,
                null,
                isset($matches['web_service_calls']['accessUrlId']) ? ((int) $matches['web_service_calls']['accessUrlId']) : 1
            );
            //$session_id = SessionManager::add($session_info);
            $session_info = api_get_session_info($session_id);
            if ($session_id) {
                if (!empty($cid)) {
                    if (!is_array($cid)) {
                        $cid = [$cid];
                    }
                    SessionManager::add_courses_to_session($session_id, $cid);
                }
                $data_list['sessions'][$data['item_id']] = $session_id;
                return array(
                    'entity' => 'session',
                    'before' => null,
                    'after' => $session_info,
                    'message' => "Session was created. Id: $session_id session data: ".print_r($session_info, 1),
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "Session was NOT created: {$data['item_id']} session data: ".print_r($session_info, 1),
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            //Return error
            return $session_info;
        }
    }


    /**
     * eliminar p.a. pa_eliminar PID
     * const TRANSACTION_TYPE_DEL_SESS    =  9;
     * @param array $data
     * @return array
     */
    static function transaction_9($data)
    {
        $uidIdPrograma = $data['item_id'];
        global $data_list;
        $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
        if (!empty($session_id)) {
            $session_info_before = api_get_session_info($session_id);
            SessionManager::delete($session_id, true);
            $session_info = api_get_session_info($session_id);
            $data_list['sessions'][$data['item_id']] = null;
            return array(
                'entity' => 'session',
                'before' => $session_info_before,
                'after' => $session_info,
                'message' => "Session was deleted  session_id: $session_id - id: $uidIdPrograma",
                'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                'message' => "Session does not exists $uidIdPrograma",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    /**
     * editar detalles de p.a. pa_editar PID
     * const TRANSACTION_TYPE_EDIT_SESS   = 10;
     * @param array $data
     * @param array $web_service_details
     * @return array
     */
    static function transaction_10($data, $web_service_details)
    {
        $uidIdPrograma = $data['item_id'];
        global $data_list;
        $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
        if (!empty($session_id)) {
            $session_info = Migration::soap_call($web_service_details, 'programaDetalles',
                array('intIdSede' => $data['branch_id'], 'uididprograma' => $data['item_id']));
            if ($session_info['error'] == false) {
                self::fixAccessDates($session_info);
                $session_info['id'] = $session_id;

                if (isset($session_info['id_coach'])) {
                    $coachId = $session_info['id_coach'];

                    $efv = new ExtraFieldValue('user');
                    $fieldValue = $efv->get_values_by_handler_and_field_variable($coachId, 'uididpersona');
                    $uidIdPersona = trim($fieldValue['value']);

                    $session_info['id_coach'] = empty($uidIdPersona) ? 0 : intval($coachId);
                }

                unset($session_info['error']);
                $session_info_before = api_get_session_info($session_id);
                $extra = [];
                if (!empty($session_info['extra_uidIdPrograma'])) {
                    $extra['extra_uididprograma'] = $session_info['extra_uidIdPrograma'];
                }
                if (!empty($session_info['estado'])) {
                    $extra['extra_estado'] = $session_info['estado'];
                }
                if (!empty($session_info['extra_sede'])) {
                    $extra['extra_sede'] = $session_info['extra_sede'];
                }
                if (!empty($session_info['extra_horario'])) {
                    $extra['extra_horario'] = $session_info['extra_horario'];
                }
                if (!empty($session_info['extra_periodo'])) {
                    $extra['extra_periodo'] = $session_info['extra_periodo'];
                }
                if (!empty($session_info['extra_aula'])) {
                    $extra['extra_aula'] = $session_info['extra_aula'];
                }
                SessionManager::edit_session(
                    $session_info['id'],
                    $session_info['name'],
                    $session_info['access_start_date'],
                    $session_info['access_end_date'],
                    $session_info['display_start_date'],
                    $session_info['display_end_date'],
                    $session_info['coach_access_start_date'],
                    $session_info['coach_access_end_date'],
                    $coachId,
                    null,
                    SESSION_VISIBLE_READ_ONLY,
                    null,
                    null,
                    null,
                    $extra
                );
                $session_info = api_get_session_info($session_id);
                return array(
                    'entity' => 'session',
                    'before' => $session_info_before,
                    'after' => $session_info,
                    'message' => "Session updated $uidIdPrograma", // with data: ".print_r($session_info, 1),
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $session_info;
            }
        } else {
            return array(
                'message' => "Session does not exists $uidIdPrograma",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    /**
     * @param $data
     * @param $param
     * @return array
     */
    static function insAsistenciaDocente($data, $param)
    {
        if ($data->insAsistenciaDocenteResult) {
            return array('error' => false);
        } else {
            return array('error' => true);
        }
    }

    /**
     * This is to split the date and time
     * @param $dateTimeParam
     * @return array
     */
    public static function explodeDateTime($dateTimeParam)
    {
        $dateTime = explode(' ', $dateTimeParam);

        return array(
            'date' => $dateTime[0],
            'time' => $dateTime[1]
        );
    }

    /**
     *
     * @param string $extra_field_variable
     * @param array $data
     * @return array
     */
    static function transaction_cambiar_generic($extra_field_variable, $data)
    {
        global $data_list;
        $uidIdPrograma = $data['item_id'];
        //$origin = $data['origin'];
        $destination_id = $data['dest_id'];

        $common_message = " - item_id:  $uidIdPrograma, dest_id: $destination_id -  looking for extra_field_variable: $extra_field_variable - with data ".print_r($data,
                1);

        $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
        if (!empty($session_id)) {
            //??
            $extra_field = new ExtraField('session');
            $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable); //horario, aula, etc

            if (empty($extra_field_info)) {
                return array(
                    'message' => "Extra field $extra_field_variable doest not exists in the DB $common_message",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            //check if option exists
            $extra_field_option = new ExtraFieldOption('session');
            $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['id'],
                $destination_id); //horario, aula, etc

            if ($extra_field_option_info) {
                $extra_field_value = new ExtraFieldValue('session');

                //Getting info before
                $info_before = $extra_field_value->get_values_by_handler_and_field_id($session_id,
                    $extra_field_info['id']);

                //Delete previous extra field value
                $extra_field_value->delete_values_by_handler_and_field_id($session_id, $extra_field_info['id']);

                $params = array(
                    'session_id' => $session_id,
                    'field_id' => $extra_field_info['id'],
                    'value' => $destination_id,
                );
                $extra_field_value->save($params);

                //Getting info after
                $info_after = $extra_field_value->get_values_by_handler_and_field_id($session_id,
                    $extra_field_info['id']);

                return array(
                    'entity' => $extra_field_variable,
                    'before' => $info_before,
                    'after' => $info_after,
                    'message' => "Extra field  $extra_field_variable saved with params: ".print_r($params, 1),
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "Option does not exists dest_id: $destination_id  $common_message",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return array(
                'message' => "Session does not exists: $uidIdPrograma   $common_message",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    /**
     * cambiar aula pa_cambiar_aula PID ORIG DEST
     * const TRANSACTION_TYPE_UPD_ROOM    = 11;
     * @param $data
     * @return array
     */
    static function transaction_11($data)
    {
        return self::transaction_cambiar_generic('aula', $data);
    }

    /**
     * cambiar horario pa_cambiar_horario PID ORIG DEST
     * const TRANSACTION_TYPE_UPD_SCHED   = 12;
     * @param $data
     * @return array
     */
    static function transaction_12($data)
    {
        return self::transaction_cambiar_generic('horario', $data);
    }

    /**
     * cambiar sede pa_cambiar_sede PID ORIG DEST
     * no se usa (se declara el p.a. en otra sede, nada más)
     * @param $data
     * @return array
     */
    static function transaction_pa_cambiar_sede($data)
    {
        return self::transaction_cambiar_generic('sede', $data);
    }

    /**
     * cambiar intensidad pa_cambiar_fase_intensidad CID ORIG DEST (id de "intensidadFase")
     * no se usa (se declara el p.a. en otra sede, nada más)
     * @param $data
     * @return array
     */
    static function transaction_cambiar_pa_fase($data)
    {
        return self::transaction_cambiar_generic('fase', $data);
    }


    /**
     * No se usa (se declara el p.a. en otra sede, nada más)
     * @param array $data
     * @return array
     */
    static function transaction_cambiar_pa_intensidad($data)
    {
        return self::transaction_cambiar_generic('intensidad', $data);
    }

    /**
     *
     * @param string $extra_field_variable
     * @param array $original_data
     * @param array $web_service_details
     * @param string $type
     * @return array
     */
    static function transaction_extra_field_agregar_generic(
        $extra_field_variable,
        $original_data,
        $web_service_details,
        $type = 'session'
    ) {
        $function_name = $extra_field_variable."Detalles";
        $data = Migration::soap_call($web_service_details, $function_name, array(
            'intIdSede' => $original_data['branch_id'],
            "uidid".$extra_field_variable => $original_data['item_id']
        ));

        if ($data['error'] == false) {
            // Exceptional treatment for specific fields
            if ($extra_field_variable == 'aula') {
                $data['name'] = $original_data['branch_id'].' - '.$data['name'];
            }
            $extra_field = new ExtraField($type);
            $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);
            if ($extra_field_info) {
                $extra_field_option = new ExtraFieldOption($type);

                $info_before = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

                $params = array(
                    'field_id' => $extra_field_info['id'],
                    'option_value' => $original_data['item_id'],
                    'display_text' => $data['name'],
                    'option_order' => null
                );

                $result = $extra_field_option->save_one_item($params);

                $info_after = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

                if ($result) {
                    return array(
                        'entity' => $extra_field_variable,
                        'before' => $info_before,
                        'after' => $info_after,
                        'message' => "Extra field option added - $extra_field_variable was saved with data: ".print_r($params,
                                1),
                        'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                    );
                } else {
                    return array(
                        'message' => "Extra field option added -  $extra_field_variable was NOT saved with data: ".print_r($params,
                                1),
                        'status_id' => self::TRANSACTION_STATUS_FAILED
                    );
                }
            } else {
                return array(
                    'message' => "Extra field was not found: $extra_field_variable",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return $data;
        }
    }

    /**
     *
     * @param string $extra_field_variable
     * @param array $original_data
     * @param array $web_service_details
     * @param string $type
     * @return array
     */
    static function transaction_extra_field_editar_generic(
        $extra_field_variable,
        $original_data,
        $web_service_details,
        $type = 'session'
    ) {
        $extra_field = new ExtraField($type);
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);
        if (empty($extra_field_info)) {
            return array(
                'message' => "Extra field can't be edited extra field does not exists:  extra_field_variable: ".$extra_field_variable,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }

        $extra_field_option = new ExtraFieldOption($type);
        $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['id'],
            $original_data['item_id']);

        $function_name = $extra_field_variable."Detalles";

        $params = array(
            'intIdSede' => $original_data['branch_id'],
            "uidid".$extra_field_variable => $original_data['item_id']
        );

        $data = Migration::soap_call($web_service_details, $function_name, $params);
        if ($data['error'] == false) {

            // Exceptional treatment for specific fields
            if ($extra_field_variable == 'aula') {
                $data['name'] = $original_data['branch_id'].' - '.$data['name'];
            }
            //Update 1 item
            if (!empty($extra_field_option_info)) {

                $info_before = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

                if (count($extra_field_option_info) > 1) {
                    //var_dump($extra_field_option_info);
                    //Take the first one
                    error_log('Warning! There are several options with the same key. You should delete doubles. Check your DB with this query:');
                    error_log("SELECT * FROM ".$type."_field_options WHERE field_id =  {$extra_field_info['id']} AND option_value = '{$original_data['item_id']}' ");
                    error_log('All options are going to be updated');
                }

                $options_updated = array();
                foreach ($extra_field_option_info as $option) {
                    $extra_field_option_info = array(
                        'id' => $option['id'],
                        'field_id' => $extra_field_info['id'],
                        'option_value' => $original_data['item_id'],
                        'display_text' => $data['name'],
                        'option_order' => null
                    );
                    $extra_field_option->update($extra_field_option_info);
                    $options_updated[] = $option['id'];
                }

                $info_after = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

                $options_updated = implode(',', $options_updated);

                return array(
                    'entity' => $extra_field_variable,
                    'before' => $info_before,
                    'after' => $info_after,
                    'message' => "Extra field options id updated: $options_updated",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            } else {
                return array(
                    'message' => "Extra field option not found item_id: {$original_data['item_id']}",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return $data;
        }
    }

    /**
     * Delete all options with option_value = item_id
     * @param string $extra_field_variable
     * @param array $original_data
     * @param array $web_service_details
     * @param string $type
     * @return array
     */
    static function transaction_extra_field_eliminar_generic(
        $extra_field_variable,
        $original_data,
        $web_service_details,
        $type = 'session'
    ) { //horario
        $extra_field = new ExtraField($type);
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);

        $extra_field_option = new ExtraFieldOption($type);
        $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['id'],
            $original_data['item_id']);

        if (!empty($extra_field_option_info)) {

            $info_before = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

            $deleting_option_ids = array();
            foreach ($extra_field_option_info as $option) {
                //@todo Delete all horario in sessions?
                $result = $extra_field_option->delete($option['id']);
                if ($result) {
                    $deleting_option_ids[] = $option['id'];
                }
            }
            $info_after = $extra_field_option->get_field_options_by_field($extra_field_info['id']);

            if (!empty($deleting_option_ids)) {
                $deleting_option_ids = implode(',', $deleting_option_ids);
                return array(
                    'entity' => $extra_field_variable,
                    'before' => $info_before,
                    'after' => $info_after,
                    'message' => "Extra field options were deleted for the field_variable: $extra_field_variable, options id deleted: $deleting_option_ids",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            } else {
                return array(
                    'message' => "Extra field option was NOT deleted. No field options ids where found for variable: '$extra_field_variable' with id:'".$original_data['item_id']."'",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return array(
                'message' => "Extra field option was NOT deleted  - Extra field not found in DB. Trying to locate field_variable: '$extra_field_variable' with id: '{$original_data['item_id']}'",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }

    //        Horario

    /**
     * Añadir horario_agregar HID
     * const TRANSACTION_TYPE_ADD_SCHED   = 13;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_13($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('horario', $data, $web_service_details);
    }

    /**
     * eliminar horario_eliminar HID
     * const TRANSACTION_TYPE_DEL_SCHED   = 14;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_14($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('horario', $data, $web_service_details);
    }

    /**
     * Editar horario_editar HID
     * const TRANSACTION_TYPE_EDIT_SCHED  = 15;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_15($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('horario', $data, $web_service_details);
    }

    // Aula

    /**
     * Añadir aula_agregar AID
     * const TRANSACTION_TYPE_ADD_ROOM    = 16;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_16($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('aula', $data, $web_service_details);
    }

    /**
     * Eliminar aula_eliminar AID
     * const TRANSACTION_TYPE_DEL_ROOM    = 17;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_17($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('aula', $data, $web_service_details);
    }

    /**
     * Editar aula_editor AID
     * const TRANSACTION_TYPE_EDIT_ROOM   = 18;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_18($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('aula', $data, $web_service_details);
    }
    //        Sede

    /**
     * Añadir aula_agregar SID
     * const TRANSACTION_TYPE_ADD_BRANCH  = 19;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_19($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('sede', $data, $web_service_details);
    }

    /**
     * Eliminar aula_eliminar SID
     * const TRANSACTION_TYPE_DEL_BRANCH  = 20;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_20($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('sede', $data, $web_service_details);
    }

    /**
     * Editar aula_editar SID
     * const TRANSACTION_TYPE_EDIT_BRANCH = 21;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_21($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('sede', $data, $web_service_details);
    }

    //        Frecuencia

    /**
     * Añadir frec FID
     * const TRANSACTION_TYPE_ADD_FREQ    = 22;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_22($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('frecuencia', $data, $web_service_details, 'course');
    }

    /**
     * Eliminar Freca_eliminar FID
     * const TRANSACTION_TYPE_DEL_FREQ    = 23;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_23($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('frecuencia', $data, $web_service_details, 'course');
    }

    /**
     * Editar aula_editar FID
     * const TRANSACTION_TYPE_EDIT_FREQ   = 24;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_24($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('frecuencia', $data, $web_service_details, 'course');
    }

    //
    //        Intensidad/Fase
    /**
     * Añadir intfase_agregar IID
     * const TRANSACTION_TYPE_ADD_INTENS  = 25;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_25($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('intensidad', $data, $web_service_details, 'course');
    }

    /**
     * Eliminar intfase_eliminar IID
     * const TRANSACTION_TYPE_DEL_INTENS  = 26;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_26($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('intensidad', $data, $web_service_details, 'course');
    }

    /**
     * Editar intfase_editar IID
     * const TRANSACTION_TYPE_EDIT_INTENS = 27;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_27($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('intensidad', $data, $web_service_details, 'course');
    }
    //        Fase

    /**
     * Añadir fase_agregar IID
     * const TRANSACTION_TYPE_ADD_FASE  = 28;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_28($data, $web_service_details)
    {
        return self::transaction_extra_field_agregar_generic('fase', $data, $web_service_details, 'course');
    }

    /**
     * Eliminar fase_eliminar IID
     * const TRANSACTION_TYPE_DEL_FASE  = 29;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_29($data, $web_service_details)
    {
        return self::transaction_extra_field_eliminar_generic('fase', $data, $web_service_details, 'course');
    }

    /**
     * Editar fase_editar IID
     * const TRANSACTION_TYPE_EDIT_FASE = 30;
     * @param $data
     * @param $web_service_details
     * @return array
     */
    static function transaction_30($data, $web_service_details)
    {
        return self::transaction_extra_field_editar_generic('fase', $data, $web_service_details, 'course');
    }

    //        NOTA

    /**
     * Add "nota_agregar" IID
     * const TRANSACTION_TYPE_ADD_NOTA  = 31;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_31($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'notaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'intIdSede' => $original_data['branch_id']
        ));

        if ($data['error'] == false) {
            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];
            $score = $data['name'];

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists in DB: $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Look for gradebook and create it if it does not exist yet
                    $gradebook = new Gradebook();
                    $gradebook = $gradebook->find(
                        'all',
                        array(
                            'where' => array(
                                'course_code = ? AND session_id = ?' => array($course_data['code'], $session_id)
                            )
                        )
                    );
                    error_log("Looking for gradebook in course code: {$course_data['code']} - session_id: $session_id");
                    if (count($gradebook) === 0) {
                        // This should not happen anymore as gradebook are automatically created on course+session creation
                        //require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                        //$gradebook = array('id' => create_default_course_gradebook($course_data['code'], false, $session_id));
                        error_log('Gradebook not found.');
                        //$data_list['session_course_gradebook'][$course_data['code']][$session_id] = $gradebook['id'];
                    }
                    if (!empty($gradebook)) {
                        //Check if gradebook exists
                        $eval = new Evaluation();
                        $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null,
                            'Evaluación General');

                        //Try to create a gradebook evaluation
                        if (empty($evals_found)) {
                            //error_log("Trying to create a new evaluation in course code:  {$course_data['code']} - session_id: $session_id");
                            $params = array(
                                'session_id' => $session_id,
                                'gradebook_description' => 'Evaluación General',
                                'gradebook_evaluation_type_id' => 0
                            );
                            self::createGradebookEvaluation($params);
                            $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null,
                                'Evaluación General');
                        }

                        if (!empty($evals_found)) {
                            $evaluation = current($evals_found);
                            $eval_id = $evaluation->get_id();

                            //error_log("Evaluation found in gradebook: {$gradebook['id']}, eval_id: $eval_id");

                            //Eval found
                            $res = new Result();
                            $check_result = Result:: load(null, $user_id, $eval_id);
                            if (empty($check_result)) {
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);

                                //if no scores are given, don't set the score
                                $res->set_score($score);
                                $res->add();

                                $eval_result = Result:: load(null, $user_id, $eval_id);

                                return array(
                                    'entity' => 'gradebook_evaluation_result',
                                    'before' => null,
                                    'after' => $eval_result,
                                    'message' => "Gradebook result added ($score)",
                                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                                );
                            } else {
                                $message = "Result already added user_id: $user_id - eval_id: $eval_id - gradebook_id: {$gradebook['id']}";
                            }
                        } else {
                            $message = "Evaluation not found in gradebook: {$gradebook['id']} : in course: {$course_data['code']} - session_id: $session_id";
                        }
                    } else {
                        $message = "Gradebook does not exists in course: ".$course_data['code']." - session_id: $session_id";
                    }
                } else {
                    $message = "Something is wrong with the course ";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }

            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    /**
     * Eliminar nota_eliminar IID
     * const TRANSACTION_TYPE_DEL_NOTA  = 32;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_32($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'notaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'intIdSede' => $original_data['branch_id']
        ));

        if ($data['error'] == false) {
            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists in DB: $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    $gradebook = null;
                    if (!empty($data_list['session_course_gradebook'][$course_data['code']][$session_id])) {
                        $gradebook['id'] = $data_list['session_course_gradebook'][$course_data['code']][$session_id];
                    } else {
                        $gradebook = new Gradebook();
                        $gradebook = $gradebook->get(
                            'all',
                            array(
                                'where' => array(
                                    'course_code = ? AND session_id = ?' => array($course_data['code'], $session_id)
                                )
                            )
                        );
                        error_log("Looking for gradebook in course code:  {$course_data['code']} - session_id: $session_id");
                        if (count($gradebook) === 0) {
                            // This shouldn't happen anymore as gradebooks are created by default
                            //require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
                            //$gradebook = array('id' => create_default_course_gradebook($course_data['code'], false, $session_id));
                            error_log('Gradebook not found.');
                            //$data_list['session_course_gradebook'][$course_data['code']][$session_id] = $gradebook['id'];
                        }
                    }
                    // At this point we tried 3 different ways of obtaining a
                    // gradebook id, so no chance to fail (hopefully)
                    if (!empty($gradebook)) {
                        //Check if gradebook exists
                        $eval = new Evaluation();
                        $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null);

                        if (!empty($evals_found)) {
                            //error_log("Gradebook exists: {$gradebook['id']}");
                            $evaluation = current($evals_found);
                            $eval_id = $evaluation->get_id();

                            //Eval found
                            $res = new Result();
                            $check_result = Result:: load(null, $user_id, $eval_id);
                            if (!empty($check_result)) {
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);
                                $res->delete();

                                $eval_result = Result:: load(null, $user_id, $eval_id);

                                return array(
                                    'entity' => 'gradebook_evaluation_result',
                                    'before' => $check_result,
                                    'after' => $eval_result,
                                    'message' => "Gradebook result deleted ",
                                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                                );
                            } else {
                                $message = "Gradebook result does not exist for user_id: $user_id - eval_id $eval_id - in course: {$course_data['code']} - session_id: $session_id ";
                            }
                        } else {
                            $message = "Evaluation not found in gradebook: {$gradebook['id']} : in course: {$course_data['code']} - session_id: $session_id";
                        }
                    } else {
                        $message = "Gradebook does not exists in course: {$course_data['code']} - session_id: $session_id";
                    }
                } else {
                    $message = "Something is wrong with the course ";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }

            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    /**
     * Editar nota_editar IID
     * const TRANSACTION_TYPE_EDIT_NOTA = 33;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_33($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'notaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'intIdSede' => $original_data['branch_id']
        ));

        if ($data['error'] == false) {
            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];
            $score = $data['name'];

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists in DB: $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    $gradebook = new Gradebook();
                    $gradebook = $gradebook->get(
                        array(
                            'where' => array(
                                'course_code = ? AND session_id = ?' => array($course_data['code'], $session_id)
                            )
                        )
                    );
                    error_log("Looking for gradebook in course code:  {$course_data['code']} - session_id: $session_id");
                    if (!empty($gradebook)) {
                        //Check if gradebook exists
                        $eval = new Evaluation();
                        $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null);

                        //Try to create a gradebook evaluation
                        if (empty($evals_found)) {
                            error_log("Trying to create a new evaluation in course code:  {$course_data['code']} - session_id: $session_id");

                            $params = array(
                                'session_id' => $session_id,
                                'gradebook_description' => 'Evaluación General',
                                'gradebook_evaluation_type_id' => 0
                            );
                            self::createGradebookEvaluation($params);
                            $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null);
                        }

                        if (!empty($evals_found)) {
                            $evaluation = current($evals_found);
                            $eval_id = $evaluation->get_id();

                            //error_log("Gradebook exists: {$gradebook['id']} eval_id: $eval_id");

                            //Eval found
                            $res = new Result();
                            $check_result = Result:: load(null, $user_id, $eval_id);

                            if (!empty($check_result) && isset($check_result[0])) {
                                // Gradebook result found. Updating...
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);
                                $res->set_score($score);
                                /** @var Result $checkResultItem */
                                $checkResultItem = $check_result[0];
                                $res->set_id($checkResultItem->get_id());
                                $res->save();

                                $eval_result = Result:: load(null, $user_id, $eval_id);

                                return array(
                                    'entity' => 'gradebook_evaluation_result',
                                    'before' => $check_result,
                                    'after' => $eval_result,
                                    'message' => "Gradebook result edited ",
                                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                                );
                            } else {
                                // Gradebook result not found. Creating...
                                // @todo disable when moving to production
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);

                                //if no scores are given, don't set the score
                                $res->set_score($score);
                                $res->add();

                                $eval_result = Result:: load(null, $user_id, $eval_id);

                                //$message = "Gradebook result not modified because gradebook result does not exist for user_id: $user_id - eval_id: $eval_id - gradebook_id: {$gradebook['id']} - course: {$course_data['code']} - session_id: $session_id";
                                return array(
                                    'entity' => 'gradebook_evaluation_result',
                                    'before' => null,
                                    'after' => $eval_result,
                                    'message' => "Gradebook result added because it did not exist for update",
                                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                                );
                            }
                        } else {
                            $message = "Evaluation not found in gradebook: {$gradebook['id']} : in course: {$course_data['code']} - session_id: $session_id";
                        }
                    } else {
                        $message = "Gradebook does not exists in course: {$course_data['code']} - session_id: $session_id";
                    }
                } else {
                    $message = "Something is wrong with the course ";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }
            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    //        Asistencias

    /**
     * Añadir assist_agregar IID
     * const TRANSACTION_TYPE_ADD_ASSIST  = 34;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_34($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'asistenciaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'uididfecha' => $original_data['external_info'],
            'intIdSede' => $original_data['branch_id']
        ));

        if ($data['error'] == false) {

            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];

            $attendance_date = $data['date_assist'];
            $attendance_user_status = $data['status']; // modified in the asistenciaDetalles function

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                //There's only one course per session so far
                $course_data = current($course_list);
                if (isset($course_data['code'])) {

                    //Check if user exist in the session
                    $status = SessionManager::get_user_status_in_course_session($user_id, $course_data['code'],
                        $session_id);

                    if ($status === false) {
                        return array(
                            'message' => "User #$user_id is not registered in course code: {$course_data['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }
                    // attendance are registered with date + time, so get time
                    // from session schedule
                    $time = self::getScheduleValue($session_id);
                    $attendance_date .= " $time:00";

                    $attendance = new Attendance();

                    $course_info = api_get_course_info($course_data['code']);
                    $attendance->set_course_id($course_info['real_id']);
                    $attendance->set_session_id($session_id);
                    $attendance_list = array();
                    if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions'] && isset($data_list['sessions_attendances'][$course_info['real_id']][$session_id])) {
                        $list = $data_list['sessions_attendances'][$course_info['real_id']][$session_id];
                        foreach ($list as $at_id) {
                            $attendance_list[$at_id] = array('id' => $at_id);
                        }
                    } else {
                        $attendance_list = $attendance->get_attendances_list($course_info['real_id'], $session_id);
                    }

                    if (count($attendance_list) == 0) {
                        $d = array(
                            'session_id' => $session_id,
                            'user_id' => $user_id,
                            'fecha' => $attendance_date,
                            'status' => $attendance_user_status,
                        );
                        $attendance->set_name('Asistencia');
                        $attendance->set_description('');
                        $link_to_gradebook = false;
                        $attendance_id = $attendance->attendance_add($link_to_gradebook);
                        if (is_array($data_list) && isset($data_list) && $data_list['boost_sessions']) {
                            $data_list['sessions_attendances'][$course_info['real_id']][$session_id][] = $attendance_id;
                        }
                        error_log("Attendance created in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id - $attendance_date");
                        //self::createAttendance($d);
                        /*return array(
                            'entity' => 'attendance_sheet',
                            'before' => null,
                            'after'  => $attendance_sheet_after,
                            'message' => "Attendance sheet added with id: $result",
                            'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                        );*/
                        /*
                        return array(
                            'message' => "Attendance not found for course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                        */
                        //only 1 course per session
                    } else {

                        $attendance_data = current($attendance_list);
                        $attendance_id = $attendance_data['id'];
                        error_log("Attendance found in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id - $attendance_date");
                    }

                    $cal_info = $attendance->get_attendance_calendar_data_by_date($attendance_id, $attendance_date);

                    if ($cal_info && isset($cal_info['id'])) {
                        $cal_id = $cal_info['id'];
                    } else {

                        //Creating the attendance date
                        $attendance->set_date_time($attendance_date);
                        $cal_id = $attendance->attendance_calendar_add($attendance_id, true);

                        //error_log("Creating a new calendar item $cal_id");

                        /*return array(
                            'message' => "Attendance calendar does not exist for date: $attendance_date in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );*/
                    }

                    $users_present = array($user_id => $attendance_user_status);

                    $attendance_sheet_info = $attendance->get_users_attendance_sheet($cal_id, $user_id);

                    if (empty($attendance_sheet_info)) {
                        $result = $attendance->attendance_sheet_add($cal_id, $users_present, $attendance_id, false);
                        $attendance_sheet_after = $attendance->get_users_attendance_sheet($cal_id, $user_id);
                    } else {
                        return array(
                            'message' => "Attendance sheet can't be added, because it already exists - attendance_id: $attendance_id - cal_id: $cal_id - user_id: $user_id - course: {$course_info['code']} - session_id: $session_id ",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }

                    if ($result) {
                        return array(
                            'entity' => 'attendance_sheet',
                            'before' => null,
                            'after' => $attendance_sheet_after,
                            'message' => "Attendance sheet added with id: $result",
                            'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                        );
                    } else {
                        return array(
                            'message' => "Attendance sheet can't be added attendance_id: $attendance_id - cal_id: $cal_id - user_id: $user_id - course: {$course_info['code']} - session_id: $session_id ",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }
                } else {
                    $message = "Something is wrong with the course";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }
            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    /**
     * Eliminar assist_eliminar IID
     * const TRANSACTION_TYPE_DEL_ASSIST  = 35;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_35($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'asistenciaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'uididfecha' => $original_data['external_info'],
            'intIdSede' => $original_data['branch_id']
        ));

        if ($data['error'] == false) {

            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];

            $attendance_date = $data['date_assist'];

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {

                    //Check if user exist in the session
                    $status = SessionManager::get_user_status_in_course_session($user_id, $course_data['code'],
                        $session_id);

                    if ($status === false) {
                        return array(
                            'message' => "User #$user_id is not registered in course code: {$course_data['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }

                    $time = self::getScheduleValue($session_id);
                    $attendance_date .= " $time";

                    $attendance = new Attendance();

                    $course_info = api_get_course_info($course_data['code']);
                    $attendance->set_course_id($course_info['real_id']);
                    $attendance->set_session_id($session_id);

                    $attendance_list = $attendance->get_attendances_list($course_info['real_id'], $session_id);

                    if (empty($attendance_list)) {
                        return array(
                            'message' => "Attendance not found for course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                        //only 1 course per session
                    } else {
                        $attendance_data = current($attendance_list);
                        $attendance_id = $attendance_data['id'];
                        //error_log("Attendance found in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id - $attendance_date");
                    }

                    $cal_info = $attendance->get_attendance_calendar_data_by_date($attendance_id, $attendance_date);

                    if ($cal_info && isset($cal_info['id'])) {
                        $cal_id = $cal_info['id'];
                    } else {
                        return array(
                            'message' => "Attendance calendar does not exist for date: $attendance_date in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }

                    $attendance_sheet_before = $attendance->get_users_attendance_sheet($cal_id, $user_id);

                    $result = $attendance->disableAttendanceSheet($cal_id, $user_id);

                    $attendance_sheet_after = $attendance->get_users_attendance_sheet($cal_id, $user_id);


                    if ($result) {
                        return array(
                            'entity' => 'attendance_sheet',
                            'before' => $attendance_sheet_before,
                            'after' => $attendance_sheet_after,
                            'message' => "Attendance sheet removed",
                            'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                        );
                    } else {
                        return array(
                            'message' => "Attendance sheet can't be removed attendance_id: $attendance_id - cal_id: $cal_id - user_id: $user_id - course: {$course_info['code']} - session_id: $session_id ",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }
                } else {
                    $message = "Something is wrong with the course";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }
            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    /**
     * Editar assist_editar IID
     * const TRANSACTION_TYPE_EDIT_ASSIST = 36;
     * @param $original_data
     * @param $web_service_details
     * @return array
     */
    static function transaction_36($original_data, $web_service_details)
    {
        global $data_list;
        $data = Migration::soap_call($web_service_details, 'asistenciaDetalles', array(
            'uididpersona' => $original_data['item_id'],
            'uididprograma' => $original_data['origin'],
            'uididfecha' => $original_data['external_info'],
            'intIdSede' => $original_data['branch_id']
        ));
        if ($data['error'] == false) {

            $uidIdPrograma = $original_data['origin'];
            $uidIdPersona = $original_data['item_id'];

            $attendance_date = $data['date_assist'];
            $attendance_user_status = $data['status']; // modified in the asistenciaDetalles function

            $session_id = self::getSessionIDByProgramID($uidIdPrograma, $data_list);
            $user_id = self::getUserIDByPersonaID($uidIdPersona, $data_list);

            if (empty($user_id)) {
                return array(
                    'message' => "User does not exists in DB: $uidIdPersona",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            if (empty($session_id)) {
                return array(
                    'message' => "Session does not exists $uidIdPrograma",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }

            $course_list = SessionManager::get_course_list_by_session_id($session_id);

            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {

                    //Check if user exist in the session
                    $status = SessionManager::get_user_status_in_course_session($user_id, $course_data['code'],
                        $session_id);

                    if ($status === false) {
                        return array(
                            'message' => "User #$user_id is not registered in course code: {$course_data['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }

                    $time = self::getScheduleValue($session_id);
                    $attendance_date .= " $time:00";

                    $attendance = new Attendance();
                    $course_info = api_get_course_info($course_data['code']);
                    $attendance->set_course_id($course_info['real_id']);
                    $attendance->set_session_id($session_id);

                    $attendance_list = $attendance->get_attendances_list($course_info['real_id'], $session_id);

                    if (empty($attendance_list)) {
                        return array(
                            'message' => "Attendance not found for course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                        //only 1 course per session
                    } else {
                        $attendance_data = current($attendance_list);
                        $attendance_id = $attendance_data['id'];
                        //error_log("Attendance found in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id - $attendance_date");
                    }

                    $cal_info = $attendance->get_attendance_calendar_data_by_date($attendance_id, $attendance_date);

                    if ($cal_info && isset($cal_info['id'])) {
                        $cal_id = $cal_info['id'];
                    } else {
                        //Creating the attendance date

                        $attendance->set_date_time($attendance_date);
                        $cal_id = $attendance->attendance_calendar_add($attendance_id, true);
                        //error_log("Creating a new calendar item $cal_id");
                        /*
                        return array(
                            'message' => "Attendance calendar does not exist for date: $attendance_date in attendance_id = $attendance_id - course code: {$course_info['code']} - session_id: $session_id",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );*/
                    }
                    $users_present = array($user_id => $attendance_user_status);

                    $attendance_sheet_before = $attendance->get_users_attendance_sheet($cal_id, $user_id);

                    $result = $attendance->attendance_sheet_add($cal_id, $users_present, $attendance_id, false);

                    $attendance_sheet_after = $attendance->get_users_attendance_sheet($cal_id, $user_id);

                    if ($result) {
                        return array(
                            'entity' => 'attendance_sheet',
                            'before' => $attendance_sheet_before,
                            'after' => $attendance_sheet_after,
                            'message' => "Attendance sheet edited with",
                            'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                        );
                    } else {
                        return array(
                            'message' => "Attendance sheet can't be edited attendance_id: $attendance_id - cal_id: $cal_id - user_id: $user_id - course: {$course_info['code']} - session_id: $session_id ",
                            'status_id' => self::TRANSACTION_STATUS_FAILED
                        );
                    }
                } else {
                    $message = "Course is not set";
                }
            } else {
                $message = "NO course found for session id: $session_id";
            }
            return array(
                'message' => $message,
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        } else {
            return $data;
        }
    }

    //Custom class moved here

    /**
     * @param array $data
     * @return array
     */
    static function transacciones($data)
    {
        if ($data) {
            $xml = $data->transaccionesResult->any;
            // Cut the invalid XML and extract the valid chunk with the data
            $stripped_xml = strstr($xml, '<diffgr:diffgram');
            $xml = simplexml_load_string($stripped_xml);
            if (!empty($xml->NewDataSet)) {
                $transaction_list = array();
                foreach ($xml->NewDataSet->Table as $item) { //this is a "Table" object
                    $item = (array)$item;
                    $transaction_list[] = $item;
                }
                return $transaction_list;
            } else {
                error_log('No transactions found');
            }
        } else {
            error_log('Data is not valid');
        }

        return [];
    }

    /*  object(SimpleXMLElement)#11 (5) {
      ["idt"]=>
      string(6) "354913"
      ["idsede"]=>
      string(1) "2"
      ["ida"]=>
      string(2) "10"
      ["id"]=>
      string(36) "cf0f2c9b-3e79-4960-8dec-b1a02b367921"
      ["timestamp"]=>
      string(12) "AAAAATbYxkg="
    }
    */
    /**
     * @param $params
     * @param $web_service_details
     * @param bool $check_duplicates_in_db
     * @return int
     */
    static function processTransactions($params, $web_service_details, $check_duplicates_in_db = false)
    {
        error_log('WS: Query last '.$params['cantidad'].' tx from tx '.$params['ultimo'].' in branch '.$params['intIdSede']);
        $transactions = Migration::soap_call($web_service_details, 'transacciones', $params);
        error_log('WS:   WS called, answer received');
        $transaction_status_list = self::getTransactionStatusList();
        $counter = 0;

        if (isset($transactions) && isset($transactions['error']) && $transactions['error'] == true) {
            error_log('WS:   '.$transactions['message']);
        } else {
            $counter = count($transactions);
            error_log("WS:   Processing $counter transaction(s)");
            // YYYY Uncomment following line to get a detailed list of transactions read
            //error_log("Transactions: \n".print_r($transactions,1));
            $count = 1;
            $exclude_list = self::checkTransactionsDuplicity($transactions, $check_duplicates_in_db);
            if (!empty($transactions)) {
                foreach ($transactions as $id => $transaction_info) {
                    $result = array();
                    //Add transactions here
                    if (in_array($id, $exclude_list)) {
                        // Insert as deprecated
                        $result = self::processTransaction($transaction_info, $transaction_status_list, null, true);
                    } else {
                        // Do normal insert
                        $result = self::processTransaction($transaction_info, $transaction_status_list);
                    }
                    $count++;
                    if ($result['error'] == true) {
                        error_log('ERROR: '.$result['message']);
                        //exit;
                    } else {
                        //error_log($result['message']);
                    }
                }
            }
	    error_log("WS:   Done processing $counter transaction(s) for branch ".$params['intIdSede']);
        }
        return $counter;
    }

    /**
     * @param array $transaction_info
     * @return array|bool
     */
    static function validateTransaction($transaction_info)
    {
        if (empty($transaction_info) || empty($transaction_info['transaction_id']) || empty($transaction_info['action']) || empty($transaction_info['branch_id']) || empty($transaction_info['item_id'])) {
            return array(
                'id' => null,
                'error' => true,
                'message' => "Transaction could not be added there are some missing params: ".print_r($transaction_info,
                        1)
            );
        } else {
            return true;
        }
    }

    /**
     *
     * @param array $transaction_info simple return of the webservice transaction
     * @param array $transaction_status_list
     * @param bool $forced Force deletion of the transaction if it exists already (and reexecute)
     * @param bool $deprecated Whether this transaction should be inserted as deprecated or not
     * @return int
     */
    static function processTransaction(
        $transaction_info,
        $transaction_status_list = array(),
        $forced = false,
        $deprecated = false
    ) {
        if ($transaction_info) {
            if (empty($transaction_status_list)) {
                $transaction_status_list = self::getTransactionStatusList();
            }

            $params = array(
                'transaction_id' => isset($transaction_info['idt']) ? $transaction_info['idt'] : null,
                'action' => isset($transaction_info['ida']) ? $transaction_info['ida'] : null,
                'item_id' => isset($transaction_info['id']) ? strtoupper($transaction_info['id']) : null,
                'origin' => isset($transaction_info['orig']) ? $transaction_info['orig'] : null,
                'branch_id' => isset($transaction_info['idsede']) ? $transaction_info['idsede'] : null,
                'dest_id' => isset($transaction_info['dest']) ? $transaction_info['dest'] : null,
                'external_info' => isset($transaction_info['infoextra']) ? $transaction_info['infoextra'] : null,
                'status_id' => $deprecated ? self::TRANSACTION_STATUS_DEPRECATED : 0,
            );

            $validate = self::validateTransaction($params);

            if (isset($validate['error']) && $validate['error']) {
                return $validate;
            }

            if ($forced) {
                //Delete transaction
                Migration::delete_transaction_by_transaction_id($params['transaction_id'], $params['branch_id']);
            }

            //what to do if transaction already exists?
            $transaction_info = Migration::get_transaction_by_transaction_id($params['transaction_id'],
                $params['branch_id']);

            if (empty($transaction_info)) {
                $transaction_id = Migration::add_transaction($params);
                if ($deprecated) {
                    error_log('Inserted transaction '.$transaction_id.' as deprecated');
                }
                if ($transaction_id) {
                    return array(
                        'id' => $transaction_id,
                        'error' => false,
                        'message' => "3rd party trans id #{$params['transaction_id']} added to Chamilo, id #$transaction_id, status {$params['status_id']}"
                    );
                } else {
                    return array(
                        'id' => null,
                        'error' => true,
                        'message' => 'There was an error while creating the transaction'
                    );
                }
            } else {
                //only process transaction if it was failed or to be executed or is 0 registered
                if (in_array($transaction_info['status_id'],
                    array(0, self::TRANSACTION_STATUS_FAILED, self::TRANSACTION_STATUS_TO_BE_EXECUTED))) {
                    return array(
                        'id' => $transaction_info['id'],
                        'error' => false,
                        'message' => "Third party transaction id  #{$params['transaction_id']} was already added to Chamilo with id #{$transaction_info['id']}. Trying to execute because transaction has status: {$transaction_status_list[$transaction_info['status_id']]['title']}"
                    );
                } else {
                    // The transaction already exists and was already successfull.
                    // Don't report it in full to avoid filling logs with trash.
                    return array(
                        'id' => null,
                        'error' => true,
                        'message' => "TPT #{$params['transaction_id']} already {$transaction_status_list[$transaction_info['status_id']]['title']}"
                    );
                    /*
                    return array(
                        'id' => null,
                        'error' => true,
                        'message' => "Third party transaction id #{$params['transaction_id']} was already added to Chamilo with id #{$transaction_info['id']}. Transaction can't be executed twice. Transacion status_id = {$transaction_status_list[$transaction_info['status_id']]['title']}"
                    );
                    */
                }
            }
            return array(
                'id' => null,
                'error' => true,
                'message' => 'Third party transaction was already treated'
            );
        } else {
            return array(
                'id' => null,
                'error' => true,
                'message' => 'Third party transaction is not an array'
            );
        }
    }

    /**
     * @param $data
     * @param $result_name
     * @param array $params
     * @return array
     */
    static function genericDetalles($data, $result_name, $params = array())
    {
        error_log("Called $result_name");// with received data ".print_r($data,1));
        $original_webservice_name = $result_name;

        $result_name = $result_name.'Result';
        $xml = $data->$result_name->any;

        // Cut the invalid XML and extract the valid chunk with the data
        $stripped_xml = strstr($xml, '<diffgr:diffgram');
        $xml = simplexml_load_string($stripped_xml);

        if (!empty($xml->NewDataSet)) {
            $item = (array)$xml->NewDataSet->Table;
            $item['error'] = false;

            if (isset($item['uididsede'])) {
                $item['uididsede'] = strtoupper($item['uididsede']);
            }

            if (isset($item['uididhorario'])) {
                $item['uididhorario'] = strtoupper($item['uididhorario']);
            }
            return $item;
        } else {
            return array(
                'error' => true,
                'message' => "No data when calling web service *$original_webservice_name* Result:".print_r($data,
                        1)." with params: ".print_r($params, 1),
                'status_id' => self::TRANSACTION_STATUS_FAILED,
            );
        }
    }

    /**
     * @param $result
     * @param $params
     * @return array
    Returns an obj with this params
     * object(SimpleXMLElement)#11 (7) {
     * ["rol"]=>
     * string(8) "profesor"
     * ["username"]=>
     * string(4) "3525"
     * ["lastname"]=>
     * string(15) "Zegarra Acevedo"
     * ["firstname"]=>
     * string(10) "Allen Juan"
     * ["phone"]=>
     * object(SimpleXMLElement)#13 (0) {
     * }
     * ["email"]=>
     * string(17) "3525@aaa.com"
     * ["password"]=>
     * string(6) "xxx"
     * }
     */
    static function usuarioDetalles($result, $params)
    {
        $result = self::genericDetalles($result, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }

        $result['status'] = $result['rol'] == 'profesor' ? COURSEMANAGER : STUDENT;
        $result['phone'] = (string)$result['phone'];
        $result['active'] = (int)$result['active'];
        $result['extra_uididpersona'] = strtoupper($params['uididpersona']);
        $result['extra_birthdate'] = (string) $result['birthdate'];
        unset($result['birthdate']);
        unset($result['rol']);
        return $result;
    }

    /**
     * @param array $data
     * @param array $params
     * @return array
    ["uididsede"]=>
     * string(36) "7379a7d3-6dc5-42ca-9ed4-97367519f1d9"
     * ["uididhorario"]=>
     * string(36) "cdce484b-a564-4499-b587-bc32b3f82810"
     * ["chrperiodo"]=>
     * string(6) "200910"
     * ["display_start_date"]=>
     * string(25) "2009-09-30T00:00:00-05:00"
     * ["display_end_date"]=>
     * string(25) "2009-10-26T00:00:00-05:00"
     * ["access_start_date"]=>
     * string(25) "2009-09-30T00:00:00-05:00"
     * ["access_end_date"]=>
     * string(25) "2009-10-26T00:00:00-05:00"
     *
     * ["c_id"]=>
     * string(36) "5b7e9b5a-5145-4a42-be48-223a70d9ad52"
     * ["id_coach"]=>
     * string(36) "26dbb1c1-32b7-4cf8-a81f-43d1cb231abe"
     *
     */
    static function programaDetalles($data, $params)
    {
        global $data_list;
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }

        //Searching course code
        $courseId = MigrationCustom::getRealCourseCode($result['course_code']);
        $result['c_id'] = $courseId;

        $course_info = api_get_course_info_by_id($courseId);

        //Getting sede
        $extra_field = new ExtraField('session');
        $extra_field_option = new ExtraFieldOption('session');

        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable('sede');
        $extra_field_option_info_sede = $extra_field_option->get_field_option_by_field_and_option(
            $extra_field_info['id'],
            $result['uididsede']
        );

        $sede_name = null;
        if (isset($extra_field_option_info_sede[0]) && !empty($extra_field_option_info_sede[0]['display_text'])) {
            $sede_name = $extra_field_option_info_sede[0]['display_text'];
        }

        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable('aula');
        $extra_field_option_info_aula = $extra_field_option->get_field_option_by_field_and_option(
            $extra_field_info['id'],
            $result['uididaula']
        );

        $aula_name = null;
        if (isset($extra_field_option_info_aula[0]) && !empty($extra_field_option_info_sede[0]['display_text'])) {
            $aula_name = $extra_field_option_info_aula[0]['display_text'];
        }

        //Getting horario
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable('horario');
        $extra_field_option_info_horario = $extra_field_option->get_field_option_by_field_and_option(
            $extra_field_info['id'],
            $result['uididhorario']
        );

        $horario_name = null;
        if (isset($extra_field_option_info_horario[0]) && !empty($extra_field_option_info_horario[0]['display_text'])) {
            $horario_name = $extra_field_option_info_horario[0]['display_text'];
        }

        //Setting the session name
        $result['name'] = substr($sede_name,
                13).' - '.$result['chrperiodo']." - ".$course_info['title'].'  '.$horario_name.' '.$aula_name;

        $result['extra_uidIdPrograma'] = strtoupper($params['uididprograma']);
        $result['extra_horario'] = strtoupper($result['uididhorario']);
        $result['extra_sede'] = strtoupper($result['uididsede']);
        $result['extra_aula'] = strtoupper($result['uididaula']);
        $result['extra_periodo'] = strtoupper($result['chrperiodo']);

        $result['display_start_date'] = MigrationCustom::cleanDateTimeFromWS($result['display_start_date']);
        $result['display_end_date'] = MigrationCustom::cleanDateTimeFromWS($result['display_end_date']);
        $result['access_start_date'] = MigrationCustom::cleanDateTimeFromWS($result['access_start_date']);
        $result['access_end_date'] = MigrationCustom::cleanDateTimeFromWS($result['access_end_date']);
        //$result['estado'] = intval($result['estado']);

        //Searching id_coach
        $result['id_coach'] = isset($result['id_coach'])
                ? MigrationCustom::getUserIDByPersonaID($result['id_coach'], $data_list)
                : null;

        unset($result['uididprograma']);
        unset($result['uididsede']);
        unset($result['uididhorario']);
        unset($result['chrperiodo']);

        return $result;
    }

    /**
     *
     * @param array $data
     * @param array $params
     * @return array
    ["name"]=>
     * string(42) "(A02SA) Pronunciacion Two Sabado Acelerado"
     * ["frecuencia"]=>
     * string(36) "0091cd3b-f042-11d7-b338-0050dab14015"
     * ["intensidad"]=>
     * string(36) "0091cd3d-f042-11d7-b338-0050dab14015"
     * ["fase"]=>
     * string(36) "90854fc9-f748-4e85-a4bd-ace33598417d"
     * ["meses"]=>
     * string(3) "2  "
     */
    static function cursoDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }

        $result['title'] = $result['name'];
        $result['extra_frecuencia'] = strtoupper($result['frecuencia']);
        $result['extra_intensidad'] = strtoupper($result['intensidad']);
        $result['extra_fase'] = strtoupper($result['fase']);
        $result['extra_meses'] = strtoupper($result['meses']);
        $result['extra_uidIdCurso'] = strtoupper($params['uididcurso']);

        unset($result['frecuencia']);
        unset($result['intensidad']);
        unset($result['fase']);
        unset($result['name']);
        unset($result['meses']);

        return $result;
    }

    /**
     * @param array $data
     * @param array $params
     * @return array
     * Calling frecuenciaDetalles
     * array(1) {
     * ["name"]=>
     * string(8) "Sabatino"
     * }
     */
    static function faseDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    /**
     * @param $data
     * @param $params
     * @return array
     */
    static function frecuenciaDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    /**
     * @param array $data
     * @param array $params
     * @return array
     * Calling intensidadDetalles
     * array(1) {
     * ["name"]=>
     * string(6) "Normal"
     * }
     */
    static function intensidadDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        //$result['option_value'] = $params['uididintensidad'];
        return $result;
    }

    /**
     * Calling mesesDetalles
     * @param array $data
     * @param array $params
     * @return array
    Calling mesesDetalles
     * array(1) {
     * ["name"]=>
     * string(3) "4  "
     * }
     */
    static function mesesDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    static function aulaDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    /**
     * Calling sedeDetalles
     * @param array $data
     * @param array $params
     * @return array
    array(1) {
     * ["name"]=>
     * string(23) "Sede Miraflores"
     * }
     */
    static function sedeDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    /**
     * Calling horarioDetalles
     * @param array $data
     * @param array $params
     * @return array
    array(3) {
     * ["start"]=>
     * string(5) "08:45"
     * ["end"]=>
     * string(5) "10:15"
     * ["id"]=>
     * string(2) "62"
     * }
     */
    static function horarioDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        if ($result['error'] == true) {
            return $result;
        }

        $result['name'] = $result['id'].' '.$result['start'].' '.$result['end'];
        //$result['option_value'] = $params['uididhorario'];
        unset($result['id']);
        unset($result['start']);
        unset($result['end']);
        return $result;
    }

    /**
     *
     * @param array $data
     * @param array $params
     * @return array
     */
    static function notaDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        //error_log(print_r($result, 1));
        if ($result['error'] == true) {
            return $result;
        }
        return $result;
    }

    /**
     * @param array $data
     * @param array $params
     * @return array
     */
    static function asistenciaDetalles($data, $params)
    {
        $result = self::genericDetalles($data, __FUNCTION__, $params);
        //error_log(print_r($result, 1));
        if ($result['error'] == true) {
            return $result;
        }
        $array = array(
            'AUS' => 0,//(ausencia)
            'TAR' => 2,//(tarde)
            'DEF' => 4, //(default -> cuando todavía no se marcó nada)
            'PRE' => 1,//(presente)
            'T45' => 3,//(tarde de más de 45 minutos)
        );

        if (isset($result['name'])) {
            $result['name'] = trim($result['name']);
            $result['status'] = isset($array[$result['name']]) ? $array[$result['name']] : 4;
        }
        $result['date'] = trim($result['date_assist']);

        return $result;
    }

    /**
     * Review the dates given in the session details array and make sure we
     * define them in the best possible way. Although it is nice to call this
     * function, in general, people from ICPNA define the date during an update
     * following the creation.
     * @param array Array of session data passed by reference (modified in-place)
     * @return bool Always returns true
     */
    static function fixAccessDates(&$data)
    {
        // Check the $data array for access_start_date, access_end_date,
        //  coach_access_start_date and coach_access_end_date. If any is not
        //  defined, reuse the period or other dates to fill it
        $nt = '0000-00-00 00:00:00'; //declar a "null time"
        $period = (!empty($data['extra_field_periodo']) ? $data['extra_field_periodo'] : '000000');
        $asd = (!empty($data['access_start_date']) ? $data['access_start_date'] : $nt);
        $aed = (!empty($data['access_end_date']) ? $data['access_end_date'] : $nt);
        $casd = (!empty($data['coach_access_start_date']) ? $data['coach_access_start_date'] : $nt);
        $caed = (!empty($data['coach_access_end_date']) ? $data['coach_access_end_date'] : $nt);
        $dsd = (!empty($data['display_start_date']) ? $data['display_start_date'] : $asd);
        $ded = (!empty($data['display_end_date']) ? $data['display_end_date'] : $aed);
        $vstart = $vend = $nt;
        $matches = array();
        $match = preg_match('/-\s(\d{4})(\d{2})\s-/', $data['name'], $matches);
        $now = new DateTime(null);
        $cy = $now->format('Y');
        $cm = $now->format('m');
        if (!empty($match)) {
            $ny = $y = $matches[1];
            $nm = 1 + $m = $matches[2];
            //ignore current month
            if ($y == $cy && $m == $cm) {
                //do nothing
            } else {
                if ($m == 12) {
                    $ny = $y + 1;
                    $nm = 1;
                }
                $start = new DateTime();
                $end = new DateTime();
                $start->setDate($y, $m, 1);
                $end->setDate($ny, $nm, 1);
                $end->modify('-1 day');
                $vstart = $start->format('Y-m-d H:i:s');
                $vend = $end->format('Y-m-d H:i:s');
            }
        }
        // Now assess the situation
        if ($period != '000000') {
            if ($asd != $nt && $aed != $nt && $casd != $nt && $caed != $nt) {
                //everything is defined, perfect, nothing to do
            } else {
                if ($asd == $nt) {
                    //if access_start_date is undefined, re-use the period's date
                    $asd = $vstart;
                }
                if ($casd == $nt) {
                    //access_start_date is defined but not coach_access_start_date,
                    // so re-use access_start_date
                    $casd = $asd;
                }
                if ($aed == $nt) {
                    //if access_end_date is undefined, re-use the period's date
                    $aed = $vend;
                }
                if ($caed == $nt) {
                    //access_end_date is defined but not coach_access_end_date,
                    // so re-use access_end_date
                    $caed = $aed;
                }
            }
        } else {
            // if the period is not defined
            if ($asd != $nt && $casd == $nt) {
                $casd = $asd;
            }
            if ($aed != $nt && $caed == $nt) {
                $caed = $aed;
            }
        }
        // If the session is not a "PLEX" session, we will give access to coaches one day before
        if (!empty($data['name'])) {
            $isPlex = preg_match('/PLEX/i', $data['name']);
            // if the session is not a PLEX
            if (!$isPlex) {
                // Then coach date must be one day before the given date
                $temp = new DateTime($casd);
                $temp->modify('-1 day');
                $casd = $temp->format('Y-m-d H:i:s');
            }
        }
        // Fix end dates at 23:59:59 if same as start date
        if ($asd == $aed) {
            $aed = substr($aed, 0, 11).'23:59:59';
        }
        if ($casd == $caed) {
            $caed = substr($caed, 0, 11).'23:59:59';
        }
        if ($dsd == $ded) {
            $ded = substr($ded, 0, 11).'23:59:59';
        }
        $data['access_start_date'] = $asd;
        $data['access_end_date'] = $aed;
        $data['display_start_date'] = $dsd;
        $data['display_end_date'] = $ded;
        $data['coach_access_start_date'] = $casd;
        $data['coach_access_end_date'] = $caed;
        $data['name'] .= ' [#'.substr($dsd, 8, 2).']';

        return true;
    }

    /**
     * Gets the value of the 'horario' field for a given session
     * @param int $session_id The session ID
     * @return string   The db-stored value of the 'horario' field for this session
     */
    static function getScheduleValue($session_id)
    {
        $extra_field_value = new ExtraFieldValue('session');
        //Getting horario info
        $extra_field = new ExtraField('session');
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable('horario');

        $horario_info = $extra_field_value->get_values_by_handler_and_field_id($session_id, $extra_field_info['id']);
        $extra_field_option = new ExtraFieldOption('session');
        $horario_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['id'],
            $horario_info['value']);

        $time = "08:00";
        if (isset($horario_info) && isset($horario_info[0])) {
            $horario = $horario_info[0]['display_text'];
            $horario_array = explode(' ', $horario);
            //Schedule format is "(01) 07:00 09:00" in this case. Adapt to your case
            if (isset($horario_array[1])) {
                $time = $horario_array[1];
            }
        }
        return $time;
    }

    /**
     * Prepare an array of items to avoid many queries to the database afterwards, during the treatment
     * of transactions. The data provided depends on the elements contained in the array passed as parameter.
     * @param array $omigrate Array of items pre-loaded from the database to boost processing
     * @return bool Always returns true
     */
    static function fillDataList(&$omigrate)
    {
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_users']) {
            // uidIdPersona field is ID 13 in user_field
            $sql = "SELECT id FROM extra_field WHERE extra_field_type = 1 AND variable = 'uididpersona'";
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            $fieldId = $row['id'];
            $sql = "SELECT item_id, value FROM extra_field_values WHERE field_id = $fieldId ORDER BY item_id";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['users'][$row['value']] = $row['item_id'];
            }
        }
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_courses']) {
            // uidIdCurso field is ID 5 in course_field
            $sql = "SELECT id FROM extra_field WHERE extra_field_type = 2 AND variable = 'uididcurso'";
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            $fieldId = $row['id'];
            $sql = "SELECT item_id, value FROM extra_field_values WHERE field_id = $fieldId ORDER BY item_id";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['courses'][$row['value']] = $row['item_id'];
            }
            $sql = "SELECT id, code FROM course";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['course_ids'][$row['code']] = $row['id'];
            }
        }
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_sessions']) {
            // uidIdPrograma field is ID 1 in session_field
            $sql = "SELECT id FROM extra_field WHERE extra_field_type = 3 AND variable = 'uididprograma'";
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            $fieldId = $row['id'];
            $sql = "SELECT item_id, value FROM extra_field_values WHERE field_id = $fieldId ORDER BY item_id";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['sessions'][$row['value']] = $row['item_id'];
            }
            $sql = "SELECT session_id, c_id FROM session_rel_course";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['session_course'][$row['session_id']] = $row['c_id'];
            }
        }
        if (is_array($omigrate) && isset($omigrate) && $omigrate['boost_gradebooks']) {
            //$evals_found = $eval->load(null, null, $course_data['code'], $gradebook['id'], null, null, $title);
            $tbl_grade_evaluations = Database:: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
            $sql = "SELECT id, course_code, category_id, name FROM $tbl_grade_evaluations ORDER BY course_code";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['course_evals'][$row['course_code']][$row['category_id']][$row['name']] = $row['id'];
            }
            $tbl_grade_results = Database:: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
            $sql = "SELECT id, user_id, evaluation_id FROM $tbl_grade_results ORDER BY evaluation_id";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['course_eval_results'][$row['evaluation_id']][$row['user_id']] = $row['id'];
            }
            $sql = "SELECT course_code, session_id, id FROM gradebook_category ORDER BY course_code, session_id";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $omigrate['session_course_gradebook'][$row['course_code']][$row['session_id']] = $row['id'];
            }
        }
        return true;
    }

    /**
     * Check if some transactions are not deprecated because of another one
     * doing the same afterwards
     * @param array $transactions Array of transactions as received by the web service call
     * @param bool $check_duplicates_in_db Check in database if there is any posterior operation making this one useless
     * @return array Simplified list of IDs to exclude from the transaction array
     */
    protected function checkTransactionsDuplicity($transactions, $check_duplicates_in_db = false)
    {
        // The $transactions array received has rows with the following format
        // $transaction_info('idt','ida','id','orig','idsede','dest','infoextra')
        $cleanable = array();
        $list_all = array_keys($transactions);
        $exclude = array();
        // Browse through all transactions
        foreach ($transactions as $id => $t) {
            //If the item is in the "cleanable actions" list, register it.
            //  Otherwise, just ignore (it won't be inserted in the excluded
            //  list)
            if (in_array($t['ida'], self::$cleanableActions)) {
                if (!$check_duplicates_in_db) {
                    if (isset($cleanable[$t['id']][$t['ida']])) {
                        //if same id, same action was already defined, remember id
                        // for exclusion
                        //error_log('Excluding duplicated transaction '.$t['idt'].'. Transaction '.$transactions[$cleanable[$t['id']][$t['ida']]]['idt'].' duplicates it');
                        //error_log('SELECT * FROM branch_transaction WHERE branch_id = '.$t['idsede'].' AND transaction_id IN ('.$t['idt'].','.$transactions[$cleanable[$t['id']][$t['ida']]]['idt'].');');
                        $exclude[] = $cleanable[$t['id']][$t['ida']];
                    }
                    $cleanable[$t['id']][$t['ida']] = $id;
                } else {
                    // Select in database if there is another (later) transaction doing the same
                    // check operations that cancel others
                    $or = '';
                    switch ($t['ida']) {
                        case self::TRANSACTION_TYPE_ADD_FASE:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_FASE.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_INTENS:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_INTENS.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_FREQ:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_FREQ.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_BRANCH:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_BRANCH.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_ROOM:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_ROOM.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_SCHED:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_SCHED.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_SESS:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_SESS.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_COURSE:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_COURSE.' ';
                            break;
                        case self::TRANSACTION_TYPE_ADD_USER:
                            $or .= ' OR action = '.self::TRANSACTION_TYPE_DEL_USER.' ';
                            break;
                    }
                    $sql = 'SELECT id FROM branch_transaction
                      WHERE branch_id = '.$t['idsede'].'
                        AND item_id = '.$t['id'].'
                        AND  (action = '.$t['ida'].' '.$or.' )
                        AND transaction_id > '.$t['idt'];
                    $res = Database::query($sql);
                    if ($res === false or Database::num_rows($res) <= 0) {
                        //nothing found, proceed with transaction (do not exclude)
                    } else {
                        $exclude[] = $id;
                    }
                }
            }
        }
        return $exclude;
    }

    /**
     * @param $session_id
     * @param $user_id
     * @return array
     */
    static function getUserStatusInSession($session_id, $user_id) {
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $session_id = intval($session_id);
        $user_id = intval($user_id);
        $sql = "SELECT * FROM $tbl_session_rel_user WHERE user_id = $user_id AND session_id = $session_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::store_result($result, 'ASSOC');
            return $result[0];
        }
        return array();
    }

    /**
     * Creates a new user for the platform from a custom WS
     * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
     * @author Roan Embrechts <roan_embrechts@yahoo.com>
     * @param  string $firstName
     * @param  string $lastName
     * @param  int    $status (1 for course tutor, 5 for student, 6 for anonymous)
     * @param  string $email
     * @param  string $loginName
     * @param  string $password
     * @param  string $uididpersona
     * @return mixed   new user id - if the new user creation succeeds, false otherwise
     * @desc The function tries to retrieve user id from the session.
     * If it exists, the current user id is the creator id. If a problem arises,
     * @assert ('Sam','Gamegie',5,'sam@example.com','jo','jo') > 1
     * @assert ('Pippin','Took',null,null,'jo','jo') === false
     */
    static function create_user_custom_ws(
        $firstName,
        $lastName,
        $status,
        $email,
        $loginName,
        $password,
        $uididpersona
    ) {

        global $matches;

        if (empty($uididpersona)) {
            return false;
        }

        $currentUserId = api_get_user_id();
        $hook = HookCreateUser::create();
        if (!empty($hook)) {
            $hook->notifyCreateUser(HOOK_EVENT_TYPE_PRE);
        }

        $original_password = $password;

        $language = api_get_setting('platformLanguage');

        if (!empty($currentUserId)) {
            $creator_id = $currentUserId;
        } else {
            $creator_id = 0;
        }

        $now = new DateTime();
        $now = $now->format('Y-m-d H:i:s');

        $t_user = Database::get_main_table(TABLE_MAIN_USER);

        $username = Database::escape_string($loginName);
        $sql = "SELECT id FROM $t_user WHERE username = '$username'";
        $res = Database::query($sql);
        $count = Database::num_rows($res);
        if ($count > 0) {
            //user already exists, return
            $auxRow = Database::fetch_assoc($res);
            error_log("El usuario '$username' ya existe en la plataforma - id: {$auxRow['id']}");
            return false;
        }

        try {
            $userId = Database::insert($t_user, [
                'username' => $loginName,
                'username_canonical' => mb_convert_case($loginName, MB_CASE_LOWER),
                'email' => $email,
                'email_canonical' => mb_convert_case($email, MB_CASE_LOWER),
                'lastname' => $lastName,
                'firstname' => $firstName,
                'password' => $password,
                'auth_source' => PLATFORM_AUTH_SOURCE,
                'status' => $status,
                'official_code' => '',
                'phone' => '',
                'address' => '',
                'picture_uri' => '',
                'creator_id' => $creator_id,
                'language' => $language,
                'registration_date' => $now,
                'hr_dept_id' => 0,
                'active' => 1,
                'enabled' => 1,
                'salt' => sha1(uniqid(null, true)),
                'roles' => 'a:0:{}',
                'created_at' => $now,
                'updated_at' => $now
            ]);
        } catch (Exception $e) {
            error_log('El usuario '.$username.' no pudo ser insertado - quizás ya exista ('.$e->getMessage().')');
            return false;
        }

        if (!empty($userId)) {

            $return = $userId;
            $sql = "UPDATE $t_user SET user_id = $return WHERE id = $return";
            Database::query($sql);

            if (!empty($matches['web_service_calls']['accessUrlId'])) {
                UrlManager::add_user_to_url($userId, $matches['web_service_calls']['accessUrlId']);
            } else {
                //we are adding by default the access_url_user table with access_url_id = 1
                $currentAccessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id(): 1;

                UrlManager::add_user_to_url($userId, $currentAccessUrlId);
            }

            //$extraData = self::get_extra_user_data_custom_ws();
            //$extraFieldsToInsert = self::get_extra_fields_user_custom_ws();
            //ksort($extraFieldsToInsert);

            //unset($extraFieldsToInsert['tags']);

            //$t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
            //
            //foreach ($extraFieldsToInsert as $id => $name) {
            //    Database::insert($t_ufv,
            //        [
            //            'field_id' => $id,
            //            'value' => '',
            //            'item_id' => $userId,
            //            'created_at' => $now,
            //            'updated_at' => $now
            //        ]
            //    );
            //}

            //Database::query("UPDATE $t_ufv SET value = 'false' WHERE field_id = '".Database::escape_string($extraData['already_logged_in'])."' AND item_id = $userId");
            //Database::query("UPDATE $t_ufv SET value = 1 WHERE field_id = '".Database::escape_string($extraData['mail_notify_group_message'])."' AND item_id = $userId");
            //Database::query("UPDATE $t_ufv SET value = 1 WHERE field_id = '".Database::escape_string($extraData['mail_notify_invitation'])."' AND item_id = $userId");
            //Database::query("UPDATE $t_ufv SET value = 1 WHERE field_id = '".Database::escape_string($extraData['mail_notify_message'])."' AND item_id = $userId");

            if (!empty($hook)) {
                $hook->setEventData(array(
                    'return' => $userId,
                    'originalPassword' => $original_password
                ));
                $hook->notifyCreateUser(HOOK_EVENT_TYPE_POST);
            }
            Event::addEvent(LOG_USER_CREATE, LOG_USER_ID, $userId);
        } else {
            Display::addFlash(Display::return_message(get_lang('ErrorContactPlatformAdmin')));

            return false;
        }

        return $return;
    }


    /**
     * Gets extra fields user fields data
     * @return    array    Array of fields => value for the given user
     */
    static function get_extra_fields_user_custom_ws()
    {

        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE
                    extra_field_type = 1
                ";

        $sql .= " ORDER BY f.id";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $extra_data[$row['id']] = $row['fvar'];
            }
        }

        return $extra_data;
    }

    /**
     * Gets extra user fields data
     * @return    array    Array of fields => value for the given user
     */
    static function get_extra_user_data_custom_ws()
    {

        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE
                    extra_field_type = 1
                ";

        $sql .= " ORDER BY f.id";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $extra_data[$row['fvar']] = $row['id'];
            }
        }

        return $extra_data;
    }

}
