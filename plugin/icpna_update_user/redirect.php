<?php
/* For licensing terms, see /license.txt */

/**
 * This script check if the user has completed his/her profile
 * Only if profile is incomplete then redirect to profile page
 *
 * Before you need set the configuration settings indicated in README.md
 */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = IcpnaUpdateUserPlugin::create();

$userId = api_get_user_id();
$efv = new ExtraFieldValue('user');
$uididpersona = $efv->get_values_by_handler_and_field_variable($userId, 'uididpersona');

$profileIsCompleted = $plugin->profileIsCompleted($uididpersona['value']);

$url = $profileIsCompleted ? api_get_path(WEB_PATH) : api_get_path(WEB_CODE_PATH).'auth/profile.php';

header('Location: '.$url);
