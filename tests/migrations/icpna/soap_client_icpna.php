<?php
/* For licensing terms, see /license.txt */
require_once __DIR__.'/../../../main/inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
//require_once $libpath.'nusoap/nusoap.php';

// Create the client instance
//$url = api_get_path(WEB_CODE_PATH)."webservices/registration.soap.php?wsdl";
$url = "http://***/***/****?wsdl";

try {
  $client = new SoapClient($url);
} catch (SoapFault $fault) {
  $error = 1;
  die('Error connecting');
}

$client->debug_flag = true;

$params = array('codigo' => '0703100500');

//$user_id = $client->call('WSCreateUserPasswordCrypted', array('createUserPasswordCrypted' => $params));
try {
  $user_details = $client->retornaDatos($params);
} catch (SoapFault $fault) { 
  $error = 2;
  die('Problem querying service');
}

if (!empty($user_details)) {
    
    // 2. Get user info of the user
    $xml = $user_details->retornaDatosResult->any;
    $stripped_xml = strstr($xml,'<diffgr:diffgram');
    $xml = simplexml_load_string($stripped_xml);
    //print_r($xml);
    foreach ($xml->NewDataSet as $user) { //this is a "Table" object
      $u = $user->Table;
      echo 'firstname: '.$u->vchprimernombre.' '.$u->vchsegundonombre."\n".'lastname: '.$u->vchpaterno.' '.$u->vchmaterno."\n";
    }
} else {
    echo 'User was not recovered, activate the debug=true in the registration.soap.php file and see the error logs'."\n";
}

