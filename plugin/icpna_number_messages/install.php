<?php

/**
 * Initialization install
 */
require_once dirname(__FILE__) . '/config.php';

IcpnaNumberMessagesPlugin::create()->install();
