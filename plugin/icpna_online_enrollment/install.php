<?php

/* For licensing terms, see /license.txt */

$onjSessionField = new ExtraField('session');
$moSessionField = $onjSessionField->get_handler_field_info_by_field_variable(
    IcpnaOnlineEnrollmentPlugin::FIELD_SO_SESSION
);

if (empty($moSessionField)) {
    $onjSessionField->save([
        'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
        'variable' => IcpnaOnlineEnrollmentPlugin::FIELD_SO_SESSION,
        'display_text' => get_plugin_lang('IsOnlineEnrollment', 'IcpnaOnlineEnrollmentPlugin'),
        'default_value' => null,
        'field_order' => null,
        'visible_to_self' => 1,
        'changeable' => 0,
        'filter' => null,
    ]);
}
