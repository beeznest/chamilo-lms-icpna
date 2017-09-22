<?php
/* For licensing terms, see /license.txt */

class IcpnaUpdateUserPluginHook extends HookObserver implements HookUpdateUserObserverInterface
{

    public static $plugin;

    /**
     * Constructor. Calls parent, mainly.
     */
    protected function __construct()
    {
        self::$plugin = IcpnaUpdateUserPlugin::create();
        parent::__construct(
            'plugin/icpna_update_user/IcpnaUpdateUserPluginHook.php',
            'icpna_update_user'
        );
    }

    /**
     * @param \HookUpdateUserEventInterface $hook
     * @return array
     */
    public function hookUpdateUser(HookUpdateUserEventInterface $hook)
    {
        $data = $hook->getEventData();
        error_log(print_r($data['type'], true));

        if ($data['type'] !== HOOK_EVENT_TYPE_POST) {
            return $data;
        }

        return $data;
    }
}