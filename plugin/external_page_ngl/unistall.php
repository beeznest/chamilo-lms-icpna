<?php

/**
 * Initialization install
 */
require_once dirname(__FILE__) . '/config.php';
ExternalPageNGLPlugin::create()->uninstall();
