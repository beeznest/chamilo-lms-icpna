<?php

class IcpnaNumberMessagesPlugin extends Plugin
{

    const FIELD_VARIABLE = 'ws_icpna_number_messages';

    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    public function get_name()
    {
        return 'icpna_number_messages';
    }

    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'tab_name' => 'text',
            'web_path' => 'text'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    public function install()
    {
        $setting = $this->get_info();

        $this->saveAdditionalConfiguration($setting);
    }

    public function uninstall()
    {
        $this->deleteAllData();
    }

    public function saveAdditionalConfiguration($params)
    {
        $this->deleteAllData();

        if ($params['tool_enable'] == "true") {
            $tabUrl = api_get_path(WEB_PLUGIN_PATH) . "icpna_number_messages/src/show_page.php?id=0";

            $this->addTab(self::FIELD_VARIABLE, $tabUrl, parent::TAB_FILTER_NO_STUDENT);

            $params['tab_name'] = rtrim($params['tab_name'], ';');

            $tabs = explode(';', $params['tab_name']);

            for ($i = 1; $i < count($tabs); $i++) {
                $tabName = $tabs[$i];
                $tabUrl = api_get_path(WEB_PLUGIN_PATH) . "icpna_number_messages/src/show_page.php?id=$i";

                $this->addTab($tabName, $tabUrl, parent::TAB_FILTER_NO_STUDENT);
            }
        }
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

        var_dump('lastTabNumber', $lastTabNumber);

        for ($i = $lastTabNumber; $i >= 1; $i--) {
            var_dump("custom_tab_$i" . parent::TAB_FILTER_NO_STUDENT);
            $this->deleteTab("custom_tab_$i" . parent::TAB_FILTER_NO_STUDENT);
        }
    }

    public function refreshCount($forced = false)
    {
        if ($forced) {
            $_SESSION['ws_icpna_refresh_count'] = 1;

            $userInfo = api_get_user_info();

            $numberMessages = $this->getNumberMessagesFromWebService($userInfo['username']);

            $this->updateNumberMessages($numberMessages, $userInfo['user_id']);
        } else {
            if ($_SESSION['ws_icpna_refresh_count'] <= 5) {
                $_SESSION['ws_icpna_refresh_count'] ++;
            } else {
                $this->refreshCount(true);
            }
        }
    }

    private function getNumberMessagesFromWebService($username)
    {
        global $_configuration;

        $countMessages = 0;

        if (isset($_configuration['ws_icpna_message_viewer_count'])) {
            $wsUrl = $_configuration['ws_icpna_message_viewer_count'];

            ini_set("soap.wsdl_cache_enabled", "0");

            try {
                $soapClient = new SoapClient(
                        $wsUrl, array(
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'exceptions' => 1)
                );
                $params = array(
                    'vchcodigorrhh' => $username,
                );
                $wsResponse = $soapClient->ObtenerNroMensajes($params);

                if ($wsResponse) {
                    $countMessages = $wsResponse->ObtenerNroMensajesResult;
                }
            } catch (\Exception $e) {
                $countMessages = 0;
            }
        }

        return $countMessages;
    }

    private function updateNumberMessages($numberMessages, $userId)
    {
        $userFieldTable = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $userFieldValuesTable = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

        $fieldConditions = array(
            'where' => array(
                'field_variable = ?' => self::FIELD_VARIABLE
            )
        );

        $fieldResult = Database::select('id', $userFieldTable, $fieldConditions);

        if (empty($fieldResult)) {
            $userFieldAttributes = array(
                'field_type' => 1,
                'field_variable' => self::FIELD_VARIABLE,
                'tms' => date('Y-m-d h:i:s')
            );

            $userFieldId = Database::insert($userFieldTable, $userFieldAttributes);
        } else {
            $fieldResult = current($fieldResult);

            $userFieldId = intval($fieldResult['id']);
        }

        $valuesConditions = array(
            'where' => array(
                'field_id = ? AND ' => $userFieldId,
                'user_id = ?' => $userId
            )
        );

        $valuesResult = Database::select('*', $userFieldValuesTable, $valuesConditions);

        if (empty($valuesResult)) {
            $valuesAttributes = array(
                'user_id' => $userId,
                'field_id' => $userFieldId,
                'field_value' => $numberMessages,
                'tms' => date('Y-m-d h:i:s')
            );

            return Database::insert($userFieldValuesTable, $valuesAttributes);
        } else {
            $valuesAttributes = array(
                'field_value' => $numberMessages,
                'tms' => date('Y-m-d h:i:s')
            );
            $valuesConditions = array(
                'field_id = ? AND ' => $userFieldId,
                'user_id = ?' => $userId
            );

            return Database::update($userFieldValuesTable, $valuesAttributes, $valuesConditions);
        }
    }

    public function getNumberMessagesFromDatabase()
    {
        $userFieldTable = Database::get_main_table(TABLE_MAIN_USER_FIELD);
        $userFieldValuesTable = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

        $fieldConditions = array(
            'where' => array(
                'field_variable = ?' => self::FIELD_VARIABLE
            )
        );

        $fieldResult = Database::select('id', $userFieldTable, $fieldConditions);

        if (!empty($fieldResult)) {
            $fieldResult = current($fieldResult);

            $userFieldId = intval($fieldResult['id']);

            $userInfo = api_get_user_info();

            $valuesConditions = array(
                'where' => array(
                    'field_id = ? AND ' => $userFieldId,
                    'user_id = ?' => $userInfo['user_id']
                )
            );

            $valuesResult = Database::select('field_value', $userFieldValuesTable, $valuesConditions);

            if (!empty($valuesResult)) {
                $valuesResult = current($valuesResult);

                return $valuesResult['field_value'];
            }
        }

        return 0;
    }

}
