<?php

//A user must be logged in
$_template['show_script'] = false;

if (!api_is_anonymous()) {
    require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
    require_once api_get_path(PLUGIN_PATH) . 'icpna_number_messages/src/icpna_number_messages_plugin.class.php';

    $icpnaNumberMessages = IcpnaNumberMessagesPlugin::create();

    $icpnaNumberMessages->refreshCount();

    $numberMessages = $icpnaNumberMessages->getNumberMessagesFromDatabase();

    $_template['show_script'] = true;
    $_template['variable'] = IcpnaNumberMessagesPlugin::FIELD_VARIABLE;
    $_template['number_messages'] = $numberMessages;
}
