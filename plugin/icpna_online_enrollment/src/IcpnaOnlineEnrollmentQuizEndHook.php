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

        SessionManager::unsubscribe_user_from_session($sessionId, $userId);
    }
}
