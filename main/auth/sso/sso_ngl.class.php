<?php

/* For licensing terms, see /license.txt */

/**
 * This file contains the necessary elements to implement a Single Sign On
 * using chamilo as a SSO server
 * @package chamilo.auth.sso
 */
class SSONGL
{

    /**
     * This is used to get the url with the SSO params
     * @param $refererSso
     * @param $additionalParams
     * @return bool|string
     */
    public function getUrl($refererSso, $additionalParams = array())
    {
        if (!empty($refererSso)) {
            $userId = api_get_user_id();
            $userExtraFieldValue = new ExtraFieldValue('user');
            $eWorkbookLoginData = $userExtraFieldValue->get_values_by_handler_and_field_variable($userId, 'eworkbooklogin');
            $hasEWorkbookLogin = ($eWorkbookLoginData != false);

            $getParams = parse_url($refererSso, PHP_URL_QUERY);
            $userInfo = api_get_user_info(api_get_user_id(), false, true);
            $chamiloUrl = api_get_path(WEB_PATH);
            $params = '';

            $sso = array(
                'username' => ($hasEWorkbookLogin) ? $eWorkbookLoginData['field_value'] : $userInfo['username'],
                'secret' => sha1($userInfo['password']),
                'master_domain' => $chamiloUrl,
                'master_auth_uri' => $chamiloUrl . '?submitAuth=true',
                'lifetime' => time() + 3600,
                'target' => $refererSso,
            );

            if (!empty($additionalParams)) {
                foreach ($additionalParams as $key => $value) {
                    if (!empty($key)) {
                        $sso[$key] = $value;
                    } else {
                        $sso[] = $value;
                    }
                }
            }

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
