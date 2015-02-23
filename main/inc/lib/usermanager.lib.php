<?php
/* For licensing terms, see /license.txt */
/**
* This library provides functions for user management.
* Include/require it in your code to use its functionality.
* @package chamilo.library
* @author Julio Montoya <gugli100@gmail.com> Social network groups added 2009/12
*/
/**
 * Class
 * @package chamilo.include.user
 */
class UserManager {
    static public $columns = array(
        'user_id',
        'lastname',
        'firstname',
        'username',
        'password',
        'auth_source',
        'email',
        'status',
        'official_code',
        'phone',
        'picture_uri',
        'creator_id',
        'competences',
        'diplomas',
        'openarea',
        'teach',
        'productions',
        'chatcall_user_id',
        'chatcall_date',
        'chatcall_text',
        'language',
        'registration_date',
        'expiration_date',
        'active',
        'openid',
        'theme',
        'hr_dept_id'
    );

    /**
     * Empty constructor. This class is mostly static.
     */
    public function __construct () {
    }

    static function clean_params($params) {
        $clean_params = array();
        foreach ($params as $key => $value) {
            if (in_array($key, self::$columns)) {
                $clean_params[$key] = $value;
            }
        }
        return $clean_params;
    }

    /**
     * Simpler version of create_user(). Doesn't send an e-mail and doesn't manage extra
     * fields, between other things
     * @param array Array of user details (array('status'=>...,'username'=>..., ...))
     * @return mixed Array of user information
     */
    public static function add($params) {
        global $_configuration;

        $access_url_id = 1;

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }

        //Hosting verifications
        $status = isset($params['status']) ? $params['status'] : STUDENT;

        if (api_get_setting('login_is_email') == 'true') {
            $params['username'] = $params['email'];
        }

        if (is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_users']) && $_configuration[$access_url_id]['hosting_limit_users'] > 0) {
            $num = self::get_number_of_users();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_users']) {
                return api_set_failure('portal users limit reached');
            }
        }

        if ($status === 1 && is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_teachers']) && $_configuration[$access_url_id]['hosting_limit_teachers'] > 0) {
            $num = self::get_number_of_users(1);
            if ($num >= $_configuration[$access_url_id]['hosting_limit_teachers']) {
                return api_set_failure('portal teachers limit reached');
            }
        }

        $params['email'] = api_valid_email($params['email']) ? $params['email'] : null;

        if (isset($params['user_id'])) {
            unset($params['user_id']);
        }

        if (empty($params['username'])) {
            return api_set_failure('provide a username');
        }

        $params['username'] = self::purify_username($params['username']);

        // First check wether the login already exists
        if (!self::is_username_available($params['username'])) {
            //Already added it
            if (isset($params['return_item_if_already_exists']) && $params['return_item_if_already_exists']) {
                $user_info = self::get_user_info_simple($params['username']);
                return $user_info;
            }
            return api_set_failure('login-pass already taken');
        }

        unset($params['return_item_if_already_exists']);

        //Checking the user language
        $languages = api_get_languages();

        if (!isset($params['language']) || !in_array($params['language'], $languages['folder'])) {
            $params['language'] = api_get_setting('platformLanguage');
        }

        if (!isset($params['creator_id'])) {
            $params['creator_id'] = api_get_user_id();
        }

        if (empty($params['encrypt_method'])) {
            $params['password'] = api_get_encrypted_password($params['password']);
        } else {
            if ($_configuration['password_encryption'] === $params['encrypt_method']) {
                if ($params['encrypt_method'] == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $params['password'])) {
                    return api_set_failure('encrypt_method invalid');
                } else if ($params['encrypt_method'] == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $params['password'])) {
                    return api_set_failure('encrypt_method invalid');
                }
            } else {
                return api_set_failure('encrypt_method invalid');
            }
        }
        $params['registration_date'] = api_get_utc_datetime();

        // Database table definition
        $table = Database::get_main_table(TABLE_MAIN_USER);

        $clean_params = self::clean_params($params);
        $user_id = Database::insert($table, $clean_params);

        if ($user_id) {
            if (api_get_multiple_access_url()) {
                UrlManager::add_user_to_url($user_id, api_get_current_access_url_id());
            } else {
                //we are adding by default the access_url_user table with access_url_id = 1
                UrlManager::add_user_to_url($user_id, 1);
            }

            //saving extra fields
            $field_value = new ExtraFieldValue('user');
            $params['user_id'] = $user_id;
            $field_value->save_field_values($params);

            // Add event to system log
            $user_id_manager = api_get_user_id();
            $user_info = api_get_user_info($user_id);
            event_system(LOG_USER_CREATE, LOG_USER_ID, $user_id, api_get_utc_datetime(), $user_id_manager);
            event_system(LOG_USER_CREATE, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), $user_id_manager);
            return $user_info;
        } else {
            return api_set_failure('error inserting in Database');
        }
    }

    /**
     * Simple update user need to add validations here
     * @param type $params
     * @return boolean
     */
    public static function update($params) {
        global $_configuration;
        $table = Database::get_main_table(TABLE_MAIN_USER);
        if (empty($params['user_id'])) {
            return false;
        }

        if (isset($params['password'])) {
            if (empty($params['encrypt_method'])) {
                $params['password'] = api_get_encrypted_password($params['password']);
            } else {
                if ($_configuration['password_encryption'] === $params['encrypt_method']) {
                    if ($params['encrypt_method'] == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $params['password'])) {
                        return api_set_failure('encrypt_method invalid');
                    } else if ($params['encrypt_method'] == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $params['password'])) {
                        return api_set_failure('encrypt_method invalid');
                    }
                } else {
                    return api_set_failure('encrypt_method invalid');
                }
            }
        }

        //saving extra fields
        $field_value = new ExtraFieldValue('user');
        $params['user_id'] = $params['user_id'];
        $field_value->save_field_values($params);

        $clean_params = self::clean_params($params);

        return Database::update($table, $clean_params, array('user_id = ?' => $params['user_id']));
    }

    /**
     * Creates a new user for the platform
     * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
     * @author Roan Embrechts <roan_embrechts@yahoo.com>
     * @param    string    Firstname
     * @param    string    Lastname
     * @param    int       Status (1 for course tutor, 5 for student, 6 for anonymous)
     * @param    string    e-mail address
     * @param    string    Login
     * @param    string    Password
     * @param    string    Any official code (optional)
     * @param    string    User language    (optional)
     * @param    string    Phone number    (optional)
     * @param    string    Picture URI        (optional)
     * @param    string    Authentication source    (optional, defaults to 'platform', dependind on constant)
     * @param    string    Account expiration date (optional, defaults to '0000-00-00 00:00:00')
     * @param    int        Whether the account is enabled or disabled by default
     * @param    int        The department of HR in which the user is registered (optional, defaults to 0)
     * @param     array    Extra fields
     * @param    string    Encrypt method used if password is given encrypted. Set to an empty string by default
     * @return mixed   new user id - if the new user creation succeeds, false otherwise
     * @desc The function tries to retrieve $_user['user_id'] from the global space. If it exists, $_user['user_id'] is the creator id. If a problem arises, it stores the error message in global $api_failureList
     * @assert ('Sam','Gamegie',5,'sam@example.com','jo','jo') > 1
     * @assert ('Pippin','Took',null,null,'jo','jo') === false
     */
    public static function create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code = '', $language = '', $phone = '', $picture_uri = '', $auth_source = PLATFORM_AUTH_SOURCE, $expiration_date = '0000-00-00 00:00:00', $active = 1, $hr_dept_id = 0, $extra = null, $encrypt_method = '', $send_mail = false) {
        global $_user, $_configuration;
        $original_password = $password;
        $access_url_id = 1;

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }

        if (is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_users']) && $_configuration[$access_url_id]['hosting_limit_users'] > 0) {
            $num = self::get_number_of_users();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_users']) {
                return api_set_failure('portal users limit reached');
            }
        }

        if ($status === 1 && is_array($_configuration[$access_url_id]) && isset($_configuration[$access_url_id]['hosting_limit_teachers']) && $_configuration[$access_url_id]['hosting_limit_teachers'] > 0) {
            $num = self::get_number_of_users(1);
            if ($num >= $_configuration[$access_url_id]['hosting_limit_teachers']) {
                return api_set_failure('portal teachers limit reached');
            }
        }

        $firstName     = Security::remove_XSS($firstName);
        $lastName    = Security::remove_XSS($lastName);
        $loginName     = Security::remove_XSS($loginName);
        $phone         = Security::remove_XSS($phone);

        // database table definition
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        //Checking the user language
        $languages = api_get_languages();
        if (!in_array($language, $languages['folder'])) {
            $language = api_get_setting('platformLanguage');
        }

        if ($_user['user_id']) {
            $creator_id = intval($_user['user_id']);
        } else {
            $creator_id = '';
        }
        // First check wether the login already exists
        if (!self::is_username_available($loginName)) {
            return api_set_failure('login-pass already taken');
        }

        //$password = "PLACEHOLDER";

        if (empty($encrypt_method)) {
            $password = api_get_encrypted_password($password);
        } else {
            if ($_configuration['password_encryption'] === $encrypt_method ) {
                if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
                    return api_set_failure('encrypt_method invalid');
                } else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
                    return api_set_failure('encrypt_method invalid');
                }
            } else {
                return api_set_failure('encrypt_method invalid');
            }
        }


        //@todo replace this date with the api_get_utc_date function big problem with users that are already registered
        $current_date = api_get_utc_datetime();
        $sql = "INSERT INTO $table_user ".
               "SET lastname =         '".Database::escape_string(trim($lastName))."',".
               "firstname =         '".Database::escape_string(trim($firstName))."',".
               "username =            '".Database::escape_string(trim($loginName))."',".
               "status =             '".Database::escape_string($status)."',".
               "password =             '".Database::escape_string($password)."',".
               "email =             '".Database::escape_string($email)."',".
               "official_code    =     '".Database::escape_string($official_code)."',".
               "picture_uri     =     '".Database::escape_string($picture_uri)."',".
               "creator_id      =     '".Database::escape_string($creator_id)."',".
               "auth_source =         '".Database::escape_string($auth_source)."',".
               "phone =             '".Database::escape_string($phone)."',".
               "language =             '".Database::escape_string($language)."',".
               "registration_date = '".$current_date."',".
               "expiration_date =     '".Database::escape_string($expiration_date)."',".
               "hr_dept_id =         '".Database::escape_string($hr_dept_id)."',".
               "active =             '".Database::escape_string($active)."'";
        $result = Database::query($sql);

        if ($result) {
            //echo "id returned";
            $return = Database::insert_id();
            if (api_get_multiple_access_url()) {
                UrlManager::add_user_to_url($return, api_get_current_access_url_id());
            } else {
                //we are adding by default the access_url_user table with access_url_id = 1
                UrlManager::add_user_to_url($return, 1);
            }

            if (!empty($email) && $send_mail) {
                $recipient_name = api_get_person_name($firstName, $lastName, null, PERSON_NAME_EMAIL_ADDRESS);
                $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');

                $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
                $email_admin = api_get_setting('emailAdministrator');

                if ($_configuration['multiple_access_urls']) {
                    $access_url_id = api_get_current_access_url_id();
                    if ($access_url_id != -1) {
                        $url = api_get_access_url($access_url_id);
                        $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstName, $lastName)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $loginName ."\n". get_lang('Pass')." : ".stripslashes($original_password)."\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $url['url'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
                    }
                } else {
                    $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstName, $lastName)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $loginName ."\n". get_lang('Pass')." : ".stripslashes($original_password)."\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $_configuration['root_web'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
                }


                /* MANAGE EVENT WITH MAIL */
                if (EventsMail::check_if_using_class('user_registration')) {
                    $values["about_user"] = $return;
                    $values["password"] = $original_password;
                    $values["send_to"] = array($return);
                    $values["prior_lang"] = null;
                    EventsDispatcher::events('user_registration', $values);
                } else {
                    @api_mail_html($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
                }
                /* ENDS MANAGE EVENT WITH MAIL */
            }
            // Add event to system log
            $user_id_manager = api_get_user_id();
            $user_info = api_get_user_info($return);
            event_system(LOG_USER_CREATE, LOG_USER_ID, $return, api_get_utc_datetime(), $user_id_manager);
            event_system(LOG_USER_CREATE, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), $user_id_manager);

        } else {
            $return = false;
            return api_set_failure('error inserting in Database');
        }

        if (is_array($extra) && count($extra) > 0) {
            $res = true;
            foreach($extra as $fname => $fvalue) {
                $res = $res && self::update_extra_field_value($return, $fname, $fvalue);
            }
        }
        self::update_extra_field_value($return, 'already_logged_in', 'false');
        return $return;
    }

    /**
     * Can user be deleted? This function checks whether there's a course
         * in which the given user is the
     * only course administrator. If that is the case, the user can't be
     * deleted because the course would remain without a course admin.
     * @param int $user_id The user id
     * @return boolean true if user can be deleted
         * @assert (null) === false
         * @assert (-1) === false
         * @assert ('abc') === false
     */
    public static function can_delete_user($user_id) {
        global $_configuration;
        if (isset($_configuration['delete_users']) && $_configuration['delete_users'] == false) {
                return false;
        }
        $table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;
        $sql = "SELECT * FROM $table_course_user WHERE status = '1' AND user_id = '".$user_id."'";
        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
                $sql = "SELECT user_id FROM $table_course_user WHERE status='1' AND course_code ='".Database::escape_string($course->course_code)."'";
                $res2 = Database::query($sql);
                if (Database::num_rows($res2) == 1) {
                        return false;
                }
        }
        return true;
    }

    /**
     * Delete a user from the platform, and all its belongings. This is a
     * very dangerous function that should only be accessible by
     * super-admins. Other roles should only be able to disable a user,
     * which removes access to the platform but doesn't delete anything.
     * @param int The ID of th user to be deleted
     * @return boolean true if user is succesfully deleted, false otherwise
     * @assert (null) === false
     * @assert ('abc') === false
     */
    public static function delete_user($user_id) {

        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;

        if (!self::can_delete_user($user_id)) {
            return false;
        }
        $table_user                   = Database :: get_main_table(TABLE_MAIN_USER);
        $usergroup_rel_user           = Database :: get_main_table(TABLE_USERGROUP_REL_USER);
        $table_course_user            = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $table_class_user             = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
        $table_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
        $table_admin                  = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $table_session_user           = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
        $table_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_group                  = Database :: get_course_table(TABLE_GROUP_USER);
        $table_work                   = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

        // Unsubscribe the user from all groups in all his courses
        $sql = "SELECT c.id FROM $table_course c, $table_course_user cu
                WHERE cu.user_id = '".$user_id."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND c.code = cu.course_code";
        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
            $sql = "DELETE FROM $table_group WHERE c_id = {$course->id} AND user_id = $user_id";
            Database::query($sql);
        }

        // Unsubscribe user from all classes
        //Classes are not longer supported
        /*$sql = "DELETE FROM $table_class_user WHERE user_id = '".$user_id."'";
        Database::query($sql);*/

        // Unsubscribe user from usergroup_rel_user
        $sql = "DELETE FROM $usergroup_rel_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all courses
        $sql = "DELETE FROM $table_course_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all courses in sessions
        $sql = "DELETE FROM $table_session_course_user WHERE id_user = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all sessions
        $sql = "DELETE FROM $table_session_user WHERE id_user = '".$user_id."'";
        Database::query($sql);

        // Delete user picture
        // TODO: Logic about api_get_setting('split_users_upload_directory') === 'true' , a user has 4 differnt sized photos to be deleted.
        $user_info = api_get_user_info($user_id);

        if (strlen($user_info['picture_uri']) > 0) {
            $img_path = api_get_path(SYS_CODE_PATH).'upload/users/'.$user_id.'/'.$user_info['picture_uri'];
            if (file_exists($img_path))
                unlink($img_path);
        }

        // Delete the personal course categories
        $course_cat_table = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "DELETE FROM $course_cat_table WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user from database
        $sql = "DELETE FROM $table_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user from the admin table
        $sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete the personal agenda-items from this user
        $agenda_table = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
        $sql = "DELETE FROM $agenda_table WHERE user = '".$user_id."'";
        Database::query($sql);

        $gradebook_results_table = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'DELETE FROM '.$gradebook_results_table.' WHERE user_id = '.$user_id;
        Database::query($sql);

        $user = Database::fetch_array($res);
        $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $sqlv = "DELETE FROM $t_ufv WHERE user_id = $user_id";
        $resv = Database::query($sqlv);

        require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
        if (api_get_multiple_access_url()) {
            $url_id = api_get_current_access_url_id();
            UrlManager::delete_url_rel_user($user_id, $url_id);
        } else {
            //we delete the user from the url_id =1
            UrlManager::delete_url_rel_user($user_id, 1);
        }

        if (api_get_setting('allow_social_tool')=='true' ) {

            require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
            //Delete user from portal groups
            $group_list = GroupPortalManager::get_groups_by_user($user_id);
            if (!empty($group_list)) {
                foreach($group_list as $group_id => $data) {
                    GroupPortalManager::delete_user_rel_group($user_id, $group_id);
                }
            }

            //Delete user from friend lists
            SocialManager::remove_user_rel_user($user_id, true);
        }
        // Delete students works
        $sqlw = "DELETE FROM $table_work WHERE user_id = $user_id";
        $resw = Database::query($sqlw);
        unset($sqlw);
        // Add event to system log
        $user_id_manager = api_get_user_id();
        event_system(LOG_USER_DELETE, LOG_USER_ID, $user_id, api_get_utc_datetime(), $user_id_manager, null, $user_info);
            event_system(LOG_USER_DELETE, LOG_USER_OBJECT, implode(';',$user_info), api_get_utc_datetime(), $user_id_manager, null, $user_info);
        return true;
    }

    /**
     * Deletes users completely. Can be called either as:
     * - UserManager :: delete_users(1, 2, 3); or
     * - UserManager :: delete_users(array(1, 2, 3));
     * @param array|int $ids
     * @return boolean  True if at least one user was successfuly deleted. False otherwise.
     * @author Laurent Opprecht
     * @uses UserManager::delete_user() to actually delete each user
     * @assert (null) === false
     * @assert (-1) === false
     * @assert (array(-1)) === false
     */
    static function delete_users($ids = array()) {
        $result = false;
        $ids = is_array($ids) ? $ids : func_get_args();
        $ids = array_map('intval', $ids);
        foreach ($ids as $id) {
            $deleted = self::delete_user($id);
            $result = $deleted || $result;
        }
        return $result;
    }

    /**
     * Disable users. Can be called either as:
     * - UserManager :: deactivate_users(1, 2, 3);
     * - UserManager :: deactivate_users(array(1, 2, 3));
     * @param array|int $ids
     * @return boolean
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    static function deactivate_users($ids = array()) {
        if (empty($ids)) {
            return false;
        }
        $ids = is_array($ids) ? $ids : func_get_args();
        if (!empty($ids)) {
            foreach ($ids as $id) {
                self::change_active_state($id, 0);
            }
        }
        return true;
    }

    /**
     * Enable users. Can be called either as:
     * - UserManager :: activate_users(1, 2, 3);
     * - UserManager :: activate_users(array(1, 2, 3));
     * @param array|int IDs of the users to enable
     * @return boolean
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    static function activate_users($ids = array()) {
        if (empty($ids)) {
            return false;
        }
        $ids = is_array($ids) ? $ids : func_get_args();
        if (!empty($ids)) {
            foreach ($ids as $id) {
                self::change_active_state($id, 1);
            }
        }
    }

    /**
     * Update user information with new openid
     * @param int $user_id
     * @param string $openid
     * @return boolean true if the user information was updated
     * @assert (false,'') === false
     * @assert (-1,'') === false
     */
    public static function update_openid($user_id, $openid) {
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;
        $sql = "UPDATE $table_user SET openid='".Database::escape_string($openid)."'";
        $sql .=    " WHERE user_id='$user_id'";
        return Database::query($sql);
    }

    /**
     * Update user information with all the parameters passed to this function
     * @param int The ID of the user to be updated
     * @param string The user's firstname
     * @param string The user's lastname
     * @param string The user's username (login)
     * @param string The user's password
     * @param string The authentication source (default: "platform")
     * @param string The user's e-mail address
     * @param int The user's status
     * @param string The user's official code (usually just an internal institutional code)
     * @param string The user's phone number
     * @param string The user's picture URL (internal to the Chamilo directory)
     * @param int The user ID of the person who registered this user (optional, defaults to null)
     * @param int The department of HR in which the user is registered (optional, defaults to 0)
     * @param    array    A series of additional fields to add to this user as extra fields (optional, defaults to null)
     * @return boolean true if the user information was updated
     * @assert (false) === false
     */
    public static function update_user($user_id, $firstname, $lastname, $username, $password = null, $auth_source = null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, $creator_id = null, $hr_dept_id = 0, $extra = null, $language = 'english', $encrypt_method = '', $send_email = false, $reset_password = 0) {
        global $_configuration;
        $original_password = $password;

        $user_info = api_get_user_info($user_id, false, true);

        if ($reset_password == 0) {
            $password = null;
            $auth_source = $user_info['auth_source'];
        } elseif($reset_password == 1) {
            $original_password = $password = api_generate_password();
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif($reset_password == 2) {
            $password = $password;
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif($reset_password == 3) {
            $password = $password;
            $auth_source = $auth_source;
        }

        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;

        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        //Checking the user language
        $languages = api_get_languages();
        if (!in_array($language, $languages['folder'])) {
            $language = api_get_setting('platformLanguage');
        }

        $sql = "UPDATE $table_user SET
                lastname='".Database::escape_string($lastname)."',
                firstname='".Database::escape_string($firstname)."',
                username='".Database::escape_string($username)."',
                language='".Database::escape_string($language)."',";

        if (!is_null($password)) {
            if ($encrypt_method == '') {
                $password = api_get_encrypted_password($password);
            } else {
                if ($_configuration['password_encryption'] === $encrypt_method ) {
                    if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
                        return api_set_failure('encrypt_method invalid');
                    } else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
                        return api_set_failure('encrypt_method invalid');
                    }
                } else {
                    return api_set_failure('encrypt_method invalid');
                }
            }
            $sql .= " password='".Database::escape_string($password)."',";
        }
        if (!is_null($auth_source)) {
            $sql .=    " auth_source='".Database::escape_string($auth_source)."',";
        }
        $sql .=    "
                email='".Database::escape_string($email)."',
                status='".Database::escape_string($status)."',
                official_code='".Database::escape_string($official_code)."',
                phone='".Database::escape_string($phone)."',
                picture_uri='".Database::escape_string($picture_uri)."',
                expiration_date='".Database::escape_string($expiration_date)."',
                active='".Database::escape_string($active)."',
                hr_dept_id=".intval($hr_dept_id);
        if (!is_null($creator_id)) {
            $sql .= ", creator_id='".Database::escape_string($creator_id)."'";
        }
        $sql .=    " WHERE user_id = '$user_id' ";
        $return = Database::query($sql);
        if (is_array($extra) && count($extra) > 0) {
            $res = true;
            foreach($extra as $fname => $fvalue) {
                $res = $res && self::update_extra_field_value($user_id,$fname,$fvalue);
            }
        }

        if ($user_info['active'] != $active) {
            self::change_active_state($user_id, $active);
        }
        //active='".Database::escape_string($active)."',

        if (!empty($email) && $send_email) {
            $recipient_name = api_get_person_name($firstname, $lastname, null, PERSON_NAME_EMAIL_ADDRESS);
            $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
            $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
            $email_admin = api_get_setting('emailAdministrator');

            if ($_configuration['multiple_access_urls']) {
                $access_url_id = api_get_current_access_url_id();
                if ($access_url_id != -1) {
                    $url = api_get_access_url($access_url_id);
                    $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ". api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username . (($reset_password > 0) ? "\n". get_lang('Pass')." : ".stripslashes($original_password) : "") . "\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $url['url'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
                }
            } else {
                $emailbody=get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ". api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username . (($reset_password > 0) ? "\n". get_lang('Pass')." : ".stripslashes($original_password) : "") . "\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $_configuration['root_web'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
            }
            @api_mail_html($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
        }

        $user_info = api_get_user_info($user_id);
        event_system(LOG_USER_UPDATED, LOG_USER_ID, $user_id, api_get_utc_datetime(), api_get_user_id());
        event_system(LOG_USER_UPDATED, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), api_get_user_id());
        return $return;
    }

    /**
     * Disables or enables a user
     *
     * @param int user_id
     * @param int Enable or disable
     * @return void
     * @assert (-1,0) === false
     * @assert (1,1) === true
     */
    public static function change_active_state($user_id, $active, $send_email_if_activated = false) {
        $user_id = intval($user_id);
        $active = intval($active);
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        $sql = "UPDATE $table_user SET active = '$active' WHERE user_id = '$user_id';";
        Database::query($sql);

        $log_event = LOG_USER_DEACTIVATED;
        if ($active == 1) {
            $log_event = LOG_USER_ACTIVATED;

            if ($send_email_if_activated) {
                $user_info = api_get_user_info($user_id);
                $recipient_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
                //$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
                $emailbody=get_lang('Dear')." ".stripslashes($recipient_name).",\n\n";
                $emailbody.=sprintf(get_lang('YourAccountOnXHasJustBeenApprovedByOneOfOurAdministrators'), api_get_setting('siteName'))."\n";
                $emailbody.=sprintf(get_lang('YouCanNowLoginAtXUsingTheLoginAndThePasswordYouHaveProvided'), api_get_path(WEB_PATH)).",\n\n";
                $emailbody.=get_lang('HaveFun')."\n\n";
                $emailbody.=get_lang('Problem'). "\n\n". get_lang('Formula');
                $emailbody.= api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');

                MessageManager::send_message_simple($user_id, $emailsubject, $emailbody);
                //$result = api_mail_html($recipient_name, $user_info['mail'], $emailsubject, $emailbody, $sender_name, $email_admin);
            }
        }

        $user_info = api_get_user_info($user_id);
        event_system($log_event, LOG_USER_ID, $user_id, api_get_utc_datetime(), api_get_user_id());
        event_system($log_event, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), api_get_user_id());
    }

    /**
     * Disables a user
     *
     * @param int User id
     * @uses UserManager::change_active_state() to actually disable the user
     * @assert (0) === false
     */
    public static function disable($user_id) {
        self::change_active_state($user_id, 0);
    }

    /**
     * Enable a user
     *
     * @param int User id
     * @uses UserManager::change_active_state() to actually disable the user
     * @assert (0) === false
     */
    public static function enable($user_id) {
        self::change_active_state($user_id, 1);
    }

    /**
     * Returns the user's id based on the original id and field name in
     * the extra fields. Returns 0 if no user was found. This function is
     * mostly useful in the context of a web services-based sinchronization
     * @param string Original user id
     * @param string Original field name
     * @return int User id
     * @assert ('0','---') === 0
     */
    public static function get_user_id_from_original_id($original_user_id_value, $original_user_id_name) {
        $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $sql = "SELECT user_id    FROM $t_uf uf INNER JOIN $t_ufv ufv ON ufv.field_id=uf.id WHERE field_variable='$original_user_id_name' AND field_value='$original_user_id_value';";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->user_id;
        } else {
            return 0;
        }
    }

    /**
     * Check if a username is available
     * @param string the wanted username
     * @return boolean true if the wanted username is available
     * @assert ('') === false
     * @assert ('xyzxyzxyz') === true
     */
    public static function is_username_available($username) {
        if (empty($username)) { return false; }
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT username FROM $table_user WHERE username = '".Database::escape_string($username)."'";
        $res = Database::query($sql);
        return Database::num_rows($res) == 0;
    }

    /**
     * Creates a username using person's names, i.e. creates jmontoya from Julio Montoya.
     * @param string $firstname                The first name of the user.
     * @param string $lastname                The last name of the user.
     * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string                        Suggests a username that contains only ASCII-letters and digits, without check for uniqueness within the system.
     * @author Julio Montoya Armas
     * @author Ivan Tcholakov, 2009 - rework about internationalization.
     * @assert ('','') === false
     * @assert ('a','b') === 'ab'
     */
    public static function create_username($firstname, $lastname, $language = null, $encoding = null) {
        if (is_null($encoding)) {
            $encoding = api_get_system_encoding();
        }
        if (is_null($language)) {
            $language = api_get_interface_language();
        }
        $firstname = api_substr(preg_replace(USERNAME_PURIFIER, '', api_transliterate($firstname, '', $encoding)), 0, 1); // The first letter only.

        //Looking for a space in the lastname
        $pos = api_strpos($lastname, ' ');
            if ($pos !== false ) {
                $lastname = api_substr($lastname, 0, $pos);
        }

        $lastname = preg_replace(USERNAME_PURIFIER, '', api_transliterate($lastname, '', $encoding));
        //$username = api_is_western_name_order(null, $language) ? $firstname.$lastname : $lastname.$firstname;
        $username = $firstname.$lastname;
        if (empty($username)) {
            $username = 'user';
        }
        return strtolower(substr($username, 0, USERNAME_MAX_LENGTH - 3));
    }

    /**
     * Creates a unique username, using:
     * 1. the first name and the last name of a user;
     * 2. an already created username but not checked for uniqueness yet.
     * @param string $firstname                The first name of a given user. If the second parameter $lastname is NULL, then this
     * parameter is treated as username which is to be checked for uniqueness and to be modified when it is necessary.
     * @param string $lastname                The last name of the user.
     * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string                        Returns a username that contains only ASCII-letters and digits, and that is unique within the system.
     * Note: When the method is called several times with same parameters, its results look like the following sequence: ivan, ivan2, ivan3, ivan4, ...
     * @author Ivan Tcholakov, 2009
     */
    public static function create_unique_username($firstname, $lastname = null, $language = null, $encoding = null) {
        if (is_null($lastname)) {
            // In this case the actual input parameter $firstname should contain ASCII-letters and digits only.
            // For making this method tolerant of mistakes, let us transliterate and purify the suggested input username anyway.
            // So, instead of the sentence $username = $firstname; we place the following:
            $username = strtolower(preg_replace(USERNAME_PURIFIER, '', api_transliterate($firstname, '', $encoding)));
        } else {
            $username = self::create_username($firstname, $lastname, $language, $encoding);
        }
        if (!self::is_username_available($username)) {
            $i = 2;
            $temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string)$i)).$i;
            while (!self::is_username_available($temp_username)) {
                $i++;
                $temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string)$i)).$i;
            }
            $username = $temp_username;
        }
        return $username;
    }

    /**
     * Modifies a given username accordingly to the specification for valid characters and length.
     * @param $username string                The input username.
     * @param bool $strict (optional)        When this flag is TRUE, the result is guaranteed for full compliance, otherwise compliance may be partial. The default value is FALSE.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string                        The resulting purified username.
     */
    public static function purify_username($username, $strict = false, $encoding = null) {
        if ($strict) {
            // 1. Conversion of unacceptable letters (latinian letters with accents for example) into ASCII letters in order they not to be totally removed.
            // 2. Applying the strict purifier.
            // 3. Length limitation.
            $toreturn = api_get_setting('login_is_email') == 'true' ? substr(preg_replace(USERNAME_PURIFIER_MAIL, '', api_transliterate($username, '', $encoding)), 0, USERNAME_MAX_LENGTH): substr(preg_replace(USERNAME_PURIFIER, '', api_transliterate($username, '', $encoding)), 0, USERNAME_MAX_LENGTH);
            return $toreturn;
        }
        // 1. Applying the shallow purifier.
        // 2. Length limitation.
        return substr(preg_replace(USERNAME_PURIFIER_SHALLOW, '', $username), 0, USERNAME_MAX_LENGTH);
    }

    /**
     * Checks whether the user id exists in the database
     *
     * @param int User id
     * @return bool True if user id was found, false otherwise
     */
    public static function is_user_id_valid($user_id) {
        $user_id = (int)$user_id;
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id FROM $table_user WHERE user_id = '".$user_id."'";
        $res = Database::query($sql);
        $num_rows = Database::num_rows($res);
        if($num_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks whether a given username matches to the specification strictly. The empty username is assumed here as invalid.
     * Mostly this function is to be used in the user interface built-in validation routines for providing feedback while usernames are enterd manually.
     * @param string $username                The input username.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return bool                            Returns TRUE if the username is valid, FALSE otherwise.
     */
    public static function is_username_valid($username, $encoding = null) {
        return !empty($username) && $username == self::purify_username($username, true);
    }

    /**
     * Checks whether a username is empty. If the username contains whitespace characters, such as spaces, tabulators, newlines, etc.,
     * it is assumed as empty too. This function is safe for validation unpurified data (during importing).
     * @param string $username                The given username.
     * @return bool                            Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
     */
    public static function is_username_empty($username) {
        return (strlen(self::purify_username($username, false)) == 0);
    }

    /**
     * Checks whether a username is too long or not.
     * @param string $username                The given username, it should contain only ASCII-letters and digits.
     * @return bool                            Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
     */
    public static function is_username_too_long($username) {
        return (strlen($username) > USERNAME_MAX_LENGTH);
    }

    public static function get_user_list_by_ids($ids = array(), $active = null)
    {
        if(empty($ids)) {
            return array();
        }

        $ids = is_array($ids) ? $ids : array($ids);
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $tbl_user WHERE user_id IN ($ids)";
        if(!is_null($active)) {
            $sql .= ' AND active=' . ($active ? '1' : '0');
        }

        $rs = Database::query($sql);
        $result = array();
        while ($row = Database::fetch_array($rs)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
    * Get a list of users of which the given conditions match with an = 'cond'
    * @param array $conditions a list of condition (exemple : status=>STUDENT)
    * @param array $order_by a list of fields on which sort
    * @return array An array with all users of the platform.
    * @todo optional course code parameter, optional sorting parameters...
    */
    public static function get_user_list($conditions = array(), $order_by = array(), $limit_from = false, $limit_to = false) {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= "$field = '$value'";
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }

        if (is_numeric($limit_from) && is_numeric($limit_from)) {
            $limit_from = intval($limit_from);
            $limit_to   = intval($limit_to);
            $sql_query .= " LIMIT $limit_from, $limit_to";
        }
        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }


    /**
    * Get a list of users of which the given conditions match with a LIKE '%cond%'
    * @param array $conditions a list of condition (exemple : status=>STUDENT)
    * @param array $order_by a list of fields on which sort
    * @return array An array with all users of the platform.
    * @todo optional course code parameter, optional sorting parameters...
    */
    public static function get_user_list_like($conditions = array(), $order_by = array(), $simple_like = false, $condition = 'AND') {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            $temp_conditions = array();
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                if ($simple_like) {
                    $temp_conditions[]= $field." LIKE '$value%'";
                } else {
                    $temp_conditions[]= $field.' LIKE \'%'.$value.'%\'';
                }
            }
            if (!empty($temp_conditions)) {
                $sql_query .= implode(' '.$condition.' ', $temp_conditions);
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }
        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }

    /**
     * Get user information
     * @param     string     The username
     * @return array All user information as an associative array
     */
    public static function get_user_info($username) {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $username = Database::escape_string($username);
        $sql = "SELECT * FROM $user_table WHERE username='".$username."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            return Database::fetch_array($res, 'ASSOC');
        }
        return false;
    }

        /**
     * Get user information
     * @param     string     The username
     * @return array All user information as an associative array
     */
    public static function get_user_info_simple($username) {
        static $user_list = array();
        //error_log($sql);
        if (isset($user_list[$username])) {
            //error_log('loaded with static');
            return $user_list[$username];
        }
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $username = Database::escape_string($username);
        $sql = "SELECT user_id, username, firstname, lastname FROM $user_table WHERE username='".$username."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $return = Database::fetch_array($res, 'ASSOC');
            $user_list[$username] = $return;
            return $return;
        }
        return false;
    }

    /**
     * Get user information
     * @param    string    The id
     * @param    boolean    Whether to return the user's extra fields (defaults to false)
     * @return    array     All user information as an associative array
     * @todo    Use api_get_user_info() instead
     */
    public static function get_user_info_by_id($user_id, $user_fields = false) {
        $user_id = intval($user_id);
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $user_table WHERE user_id=".$user_id;
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $user = Database::fetch_array($res);
            $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
            $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
            $sqlf = "SELECT * FROM $t_uf ORDER BY field_order";
            $resf = Database::query($sqlf);
            if (Database::num_rows($resf) > 0) {
                while ($rowf = Database::fetch_array($resf)) {
                    $sqlv = "SELECT * FROM $t_ufv WHERE field_id = ".$rowf['id']." AND user_id = ".$user['user_id']." ORDER BY id DESC";
                    $resv = Database::query($sqlv);
                    if (Database::num_rows($resv) > 0) {
                        //There should be only one value for a field and a user
                        $rowv = Database::fetch_array($resv);
                        $user['extra'][$rowf['field_variable']] = $rowv['field_value'];
                    } else {
                        $user['extra'][$rowf['field_variable']] = '';
                    }
                }
            }
            return $user;
        }
        return false;
    }

    /** Get the teacher list
     * @param int the course ID
     * @param array Content the list ID of user_id selected
     */
    //for survey
    // TODO: Ivan, 14-SEP-2009: It seems that this method is not used at all (it can be located in a test unit only. To be deprecated?
    public static function get_teacher_list($course_id, $sel_teacher = '') {
        $user_course_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $course_id = Database::escape_string($course_id);
        $sql_query = "SELECT * FROM $user_table a, $user_course_table b where a.user_id=b.user_id AND b.status=1 AND b.course_code='$course_id'";
        $sql_result = Database::query($sql_query);
        echo "<select name=\"author\">";
        while ($result = Database::fetch_array($sql_result)) {
            if ($sel_teacher == $result['user_id']) $selected ="selected";
            echo "\n<option value=\"".$result['user_id']."\" $selected>".$result['firstname']."</option>";
        }
        echo "</select>";
    }

    /**
     * Get user picture URL or path from user ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     * @param    integer    User ID
     * @param    string    Type of path to return (can be 'none', 'system', 'rel', 'web')
     * @param    bool    Whether we want to have the directory name returned 'as if' there was a file or not (in the case we want to know which directory to create - otherwise no file means no split subdir)
     * @param    bool    If we want that the function returns the /main/img/unknown.jpg image set it at true
     * @return    array     Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty er's
     */
    public static function get_user_picture_path_by_id($id, $type = 'none', $preview = false, $anonymous = false) {

        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_CODE_PATH);
                break;
            case 'rel': // Base: semi-absolute web path (no server base).
                $base = api_get_path(REL_CODE_PATH);
                break;
            case 'web': // Base: absolute web path.
                $base = api_get_path(WEB_CODE_PATH);
                break;
            case 'none':
            default: // Base: empty, the result path below will be relative.
                $base = '';
        }

        if (empty($id) || empty($type)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
        }

        $user_id = intval($id);

        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT picture_uri FROM $user_table WHERE user_id=".$user_id;
        $res = Database::query($sql);

        if (!Database::num_rows($res)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
        }

        $user = Database::fetch_array($res);
        $picture_filename = trim($user['picture_uri']);

        if (api_get_setting('split_users_upload_directory') === 'true') {
            /*if (!empty($picture_filename) or $preview) {
                $dir = $base.'upload/users/'.substr((string)$user_id, 0, 1).'/'.$user_id.'/';
            } else {
                $dir = $base.'upload/users/'.$user_id.'/';
            }*/
            $dir = $base . 'upload/users/' . substr(
                    (string)$user_id,
                    0,
                    1
                ) . '/' . $user_id . '/';
        } else {
            $dir = $base.'upload/users/'.$user_id.'/';
        }
        if (empty($picture_filename) && $anonymous) {
            return array('dir' => $base.'img/', 'file' => 'unknown.jpg');
        }
        return array('dir' => $dir, 'file' => $picture_filename);
    }


    /**
     * Creates new user pfotos in various sizes of a user, or deletes user pfotos.
     * Note: This method relies on configuration setting from dokeos/main/inc/conf/profile.conf.php
     * @param     int $user_id        The user internal identitfication number.
     * @param     string $file        The common file name for the newly created pfotos.
     *                                 It will be checked and modified for compatibility with the file system.
     *                                 If full name is provided, path component is ignored.
     *                                 If an empty name is provided, then old user photos are deleted only,
     * @see     UserManager::delete_user_picture() as the prefered way for deletion.
     * @param     string $source_file    The full system name of the image from which user photos will be created.
     * @return     string/bool            Returns the resulting common file name of created images which usually should be stored in database.
     * When deletion is recuested returns empty string. In case of internal error or negative validation returns FALSE.
     */
    public static function update_user_picture($user_id, $file = null, $source_file = null) {
        if (empty($user_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // Configuration options about user photos.
        require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

        // User-reserved directory where photos have to be placed.
        $path_info = self::get_user_picture_path_by_id($user_id, 'system', true);
        $path = $path_info['dir'];
        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if (!empty($old_file)) {
            if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
                $prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
                @rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
                @rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
                @rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
                @rename($path.$old_file, $path.$prefix.$old_file);
            } else {
                @unlink($path.'small_'.$old_file);
                @unlink($path.'medium_'.$old_file);
                @unlink($path.'big_'.$old_file);
                @unlink($path.$old_file);
            }
        }

        // Exit if only deletion has been requested. Return an empty picture name.
        if ($delete) {
            return '';
        }

        // Validation 2.
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file = str_replace('\\', '/', $file);
        $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }

        // This is the common name for the new photos.
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
            $old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
            $filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
            $filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
        } else {
            $filename = replace_dangerous_char($filename);
            if (PREFIX_IMAGE_FILENAME_WITH_UID) {
                $filename = uniqid('').'_'.$filename;
            }
            // We always prefix user photos with user ids, so on setting
            // api_get_setting('split_users_upload_directory') === 'true'
            // the correspondent directories to be found successfully.
            $filename = $user_id.'_'.$filename;
        }

        // Storing the new photos in 4 versions with various sizes.

        $small  = self::resize_picture($source_file, 22);
        $medium = self::resize_picture($source_file, 85);
        $normal = self::resize_picture($source_file, 200);

        $big    = new Image($source_file); // This is the original picture.

        $ok = false;
        $ok = $small->send_image($path.'small_'.$filename) &&
              $medium->send_image($path.'medium_'.$filename) &&
              $normal->send_image($path.$filename) &&
              $big->send_image( $path.'big_'.$filename);
        return $ok ? $filename : false;
    }

    /**
     * Deletes user pfotos.
     * Note: This method relies on configuration setting from dokeos/main/inc/conf/profile.conf.php
     * @param int $user_id            The user internal identitfication number.
     * @return string/bool            Returns empty string on success, FALSE on error.
     */
    public static function delete_user_picture($user_id) {
        return self::update_user_picture($user_id);
    }

    /* PRODUCTIONS FUNCTIONS */

    /**
     * Returns an XHTML formatted list of productions for a user, or FALSE if he
     * doesn't have any.
     *
     * If there has been a request to remove a production, the function will return
     * without building the list unless forced to do so by the optional second
     * parameter. This increases performance by avoiding to read through the
     * productions on the filesystem before the removal request has been carried
     * out because they'll have to be re-read afterwards anyway.
     *
     * @param    $user_id    User id
     * @param    $force    Optional parameter to force building after a removal request
     * @return    A string containing the XHTML code to dipslay the production list, or FALSE
     */
    public static function build_production_list($user_id, $force = false, $showdelete = false) {

        if (!$force && !empty($_POST['remove_production'])) {
            return true; // postpone reading from the filesystem
        }
        $productions = self::get_user_productions($user_id);

        if (empty($productions)) {
            return false;
        }

        $production_path = self::get_user_picture_path_by_id($user_id, 'web');
        $production_dir = $production_path['dir'];
        $del_image = api_get_path(WEB_CODE_PATH).'img/delete.gif';
        $del_text = get_lang('Delete');
        $production_list = '';
        if (count($productions) > 0) {
            $production_list = '<ul id="productions">';
            foreach ($productions as $file) {
                $production_list .= '<li><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
                if ($showdelete) {
                    $production_list .= '<input type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="javascript: return confirmation(\''.htmlentities($file).'\');" /></li>';
                }
            }
            $production_list .= '</ul>';
        }

        return $production_list;
    }

    /**
     * Returns an array with the user's productions.
     *
     * @param    $user_id    User id
     * @return    An array containing the user's productions
     */
    public static function get_user_productions($user_id) {
        $production_path = self::get_user_picture_path_by_id($user_id, 'system', true);
        $production_repository = $production_path['dir'].$user_id.'/';
        $productions = array();

        if (is_dir($production_repository)) {
            $handle = opendir($production_repository);

            while ($file = readdir($handle)) {
                if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($production_repository.$file)) {
                    continue; // skip current/parent directory and .htaccess
                }
                if (preg_match('/('.$user_id.'|[0-9a-f]{13}|saved)_.+\.(png|jpg|jpeg|gif)$/i', $file)) {
                    // User's photos should not be listed as productions.
                    continue;
                }
                $productions[] = $file;
            }
        }

        return $productions; // can be an empty array
    }

    /**
     * Remove a user production.
     *
     * @param    $user_id        User id
     * @param    $production    The production to remove
     */
    public static function remove_user_production($user_id, $production) {
        $production_path = self::get_user_picture_path_by_id(
            $user_id,
            'system'
        );
        $production_file = $production_path['dir'] . $production;
        if (is_file($production_file)) {
            unlink($production_file);
            return true;
        }
        return false;
    }

    /**
     * Update an extra field. This function is called when a user changes his/her profile
     * and by consequence fills or edits his/her extra fields.
     *
     * @param    integer    Field ID
     * @param    array    Database columns and their new value
     * @return    boolean    true if field updated, false otherwise
     */
    public static function update_extra_field($fid, $columns)  {
        //TODO check that values added are values proposed for enumerated field types
        $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $fid = Database::escape_string($fid);
        $sqluf = "UPDATE $t_uf SET ";
        $known_fields = array('id', 'field_variable', 'field_type', 'field_display_text', 'field_default_value', 'field_order', 'field_visible', 'field_changeable', 'field_filter');
        $safecolumns = array();
        foreach ($columns as $index => $newval) {
            if (in_array($index, $known_fields)) {
                $safecolumns[$index] = Database::escape_string($newval);
                $sqluf .= $index." = '".$safecolumns[$index]."', ";
            }
        }
        $time = api_get_utc_datetime();
        $sqluf .= " tms = '$time' WHERE id= '$fid' ";
        $resuf = Database::query($sqluf);
        return $resuf;
    }

    /**
     * Update an extra field value for a given user
     * @param    integer    User ID
     * @param    string    Field variable name
     * @param    string    Field value
     * @return    boolean    true if field updated, false otherwise
     */
    public static function update_extra_field_value($user_id, $fname, $fvalue = '') {
        //TODO check that values added are values proposed for enumerated field types
        $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufo = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
        $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $fname = Database::escape_string($fname);
        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;
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

        $sqluf = "SELECT * FROM $t_uf WHERE field_variable='$fname'";
        $resuf = Database::query($sqluf);
        if (Database::num_rows($resuf) == 1) {
            //ok, the field exists
            // Check if enumerated field, if the option is available
            $rowuf = Database::fetch_array($resuf);
            switch ($rowuf['field_type']) {
                case ExtraField::FIELD_TYPE_TAG :
                    //4. Tags are process here comes from main/auth/profile.php
                    UserManager::process_tags(explode(';', $fvalues), $user_id, $rowuf['id']);
                    return true;
                    break;
                case ExtraField::FIELD_TYPE_RADIO:
                case ExtraField::FIELD_TYPE_SELECT:
                case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    $sqluo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowuf['id'];
                    $resuo = Database::query($sqluo);
                    $values = split(';',$fvalues);
                    if (Database::num_rows($resuo) > 0) {
                        $check = false;
                        while ($rowuo = Database::fetch_array($resuo)) {
                            if (in_array($rowuo['option_value'], $values)) {
                                $check = true;
                                break;
                            }
                        }
                        if (!$check) {
                            return false; //option value not found
                        }
                    } else {
                        return false; //enumerated type but no option found
                    }
                    break;
                case 1:
                case 2:
                default:
                    break;
            }
            $tms = api_get_utc_datetime();
            $sqlufv = "SELECT * FROM $t_ufv WHERE user_id = $user_id AND field_id = ".$rowuf['id']." ORDER BY id";
            $resufv = Database::query($sqlufv);
            $n = Database::num_rows($resufv);

            if ($n > 1) {
                //problem, we already have to values for this field and user combination - keep last one
                while ($rowufv = Database::fetch_array($resufv)) {
                    if ($n > 1) {
                        $sqld = "DELETE FROM $t_ufv WHERE id = ".$rowufv['id'];
                        Database::query($sqld);
                        $n--;
                    }
                    $rowufv = Database::fetch_array($resufv);
                    if ($rowufv['field_value'] != $fvalues) {
                        $sqlu = "UPDATE $t_ufv SET field_value = '$fvalues', tms = '$tms' WHERE id = ".$rowufv['id'];
                        $resu = Database::query($sqlu);
                        return $resu ? true : false;
                    }
                    return true;
                }
            } elseif ($n == 1) {
                //we need to update the current record
                $rowufv = Database::fetch_array($resufv);
                if ($rowufv['field_value'] != $fvalues) {
                    // If the new field is empty, delete it
                    if ($fvalues == '') {
                        $sql_query = "DELETE FROM $t_ufv WHERE id = ".$rowufv['id'].";";
                    } else {
                        // Otherwise update it
                        $sql_query = "UPDATE $t_ufv SET field_value = '$fvalues', tms = '$tms' WHERE id = ".$rowufv['id'];
                    }


                    $resu = Database::query($sql_query);
                    return $resu ? true : false;
                }
                return true;
            } else {
                $sqli = "INSERT INTO $t_ufv (user_id,field_id,field_value,tms)
                         VALUES ($user_id,".$rowuf['id'].",'$fvalues', '$tms')";
                $resi = Database::query($sqli);
                return $resi ? true : false;
            }
        } else {
            return false; //field not found
        }
    }

    /**
     * Get an array of extra fieds with field details (type, default value and options)
     * @param    integer    Offset (from which row)
     * @param    integer    Number of items
     * @param    integer    Column on which sorting is made
     * @param    string    Sorting direction
     * @param    boolean    Optional. Whether we get all the fields or just the visible ones
     * @param    int        Optional. Whether we get all the fields with field_filter 1 or 0 or everything
     * @return    array    Extra fields details (e.g. $list[2]['type'], $list[4]['options'][2]['title']
     */
    public static function get_extra_fields($from = 0, $number_of_items = 0, $column = 5, $direction = 'ASC', $all_visibility = true, $field_filter = null, $return_assoc = false) {
        $fields = array();
        $t_uf  = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
        $columns = array('id', 'field_variable', 'field_type', 'field_display_text', 'field_default_value', 'field_order', 'field_filter', 'tms');
        $column = intval($column);
        $sort_direction = '';
        if (in_array(strtoupper($direction), array('ASC', 'DESC'))) {
            $sort_direction = strtoupper($direction);
        }
        $sqlf = "SELECT * FROM $t_uf WHERE 1 = 1  ";
        if (!$all_visibility) {
            $sqlf .= " AND field_visible = 1 ";
        }
        if (!is_null($field_filter)) {
            $field_filter = intval($field_filter);
            $sqlf .= " AND field_filter = $field_filter ";
        }
        $sqlf .= " ORDER BY ".$columns[$column]." $sort_direction " ;
        if ($number_of_items != 0) {
            $sqlf .= " LIMIT ".Database::escape_string($from).','.Database::escape_string($number_of_items);
        }

        $resf = Database::query($sqlf);
        if (Database::num_rows($resf) > 0) {
            while($rowf = Database::fetch_array($resf)) {

                if ($return_assoc) {
                    $fields[$rowf['id']] = array(
                        'id' => $rowf['id'],
                        'field_variable' => $rowf['field_variable'],
                        'field_type' => $rowf['field_type'],
                        //3 => (empty($rowf['field_display_text']) ? '' : get_lang($rowf['field_display_text'], '')),
                        // Temporarily removed auto-translation. Need update to get_lang() to know if translation exists (todo)
                        // Ivan, 15-SEP-2009: get_lang() has been modified accordingly in order this issue to be solved.
                        'field_display_text' => (empty($rowf['field_display_text']) ? '' : $rowf['field_display_text']),
                        'field_default_value' => $rowf['field_default_value'],
                        'field_order' => $rowf['field_order'],
                        'field_visible' => $rowf['field_visible'],
                        'field_changeable' => $rowf['field_changeable'],
                        'field_filter' => $rowf['field_filter'],
                        'options' => array()

                    );
                } else {
                    $fields[$rowf['id']] = array(
                        0 => $rowf['id'],
                        1 => $rowf['field_variable'],
                        2 => $rowf['field_type'],
                        //3 => (empty($rowf['field_display_text']) ? '' : get_lang($rowf['field_display_text'], '')),
                        // Temporarily removed auto-translation. Need update to get_lang() to know if translation exists (todo)
                        // Ivan, 15-SEP-2009: get_lang() has been modified accordingly in order this issue to be solved.
                        3 => (empty($rowf['field_display_text']) ? '' : $rowf['field_display_text']),
                        4 => $rowf['field_default_value'],
                        5 => $rowf['field_order'],
                        6 => $rowf['field_visible'],
                        7 => $rowf['field_changeable'],
                        8 => $rowf['field_filter'],
                        9 => array()
                    );
                }

                $sqlo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowf['id']." ORDER BY option_order ASC";
                $reso = Database::query($sqlo);
                if (Database::num_rows($reso) > 0) {
                    while ($rowo = Database::fetch_array($reso)) {
                        if ($return_assoc) {
                             $fields[$rowf['id']]['options'][$rowo['id']] = array(
                                'id' => $rowo['id'],
                                'option_value' => $rowo['option_value'],
                                //2 => (empty($rowo['option_display_text']) ? '' : get_lang($rowo['option_display_text'], '')),
                                'option_display_text' => (empty($rowo['option_display_text']) ? '' : $rowo['option_display_text']),
                                'option_order' => $rowo['option_order']
                            );
                        } else {
                            $fields[$rowf['id']][9][$rowo['id']] = array(
                                0 => $rowo['id'],
                                1 => $rowo['option_value'],
                                //2 => (empty($rowo['option_display_text']) ? '' : get_lang($rowo['option_display_text'], '')),
                                2 => (empty($rowo['option_display_text']) ? '' : $rowo['option_display_text']),
                                3 => $rowo['option_order']
                            );
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Get the list of options attached to an extra field
     * @param string $fieldname the name of the field
     * @return array the list of options
     */
    public static function get_extra_field_options($field_name) {
        $t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

        $sql = 'SELECT options.*
                FROM '.$t_ufo.' options
                INNER JOIN '.$t_uf.' fields
                ON fields.id = options.field_id AND
                   fields.field_variable="'.Database::escape_string($field_name).'"';
        $rs = Database::query($sql);
        return Database::store_result($rs);
    }

    /**
     * Get the number of extra fields currently recorded
     * @param    boolean    Optional switch. true (default) returns all fields, false returns only visible fields
     * @return    integer    Number of fields
     */
    public static function get_number_of_extra_fields($all_visibility = true) {
        $t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
        $sqlf = "SELECT * FROM $t_uf ";
        if (!$all_visibility) {
            $sqlf .= " WHERE field_visible = 1 ";
        }
        $sqlf .= " ORDER BY field_order";
        $resf = Database::query($sqlf);
        return Database::num_rows($resf);
    }

    /**
      * Creates a new extra field
      * @param    string    Field's internal variable name
      * @param    int        Field's type
      * @param    string    Field's language var name
      * @param    string    Field's default value
      * @param    string    Optional comma-separated list of options to provide for select and radio
      * @return int     new user id - if the new user creation succeeds, false otherwise
      */
    public static function create_extra_field($fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions = '') {
        // database table definition
        $table_field         = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $table_field_options= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

        // First check wether the login already exists
        if (self::is_extra_field_available($fieldvarname)) {
            return api_set_failure('login-pass already taken');
        }
        $sql = "SELECT MAX(field_order) FROM $table_field";
        $res = Database::query($sql);
        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $order = $row[0]+1;
        }
        $time = api_get_utc_datetime();
        $sql = "INSERT INTO $table_field
                SET field_type = '".Database::escape_string($fieldtype)."',
                field_variable = '".Database::escape_string($fieldvarname)."',
                field_display_text = '".Database::escape_string($fieldtitle)."',
                field_default_value = '".Database::escape_string($fielddefault)."',
                field_order = '$order',
                tms = '$time'";
        $result = Database::query($sql);
        if ($result) {
            //echo "id returned";
            $return = Database::insert_id();
        } else {
            //echo "false - failed" ;
            return false;
        }

        if (!empty($fieldoptions) && in_array($fieldtype, array(ExtraField::FIELD_TYPE_RADIO, ExtraField::FIELD_TYPE_SELECT, ExtraField::FIELD_TYPE_SELECT_MULTIPLE, ExtraField::FIELD_TYPE_DOUBLE_SELECT))) {
            if ($fieldtype == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                $twolist = explode('|', $fieldoptions);
                $counter = 0;
                foreach ($twolist as $individual_list) {
                    $splitted_individual_list = split(';', $individual_list);
                    foreach    ($splitted_individual_list as $individual_list_option) {
                        //echo 'counter:'.$counter;
                        if ($counter == 0) {
                            $list[] = $individual_list_option;
                        } else {
                            $list[] = str_repeat('*', $counter).$individual_list_option;
                        }
                    }
                    $counter++;
                }
            } else {
                $list = split(';', $fieldoptions);
            }
            foreach ($list as $option) {
                $option = Database::escape_string($option);
                $sql = "SELECT * FROM $table_field_options WHERE field_id = $return AND option_value = '".$option."'";
                $res = Database::query($sql);
                if (Database::num_rows($res) > 0) {
                    //the option already exists, do nothing
                } else {
                    $sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = $return";
                    $res = Database::query($sql);
                    $max = 1;
                    if (Database::num_rows($res) > 0) {
                        $row = Database::fetch_array($res);
                        $max = $row[0] + 1;
                    }
                    $time = time();
                    $sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms) VALUES ($return,'$option','$option',$max, '$time')";
                    $res = Database::query($sql);
                    if ($res === false) {
                        $return = false;
                    }
                }
            }
        }
        return $return;
    }

    /**
      * Save the changes in the definition of the extra user profile field
      * The function is called after you (as admin) decide to store the changes you have made to one of the fields you defined
      *
      * There is quite some logic in this field
      * 1.  store the changes to the field (tupe, name, label, default text)
      * 2.  remove the options and the choices of the users from the database that no longer occur in the form field 'possible values'. We should only remove
      *     the options (and choices) that do no longer have to appear. We cannot remove all options and choices because if you remove them all
      *     and simply re-add them all then all the user who have already filled this form will loose their selected value.
      * 3.    we add the options that are newly added
      *
      * <code> current options are a;b;c and the user changes this to a;b;x (removing c and adding x)
      *             we first remove c (and also the entry in the option_value table for the users who have chosen this)
      *             we then add x
      *             a and b are neither removed nor added
      * </code>
      * @param     integer $fieldid        the id of the field we are editing
      * @param    string    $fieldvarname    the internal variable name of the field
      * @param    int        $fieldtype        the type of the field
      * @param    string    $fieldtitle        the title of the field
      * @param    string    $fielddefault    the default value of the field
      * @param    string    $fieldoptions    Optional comma-separated list of options to provide for select and radio
      * @return boolean true
      *
      *
      * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
      * @version July 2008
      * @since Dokeos 1.8.6
      */
    public static function save_extra_field_changes($fieldid, $fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions = '') {
        // database table definition
        $table_field                 = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $table_field_options        = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
        $table_field_options_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

        $time = api_get_utc_datetime();

        // we first update the field definition with the new values
        $sql = "UPDATE $table_field
                SET field_type = '".Database::escape_string($fieldtype)."',
                field_variable = '".Database::escape_string($fieldvarname)."',
                field_display_text = '".Database::escape_string($fieldtitle)."',
                field_default_value = '".Database::escape_string($fielddefault)."',
                tms = '$time'
            WHERE id = '".Database::escape_string($fieldid)."'";
        $result = Database::query($sql);

        // we create an array with all the options (will be used later in the script)
        if ($fieldtype == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
            $twolist = explode('|', $fieldoptions);
            $counter = 0;
            foreach ($twolist as $individual_list) {
                $splitted_individual_list = split(';', $individual_list);
                foreach    ($splitted_individual_list as $individual_list_option) {
                    //echo 'counter:'.$counter;
                    if ($counter == 0) {
                        $list[] = trim($individual_list_option);
                    } else {
                        $list[] = str_repeat('*', $counter).trim($individual_list_option);
                    }
                }
                $counter++;
            }
        } else {
            $templist = split(';', $fieldoptions);
            $list = array_map('trim', $templist);
        }

        // Remove all the field options (and also the choices of the user) that are NOT in the new list of options
        $sql = "SELECT * FROM $table_field_options
                WHERE option_value NOT IN ('".implode("','", $list)."') AND field_id = '".Database::escape_string($fieldid)."'";
        $result = Database::query($sql);
        $return['deleted_options'] = 0;
        while ($row = Database::fetch_array($result)) {
            // deleting the option
            $sql_delete_option = "DELETE FROM $table_field_options WHERE id='".Database::escape_string($row['id'])."'";
            Database::query($sql_delete_option);
            $return['deleted_options']++;

            // deleting the answer of the user who has chosen this option
            $sql_delete_option_value = "DELETE FROM $table_field_options_values
                                        WHERE field_id = '".Database::escape_string($fieldid)."' AND field_value = '".Database::escape_string($row['option_value'])."'";
            Database::query($sql_delete_option_value);
            $return['deleted_option_values'] = $return['deleted_option_values'] + Database::affected_rows();
        }

        // we now try to find the field options that are newly added
        $sql = "SELECT * FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // we remove every option that is already in the database from the $list
            if (in_array(trim($row['option_display_text']), $list)) {
                $key = array_search(trim($row['option_display_text']), $list);
                unset($list[$key]);
            }
        }

        // we store the new field options in the database
        foreach ($list as $key => $option) {
            $sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
            $res = Database::query($sql);
            $max = 1;
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $max = $row[0] + 1;
            }
            $sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms)
                    VALUES ('".Database::escape_string($fieldid)."','".Database::escape_string($option)."','".Database::escape_string($option)."', $max, '$time')";
            $result = Database::query($sql);
        }
        return true;
    }

    /**
     * Check if a field is available
     * @param    string    the wanted fieldname
     * @return    boolean    true if the wanted username is available
     */
    public static function is_extra_field_available($fieldname) {
        $t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
        $sql = "SELECT * FROM $t_uf WHERE field_variable = '".Database::escape_string($fieldname)."'";
        $res = Database::query($sql);
        return Database::num_rows($res) > 0;
    }

    /**
     * Gets user extra fields data
     * @param    integer    User ID
     * @param    boolean    Whether to prefix the fields indexes with "extra_" (might be used by formvalidator)
     * @param    boolean    Whether to return invisible fields as well
     * @param    boolean    Whether to split multiple-selection fields or not
     * @return    array    Array of fields => value for the given user
     */
    public static function get_extra_user_data($user_id, $prefix = false, $all_visibility = true, $splitmultiple = false, $field_filter = null) {
        // A sanity check.
        if (empty($user_id)) {
            $user_id = 0;
        } else {
            if ($user_id != strval(intval($user_id))) return array();
        }
        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $user_id = Database::escape_string($user_id);
        $sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";
        $filter_cond = '';

        if (!$all_visibility) {
            if (isset($field_filter)) {
                $field_filter = intval($field_filter);
                $filter_cond .= " AND field_filter = $field_filter ";
            }
            $sql .= " WHERE f.field_visible = 1 $filter_cond ";
        } else {
            if (isset($field_filter)) {
                $field_filter = intval($field_filter);
                $sql .= " WHERE field_filter = $field_filter ";
            }
        }
        $sql .= " ORDER BY f.field_order";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                if ($row['type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                    $field_options = self::get_extra_field_options($row['fvar']);

                    $field_details['options'] = $field_options;
                    $field_details['field_variable'] = $row['fvar'];

                    $values = array();
                    foreach ($field_details['options'] as $key => $element) {
                        if ($element['option_display_text'][0] == '*') {
                            $values['*'][$element['option_value']] = str_replace('*', '', $element['option_display_text']);
                        } else {
                            $values[0][$element['option_value']] = $element['option_display_text'];
                        }
                    }

                    if (is_array($extra_data)) {
                        $sqlu = "SELECT field_value as fval FROM $t_ufv WHERE field_id=".$row['id']." AND user_id = ".$user_id;
                        $resu = Database::query($sqlu);
                        $rowu = Database::fetch_array($resu);

                        $selected_values = explode(';', $rowu['fval']);
                        $extra_data['extra_'.$field_details['field_variable']] = array();

                        // looping through the selected values and assigning the selected values to either the first or second select form
                        foreach ($selected_values as $key => $selected_value) {
                            if (in_array($selected_value, $values[0])) {
                                $extra_data['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable']] = $selected_value;
                            } else {
                                $extra_data['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable'].'*'] = $selected_value;
                            }
                        }
                    }

                } elseif ($row['type'] == ExtraField::FIELD_TYPE_TAG) {
                    $tags = self::get_user_tags_to_string($user_id, $row['id'], false);
                    $extra_data['extra_'.$row['fvar']] = $tags;
                } else {
                    $sqlu = "SELECT field_value as fval FROM $t_ufv WHERE field_id=".$row['id']." AND user_id = ".$user_id;
                    $resu = Database::query($sqlu);
                    $fval = '';
                    // get default value
                    $sql_df = "SELECT field_default_value as fval_df FROM $t_uf WHERE id=".$row['id'];
                    $res_df = Database::query($sql_df);

                    if (Database::num_rows($resu) > 0) {
                        $rowu = Database::fetch_array($resu);
                        $fval = $rowu['fval'];
                        if ($row['type'] ==  ExtraField::FIELD_TYPE_SELECT_MULTIPLE) {
                            $fval = split(';', $rowu['fval']);
                        }
                    } else {
                        $row_df = Database::fetch_array($res_df);
                        $fval = $row_df['fval_df'];
                    }
                    // We get here (and fill the $extra_data array) even if there is no user with data (we fill it with default values)
                    if ($prefix) {
                        if ($row['type'] ==  ExtraField::FIELD_TYPE_RADIO) {
                            $extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
                        } else {
                            $extra_data['extra_'.$row['fvar']] = $fval;
                        }
                    } else {
                        if ($row['type'] ==  ExtraField::FIELD_TYPE_RADIO) {
                            $extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
                        } else {
                            $extra_data[$row['fvar']] = $fval;
                        }
                    }
                }
            }
        }
        return $extra_data;
    }

    /** Get extra user data by field
     * @param int    user ID
     * @param string the internal variable name of the field
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_field($user_id, $field_variable, $prefix = false, $all_visibility = true, $splitmultiple = false) {
        // A sanity check.
        if (empty($user_id)) {
            $user_id = 0;
        } else {
            if ($user_id != strval(intval($user_id))) return array();
        }
        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $user_id = Database::escape_string($user_id);

        $sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";
        $sql .= " WHERE f.field_variable = '$field_variable' ";

        if (!$all_visibility) {
            $sql .= " AND f.field_visible = 1 ";
        }

        $sql .= " ORDER BY f.field_order";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $sqlu = "SELECT field_value as fval " .
                        " FROM $t_ufv " .
                        " WHERE field_id=".$row['id']."" .
                        " AND user_id=".$user_id;
                $resu = Database::query($sqlu);
                $fval = '';
                if (Database::num_rows($resu) > 0) {
                    $rowu = Database::fetch_array($resu);
                    $fval = $rowu['fval'];
                    if ($row['type'] ==  ExtraField::FIELD_TYPE_SELECT_MULTIPLE) {
                        $fval = split(';',$rowu['fval']);
                    }
                }
                if ($prefix) {
                    $extra_data['extra_'.$row['fvar']] = $fval;
                } else {
                    $extra_data[$row['fvar']] = $fval;
                }
            }
        }

        return $extra_data;
    }

    /**
     * Get the extra field information for a certain field (the options as well)
     * @param  int     The name of the field we want to know everything about
     * @return array   Array containing all the information about the extra profile field (first level of array contains field details, then 'options' sub-array contains options details, as returned by the database)
     * @author Julio Montoya
     * @since Dokeos 1.8.6
     */
    public static function get_extra_field_information_by_name($field_variable, $fuzzy = false) {
        // database table definition
        $table_field             = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $table_field_options    = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

        // all the information of the field
        $sql = "SELECT * FROM $table_field WHERE field_variable='".Database::escape_string($field_variable)."'";
        $result = Database::query($sql);
        $return = Database::fetch_array($result);

        // all the options of the field
        $sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($return['id'])."' ORDER BY option_order ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $return['options'][$row['id']] = $row;
        }
        return $return;
    }

    public static function get_all_extra_field_by_type($field_type) {
        // database table definition
        $table_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);

        // all the information of the field
        $sql = "SELECT * FROM $table_field WHERE field_type='".Database::escape_string($field_type)."'";
        $result = Database::query($sql);
        $return = array();
        while ($row = Database::fetch_array($result)) {
            $return[] = $row['id'];
        }
        return $return;
    }

    /**
     * Get all the extra field information of a certain field (also the options)
     *
     * @param int $field_name the name of the field we want to know everything of
     * @return array $return containing all th information about the extra profile field
     * @author Julio Montoya
     * @since Dokeos 1.8.6
     */
    public static function get_extra_field_information($field_id) {
        // database table definition
        $table_field             = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $table_field_options    = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

        // all the information of the field
        $sql = "SELECT * FROM $table_field WHERE id='".Database::escape_string($field_id)."'";
        $result = Database::query($sql);
        $return = Database::fetch_array($result);

        // all the options of the field
        $sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($field_id)."' ORDER BY option_order ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $return['options'][$row['id']] = $row;
        }
        return $return;
    }

    /** Get extra user data by value
     * @param string the internal variable name of the field
     * @param string the internal value of the field
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */

    public static function get_extra_user_data_by_value($field_variable, $field_value, $all_visibility = true) {
        //$extra_data = array();
        $table_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $table_user_field_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        //$table_user_field_options = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

        $where = "field_variable='".Database::escape_string($field_variable)."' AND field_value='".Database::escape_string($field_value)."'";

        $sql = "SELECT user_id FROM $table_user_field user_field INNER JOIN $table_user_field_values user_field_values
                    ON (user_field.id = user_field_values.field_id)
                WHERE $where";

        if ($all_visibility) {
            $sql .= " AND user_field.field_visible = 1 ";
        } else {
            $sql .= " AND user_field.field_visible = 0 ";
        }
        $res = Database::query($sql);
        $result_data = array();
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $result_data[] = $row['user_id'];
            }
        }
        return $result_data;
    }

    /**
     * Get extra user data by field variable
     * @param string    field variable
     * @return array    data
     */
    public static function get_extra_user_data_by_field_variable($field_variable) {
        $tbl_user_field_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
        $extra_information_by_variable = self::get_extra_field_information_by_name($field_variable);
        $field_id = intval($extra_information_by_variable['id']);
        $data = array();
        $sql = "SELECT * FROM $tbl_user_field_values WHERE field_id='$field_id'";
        $rs  = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $user_id = $row['user_id'];
                $data[$user_id] = $row;
            }
        }
        return $data;
    }

    /**
     * Gives a list of [session_category][session_id] for the current user.
     * @param integer $user_id
     * @param boolean $is_time_over
     * @param boolean $get_count
     * @param boolean $reverse_order
     * @param array $option
     * @return array $categories list of statuses [session_category][session_id]
     * @todo ensure multiple access urls are managed correctly
     */
    public static function get_sessions_by_category($user_id, $is_time_over = false, $get_count = false, $reverse_order = false, $option = array()) {
        // Database Table Definitions
        $tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_user           = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session_category       = Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);

        if ($user_id != strval(intval($user_id))) return array();

        $categories = array();

        $now = api_get_utc_datetime();

        // Get the list of sessions per user
        //$condition_date_start1 = null;
        //$condition_date_start2 = null;
        $condition_date_end1 = null;
        $condition_date_end2 = null;
        // uncomment commented query lines to alter the query sorting
        $order = ' ORDER BY session_category_name, short_name';
        //$order = ' ORDER BY session_category_name, name ';
        if ($reverse_order) {
            $order = ' ORDER BY session_category_name DESC, short_name DESC';
            //$order = ' ORDER BY session_category_name DESC, name DESC ';
        }

        if ($is_time_over) {
            $condition_date_end1 = " AND ((session.access_end_date < '$now' AND session.access_end_date != '0000-00-00 00:00:00') OR moved_to <> 0) ";
            $condition_date_end2 = " AND ((session.access_end_date < '$now' AND session.access_end_date != '0000-00-00 00:00:00')) ";
        } else {
            if (api_is_allowed_to_create_course()) {
                //Teachers can access the session depending in the access_coach date
                //$condition_date_end1 = null;
                //$condition_date_end2 = null;
                //$condition_date_start1 = " AND (session.coach_access_start_date <= '$now' OR session.coach_access_start_date = '0000-00-00 00:00:00') ";
                //$condition_date_start2 = " AND (session.coach_access_start_date <= '$now' OR session.coach_access_start_date = '0000-00-00 00:00:00') ";
            } else {
                //Student can't access before the start date or after the end date
                //$condition_date_start1 = " AND (session.access_start_date <= '$now' OR session.access_start_date = '0000-00-00 00:00:00') ";
                //$condition_date_start2 = " AND (session.access_start_date <= '$now' OR session.access_start_date = '0000-00-00 00:00:00') ";
                $condition_date_end1 = " AND (session.access_end_date >= '$now' OR session.access_end_date = '0000-00-00 00:00:00') ";
                $condition_date_end2 = " AND (session.access_end_date >= '$now' OR session.access_end_date = '0000-00-00 00:00:00') ";
            }
        }

        if (!empty($option['moved_to']) || $option['moved_to'] === false) {
            if ($option['moved_to'] == true) {
                $condition_date_end1 .= " AND su.moved_status > 0";
            } else {
                if (!api_is_teacher()) {
                    $condition_date_end1 .= " AND su.moved_status = 0";
                }
            }
        }

        $select = "SELECT DISTINCT ".
                   " session.id, ".
                   " session.name, ".
                   " SUBSTR(session.name,LOCATE('-',session.name)+2) as short_name, ".
                   " access_start_date, ".
                   " access_end_date, ".
                   " coach_access_start_date, ".
                   " coach_access_end_date, ".

                   " display_start_date, ".
                   " display_end_date, ".

                   " session_category_id, ".
                   " session_category.name as session_category_name, ".
                   " session_category.date_start session_category_date_start, ".
                   " session_category.date_end session_category_date_end, ".
                   " id_coach ";

        $select_1 = '';

        if (!api_is_teacher()) {
            $select_1 = ", moved_to, " .
                " moved_status, " .
                " scu.id_user";
        }

        if ($get_count) {
            $select = "SELECT count(session.id) as total_rows ";
        }

        // select specific to course coaches
        //$select1 = " $select ".($get_count?'':$select_1)." FROM $tbl_session as session LEFT JOIN $tbl_session_category session_category ON (session_category_id = session_category.id) ";
        //$sql1 = $select1 . " INNER JOIN $tbl_session_course_user as scu ON (scu.id_session = session.id and scu.id_user = $user_id) LEFT JOIN $tbl_session_user su ON su.id_session = session.id AND su.id_user = scu.id_user WHERE scu.id_user = $user_id $condition_date_start1 $condition_date_end1 $order";

        $select1 = " $select ".($get_count ? '' : $select_1)
                   ." FROM $tbl_session as session "
                   ." LEFT JOIN $tbl_session_category session_category "
                   ." ON (session_category_id = session_category.id) ";

        $sql1 = $select1 . " INNER JOIN $tbl_session_course_user as scu "
                ." ON (scu.id_session = session.id and scu.id_user = $user_id) ";

        if (!api_is_teacher()) {
            $sql1 .= "LEFT JOIN $tbl_session_user su "
                . "ON su.id_session = session.id AND su.id_user = scu.id_user ";
        }

        $sql1 .= "WHERE scu.id_user = $user_id $condition_date_end1 $order";

        // select specific to session coaches
        $select2 = " $select FROM $tbl_session as session LEFT JOIN $tbl_session_category session_category ON (session_category_id = session_category.id) ";
        //$sql2 = $select2 . "  WHERE session.id_coach = $user_id $condition_date_start2 $condition_date_end2 $order";
        $sql2 = $select2 . "  WHERE session.id_coach = $user_id $condition_date_end2 $order";
        $result1 = Database::query($sql1);
        $result2 = Database::query($sql2);

        $join = array();
        $ordered_join = array();
        $ids = array();
        if (Database::num_rows($result2) > 0) {
            // First take $row2, as it contains less data and this data is enough
            while ($row2 = Database::fetch_array($result2)) {
                $join[] = $row2;
                $ordered_join[] = $row2;
                $ids[] = $row2['id'];
            }
        }
        if (Database::num_rows($result1) > 0) {
            // Now add the diff with $row1, ordering elements as planned by
            //   query
            $i = 0;
            while ($row1 = Database::fetch_array($result1)) {

                if (!in_array($row1['id'],$ids)) {
                    if ($reverse_order) {
                        while (strcmp($row1['session_category_name'],$join[$i]['session_category_name'])<=0 && isset($join[$i])) {
                            $ordered_join[] = $join[$i];
                            $i++;
                        }
                    } else {
                        while (strcmp($row1['session_category_name'],$join[$i]['session_category_name'])>0 && isset($join[$i])) {
                            $ordered_join[] = $join[$i];
                            $i++;
                        }
                        if (strcmp($row1['session_category_name'],$join[$i]['session_category_name']) === 0 && isset($join[$i])) {
                            while (isset($join[$i]) && strcmp($row1['short_name'],$join[$i]['short_name'])>0) {
                                // while row1['short_name'] is greater than join[$i]['short_name'], add join[i] at the end of the list
                                $ordered_join[] = $join[$i];
                                $i++;
                            }
                        }
                    }
                    $ordered_join[] = $row1;
                    $join[] = $row1;
                }
            }
        }
        if (count($ordered_join)==0) {
            $ordered_join = $join;
        }
        if (count($ordered_join)>0) {
            foreach ($ordered_join as $row) {
                if ($get_count) {
                    return $row['total_rows'];
                }
                $categories[$row['session_category_id']]['session_category']['id']                          = $row['session_category_id'];
                $categories[$row['session_category_id']]['session_category']['name']                        = $row['session_category_name'];
                $categories[$row['session_category_id']]['session_category']['date_start']                  = $row['session_category_date_start'];
                $categories[$row['session_category_id']]['session_category']['date_end']                    = $row['session_category_date_end'];

                $session_id = $row['id'];
                //$session_info = api_get_session_info($session_id);
                // The only usage of $session_info is to call
                // api_get_session_date_valudation, which only needs id and
                // dates from the session itself, so really no need to query
                // the session table again
                $session_info = $row;

                //Checking session visibility
                $visibility = api_get_session_visibility($session_id, null, false);

                switch ($visibility) {
                    case SESSION_VISIBLE_READ_ONLY: //1 in DB
                    case SESSION_VISIBLE: //2 in DB
                    case SESSION_AVAILABLE: //4 in DB
                        break;
                    case SESSION_INVISIBLE: //3 in DB
                        continue(2);
                }

                if ($is_time_over == false) {
                    $date_validation = api_get_session_date_validation($session_info, null, false, false);
                    if (!$date_validation) {
                        continue;
                    }
                }

                $categories[$row['session_category_id']]['sessions'][$row['id']]['session_name']            = $row['name'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['session_id']              = $row['id'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['id_coach']                = $row['id_coach'];

                if (isset($row['id_coach']) && !empty($row['id_coach'])) {
                    $user_info = api_get_user_info($row['id_coach']);
                    $categories[$row['session_category_id']]['sessions'][$row['id']]['coach_info']          = $user_info;
                }

                $categories[$row['session_category_id']]['sessions'][$row['id']]['access_start_date']       = $row['access_start_date'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['access_end_date']         = $row['access_end_date'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['coach_access_start_date'] = $row['coach_access_start_date'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['coach_access_end_date']   = $row['coach_access_end_date'];

                $date_message = SessionManager::parse_session_dates($row);
                $categories[$row['session_category_id']]['sessions'][$row['id']]['date_message']   = $date_message;

                $courses = UserManager::get_courses_list_by_session($user_id, $row['id']);
                $course_list = array();
                foreach ($courses as $course) {

                    //Checking course session visibility
                    $visibility = api_get_session_visibility($session_id, $course['code']);

                    if ($visibility == SESSION_INVISIBLE) {
                        continue;
                    }

                    $user_status_in_course = CourseManager::get_user_in_course_status($user_id, $course['code']);
                    $course['user_status_in_course'] = $user_status_in_course;
                    $course_list[] = $course;
                }
                $categories[$row['session_category_id']]['sessions'][$row['id']]['courses']                 = $course_list;
                $categories[$row['session_category_id']]['sessions'][$row['id']]['moved_to']                = $row['moved_to'];
                $categories[$row['session_category_id']]['sessions'][$row['id']]['moved_status']            = $row['moved_status'];
            }
        }
        return $categories;
    }

    /**
     * Gives a list of [session_id-course_code] => [status] for the current user.
     * @param integer $user_id
     * @return array  list of statuses (session_id-course_code => status)
     */
    public static function get_personal_session_course_list($user_id) {

        // Database Table Definitions
        $tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_user                     = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session                 = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_user            = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_course_user             = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user     = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        if ($user_id != strval(intval($user_id))) return array();

        //we filter the courses from the URL
        $join_access_url = $where_access_url = '';

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url = "INNER JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code = course.code";
                $where_access_url = " AND access_url_id = $access_url_id ";
            }
        }

        //Courses in which we are subscribed out of any session
        $tbl_user_course_category = Database :: get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        $personal_course_list_sql = "SELECT course.code,
                                            course_rel_user.status course_rel_status,
                                            course_rel_user.sort sort,
                                            course_rel_user.user_course_cat user_course_cat
                                     FROM $tbl_course_user course_rel_user
                                        INNER JOIN $tbl_course course
                                        ON course.code = course_rel_user.course_code
                                        INNER JOIN $tbl_user_course_category user_course_category
                                        ON course_rel_user.user_course_cat = user_course_category.id
                                     $join_access_url
                                     WHERE  course_rel_user.user_id = '".$user_id."' AND
                                            course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."  $where_access_url
                                     ORDER BY user_course_category.sort, course_rel_user.sort, course.title ASC";

        $course_list_sql_result = Database::query($personal_course_list_sql);

        $personal_course_list = array();
        if (Database::num_rows($course_list_sql_result) > 0 ) {
            while ($result_row = Database::fetch_array($course_list_sql_result, 'ASSOC')) {
                $course_info = api_get_course_info($result_row['code']);
                $result_row['course_info'] = $course_info;
                $personal_course_list[] = $result_row;
            }
        }

        // Get the list of sessions where the user is subscribed as student, course coach or session admin
        $sessions_sql = "(
                            SELECT DISTINCT s.id, s.name
                            FROM $tbl_session_user su INNER JOIN $tbl_session s
                            ON (su.id_session = s.id)
                            WHERE  su.id_user = $user_id AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                        )
                        UNION (
                            SELECT DISTINCT s.id, s.name
                            FROM $tbl_session s
                            WHERE id_coach = $user_id
                        )
                        UNION (
                            SELECT DISTINCT s.id, s.name
                            FROM $tbl_session_user su INNER JOIN $tbl_session s
                            ON (su.id_session = s.id)
                            INNER JOIN $tbl_session_course_user scu
                            ON (scu.id_session = s.id)
                            WHERE (scu.id_user = $user_id)
                        )
                        ORDER BY name ";
        //AND scu.status = 2

        $result     = Database::query($sessions_sql);
        $sessions   = Database::store_result($result, 'ASSOC');

        if (!empty($sessions)) {
            foreach ($sessions as $enreg) {
                $session_id = $enreg['id'];

                $courseList = SessionManager::get_course_list_by_session_id($session_id);
                foreach ($courseList as $course) {
                    $sessionVisibility = api_get_session_visibility($session_id, $course['code']);

                    if ($sessionVisibility == SESSION_INVISIBLE) {
                        continue;
                    }
                    $course['course_info'] = $course;
                    $key = $session_id.' - '.$course['code'];
                    $personal_course_list[$key] = $course;
                }
            }
        }

        return $personal_course_list;
    }

    /**
     * Gives a list of courses for the given user in the given session
     * @param integer $user_id
     * @return array  list of statuses (session_id-course_code => status)
     */
    public static function get_courses_list_by_session($user_id, $session_id) {
        // Database Table Definitions
        $tbl_session                 = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course_user     = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $user_id    = intval($user_id);
        $session_id = intval($session_id);
        //we filter the courses from the URL
        $join_access_url=$where_access_url='';

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
                $join_access_url= " ,  $tbl_url_session url_rel_session ";
                $where_access_url=" AND access_url_id = $access_url_id AND url_rel_session.session_id = $session_id ";
            }
        }

        $personal_course_list = array();
        $courses = array();

        // this query is very similar to the above query, but it will check the session_rel_course_user table if there are courses registered to our user or not
        $personal_course_list_sql = "SELECT DISTINCT scu.course_code as code FROM $tbl_session_course_user as scu $join_access_url
                                    WHERE scu.id_user = $user_id AND scu.id_session = $session_id $where_access_url
                                    ORDER BY code";

        $course_list_sql_result = Database::query($personal_course_list_sql);

        if (Database::num_rows($course_list_sql_result) > 0) {
            while ($result_row = Database::fetch_array($course_list_sql_result)) {
                $result_row['status'] = 5;
                if (!in_array($result_row['code'], $courses)) {
                    $personal_course_list[] = $result_row;
                    $courses[] = $result_row['code'];
                }
            }
        }

        if (api_is_allowed_to_create_course()) {
            $personal_course_list_sql = "SELECT DISTINCT scu.course_code as code FROM $tbl_session_course_user as scu, $tbl_session as s $join_access_url
                                        WHERE s.id = $session_id AND scu.id_session = s.id AND ((scu.id_user=$user_id AND scu.status=2) OR s.id_coach = $user_id)
                                        $where_access_url
                                        ORDER BY code";
            $course_list_sql_result = Database::query($personal_course_list_sql);

            if (Database::num_rows($course_list_sql_result)>0) {
                while ($result_row = Database::fetch_array($course_list_sql_result)) {
                    $result_row['status'] = 2;
                    if (!in_array($result_row['code'],$courses)) {
                        $personal_course_list[] = $result_row;
                        $courses[] = $result_row['code'];
                    }
                }
            }
        }
        $course_list = array();
        if (api_is_drh()) {
            $session_list = SessionManager::get_sessions_followed_by_drh($user_id);
            $session_list = array_keys($session_list);
            if (in_array($session_id, $session_list)) {
                $course_list = SessionManager::get_course_list_by_session_id($session_id);
                if (!empty($course_list)) {
                    foreach ($course_list as $course) {
                        $personal_course_list[] = $course;
                    }
                }
            }
        } else {
            //check if user is general coach for this session
            $s = api_get_session_info($session_id);
            if ($s['id_coach'] == $user_id) {
                if (count($course_list)==0) {
                    $course_list = SessionManager::get_course_list_by_session_id($session_id);
                    if (!empty($course_list)) {
                        foreach ($course_list as $course) {
                            if (!in_array($course['code'],$courses)) {
                                $personal_course_list[] = $course;
                            }
                        }
                    }
                }
            }
        }
        return $personal_course_list;
    }

    /**
     * Get user id from a username
     * @param    string    Username
     * @return    int        User ID (or false if not found)
     */
    public static function get_user_id_from_username($username) {
        if (empty($username)) {
            return false;
        }
        $username = Database::escape_string($username);
        $t_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id FROM $t_user WHERE username = '$username'";
        $res = Database::query($sql);
        if ($res === false) { return false; }
        if (Database::num_rows($res) !== 1) { return false; }
        $row = Database::fetch_array($res);
        return $row['user_id'];
    }

    /**
     * Get the users files upload from his share_folder
     * @param    string    User ID
     * @param   string  course directory
     * @param   string  resourcetype: images, all
     * @return    int        User ID (or false if not found)
     */
    public static function get_user_upload_files_by_course($user_id, $course, $resourcetype='all') {
        $return = '';
        if (!empty($user_id) && !empty($course)) {
            $user_id = intval($user_id);
            $path = api_get_path(SYS_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $web_path = api_get_path(WEB_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $file_list = array();

            if (is_dir($path)) {
                $handle = opendir($path);
                while ($file = readdir($handle)) {
                    if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($path.$file)) {
                        continue; // skip current/parent directory and .htaccess
                    }
                    $file_list[] = $file;
                }
                if (count($file_list) > 0) {
                    $return = "<h4>$course</h4>";
                    $return .= '<ul class="thumbnails">';
                }
                foreach ($file_list as $file) {
                    if ($resourcetype=="all") {
                        $return .= '<li><a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a></li>';
                    } elseif($resourcetype=="images") {
                        //get extension
                        $ext = explode('.', $file);
                        if ($ext[1]=='jpg' || $ext[1]=='jpeg'|| $ext[1]=='png' || $ext[1]=='gif' || $ext[1]=='bmp' || $ext[1]=='tif') {
                            $return .= '<li class="span2"><a class="thumbnail" href="'.$web_path.urlencode($file).'" target="_blank">
                                            <img src="'.$web_path.urlencode($file).'" ></a>
                                        </li>';
                        }
                    }
                }
                if (count($file_list) > 0) {
                    $return .= '</ul>';
                }
            }
        }
        return $return;
    }

    /**
     * Gets the API key (or keys) and return them into an array
     * @param   int     Optional user id (defaults to the result of api_get_user_id())
     * @return  array   Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    public static function get_api_keys($user_id = null, $api_service = 'dokeos') {
        if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE user_id = $user_id AND api_service='$api_service';";
        $res = Database::query($sql);
        if ($res === false) return false; //error during query
        $num = Database::num_rows($res);
        if ($num == 0) return false;
        $list = array();
        while ($row = Database::fetch_array($res)) {
            $list[$row['id']] = $row['api_key'];
        }
        return $list;
    }

    /**
     * Adds a new API key to the users' account
     * @param   int     Optional user ID (defaults to the results of api_get_user_id())
     * @return  boolean True on success, false on failure
     */
    public static function add_api_key($user_id = null, $api_service = 'dokeos') {
        if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false; }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time() + ($user_id * 5)) - rand(10000, 10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = Database::query($sql);
        if ($res === false) return false; //error during query
        $num = Database::insert_id();
        return ($num == 0) ? false : $num;
    }

    /**
     * Deletes an API key from the user's account
     * @param   int     API key's internal ID
     * @return  boolean True on success, false on failure
     */
    public static function delete_api_key($key_id) {
        if ($key_id != strval(intval($key_id))) return false;
        if ($key_id === false) return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false) return false; //error during query
        $num = Database::num_rows($res);
        if ($num !== 1) return false;
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false) return false; //error during query
        return true;
    }

    /**
     * Regenerate an API key from the user's account
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string  API key's internal ID
     * @return  int        num
     */
    public static function update_api_key($user_id, $api_service) {
        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false; }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        if ($num == 1) {
            $id_key = Database::fetch_array($res, 'ASSOC');
            self::delete_api_key($id_key['id']);
            $num = self::add_api_key($user_id, $api_service);
        } elseif ($num == 0) {
            $num = self::add_api_key($user_id);
        }
        return $num;
    }

    /**
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string    API key's internal ID
     * @return  int    row ID, or return false if not found
     */
    public static function get_api_key_id($user_id, $api_service) {
        if ($user_id != strval(intval($user_id))) return false;
        if ($user_id === false) return false;
    if (empty($api_service)) return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $service_name = Database::escape_string($api_service);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
    if (Database::num_rows($res)<1) {
        return false;
    }
        $row = Database::fetch_array($res, 'ASSOC');
        return $row['id'];
    }

    /**
     * Checks if a user_id is platform admin
     * @param   int user ID
     * @return  boolean True if is admin, false otherwise
     * @see main_api.lib.php::api_is_platform_admin() for a context-based check
     */
    public static function is_admin($user_id) {
        if (empty($user_id) or $user_id != strval(intval($user_id))) { return false; }
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        $sql = "SELECT * FROM $admin_table WHERE user_id = $user_id";
        $res = Database::query($sql);
        return Database::num_rows($res) === 1;
    }

    /**
     * Get the total count of users
     * @param   int     Status of users to be counted
     * @param   int     Access URL ID (optional)
     * @return    mixed    Number of users or false on error
     */
    public static function get_number_of_users($status=0, $access_url_id=null) {
        $t_u = Database::get_main_table(TABLE_MAIN_USER);
    $t_a = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql = "SELECT count(*) FROM $t_u u";
        $sql2 = '';
        if (is_int($status) && $status>0) {
          $sql2 .= " WHERE u.status = $status ";
        }
        if (!empty($access_url_id) && $access_url_id == intval($access_url_id)) {
          $sql .= ", $t_a a ";
          $sql2 .= " AND a.access_url_id = $access_url_id AND u.user_id = a.user_id ";
        }
        $sql = $sql.$sql2;
        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
            return (int) Database::result($res, 0, 0);
        }
        return false;
    }

    /**
     * Resize a picture
     *
     * @param  string file picture
     * @param  int size in pixels
     * @todo move this function somewhere else image.lib?
     * @return obj image object
     */
    public static function resize_picture($file, $max_size_for_picture) {
        $temp = null;
        if (file_exists($file)) {
            $temp = new Image($file);
            $image_size =  $temp->get_image_size($file);
            $width  = $image_size['width'];
            $height = $image_size['height'];
            if ($width >= $height) {
                if ($width >= $max_size_for_picture) {
                  // scale height
                  $new_height = round($height * ($max_size_for_picture / $width));
                  $temp->resize($max_size_for_picture, $new_height, 0);
                }
            } else { // height > $width
                if ($height >= $max_size_for_picture) {
                    // scale width
                    $new_width = round($width * ($max_size_for_picture / $height));
                    $temp->resize($new_width, $max_size_for_picture, 0);
                }
            }
        }
        return $temp;
    }

    /**
     * Gets the current user image
     * @param string user id
     * @param string picture user name
     * @param string height
     * @param string picture size it can be USER_IMAGE_SIZE_SMALL,  USER_IMAGE_SIZE_MEDIUM, USER_IMAGE_SIZE_BIG or  USER_IMAGE_SIZE_ORIGINAL
     * @param string style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
   public static function get_picture_user($user_id, $picture_file, $height, $size_picture = USER_IMAGE_SIZE_MEDIUM , $style = '') {
        $picture = array();
        $picture['style'] = $style;
        if ($picture_file == 'unknown.jpg') {
            $picture['file'] = api_get_path(WEB_CODE_PATH).'img/'.$picture_file;
            return $picture;
        }
        switch ($size_picture) {
            case USER_IMAGE_SIZE_ORIGINAL :
                $size_picture = '';
            break;
            case USER_IMAGE_SIZE_BIG :
                $size_picture = 'big_';
            break;
            case USER_IMAGE_SIZE_MEDIUM :
                $size_picture = 'medium_';
            break;
            case USER_IMAGE_SIZE_SMALL :
                $size_picture = 'small_';
            break;
            default:
                $size_picture = 'medium_';
        }

        $image_array_sys = self::get_user_picture_path_by_id($user_id, 'system', false, true);
        $image_array = self::get_user_picture_path_by_id($user_id, 'web', false, true);

        $file = $image_array_sys['dir'].$size_picture.$picture_file;

        if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
            $picture['style'] = '';
            if ($height > 0) {
                $dimension = api_getimagesize($picture['file']);
                $margin = (($height - $dimension['width']) / 2);

                //@ todo the padding-top should not be here
                $picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension['width'].'px; height:'.$dimension['height'].'px;" ';
                $picture['original_height'] = $dimension['width'];
                $picture['original_width']  = $dimension['height'];
            }
        } else {
            $file = $image_array_sys['dir'].$picture_file;
            if (file_exists($file) && !is_dir($file)) {
                $picture['file'] = $image_array['dir'].$picture_file;
            } else {
                switch ($size_picture) {
                    case 'big_' :
                        $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown.jpg'; break;
                    case 'medium_' :
                        $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown_50_50.jpg'; break;
                    case 'small_' :
                        $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown.jpg'; break;
                    default:
                        $picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown.jpg'; break;
                }

            }
        }
        return $picture;
    }

    /**
     * @author Isaac flores <isaac.flores@dokeos.com>
     * @param string The email administrator
     * @param integer The user id
     * @param string The message title
     * @param string The content message
     */
         public static function send_message_in_outbox($email_administrator, $user_id, $title, $content) {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $title = api_utf8_decode($title);
        $content = api_utf8_decode($content);
        $email_administrator = Database::escape_string($email_administrator);
        //message in inbox
        $sql_message_outbox = 'SELECT user_id from '.$table_user.' WHERE email="'.$email_administrator.'" ';
        //$num_row_query = Database::num_rows($sql_message_outbox);
        $res_message_outbox = Database::query($sql_message_outbox);
        $array_users_administrator = array();
        while ($row_message_outbox = Database::fetch_array($res_message_outbox, 'ASSOC')) {
            $array_users_administrator[] = $row_message_outbox['user_id'];
        }
        //allow to insert messages in outbox
        for ($i = 0; $i < count($array_users_administrator); $i++) {
            $sql_insert_outbox = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
                    " VALUES (".
                     "'".(int)$user_id."', '".(int)($array_users_administrator[$i])."', '4', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
                     ")";
            $rs = Database::query($sql_insert_outbox);
        }
    }

    /*
     *
     * USER TAGS
     *
     * Intructions to create a new user tag by Julio Montoya <gugli100@gmail.com>
     *
     * 1. Create a new extra field in main/admin/user_fields.php with the "TAG" field type make it available and visible. Called it "books" for example.
     * 2. Go to profile main/auth/profile.php There you will see a special input (facebook style) that will show suggestions of tags.
     * 3. All the tags are registered in the user_tag table and the relationship between user and tags is in the user_rel_tag table
     * 4. Tags are independent this means that tags can't be shared between tags + book + hobbies.
     * 5. Test and enjoy.
     *
     */

    /**
     * Gets the tags of a specific field_id
     *
     * @param int field_id
     * @param string how we are going to result value in array or in a string (json)
     * @return mixed
     * @since Nov 2009
     * @version 1.8.6.2
     */
    public static function get_tags($tag, $field_id, $return_format  = 'json', $limit=10) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $field_id    = intval($field_id);
        $limit        = intval($limit);
        $tag         = trim(Database::escape_string($tag));

        // all the information of the field
        $sql = "SELECT DISTINCT id, tag from $table_user_tag
                WHERE field_id = $field_id AND tag LIKE '$tag%' ORDER BY tag LIMIT $limit";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)>0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[] = array('key'=> $row['tag'], 'value'=>$row['tag']);
            }
        }
        if ($return_format=='json') {
            $return =  json_encode($return);
        }
        return $return;
    }

    public static function get_top_tags($field_id, $limit=100) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id                 = intval($field_id);
        $limit                     = intval($limit);
        // all the information of the field
        $sql = "SELECT count(*) count, tag FROM $table_user_tag_values  uv INNER JOIN $table_user_tag ut ON(ut.id = uv.tag_id)
                WHERE field_id = $field_id GROUP BY tag_id ORDER BY count DESC LIMIT $limit";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)>0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[] = $row;
            }
        }
        return $return;
    }

    /**
     * Get user's tags
     * @param int field_id
     * @param int user_id
     * @return array
     */
    public static function get_user_tags($user_id,$field_id) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = intval($field_id);
        $user_id = intval($user_id);

        // all the information of the field
        $sql = "SELECT ut.id, tag,count FROM $table_user_tag ut INNER JOIN $table_user_tag_values uv ON (uv.tag_id=ut.ID)
                WHERE field_id = $field_id AND user_id = $user_id ORDER BY tag";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)> 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[$row['id']] = array('tag'=>$row['tag'],'count'=>$row['count']);
            }
        }
        return $return;
    }


    /**
     * Get user's tags
     * @param int user_id
     * @param int field_id
     * @param bool show links or not
     * @return array
     */
    public static function get_user_tags_to_string($user_id,$field_id,$show_links=true) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = intval($field_id);
        $user_id = intval($user_id);

        // all the information of the field
        $sql = "SELECT ut.id, tag,count FROM $table_user_tag ut INNER JOIN $table_user_tag_values uv ON (uv.tag_id=ut.ID)
                WHERE field_id = $field_id AND user_id = $user_id ORDER BY tag";

        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)> 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[$row['id']] = array('tag'=>$row['tag'],'count'=>$row['count']);
            }
        }
        $user_tags = $return;
        $tag_tmp = array();
        foreach ($user_tags as $tag) {
            if ($show_links) {
                $tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/search/index.php?q='.$tag['tag'].'">'.$tag['tag'].'</a>';
            } else {
                $tag_tmp[] = $tag['tag'];
            }
        }
        if (is_array($user_tags) && count($user_tags)>0) {
            $return = implode(', ',$tag_tmp);
        } else {
            return '';
        }
        return $return;
    }


    /**
     * Get the tag id
     * @param int tag
     * @param int field_id
     * @return int returns 0 if fails otherwise the tag id
     */
    public static function get_tag_id($tag, $field_id) {
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $tag = Database::escape_string($tag);
        $field_id = intval($field_id);
        //with COLLATE latin1_bin to select query in a case sensitive mode
        $sql = "SELECT id FROM $table_user_tag WHERE tag LIKE '$tag' AND field_id = $field_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)>0) {
            $row = Database::fetch_array($result,'ASSOC');
            return $row['id'];
        } else {
            return 0;
        }
    }

    /**
     * Get the tag id
     * @param int tag
     * @param int field_id
     * @return int 0 if fails otherwise the tag id
     */
    public static function get_tag_id_from_id($tag_id, $field_id) {
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $tag_id = intval($tag_id);
        $field_id = intval($field_id);
        $sql = "SELECT id FROM $table_user_tag WHERE id = '$tag_id' AND field_id = $field_id";
        $result = Database::query($sql);
        if (Database::num_rows($result)>0) {
            $row = Database::fetch_array($result,'ASSOC');
            return $row['id'];
        } else {
            return false;
        }
    }


    /**
     * Adds a user-tag value
     * @param mixed tag
     * @param int The user id
     * @param int field id of the tag
     * @return bool
     */
    public static function add_tag($tag, $user_id, $field_id) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tag = trim(Database::escape_string($tag));
        $user_id = intval($user_id);
        $field_id = intval($field_id);

        $tag_id = UserManager::get_tag_id($tag, $field_id);

        /* IMPORTANT
         *  @todo we don't create tags with numbers
         *
         */
        if (is_numeric($tag)) {
            //the form is sending an id this means that the user select it from the list so it MUST exists
            /*$new_tag_id = UserManager::get_tag_id_from_id($tag,$field_id);
            if ($new_tag_id !== false) {
                $sql = "UPDATE $table_user_tag SET count = count + 1 WHERE id  = $new_tag_id";
                $result = Database::query($sql);
                $last_insert_id = $new_tag_id;
            } else {
                $sql = "INSERT INTO $table_user_tag (tag, field_id,count) VALUES ('$tag','$field_id', count + 1)";
                $result = Database::query($sql);
                $last_insert_id = Database::get_last_insert_id();
            }*/
        } else {

        }

        //this is a new tag
        if ($tag_id == 0) {
            //the tag doesn't exist
            $sql = "INSERT INTO $table_user_tag (tag, field_id,count) VALUES ('$tag','$field_id', count + 1)";
            $result = Database::query($sql);
            $last_insert_id = Database::get_last_insert_id();
        } else {
            //the tag exists we update it
            $sql = "UPDATE $table_user_tag SET count = count + 1 WHERE id  = $tag_id";
            $result = Database::query($sql);
            $last_insert_id = $tag_id;
        }

        if (!empty($last_insert_id) && ($last_insert_id!=0)) {
            //we insert the relationship user-tag
            $sql_select ="SELECT tag_id FROM $table_user_tag_values WHERE user_id = $user_id AND tag_id = $last_insert_id ";
            $result = Database::query($sql_select);
            //if the relationship does not exist we create it
            if (Database::num_rows($result)==0) {
                $sql = "INSERT INTO $table_user_tag_values SET user_id = $user_id, tag_id = $last_insert_id";
                $result = Database::query($sql);
            }
        }
    }

    /**
     * Deletes an user tag
     * @param int user id
     * @param int field id
     *
     */
    public static function delete_user_tags($user_id, $field_id) {
        // database table definition
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tags = UserManager::get_user_tags($user_id, $field_id);
        //echo '<pre>';var_dump($tags);
        if(is_array($tags) && count($tags)>0) {
            foreach ($tags as $key=>$tag) {
                if ($tag['count']>'0') {
                    $sql = "UPDATE $table_user_tag SET count = count - 1  WHERE id = $key ";
                    $result = Database::query($sql);
                }
                $sql = "DELETE FROM $table_user_tag_values WHERE user_id = $user_id AND tag_id = $key";
                $result = Database::query($sql);
            }

        }
    }

    /**
     * Process the tag list comes from the UserManager::update_extra_field_value() function
     * @param array the tag list that will be added
     * @param int user id
     * @param int field id
     * @return bool
     */
    public static function process_tags($tags, $user_id, $field_id) {
        //We loop the tags and add it to the DB
        if (is_array($tags)) {
            foreach($tags as $tag) {
                UserManager::add_tag($tag, $user_id, $field_id);
            }
        } else {
            UserManager::add_tag($tags,$user_id, $field_id);
        }
        return true;
    }

    /**
     * Returns a list of all admninistrators
     * @author jmontoya
     * @return array
     */
     public static function get_all_administrators() {
         $table_user = Database::get_main_table(TABLE_MAIN_USER);
         $table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            $sql = "SELECT admin.user_id, username, firstname, lastname, email FROM $tbl_url_rel_user as url INNER JOIN $table_admin as admin
                                 ON (admin.user_id=url.user_id) INNER JOIN $table_user u ON (u.user_id=admin.user_id)
                                 WHERE access_url_id ='".$access_url_id."'";
        } else {
            $sql = "SELECT admin.user_id, username, firstname, lastname, email FROM $table_admin as admin
                    INNER JOIN $table_user u ON (u.user_id=admin.user_id)";
        }
         $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)> 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[$row['user_id']] = $row;
            }
        }
        return $return;
     }


    /**
     * Searchs an user (tags, firstname, lastname and email )
     * @param string the tag
     * @param int field id of the tag
     * @param int where to start in the query
     * @param int number of items
     * @return array
     */
    public static function get_all_user_tags($tag, $field_id = 0, $from = 0, $number_of_items = 10) {

        $user_table             = Database::get_main_table(TABLE_MAIN_USER);
        $table_user_tag            = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values    = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);

        $tag = Database::escape_string($tag);
        $field_id = intval($field_id);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $where_field = "";
        if ($field_id != 0) {
            $where_field = " field_id = $field_id AND ";
        }
        // all the information of the field
        $sql = "SELECT u.user_id,u.username,firstname, lastname, email, tag, picture_uri FROM $table_user_tag ut INNER JOIN $table_user_tag_values uv ON (uv.tag_id=ut.id)
                INNER JOIN $user_table u ON(uv.user_id =u.user_id)
                WHERE $where_field tag LIKE '$tag%' ORDER BY tag";
        $sql .= " LIMIT $from,$number_of_items";

        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                if (isset($return[$row['user_id']]) && !empty($return[$row['user_id']]['tag'])) {
                    $row['tag'] = $return[$row['user_id']]['tag'].' '.Display::url($row['tag'] , api_get_path(WEB_PATH).'main/social/search.php?q='.$row['tag'] , array('class'=>'tag'));
                } else {
                    $row['tag'] = Display::url($row['tag'], api_get_path(WEB_PATH).'main/social/search.php?q='.$row['tag'],   array('class'=>'tag'));
                }
                $return[$row['user_id']] = $row;
            }
        }

        $keyword = $tag;
        $sql = "SELECT u.user_id, u.username, firstname, lastname, email, picture_uri FROM $user_table u";

        if (api_get_multiple_access_url()) {
            $access_url_rel_user_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
        }

        if (isset ($keyword)) {
                $keyword = Database::escape_string($keyword);
                //OR u.official_code LIKE '%".$keyword."%'
                // OR u.email LIKE '%".$keyword."%'
                $sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR u.username LIKE '%".$keyword."%' OR concat(u.firstname,' ',u.lastname) LIKE '%".$keyword."%' OR concat(u.lastname,' ',u.firstname) LIKE '%".$keyword."%' )";
            }
        $keyword_active = true;
        //only active users
        if ($keyword_active) {
            $sql .= " AND u.active='1'";
        }
        //avoid anonymous
        $sql .= " AND u.status <> 6 ";

        // adding the filter to see the user's only of the current access_url
        if (api_get_multiple_access_url() && api_get_current_access_url_id()!=-1) {
                $sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
        }
        $direction = 'ASC';
        if (!in_array($direction, array('ASC','DESC'))) {
            $direction = 'ASC';
        }

        //$column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        //$sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        if (Database::num_rows($res)> 0) {
            while ($row = Database::fetch_array($res,'ASSOC')) {
                if (!in_array($row['user_id'], array_keys($return))) {
                    $return[$row['user_id']] = $row;
                }
            }
        }
        return $return;
    }

    /**
     * Show the search form
     * @param string the value of the search box
     *
     */
    public static function get_search_form($query) {
        return '
        <form method="GET" class="well form-search" action="'.api_get_path(WEB_PATH).'main/social/search.php">
                <input placeholder="'.get_lang('UsersGroups').'" type="text" class="input-medium" value="'.api_htmlentities(Security::remove_XSS($query)).'" name="q"/> &nbsp;
                <button class="btn" type="submit" value="search">'.get_lang('Search').'</button>
        </form>';
    }

    /**
     * Shows the user menu
     */
    public static function show_menu(){
        echo '<div class="actions">';
        echo '<a href="/main/auth/profile.php">'.    Display::return_icon('profile.png').' '.get_lang('PersonalData').'</a>';
        echo '<a href="/main/messages/inbox.php">'.    Display::return_icon('inbox.png').' '.    get_lang('Inbox').'</a>';
        echo '<a href="/main/messages/outbox.php">'.Display::return_icon('outbox.png').' '.    get_lang('Outbox').'</a>';
        echo '<span style="float:right; padding-top:7px;">'.
             '<a href="/main/auth/profile.php?show=1">'.Display::return_icon('edit.gif').' '.get_lang('Configuration').'</a>';
             '</span>';
        echo '</div>';
    }

    /**
     * Gives a list of course auto-register (field special_course)
     * @return array  list of course
     * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>
     * @deprecated this function is never use in chamilo, use CourseManager::get_special_course_list
     * @since Dokeos 1.8.6.2
     */
    public static function get_special_course_list() {
        // Database Table Definitions
        $tbl_course_user             = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_field             = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
        $tbl_course_field_value        = Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
        $tbl_user_course_category   = Database :: get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

        //we filter the courses from the URL
        $join_access_url=$where_access_url='';

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();

            $tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $join_access_url= "INNER JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= course.code";
            $where_access_url=" AND access_url_id = $access_url_id ";

        }

        // Filter special courses
        $sql_special_course = "SELECT course_code FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf ON " .
                " tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";
        $special_course_result = Database::query($sql_special_course);
        $code_special_courses = '';
        if(Database::num_rows($special_course_result)>0) {
            $special_course_list = array();
            while ($result_row = Database::fetch_array($special_course_result)) {
                $special_course_list[] = '"'.$result_row['course_code'].'"';
            }
            $code_special_courses = ' course.code IN ('.join($special_course_list, ',').') ';
        }

        // variable initialisation
        $course_list_sql = '';
        $course_list = array();
        if(!empty($code_special_courses)) {
            $course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                                FROM    ".$tbl_course_user." course_rel_user
                                INNER JOIN ".$tbl_course." course
                                ON course.code = course_rel_user.course_code
                                INNER JOIN ".$tbl_user_course_category." user_course_category
                                ON course_rel_user.user_course_cat = user_course_category.id
                                $join_access_url
                                WHERE  $code_special_courses $where_access_url
                                GROUP BY course.code
                                ORDER BY user_course_category.sort,course.title,course_rel_user.sort ASC";
            $course_list_sql_result = Database::query($course_list_sql);
            while ($result_row = Database::fetch_array($course_list_sql_result)) {
                $course_list[] = $result_row;
            }
        }
        return $course_list;
    }

    /**
     * Allow to register contact to social network
     * @param int user friend id
     * @param int user id
     * @param int relation between users see constants definition
     */
    public static function relate_users ($friend_id,$my_user_id,$relation_type) {
        $tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);

        $friend_id = intval($friend_id);
        $my_user_id = intval($my_user_id);
        $relation_type = intval($relation_type);

        $sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' .$friend_id.' AND user_id='.$my_user_id.' AND relation_type <> '.USER_RELATION_TYPE_RRHH.' ';
        $result = Database::query($sql);
        $row = Database :: fetch_array($result, 'ASSOC');
        $current_date=date('Y-m-d H:i:s');

        if ($row['count'] == 0) {
            $sql_i = 'INSERT INTO ' . $tbl_my_friend . '(friend_user_id,user_id,relation_type,last_edit)values(' . $friend_id . ','.$my_user_id.','.$relation_type.',"'.$current_date.'");';
            Database::query($sql_i);
            return true;
        } else {
            $sql = 'SELECT COUNT(*) as count, relation_type  FROM ' . $tbl_my_friend . ' WHERE friend_user_id=' . $friend_id . ' AND user_id='.$my_user_id.' AND relation_type <> '.USER_RELATION_TYPE_RRHH.' ';
            $result = Database::query($sql);
            $row = Database :: fetch_array($result, 'ASSOC');
            if ($row['count'] == 1) {
                //only for the case of a RRHH
                if ($row['relation_type'] != $relation_type && $relation_type == USER_RELATION_TYPE_RRHH) {
                    $sql_i = 'INSERT INTO ' . $tbl_my_friend . '(friend_user_id,user_id,relation_type,last_edit)values(' . $friend_id . ','.$my_user_id.','.$relation_type.',"'.$current_date.'");';
                } else {
                    $sql_i = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.$relation_type.' WHERE friend_user_id=' . $friend_id.' AND user_id='.$my_user_id;
                }
                Database::query($sql_i);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Deletes a contact
     * @param int user friend id
     * @param bool true will delete ALL friends relationship from $friend_id
     * @author isaac flores paz <isaac.flores@dokeos.com>
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function remove_user_rel_user ($friend_id, $real_removed = false, $with_status_condition = '') {
        $tbl_my_friend  = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_my_message = Database :: get_main_table(TABLE_MAIN_MESSAGE);
        $friend_id = intval($friend_id);

        if ($real_removed) {
            //Delete user friend
            /*
            $sql_delete_relationship1 = 'UPDATE ' . $tbl_my_friend .'  SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE friend_user_id='.$friend_id;
            $sql_delete_relationship2 = 'UPDATE ' . $tbl_my_friend . ' SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE user_id=' . $friend_id;
            Database::query($sql_delete_relationship1);
            Database::query($sql_delete_relationship2);*/
            $extra_condition = '';
            if ($with_status_condition != '') {
                $extra_condition = ' AND relation_type = '.intval($with_status_condition);
            }
            $sql_delete_relationship1 = 'DELETE FROM ' . $tbl_my_friend .'  WHERE relation_type <> '.USER_RELATION_TYPE_RRHH.' AND friend_user_id='.$friend_id.' '.$extra_condition;
            $sql_delete_relationship2 = 'DELETE FROM ' . $tbl_my_friend . ' WHERE relation_type <> '.USER_RELATION_TYPE_RRHH.' AND user_id=' . $friend_id.' '.$extra_condition;
            Database::query($sql_delete_relationship1);
            Database::query($sql_delete_relationship2);

        } else {
            $user_id = api_get_user_id();
            $sql = 'SELECT COUNT(*) as count FROM ' . $tbl_my_friend . ' WHERE user_id=' . $user_id . ' AND relation_type NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND friend_user_id='.$friend_id;
            $result = Database::query($sql);
            $row = Database :: fetch_array($result, 'ASSOC');
            if ($row['count'] == 1) {
                //Delete user rel user
                $sql_i = 'UPDATE ' . $tbl_my_friend .' SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE user_id='.$user_id.' AND friend_user_id='.$friend_id;
                $sql_j = 'UPDATE ' . $tbl_my_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.' WHERE user_receiver_id=' . $user_id.' AND user_sender_id='.$friend_id.' AND update_date="0000-00-00 00:00:00" ';
                //Delete user
                $sql_ij = 'UPDATE ' . $tbl_my_friend . '  SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE user_id=' . $friend_id.' AND friend_user_id='.$user_id;
                $sql_ji = 'UPDATE ' . $tbl_my_message . ' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.' WHERE user_receiver_id=' . $friend_id.' AND user_sender_id='.$user_id.' AND update_date="0000-00-00 00:00:00" ';
                Database::query($sql_i);
                Database::query($sql_j);
                Database::query($sql_ij);
                Database::query($sql_ji);
            }
        }
    }

    /**
     * get users folloewd by human resource manager
     * @param int          hr_dept id
     * @param int        user status (optional)
     * @return array     users
     */
    public static function get_users_followed_by_drh($hr_dept_id, $user_status = 0) {
        // Database Table Definitions
        $tbl_user                = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_user_rel_user       = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $hr_dept_id = intval($hr_dept_id);
        $assigned_users_to_hrm = array();

        $condition_status = '';
        if (!empty($user_status)) {
            $status = intval($status);
            $condition_status = ' AND u.status = '.$user_status;
        }
        if (api_get_multiple_access_url()) {
            $sql = "SELECT u.user_id, u.username, u.lastname, u.firstname, u.email FROM $tbl_user u
                    INNER JOIN $tbl_user_rel_user uru ON (uru.user_id = u.user_id) LEFT JOIN $tbl_user_rel_access_url a
                    ON (a.user_id = u.user_id)
                    WHERE   friend_user_id = '$hr_dept_id' AND
                            relation_type = '".USER_RELATION_TYPE_RRHH."'
                            $condition_status AND
                            access_url_id = ".api_get_current_access_url_id()."
                    ";
        } else {
            $sql = "SELECT u.user_id, u.username, u.lastname, u.firstname, u.email FROM $tbl_user u
                    INNER JOIN $tbl_user_rel_user uru
                    ON  uru.user_id = u.user_id AND
                        friend_user_id = '$hr_dept_id' AND
                        relation_type = '".USER_RELATION_TYPE_RRHH."'
                        $condition_status ";
        }

        if (api_is_western_name_order()) {
            $sql .= " ORDER BY u.firstname, u.lastname ";
        } else {
            $sql .= " ORDER BY u.lastname, u.firstname ";
        }

        $rs_assigned_users = Database::query($sql);
        if (Database::num_rows($rs_assigned_users) > 0) {
            while ($row_assigned_users = Database::fetch_array($rs_assigned_users))    {
                $assigned_users_to_hrm[$row_assigned_users['user_id']] = $row_assigned_users;
            }
        }
        return $assigned_users_to_hrm;

    }

    /**
      * Subscribes users to human resource manager (Dashboard feature)
      *    @param    int         hr dept id
      * @param    array        Users id
      * @param    int            affected rows
      **/
    public static function suscribe_users_to_hr_manager($hr_dept_id, $users_id) {

        // Database Table Definitions
        $tbl_user           =   Database::get_main_table(TABLE_MAIN_USER);
        $tbl_user_rel_user  =   Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_user_rel_access_url     =   Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $hr_dept_id = intval($hr_dept_id);
        $affected_rows = 0;

        if (api_get_multiple_access_url()) {
            //Deleting assigned users to hrm_id
            $sql = "SELECT s.user_id FROM $tbl_user_rel_user s INNER JOIN $tbl_user_rel_access_url a ON (a.user_id = s.user_id)  WHERE friend_user_id = $hr_dept_id AND relation_type = '".USER_RELATION_TYPE_RRHH."'  AND access_url_id = ".api_get_current_access_url_id()."";
        } else {
            $sql = "SELECT user_id FROM $tbl_user_rel_user WHERE friend_user_id = $hr_dept_id AND relation_type = '".USER_RELATION_TYPE_RRHH."' ";
        }
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result))   {
                $sql = "DELETE FROM $tbl_user_rel_user WHERE user_id = '{$row['user_id']}'  AND friend_user_id = $hr_dept_id AND relation_type = '".USER_RELATION_TYPE_RRHH."' ";
                Database::query($sql);
            }
        }

        // inserting new user list
        if (is_array($users_id)) {
            foreach ($users_id as $user_id) {
                $user_id = intval($user_id);
                $insert_sql = "INSERT IGNORE INTO $tbl_user_rel_user(user_id, friend_user_id, relation_type) VALUES('$user_id', $hr_dept_id, '".USER_RELATION_TYPE_RRHH."')";
                Database::query($insert_sql);
                $affected_rows = Database::affected_rows();
            }
        }
        return $affected_rows;
    }


    /**
     * This function check if an user is followed by human resources manager
     * @param     int     User id
     * @param    int        Human resources manager
     * @return    bool
     */
    public static function is_user_followed_by_drh($user_id, $hr_dept_id) {

        // Database table and variables Definitions
        $tbl_user_rel_user     =     Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_id     = intval($user_id);
        $hr_dept_id = intval($hr_dept_id);
        $result = false;

        $sql = "SELECT user_id FROM $tbl_user_rel_user WHERE user_id='$user_id' AND friend_user_id='$hr_dept_id' AND relation_type = ".USER_RELATION_TYPE_RRHH." ";
        $rs  = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $result = true;
        }
        return $result;

    }
    /**
     * get user id of teacher or session administrator
     * @param string The course id
     * @return int The user id
     */
     public static function get_user_id_of_course_admin_or_session_admin ($course_id) {
         $session=api_get_session_id();
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
         if ($session==0 || is_null($session)) {
             $sql='SELECT u.user_id FROM '.$table_user.' u
                    INNER JOIN '.$table_course_user.' ru ON ru.user_id=u.user_id
                    WHERE ru.status=1 AND ru.course_code="'.Database::escape_string($course_id).'" ';
            $rs=Database::query($sql);
            $num_rows=Database::num_rows($rs);
            if ($num_rows==1) {
                $row=Database::fetch_array($rs);
                return $row['user_id'];
            } else {
                $my_num_rows=$num_rows;
                $my_user_id=Database::result($rs,$my_num_rows-1,'user_id');
                return $my_user_id;
            }
        } elseif ($session>0) {
            $sql='SELECT u.user_id FROM '.$table_user.' u
                INNER JOIN '.$table_session_course_user.' sru
                ON sru.id_user=u.user_id WHERE sru.course_code="'.Database::escape_string($course_id).'" ';
            $rs=Database::query($sql);
            $row=Database::fetch_array($rs);

            return $row['user_id'];
             }
         }

  /**
   * Determines if a user is a gradebook certified
   * @param int The category id of gradebook
   * @param int The user id
   * @return boolean
   */
    public static function is_user_certified($cat_id,$user_id) {
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql  = 'SELECT path_certificate FROM '.$table_certificate.' WHERE cat_id="'.Database::escape_string($cat_id).'" AND user_id="'.Database::escape_string($user_id).'" ';
        $rs   = Database::query($sql);
        $row  = Database::fetch_array($rs);
        if ($row['path_certificate']=='' || is_null($row['path_certificate'])) {
            return false;
        } else {
            return true;
        }
    }

   /**
   * Gets the info about a gradebook certificate for a user by course
   * @param string The course code
   * @param int The user id
   * @return array  if there is not information return false
   */
    public static function get_info_gradebook_certificate($course_code, $user_id) {
          $tbl_grade_certificate     = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
          $tbl_grade_category     = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
          $session_id             = api_get_session_id();

          if (empty($session_id)) {
              $session_condition = ' AND (session_id = "" OR session_id = 0 OR session_id IS NULL )';
          } else {
              $session_condition = " AND session_id = $session_id";
          }
          //Getting gradebook score
          require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php';
          require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';

          $sql = 'SELECT * FROM '.$tbl_grade_certificate.' WHERE cat_id = (SELECT id FROM '.$tbl_grade_category.' WHERE course_code = "'.Database::escape_string($course_code).'" '.$session_condition.' LIMIT 1 ) AND user_id='.Database::escape_string($user_id);

          $rs  = Database::query($sql);
          if (Database::num_rows($rs) > 0) {
              $row = Database::fetch_array($rs,'ASSOC');
              $score           = $row['score_certificate'];
              $category_id  = $row['cat_id'];
              $cat         = Category::load($category_id);
              $displayscore = ScoreDisplay::instance();
                    $grade = '';
              if (isset($cat) && $displayscore->is_custom()) {
                        $grade = $displayscore->display_score(array($score, $cat[0]->get_weight()), SCORE_DIV_PERCENT_WITH_CUSTOM);
                    } else {
                      $grade = $displayscore->display_score(array($score, $cat[0]->get_weight()));
                    }
              $row['grade'] = $grade;
              return $row;
        }
          return false;
    }

     /**
      * Gets the user path of user certificated
      * @param int The user id
      * @return array  containing path_certificate and cat_id
      */
    public static function get_user_path_certificate($user_id) {
         $my_certificate = array();
        $table_certificate             = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $table_gradebook_category     = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

         $session_id = api_get_session_id();
         $user_id = intval($user_id);
         if ($session_id==0 || is_null($session_id)) {
             $sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
         } elseif ($session_id>0) {
             $sql_session='AND session_id='.Database::escape_string($session_id);
         } else {
             $sql_session='';
         }
         $sql= "SELECT tc.path_certificate,tc.cat_id,tgc.course_code,tgc.name FROM $table_certificate tc, $table_gradebook_category tgc
               WHERE tgc.id = tc.cat_id AND tc.user_id='$user_id'  ORDER BY tc.date_certificate DESC limit 5";

         $rs=Database::query($sql);
         while ($row=Database::fetch_array($rs)) {
             $my_certificate[]=$row;
         }
         return $my_certificate;
    }


     /**
      * This function check if the user is a coach inside session course
      * @param  int     User id
      * @param  string  Course code
      * @param  int     Session id
      * @return bool    True if the user is a coach
      *
      */
    public static function is_session_course_coach($user_id, $course_code, $session_id) {
        $tbl_session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        // Protect data
        $user_id = intval($user_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);
        $result = false;

        $sql = "SELECT id_session FROM $tbl_session_course_rel_user WHERE id_session=$session_id AND course_code='$course_code' AND id_user = $user_id AND status=2 ";
        $res = Database::query($sql);

        if (Database::num_rows($res) > 0) {
            $result = true;
        }
        return $result;
    }

    /**
     * This function returns an icon path that represents the favicon of the website of which the url given. Defaults to the current Chamilo favicon
     * @param    string    URL of website where to look for favicon.ico
     * @param    string    Optional second URL of website where to look for favicon.ico
     * @return    string    Path of icon to load
     */
    public static function get_favicon_from_url($url1, $url2 = null) {
        $icon_link = '';
        $url = $url1;
        if (empty($url1)) {
            $url = $url2;
            if (empty($url)) {
                $url = api_get_access_url(api_get_current_access_url_id());
                $url = $url[0];
            }
        }
        if (!empty($url)) {
            $pieces = parse_url($url);
            $icon_link = $pieces['scheme'].'://'.$pieces['host'].'/favicon.ico';
        }
        return $icon_link;
    }

    /**
     *
     * @param int   student id
     * @param int   years
     * @param bool  show warning_message
     * @param bool  return_timestamp

     */
    public static function delete_inactive_student($student_id, $years = 2, $warning_message = false, $return_timestamp = false) {
        $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date FROM ' . $tbl_track_login . ' WHERE login_user_id = ' . intval($student_id) . ' ORDER BY login_date DESC LIMIT 0,1';
        if (empty($years)) {
            $years = 1;
        }
        $inactive_time = $years * 31536000;  //1 year
        $rs = Database::query($sql);
        if (Database::num_rows($rs)>0) {
            if ($last_login_date = Database::result($rs, 0, 0)) {
                $last_login_date = api_get_local_time($last_login_date, null, date_default_timezone_get());
                if ($return_timestamp) {
                    return api_strtotime($last_login_date);
                } else {
                    if (!$warning_message) {
                        return api_format_date($last_login_date, DATE_FORMAT_SHORT);
                    } else {
                        $timestamp = api_strtotime($last_login_date);
                        $currentTimestamp = time();

                        //If the last connection is > than 7 days, the text is red
                        //345600 = 7 days in seconds 63072000= 2 ans

                     // if ($currentTimestamp - $timestamp > 184590 )
                        if ($currentTimestamp - $timestamp > $inactive_time && UserManager::delete_user($student_id )) {
                            Display :: display_normal_message(get_lang('UserDeleted'));
                                //avec validation:
                                //  $result .= '<a href="user_list.php?action=delete_user&amp;user_id='.$student_id.'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
                            echo '<p>','id',$student_id ,':',$last_login_date,'</p>';
                        }
                    }
                }
            }
        }
        return false;
    }

    static function get_user_field_types() {
        return ExtraField::get_extra_fields_by_handler('user');
    }

    static function add_user_as_admin($user_id) {
        $table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $user_id = intval($user_id);

        if (!self::is_admin($user_id)) {
            $sql = "INSERT INTO $table_admin SET user_id = '".$user_id."'";
            Database::query($sql);
         }
    }

    static function remove_user_admin($user_id) {
        $table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $user_id = intval($user_id);
        if (self::is_admin($user_id)) {
            $sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
            Database::query($sql);
        }
    }

    static function update_all_user_languages($from, $to) {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $from = Database::escape_string($from);
        $to = Database::escape_string($to);

        if (!empty($to) && !empty($from)) {
            $sql = "UPDATE $table_user SET language = '$to' WHERE language = '$from'";
            Database::query($sql);
        }
    }

    static function transform_user_group_array($user_list, $group_list, $get_names = false, $remove_users_from_group = false) {
        $complete_list = array();
        if (!empty($user_list)) {
            foreach ($user_list as $user_item) {
                if ($get_names) {
                     $user_item = api_get_user_info($user_item);
                }
                $complete_list["USER:".$user_item['user_id']] = api_get_person_name($user_item['firstname'], $user_item['lastname']);
            }
        }
        if (!empty($group_list)) {
            foreach ($group_list as $group_item) {

                if ($get_names) {
                    $group_info = GroupManager::get_group_properties($group_item);
                } else {
                    $group_info = GroupManager::get_group_properties($group_item['id']);
                }
                $complete_list["GROUP:".$group_info['id']] = $group_info['name']." [".$group_info['count_users']." ".get_lang('Users')."]";
                if ($remove_users_from_group) {
                    $users = GroupManager::get_users($group_info['id']);
                    foreach($users as $user_id) {
                        if (isset($complete_list["USER:".$user_id])) {
                            unset($complete_list["USER:".$user_id]);
                        }
                    }
                }
            }
        }
        return $complete_list;
    }

    static function generate_user_group_array($course_code, $session_id = 0) {
        $order = api_is_western_name_order() ? 'firstname' : 'lastname';
        $user_list = CourseManager::get_real_and_linked_user_list($course_code, true, $session_id, $order);
        $group_list = CourseManager::get_group_list_of_course($course_code, $session_id, 1);
        $items = self::transform_user_group_array($user_list, $group_list);
        return $items;
    }

    static function separate_users_groups_array($to, $add_group_users = false) {
        $grouplist = array();
        $userlist = array();
        $send_to = array();

        foreach ($to as $to_item) {
            list($type, $id) = explode(':', $to_item);
            switch ($type) {
                case 'GROUP':
                    $grouplist[] = intval($id);
                    if ($add_group_users) {
                        $users = GroupManager::get_users($id);
                        foreach($users as $user_id) {
                            $userlist[] = $user_id;
                        }
                    }
                    break;
                case 'USER':
                    $userlist[] = intval($id);
                    break;
            }
        }
        $send_to['groups'] = $grouplist;
        $send_to['users'] = array_unique($userlist);
        return $send_to;
    }

    /**
     * Get the last login data for a user
     * @param int $userId The user id
     * @return array The login data. Otherwise return false
     */
    public static function getLastLogin($userId)
    {
        $loginTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $result = Database::select(
            '*',
            $loginTable,
            array(
                'where' => array(
                    'login_user_id = ?' => intval($userId)
                ),
                'order' => 'login_id DESC',
                'limit' => '1'
            ),
            'first'
        );

        return $result;
    }
}
