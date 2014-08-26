<?php

require_once 'config.php';

$plugin_info = IcpnaNumberMessagesPlugin::create()->get_info();

$plugin_info['templates'] = array('views/script.tpl');
