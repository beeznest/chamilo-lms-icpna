<?php

/* For licensing terms, see /license.txt */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__.'/../../main/inc/global.inc.php';

$keyFilePath = '/var/www/html/app/config/online_enrollment/private.key';
$keyContents = file_get_contents($keyFilePath);
$key = new Key(
    file_get_contents($keyFilePath),
    'RS256'
);

$now = time();

$jwtContent = [
    'iat' => $now,
    'exp' => $now + 60 * 2,
    'data' => [
        "username" => "46654569",
        "password" => "12345678",
        "firstname" => "Angel Fernando",
        "lastname" => "Quiroz Campos",
        "email" => "angel.quiroz@beeznest.com",
        "phone" => "011234567",
        "role" => "student",
        "uididpersona" => "CE165907-3DEF-487E-BE4B-41A7E34C982D",
        "uididprograma" => "8C95A76C-F17D-4BBC-8EAC-2753EB8048A4",
    ],
];

$jwt = JWT::encode(
    $jwtContent,
    $keyContents,
    'RS256',
    '4f08c82c971c52e93bb1'
);

$query = http_build_query(
    [
        'token' => $jwt,
    ]
);

echo "JWT:<br>
    <pre>$jwt</pre>
    Link:
    <pre>plugin/icpna_online_enrollment/index.php?$query</pre>
";