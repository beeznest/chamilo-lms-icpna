<?php

require 'client_time.inc.php';

if (isset($_GET['sso_cookie'])) {
    $cookieInformation = unserialize(base64_decode($_GET['sso_cookie']));

    $username = $cookieInformation['username'];
    $password = $cookieInformation['secret'];
    $url = $cookieInformation['loginprocess'];

    $params = array(
        'normalLogin' => $username,
        'time' => getClientTime(),
        'normalPassword' => $password,
        'closePriorIfNotResumeSession' => 'true',
        'closePrior' => 'true',
        'resumeSession' => 'false',
        'login' => $username,
        'password' => $password,
        'prod' => ''
    );

    $paramsString = http_build_query($params);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);

    curl_exec($ch);

    curl_close($ch);
}