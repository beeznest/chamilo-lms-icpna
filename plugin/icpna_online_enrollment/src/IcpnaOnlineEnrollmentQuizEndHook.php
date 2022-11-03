<?php

/* For licensing terms, see /license.txt */

class IcpnaOnlineEnrollmentQuizEndHook extends HookObserver implements HookQuizEndObserverInterface
{
    public function __construct()
    {
        parent::__construct(
            'plugin/icpna_online_enrollment/src/IcpnaOnlineEnrollmentPlugin.php',
            'icpna_online_enrollment'
        );
    }

    public function hookQuizEnd(HookQuizEndEventInterface $hookvent)
    {
        $userId = api_get_user_id();
        $sessionId = api_get_session_id();

        $objFieldValue = new ExtraFieldValue('session');
        $moSessionValue = $objFieldValue->get_values_by_handler_and_field_variable(
            $sessionId,
            IcpnaOnlineEnrollmentPlugin::FIELD_SO_SESSION
        );

        if (!is_array($moSessionValue) || empty($moSessionValue['value'])) {
            return;
        }

        SessionManager::unsubscribe_user_from_session($sessionId, $userId);

        online_logout($_SESSION['_user']['user_id']);

        Event::courseLogout([
            'tool' => 'logout',
            'tool_id' => 0,
            'tool_id_detail' => 0,
        ]);
    }
}
