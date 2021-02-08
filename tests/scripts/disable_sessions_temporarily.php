<?php

/*
 * Script to disable or hide the active sessions
 * when they have at least a course with an extra field
 * called $displayVariable set to 1.
 *
 * To hide the session we change the start dates $timeModifier in the future.
 */

require __DIR__.'/../../main/inc/global.inc.php';

exit;

if (1 === $argc) {
    exit('Arguments are missing');
}

$displayVariable = 'mostrar';
$timeModifier = '+3 months';
$action = $argv[1];

// --->

$originStartDatesVariable = 'origin_start_dates';

$objField = new ExtraField('session');
$fieldInfo = $objField->get_handler_field_info_by_field_variable($originStartDatesVariable);

$objFieldValue = new ExtraFieldValue('session');

if (empty($fieldInfo)) {
    echo "Creating extrafield '$originStartDatesVariable' to rollback".PHP_EOL;

    $objField->save(
        [
            'variable' => $originStartDatesVariable,
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'display_text' => 'OriginStartDates',
            'default_value' => '',
        ]
    );
}

if ('disable' === $action) {
    $today = api_get_utc_datetime();

    $sql = "SELECT s.id, s.name, s.access_start_date, s.coach_access_start_date, s.display_start_date
        FROM session s
        INNER JOIN session_rel_course src ON s.id = src.session_id
        INNER JOIN extra_field_values efv ON src.c_id = efv.item_id
        INNER JOIN extra_field ef ON efv.field_id = ef.id
        WHERE ef.variable = '$displayVariable' AND ef.extra_field_type = 2
            AND efv.value = '1'
            AND s.display_start_date <= '$today' AND s.display_end_date >= '$today'";

    $result = Database::query($sql);

    echo "Sessions found: ".Database::num_rows($result).PHP_EOL;

    while ($sessionInfo = Database::fetch_assoc($result)) {
        $originStartDates = $objFieldValue->get_values_by_handler_and_field_variable(
            $sessionInfo['id'],
            $originStartDatesVariable
        );

        if (!empty($originStartDates)) {
            continue;
        }

        $accessStartDate = new DateTime($sessionInfo['access_start_date'], new DateTimeZone('UTC'));
        $accessStartDate->modify($timeModifier);
        $coachAccessStartDate = new DateTime($sessionInfo['coach_access_start_date'], new DateTimeZone('UTC'));
        $coachAccessStartDate->modify($timeModifier);
        $displayStartDate = new DateTime($sessionInfo['display_start_date'], new DateTimeZone('UTC'));
        $displayStartDate->modify($timeModifier);

        $sql = "UPDATE session
            SET access_start_date = '{$accessStartDate->format('Y-m-d H:i:s')}',
                coach_access_start_date = '{$coachAccessStartDate->format('Y-m-d H:i:s')}',
                display_start_date = '{$displayStartDate->format('Y-m-d H:i:s')}'
            WHERE id = {$sessionInfo['id']}";

        Database::query($sql);

        SessionManager::update_session_extra_field_value(
            $sessionInfo['id'],
            $originStartDatesVariable,
            serialize(
                [
                    'access' => $sessionInfo['access_start_date'],
                    'coach' => $sessionInfo['coach_access_start_date'],
                    'display' => $sessionInfo['display_start_date'],
                ]
            )
        );

        echo "({$sessionInfo['id']}) {$sessionInfo['name']} ----".PHP_EOL;
        echo "\t{$sessionInfo['access_start_date']} -> {$accessStartDate->format('Y-m-d H:i:s')}".PHP_EOL;
        echo "\t{$sessionInfo['coach_access_start_date']} -> {$coachAccessStartDate->format('Y-m-d H:i:s')}".PHP_EOL;
        echo "\t{$sessionInfo['display_start_date']} -> {$displayStartDate->format('Y-m-d H:i:s')}".PHP_EOL;
    }
} elseif ('enable' === $action) {
    $sql = "SELECT s.id, s.name, s.access_start_date, s.coach_access_start_date, s.display_start_date, efv_2.value
        FROM session s
        INNER JOIN session_rel_course src ON s.id = src.session_id
        INNER JOIN extra_field_values efv_1 ON src.c_id = efv_1.item_id
        INNER JOIN extra_field ef_1 ON efv_1.field_id = ef_1.id

        INNER JOIN extra_field_values efv_2 ON src.session_id = efv_2.item_id
        INNER JOIN extra_field ef_2 ON efv_2.field_id = ef_2.id

        WHERE (ef_1.variable = '$displayVariable' AND ef_1.extra_field_type = 2 AND efv_1.value = '0')
            AND (ef_2.variable = '$originStartDatesVariable' AND ef_2.extra_field_type = 3 AND efv_2.value != '')";

    $result = Database::query($sql);

    echo "Sessions found: ".Database::num_rows($result).PHP_EOL;

    while ($sessionInfo = Database::fetch_assoc($result)) {
        if (empty($sessionInfo['value'])) {
            continue;
        }

        $originStartDates = unserialize($sessionInfo['value']);

        $sql = "UPDATE session
            SET access_start_date = '{$originStartDates['access']}',
                coach_access_start_date = '{$originStartDates['coach']}',
                display_start_date = '{$originStartDates['display']}'
            WHERE id = {$sessionInfo['id']}";

        Database::query($sql);

        SessionManager::update_session_extra_field_value($sessionInfo['id'], $originStartDatesVariable, '');

        echo "({$sessionInfo['id']}) {$sessionInfo['name']} ----".PHP_EOL;
        echo "\t{$sessionInfo['access_start_date']} -> {$originStartDates['access']}".PHP_EOL;
        echo "\t{$sessionInfo['coach_access_start_date']} -> {$originStartDates['coach']}".PHP_EOL;
        echo "\t{$sessionInfo['display_start_date']} -> {$originStartDates['display']}".PHP_EOL;
    }
}




