<?php
/* For licensing terms, see /license.txt */

//A user must be logged in
$_template['show_script'] = false;

if (!api_is_anonymous()) {
    $icpnaNumberMessages = IcpnaNumberMessagesPlugin::create();

    $icpnaNumberMessages->refreshCount();

    $numberMessages = $icpnaNumberMessages->getNumberMessagesFromDatabase();

    $settingTabName = rtrim($icpnaNumberMessages->get('tab_name'), ';');

    $tabNames = explode(";", $settingTabName);

    $_template['show_script'] = true;
    $_template['variable'] = IcpnaNumberMessagesPlugin::FIELD_VARIABLE;
    $_template['tab_name'] = $tabNames[0];
    $_template['number_messages'] = $numberMessages;
}
