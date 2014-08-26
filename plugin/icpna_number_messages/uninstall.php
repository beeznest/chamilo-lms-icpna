<?php

/**
 * Initialization uninstall
 */
require_once dirname(__FILE__) . '/config.php';

IcpnaNumberMessagesPlugin::create()->uninstall();
