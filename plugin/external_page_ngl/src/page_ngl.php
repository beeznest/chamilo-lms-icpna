<?php

require 'client_time.inc.php';

if (isset($_GET['sso_cookie'])) {
    $cookieInformation = unserialize(base64_decode($_GET['sso_cookie']));

    $username = $cookieInformation['username'];
    $password = $cookieInformation['secret'];
    $url = $cookieInformation['loginprocess'];

    $params = array(
        'normalLogin' => urlencode($username),
        'time' => urlencode(getClientTime()),
        'normalPassword' => urlencode($password),
        'closePriorIfNotResumeSession' => urlencode('true'),
        'closePrior' => urlencode('true'),
        'resumeSession' => urlencode('false'),
        'login' => urlencode($username),
        'password' => urlencode($password),
        'prod' => urlencode('')
    );

    $paramsString = '';

    foreach ($params as $key => $value) {
        $paramsString .= $key . '=' . $value . '&';
    }

    rtrim($paramsString, '&');

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString);

    curl_exec($ch);

    curl_close($ch);
}