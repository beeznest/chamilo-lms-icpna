<?php
/**
 * This script should be called by cron.d to update items based on stored
 * transactions
 * @package chamilo.migration
 */
/**
 * Init
 */
if (PHP_SAPI != 'cli') {
    exit;
}
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require_once 'config.php';
require_once 'migration.class.php'; 
require_once 'db_matches.php';
// redefine web services config
require_once 'ws.conf.php';
foreach ($servers as $server_info) {
    if ($server_info['active'])  {
        //echo "\n---- Start loading server----- \n";
        //echo $server_info['name']."\n\n";
        error_log('Treating server '.$server_info['name']);

        $config_info = $server_info['connection'];
        $db_type = $config_info['type'];

        if (empty($db_type)) {
            die("This script requires a DB type to work. Please update orig_db_conn.inc.php\n");
        }
        $file = dirname(__FILE__) . '/migration.' . $db_type . '.class.php';
        if (!is_file($file)) {
            die("Could not find db type file " . $file . "\n");
        }
        require_once $file;
        $class = 'Migration' . strtoupper($db_type);
        $m = new $class($config_info['host'], $config_info['port'], $config_info['db_user'], $config_info['db_pass'], $config_info['db_name'], $boost);
        $m->connect();
        //echo $m->describe('transaccion');
        $trans_id = $m->get_last_id('transaccion','idt');
        echo "Branch ".$server_info['name']." has Transaction ID ".$trans_id."\n";
    }

}
