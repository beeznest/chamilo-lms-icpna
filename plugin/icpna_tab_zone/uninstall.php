<?php

/**
 * Initialization install
 */
require_once dirname(__FILE__) . '/config.php';

IcpnaTabZonePlugin::create()->uninstall();
