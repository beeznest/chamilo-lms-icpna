<?php
/**
 * This script should be called by cron.d to remove substitute coach
 * @package chamilo.migration
 *
 * The procedure:
 * 1) Find substitute coaches searching into session_rel_course_rel_user table
 * 2) Find session data in extra field tables (session_field, session_field_values, session_field_options)
 * 3) Verify and validate session data
 * 4) Adapt data to branch_transaction fields
 * 5) Save data into branch_transaction table
 * 6) Delete rows from session_rel_course_rel_user
 * 7) Call WS to register substitutions
 * 8) Change status of branch_transaction row
 */

// Only available using CLI
if (PHP_SAPI != 'cli') {
    exit;
}

requireAction();
$sessionIds = getSessionIdsWithSubstitute();
if (is_array($sessionIds) and !empty($sessionIds)) {
    foreach ($sessionIds as $sessionId) {
        $sessionData = getSessionData($sessionId);
        if ($sessionData !== false) {
            $branchTransactionData = adaptSessionData($sessionData);
            if ($branchTransactionData !== false) {
                if (is_array($branchTransactionData) and !empty($branchTransactionData)) {
                    $branchTransactionIds = array();
                    foreach ($branchTransactionData as $branchTransactionRow) {
                        $migration = new Migration();
                        $res = $migration->get_transaction_by_params($branchTransactionRow);
                        if ($res) {
                            $res = current($res);
                            $branchTransactionIds[] = $res['id'];
                        } else {
                            $res = $branchTransactionRow;
                            $res['id'] = Migration::add_transaction($branchTransactionRow);
                        }
                        if ($res['id']) {
                            removeSubstituteCoachFromSession($sessionId);

                            // @TODO: Set params here
                            $webServiceDetails = array('url' => 'url.com');
                            $return537 = MigrationCustom::transaction_537($res,null);
                            if ($return537 === 537) {
                                // transaction_537() is not completed yet
                                break;
                            }
                        }
                    }
                    if (!empty($branchTransactionIds[0])) {
                        // Remove substitute coaches from session

                    }
                }

            }
        }
    }
}

/**
 *
 * METHODS
 *
 */

/**
 * group of requires
 */
function requireAction()
{
    require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
    require_once 'migration.class.php';
    require_once 'migration.custom.class.php';
    $branch_id = 0;
    // We need $branch_id defined before calling db_matches.php
    // The only thing we need from db_matches is the definition of the web service
    require_once 'db_matches.php';
    // redefine web services config
    if (!is_file(__DIR__.'/ws.conf.php')) {
        die ('Please define a ws.conf.php file (copy ws.conf.dist.php) before you run the transactions');
    } else {
        require_once 'ws.conf.php';
    }
}

/**
 * Return Role Coach Substitute status value
 * @return int
 */
function getRoleCoachSubstitute() {
    return defined(ROLE_COACH_SUBSTITUTE)? ROLE_COACH_SUBSTITUTE : 18;
}

/**
 * Return session_id array if have at least one substitute coach
 * @return array
 */
function getSessionIdsWithSubstitute()
{
    $roleCoachSubstitute = getRoleCoachSubstitute();
    $sessionIds = array();
    $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sql = "SELECT DISTINCT id_session as id FROM $sessionCourseUserTable WHERE status = $roleCoachSubstitute";
    $res = Database::query($sql);

    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $sessionIds[] = $row['id'];
        }
    }
    return $sessionIds;
}

/**
 * return session data to be adapted later to branch transaction
 * @param $id
 * @return array|bool
 */
function getSessionData($id)
{
    $sessionData = array();
    $coaches = array();
    $id = intval($id);
    if ($id === 0) {
        return false;
    }
    $roleCoachSubstitute = getRoleCoachSubstitute();
    $sessionCourseUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sql = "SELECT DISTINCT user.username FROM $sessionCourseUserTable scu
    INNER JOIN user ON user.user_id = scu.id_user
    WHERE scu.status = $roleCoachSubstitute AND id_session = $id";
    $res = Database::query($sql);

    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $coaches[] = $row['username'];
        }
    } else {
        return false;
    }

    $sessionData['coaches'] = $coaches;

    $sessionExtraField = new ExtraFieldValue('session');

    $extraData = $sessionExtraField->get_values_by_handler_and_field_variable($id, 'sede');
    if ($extraData) {
        $sessionData['sede'] = $extraData['field_value'];
    } else {
        return false;
    }

    $extraData = $sessionExtraField->get_values_by_handler_and_field_variable($id, 'uidIdPrograma');
    if ($extraData) {
        $sessionData['uidIdPrograma'] = $extraData['field_value'];
    } else {
        return false;
    }

    $extraData = $sessionExtraField->get_values_by_handler_and_field_variable($id, 'horario');
    if ($extraData) {
        $sessionData['horario'] = $extraData['field_value'];
    } else {
        return false;
    }
    return $sessionData;
}

/**
 * Adapt session data to Branch transaction fields
 * @param $sessionData
 * @return array|bool
 */
function adaptSessionData($sessionData) {

    if (is_array($sessionData['coaches']) && $sessionData['sede'] && $sessionData['uidIdPrograma'] && $sessionData['horario']) {
        $branchTransactionData = array();
        foreach ($sessionData['coaches'] as $coach) {
            $branchTransactionRow = array();
            $branchTransactionRow['id'] = null;
            $branchTransactionRow['transaction_id'] = null;
            $branchTransactionRow['branch_id'] = $sessionData['sede'];
            $branchTransactionRow['action'] = 537;
            $branchTransactionRow['item_id'] = $coach;
            $branchTransactionRow['orig_id'] = null;
            $branchTransactionRow['dest_id'] = $sessionData['uidIdPrograma'];
            $branchTransactionRow['info'] = $sessionData['horario'];
            $branchTransactionRow['status_id'] = 1;
            $branchTransactionRow['time_insert'] = api_get_utc_datetime();
            $branchTransactionRow['time_update'] = api_get_utc_datetime();
            $branchTransactionData[] = $branchTransactionRow;
        }
        return $branchTransactionData;
    }
    return false;
}

/**
 * Delete rows with substitute coach from session_rel_course_rel_user
 * @param $id
 * @return bool|int
 */
function removeSubstituteCoachFromSession($id) {
    $id = intval($id);
    if ($id === 0) {
        return false;
    }
    $roleCoachSubstitute = getRoleCoachSubstitute();
    $sessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $affectedRows = Database::delete($sessionCourseUser, array(
        'status = ? AND id_session = ?' => array($roleCoachSubstitute, $id)
    ));
    return $affectedRows;
}

