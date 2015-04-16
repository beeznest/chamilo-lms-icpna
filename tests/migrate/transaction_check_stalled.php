<?php
/**
 * Checks the status of the synchronization and sends e-mails if blocked
 */
/**
 * Init
 */
require_once '../../main/inc/global.inc.php';
$alert_mail = 'Webmaster <info@example.com>';
$last_file = __DIR__.'/chamilo.transaction.last';
$last_fix_file = __DIR__.'/chamilo.transaction.fix.last';
$pid_file = __DIR__.'/chamilo.transaction.pid';
$pid_fix_file = __DIR__.'/chamilo.transaction.fix.pid';
$now = time();
$failing = false;
/**
 * Checks
 */
// check if the normal transactions pid file is present
if (is_file($pid_file)) {
    //read contents of last transaction run log
    if (is_file($last_file)) {
        $s = file_get_contents($last_file);
        $matches = array();
        if (preg_match('/x::(\d*)::x/',$s,$matches)) {
            //enter in panic mode if last execution time was more than 10' ago
            $failing = $failing || ($now>$matches[1]+(60*10));
        }
    }
}
//If no pid file, nothing to worry about...
//Do the same for the fixes log
if (is_file($pid_fix_file)) {
    //read contents of last transaction run log
    if (is_file($last_fix_file)) {
        $s = file_get_contents($last_fix_file);
        $matches = array();
        if (preg_match('/x::(\d*)::x/',$s,$matches)) {
            //enter in panic mode if last execution time was more than 10' ago
            $failing = $failing || ($now>$matches[1]+(60*10));
        }
    }
}
/**
 * React
 */
if (!$failing) {
    error_log('Checked locked files. No locked process found. All good.');
} else {
    // if it failed, then we need to:
    echo "Sending mail to admins\n";
    // 1. send an e-mail alert
    $t = 'Transactions were locked on '.$_configuration['root_web'];
    $b = 'Please make sure you check '.$_configuration['root_web'].' to see if everything is OK'."\r\n\r\n".'This was detected on '.date('Y-m-d H:i:s',$now)."\r\n";
    $t = 'Sincronización bloqueada en '.$_configuration['root_web'];
    $b = 'Por favor, asegúrese de verificar los logs en '.__DIR__.'/php_errors.log para identificar el problema.'."\r\n\r\nPor mientras, la sincronización ha sido re-lanzada automáticamente. Si no toma efecto, borre los archivos siguientes manualmente:\r\n$pid_file\r\n$pid_fix_file\r\n\r\nEste problema fue detectado después de 10 minutos de inactividad por ".__FILE__." en el momento siguiente: ".date('Y-m-d H:i:s',$now)."\r\n";

    @api_send_mail($alert_mail,$t,$b);
    // 2. remove the lock files
    if (file_exists($pid_file)) {
        $pid = @file_get_contents($pid_file);
        if (!file_exists('/proc/'.trim($pid))) {
            @unlink($pid_file);
        }
    }
    if (file_exists($pid_fix_file)) {
        $pid = @file_get_contents($pid_fix_file);
        if (!file_exists('/proc/'.trim($pid))) {
            @unlink($pid_fix_file);
        }
    }
}
