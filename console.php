<?php
/**
 * This file is to register the symfony commands
 */
require_once 'vendor/autoload.php';
require_once 'main/console/ConsumeWS.php';
require_once 'main/console/NumberMessagesCommand.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new ConsumeWSCommand);
$application->add(new NumberMessagesCommand());
$application->run();
