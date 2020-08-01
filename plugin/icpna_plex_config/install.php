<?php
/* For licensing terms, see /license.txt */

if (api_is_platform_admin()) {
    $extraField = new ExtraField('course');
    $params = [
        'variable' => IcpnaPlexConfigPlugin::FIELD_COURSE_CATEGORY,
        'field_type' => ExtraField::FIELD_TYPE_INTEGER,
        'display_text' => get_plugin_lang('FieldCourseCategory', IcpnaPlexConfigPlugin::class),
        'default_value' => '0',
        'changeable' => true,
        'visible_to_self' => true,
    ];
    $extraField->save($params);

    HookQuizEnd::create()->attach(
        IcpnaPlexConfigQuizEndHook::create()
    );
}
