<?php
/* For licensing terms, see /license.txt */

if (api_is_platform_admin()) {
    $field = new ExtraField('course');
    $fieldInfo = $field->get_handler_field_info_by_field_variable(IcpnaPlexConfigPlugin::FIELD_COURSE_CATEGORY);

    $field->delete($fieldInfo['id']);

    HookQuizEnd::create()->detach(
        IcpnaPlexConfigQuizEndHook::create()
    );
}
