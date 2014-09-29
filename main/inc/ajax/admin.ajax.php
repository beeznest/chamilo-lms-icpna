<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

// first valid to teacher coach
if (!api_is_teacher_admin()) {
    api_protect_admin_script(true);
}

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'user_exists':        
        $user_info = api_get_user_info($_REQUEST['id']);
        if (empty($user_info)) {
            echo 0;
        } else {
            echo 1;
        }
        break;
    case 'find_coaches':
        $coaches = SessionManager::get_coaches_by_keyword($_REQUEST['tag']);
        $json_coaches = array();
        if (!empty($coaches)) {
            foreach ($coaches as $coach) {
                $json_coaches[] = array('key' => $coach['user_id'], 'value' => api_get_person_name($coach['firstname'], $coach['lastname']));
            }
        }
        echo json_encode($json_coaches);
        break;
	case 'update_changeable_setting':
        $url_id = api_get_current_access_url_id();        
        if (api_is_global_platform_admin() && $url_id == 1) {            
            if (isset($_GET['id']) && !empty($_GET['id'])) {                
                $params = array('variable = ? ' =>  array($_GET['id']));
                $data = api_get_settings_params($params);                
                if (!empty($data)) {
                    foreach ($data as $item) {                
                        $params = array('id' =>$item['id'], 'access_url_changeable' => $_GET['changeable']);
                        api_set_setting_simple($params);        
                    }
                }                
                echo '1';
            }        
        }
        break;
        /**
        * list of teachear
        */
    case 'substituteCoachList':
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);            
        $sqlNotIn = !empty($_SESSION['sqlNotIn']) ?  $_SESSION['sqlNotIn'] : '';
        $needle = $_GET['needle'];
        $return = '';

        if (!empty($needle)) {
            if ($needle == 'false') {
                $needle = '';
            }

            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = Database::escape_string($needle);
            $needle = api_convert_encoding($needle, $charset, 'utf-8');

            $sql = 'SELECT u.user_id, u.username, u.lastname, u.firstname FROM '.$tblUser.' u
                    WHERE u.lastname  LIKE "'.$needle.'%" AND u.status = 1 AND u.active = 1  '.$sqlNotIn.' order by u.lastname';
            $rs = Database::query($sql);

            $return .= '<select id="origin" name="usersList[]" multiple="multiple" size="20" style="width:360px;">';
            while ($user = Database :: fetch_array($rs)) {
                $person_name = api_get_person_name($user['firstname'], $user['lastname'], null, PERSON_NAME_EASTERN_ORDER);
                $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
            }
            $return .= '</select>';

            echo api_utf8_encode($return);
        }
        break;
}
