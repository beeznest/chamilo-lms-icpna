<?php

/**
 * Initialization uninstall
 */
require_once dirname(__FILE__).'/config.php';
AddExternalPagesPlugin::create()->uninstall();