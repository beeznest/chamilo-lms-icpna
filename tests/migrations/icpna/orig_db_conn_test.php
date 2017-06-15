<?php
/* For licensing terms, see /license.txt */
/**
 * Init: config connection to MSSQL
 */
ini_set('display_errors',1);
ini_set('mssql.datetimeconvert',0);
/**
 * Load connect info
 */
require 'config.dist.php';
/**
 * Try connecting
 */
$conn = mssql_connect($db_host,$db_user,$db_pass,TRUE);
if ($conn == FALSE) {
  printf("Could not connect. MSSQL error: %s\n",mssql_get_last_message());
  die();
}
echo "Connected\n";
/**
 * Try querying
 */
mssql_select_db($db_name,$conn);
$sql = 'SELECT * FROM Alumno WHERE intIdAlumno = 62165';
$res = mssql_query($sql,$conn);
if ($res === false) {
  echo "Error with query ".$sql.". MSSQL error: ".mssql_get_last_message()."\n";
  die();
}
$row = mssql_fetch_array($res);
print_r($row);
echo "\n";
$sql = 'SELECT cast(uidIdAlumno as varchar(50)) FROM Alumno WHERE intIdAlumno = 62165';
$res = mssql_query($sql,$conn);
if ($res === false) {
  echo "Error with query ".$sql.". MSSQL error: ".mssql_get_last_message()."\n";
  die();
}
$row = mssql_fetch_array($res);
print_r($row);
echo "\n";
