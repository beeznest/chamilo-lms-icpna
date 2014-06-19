<?php

use \ChamiloSession as Session;

/* For licensing terms, see /license.txt */
/**
 * This file contains the necessary elements to implement a Single Sign On
 * using chamilo as a SSO server
 * @package chamilo.auth.sso
 */

class ssoServer
{
    /**
     * This is used to get the url with the SSO params
     * @param $refererSso
     * @return bool|string
     */
    public function getUrl($refererSso)
    {
        if (!empty($refererSso)) {
            $getParams = parse_url($refererSso, PHP_URL_QUERY);
            $userInfo = api_get_user_info(api_get_user_id(), false, true);
            $chamiloUrl = api_get_path(WEB_PATH);
            $params = '';
            $sso = array(
                'username' => $userInfo['username'],
                'secret' => sha1($userInfo['password']),
                'master_domain' => $chamiloUrl,
                'master_auth_uri' => $chamiloUrl . '?submitAuth=true',
                'lifetime' => time() + 3600,
                'target' => $refererSso,
            );
            $cookie = base64_encode(serialize($sso));
            if ($getParams) {
                $params .= '&loginFailed=0&sso_referer=' . $refererSso . '&sso_cookie=' . urlencode($cookie);
            } else {
                $params .= '?loginFailed=0&sso_referer=' . $refererSso . '&sso_cookie=' . urlencode($cookie);
            }
            $finalPath = $refererSso . $params;

            return $finalPath;
        }

        return false;
    }
}