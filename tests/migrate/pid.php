#!/usr/bin/php5
<?php
$pidfile = __DIR__.'/chamilo.transaction.pid';
if ($argc>1) {
  $command = $argv[1];
}
$pid = getmypid();
switch ($command) {
  case 'write':
    if (is_file($pidfile)) {
      die('PID file already exists'."\n");
    }
    file_put_contents($pidfile,$pid);
    die('PID file written with PID '."$pid\n");
    break;
  case 'delete':
    if (!is_file($pidfile)) {
      die('No PID file to delete'."\n");
    }
    $res = @exec('rm '.$pidfile);
    if ($res === false) {
      die('Could not delete PID file'."\n");
    }
    die('PID file deleted'."\n");
    break;
  case 'status':
  default:
    if (is_file($pidfile)) {
      $pid = file_get_contents($pidfile);
      die('PID file already exists with PID '.$pid."\n"); 
    } else {
      die('PID file does not exist'."\n");
    }
    break;
}
