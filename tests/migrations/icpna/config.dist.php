<?php

/**
 * This is the configuration file allowing you to connect to the origin
 * database. You should either fill this one in or generate your own
 * copy as config.php
 */
/**
 * Define all connection variables
 */
/*
 * The database type allows you to define with database driver to use.
 * Currently allowed values are: mssql. Defaults to: mssql
 */
$db_type = 'mssql';
/*
 * The database host is the name of the server on which the origin
 * database is located. This name should be routeable by PHP.
 * Defaults to: localhost
 */
$db_host = 'localhost';
/*
 * The database port is the port on which to connect on the origin
 * database host. The default port for MS-SQL is 1433, which we
 * use as a default port here. Defaults to: 1433
 */
$db_port = '1433';
/*
 * The database user is the name under which to connect to the 
 * origin database server. Defaults to: lms
 */
$db_user = 'chamilo';
/*
 * The database password is the password for the user on the origin
 * database server. Defaults to: password
 */
$db_pass = '123456';
/*
 * The database name on the database origin server.
 * Defaults to: master
 */
$db_name2 = 'master';
/**
 * Boost the migration by putting the relations tables in memory (as well as
 * in the database). This might use huge amounts of memory when managing 
 * users bases of several hundred thousands, so the default is to disable it
 */
$boost = array(
    'boost_users'=>true,
    'boost_courses'=>true,
    'boost_sessions'=>true,
    'boost_attendances'=>true,
    'boost_gradebooks' => true
);

$configx = array(    
    'type' => $db_type, //or custom value
    'host' => $db_host, //or custom value
    'port' => $db_port, //or custom value
    'db_user' => $db_user,//or custom value
    'db_pass' => $db_pass, //or custom value
    'db_name' => $db_name, //or custom value
);

$servers = array(
    0 => array(
        'name'     => 'ICPNA branch 1',
        'branch_id'     => 1,
        'filename'      => 'db_matches.php',
        'connection'    => $configx,
        'active'        => false,
    ),
    //1 => ...
);
