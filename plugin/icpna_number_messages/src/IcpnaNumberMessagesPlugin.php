<?php
/* For licensing terms, see /license.txt */

/**
 * IcpnaNumberMessagesPlugin Plugin Class
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com
 * @package chamilo.plugin.icpnaNumberMessagesPlugin
 */
class IcpnaNumberMessagesPlugin extends Plugin
{

    const FIELD_VARIABLE = 'ws_icpna_number_messages';

    /**
     * IcpnaNumberMessagesPlugin constructor.
     */
    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'tab_name' => 'text',
            'web_path' => 'text'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * @return \IcpnaNumberMessagesPlugin|null
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @inheritDoc
     */
    public function get_name()
    {
        return 'icpna_number_messages';
    }

    /**
     * Action when plugin is installed
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function performActionsAfterConfigure()
    {
        $this->deleteAllData();

        if ($this->get('tool_enable') !== 'true') {
            return $this;
        }

        $tabUrl = "plugin/icpna_number_messages/src/show_page.php?id=";

        $this->addTab(
            self::FIELD_VARIABLE,
            $tabUrl.'0',
            parent::TAB_FILTER_NO_STUDENT
        );

        $tabNames = rtrim($this->get('tab_name'), ';');
        $tabs = explode(';', $tabNames);

        for ($i = 1; $i < count($tabs); $i++) {
            $this->addTab(
                $tabs[$i],
                $tabUrl.$i,
                parent::TAB_FILTER_NO_STUDENT
            );
        }

        return $this;
    }

    /**
     * Delete data generador for this plugin into table c_tool
     */
    private function deleteAllData()
    {
        $settings = $this->get_settings();

        $lastTabSubKey = null;

        foreach ($settings as $setting) {
            if (!empty($setting['comment'])) {
                $lastTabSubKey = $setting['comment'];
                break;
            }
        }

        if (empty($lastTabSubKey)) {
            return;
        }

        $lastTabNumber = str_replace('custom_tab_', '', $lastTabSubKey);
        $lastTabNumber = str_replace(parent::TAB_FILTER_NO_STUDENT, '', $lastTabNumber);
        $lastTabNumber = intval($lastTabNumber);

        for ($i = $lastTabNumber; $i >= 1; $i--) {
            $this->deleteTab("custom_tab_$i".parent::TAB_FILTER_NO_STUDENT);
        }
    }

    /**
     * Action when plugin is uninstalled
     */
    public function uninstall()
    {
        $this->deleteAllData();
    }

    /**
     * Refresh the number of messages from webservice or session variable
     * @param bool $forced Option. Force to update number of messages
     */
    public function refreshCount($forced = false)
    {
        if ($forced) {
            $_SESSION['ws_icpna_refresh_count'] = 1;

            $userInfo = api_get_user_info();
            $numberMessages = $this->getNumberMessagesFromWebService($userInfo['username']);

            $this->updateNumberMessages($numberMessages, $userInfo['user_id']);

            return;
        }

        if ($_SESSION['ws_icpna_refresh_count'] <= 5) {
            $_SESSION['ws_icpna_refresh_count']++;

            return;
        }

        $this->refreshCount(true);
    }

    /**
     * Get the number of messages from the webservice
     * @param string $username
     * @return int
     */
    private function getNumberMessagesFromWebService($username)
    {
        $wsUrl = api_get_configuration_value('ws_icpna_message_viewer_count');

        if (false === $wsUrl) {
            return 0;
        }

        $countMessages = 0;

        ini_set("soap.wsdl_cache_enabled", "0");

        try {
            $soapClient = new SoapClient(
                $wsUrl,
                ['cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => 1]
            );
            $wsResponse = $soapClient->ObtenerNroMensajes(['vchcodigorrhh' => $username]);

            if ($wsResponse) {
                $countMessages = $wsResponse->ObtenerNroMensajesResult;
            }
        } catch (\Exception $e) {
            $countMessages = 0;
        }

        return $countMessages;
    }

    /**
     * @param int $numberMessages
     * @param int $userId
     * @return bool|int
     */
    private function updateNumberMessages($numberMessages, $userId)
    {
        $extraField = new ExtraField('user');
        $userFieldId = $extraField->save([
            'variable' => self::FIELD_VARIABLE,
            'field_type' => ExtraField::FIELD_TYPE_TEXT
        ]);

        $extraFieldValue = new ExtraFieldValue('user');

        return $extraFieldValue->save([
            'item_id' => $userId,
            'field_id' => $userFieldId,
            'value' => $numberMessages,
            'comment' => null
        ]);
    }

    /**
     * @return int
     */
    public function getNumberMessagesFromDatabase()
    {
        $user = api_get_user_entity(
            api_get_user_id()
        );

        $extraFieldValue = new ExtraFieldValue('value');
        $values = $extraFieldValue->get_values_by_handler_and_field_variable($user->getId(), self::FIELD_VARIABLE);
        $value = current($values);

        if (!$value) {
            return 0;
        }

        return $value['value'];
    }

}
