<?php

use \ChamiloSession as Session;

/* For licensing terms, see /license.txt */
/**
 * This file contains the necessary elements to implement a Single Sign On 
 * mechanism with an external Drupal application (on which the Chamilo module
 * 7.x-1.0-alpha3 or above must be implemented)
 *
 * To use this class, set variable "sso_authentication_subclass" to "Drupal"
 * in Chamilo settings.  If not yet available in the "Security" tab, execute the
 * following on the Chamilo database:
 *  INSERT INTO `settings_current` (`variable`, `type`, `category`, `selected_value`, `title`, `comment`, `access_url`)
 *  VALUES ('sso_authentication_subclass', 'textfield', 'Security', 'Drupal', 'SSOSubclass', 'SSOSubclassComment', 1);
 *
 * @package chamilo.auth.sso
 */
 
/**
 * The SSO class allows for management of remote Single Sign On resources
 */
class ssoPronabec {
    public $protocol;   // 'http://',
    public $domain;     // 'localhost/project/drupal',
    public $auth_uri;   // '/?q=user',
    public $deauth_uri; // '/?q=logout',
    public $referer;    // http://my.chamilo.com/main/auth/profile.php

    /**
     * Instanciates the object, initializing all relevant URL strings
     */
    public function __construct() {
        $this->protocol   = api_get_setting('sso_authentication_protocol');
        // There can be multiple domains, so make sure to take only the first
        // This might be later extended with a decision process
//        $domains          = split(',',api_get_setting('sso_authentication_domain'));
        $domains          = preg_split('/,/', api_get_setting('sso_authentication_domain'));
        $this->domain     = trim($domains[0]);
        $this->auth_uri   = api_get_setting('sso_authentication_auth_uri');
        $this->deauth_uri = api_get_setting('sso_authentication_unauth_uri');
        //cut the string to avoid recursive URL construction in case of failure
        $this->referer    = $this->protocol.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'sso'));
        $this->deauth_url = $this->protocol.$this->domain.$this->deauth_uri;
        $this->master_url = $this->protocol.$this->domain.$this->auth_uri;
        $this->target     = api_get_path(WEB_PATH);
    }
    
    /**
     * Unlogs the user from the remote server 
     */
    public function logout() {
//        header('Location: '.$this->deauth_url);
//        exit;
    }

    /**
     * Sends the user to the master URL for a check of active connection
     */
    public function ask_master() {
        // Generate a single usage token that must be encoded by the master
        $_SESSION['sso_challenge'] = api_generate_password(48);
        // Redirect browser to the master URL
        $params = 'sso_referer='.urlencode($this->referer).'&sso_target='.urlencode($this->target).'&sso_challenge='.urlencode($_SESSION['sso_challenge']);
        if (strpos($this->master_url, "?") === false) {
            $params = "?{$params}";
        } else {
            $params = "&{$params}";
        }
        header('Location: '.$this->master_url.$params);
        exit;
    }
    
    /**
     * Validates the received active connection data with the database
     * @return	bool	Return the loginFailed variable value to local.inc.php
     */
    public function check_user() {
        global $_user;
        $loginFailed = false;
        //change the way we recover the cookie depending on how it is formed
        $ssoString = $this->decode_cookie($_REQUEST['sso_cookie']);
        $sso = array (
            'uidIdPersona' => '',
            'uidIdPrograma' => '',
            'secret' => '',
        );
        list($sso['uidIdPersona'], $sso['uidIdPrograma'], $sso['secret']) = explode (";;", $ssoString);
        //get token that should have been used and delete it
        //from session since it can only be used once
        $sso_challenge = '';
        if (isset($_SESSION['sso_challenge'])) {
          $sso_challenge = $_SESSION['sso_challenge'];
          unset($_SESSION['sso_challenge']);
        }

        //lookup the user in the main database
        $ssoUserId = UserManager::get_user_id_from_original_id(trim(Database::escape_string($sso['uidIdPersona'])), 'uidIdPersona');
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date, status
                FROM $user_table
                WHERE user_id = $ssoUserId";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $uData = Database::fetch_array($result);
            //Check the user's password
            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                $secret = (string)$sso['uidIdPersona'].$sso['uidIdPrograma'].substr(api_get_security_key(), 0, 5);
                if (trim($sso['secret']) === $secret) {
                    //Check if the account is active (not locked)
                    if ($uData['active']=='1') {
                        // check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {                            
                            //If Multiple URL is enabled
                            if (api_get_multiple_access_url()) {
                                //Check the access_url configuration setting if the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();
                                // my user is subscribed in these 
                                //sites: $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);
                            } else {
                                $current_access_url_id = 1;
                                $my_url_list = array(1);
                            }
                            
                            $my_user_is_admin = UserManager::is_admin($uData['user_id']);
                            
                            if ($my_user_is_admin === false) {
                                if (is_array($my_url_list) && count($my_url_list) > 0 ) {
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        // the user has permission to enter at this site
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        Session::write('_user', $_user);
                                        event_login();
                                        if (!isset($sso['uidIdPrograma']) || $sso['uidIdPrograma'] == '') {
                                            // Redirect to homepage
                                            $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH) .'.index.php';
                                        } else {
                                            //redirect to session
                                            $sessionId = SessionManager::get_session_id_from_original_id($sso['uidIdPrograma'], 'uidIdPrograma');
                                            if (isset ($sessionId) && $sessionId != '') {
                                                $courses = SessionManager::get_course_list_by_session_id ($sessionId);
                                                $code = current($courses)['code'];
                                                if (isset ($code) && $code != '') {
                                                    $sso_target = api_get_path(WEB_PATH) . 'courses/' . $code . '/?id_session=' . $sessionId;
                                                }
                                            }
                                        }
                                        header('Location: '. $sso_target);
                                        exit;
                                    } else {
                                        // user does not have permission for this site
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                } else {
                                    // there is no URL in the multiple 
                                    // urls list for this user
                                    $loginFailed = true;
                                    Session::erase('_uid');
                                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                    exit;
                                }
                            } else {
                                //Only admins of the "main" (first) Chamilo
                                // portal can login wherever they want
                                if (in_array(1, $my_url_list)) { 
                                    //Check if this admin is admin on the  
                                    // principal portal
                                    $_user['user_id'] = $uData['user_id'];
                                    $_user = api_get_user_info($_user['user_id']);
                                    $is_platformAdmin = $uData['status'] == COURSEMANAGER;
                                    Session::write('is_platformAdmin', $is_platformAdmin);
                                    Session::write('_user', $_user);
                                    event_login();
                                } else {
                                    //Secondary URL admin wants to login 
                                    // so we check as a normal user
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        Session::write('_user',$_user);
                                        event_login();
                                    } else {
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                }
                            }                       
                        } else {
                            // user account expired
                            $loginFailed = true;
                            Session::erase('_uid');
                            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_expired');
                            exit;
                        }
                    } else {
                        //User not active
                        $loginFailed = true;
                        Session::erase('_uid');
                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                        exit;
                    }
                } else {
                    //SHA1 of password is wrong
                    $loginFailed = true;
                    Session::erase('_uid');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_password');
                    exit;
                }
            } else {
                //Auth_source is wrong
                $loginFailed = true;
                Session::erase('_uid');
                header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_authentication_source');
                exit;
            }
        } else {
            //No user by that login
            $loginFailed = true;
            Session::erase('_uid');
            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_not_found');
            exit;
        }
        return $loginFailed;
    }
    
    /**
     * Decode the cookie (this function may vary depending on the
     * Single Sign On implementation
     * @param	string	Encoded cookie
     * @return  array   Parsed and unencoded cookie
     */
    private function decode_cookie($value) {
        // referencias para que funciona la encrypción del lado ASP
        //http://stackoverflow.com/questions/24435502/aes128-encryption-using-php-mcrypt-is-not-outputting-same-as-asp-net
        //http://php.net//manual/en/function.mcrypt-encrypt.php#47973
        $key = substr(api_get_security_key(), 0, 10);
        $ivsize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv     = mcrypt_create_iv($ivsize);
        $valuedecode = base64_decode($value);
        return mcrypt_decrypt(
                   MCRYPT_RIJNDAEL_128,
                   $key, $valuedecode,
                   MCRYPT_MODE_ECB,$iv
               );
    }
}
