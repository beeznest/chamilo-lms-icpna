<?php
/* For licensing terms, see /license.txt */

class IcpnaUpdateUserPlugin extends Plugin
{
    /**
     * IcpnaUpdateUserPlugin constructor.
     */
    protected function __construct()
    {
        $options = [
            'enable_hook' => 'boolean'
        ];

        parent::__construct('1.1', 'Angel Fernando Quiroz Campos', $options);
    }

    /**
     * @return \IcpnaUpdateUserPlugin|null
     */
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * Get plugin name
     * @return string
     */
    public function get_name()
    {
        return 'icpna_update_user';
    }

    /**
     * @inheritDoc
     */
    public function performActionsAfterConfigure()
    {
        if ($this->get('enable_hook') !== 'true') {
            $this->uninstallHook();

            return $this;
        }

        $this->installHook();

        return $this;
    }

    /**
     * Install hook for update user
     */
    public function installHook()
    {
        HookUpdateUser::create()->attach(
            IcpnaUpdateUserPluginHook::create()
        );
    }

    /**
     * Uninstall hook for update user
     */
    public function uninstallHook()
    {
        HookUpdateUser::create()->detach(
            IcpnaUpdateUserPluginHook::create()
        );
    }
}