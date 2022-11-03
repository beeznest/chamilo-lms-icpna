<?php

/* For licensing terms, see /license.txt */

IcpnaOnlineEnrollmentPlugin::create()->uninstallHook();

$onjSessionField = new ExtraField('session');
$moSessionField = $onjSessionField->get_handler_field_info_by_field_variable(
    IcpnaOnlineEnrollmentPlugin::FIELD_SO_SESSION
);

$onjSessionField->delete($moSessionField['id']);
