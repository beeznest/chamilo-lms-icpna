<?php
/* For licensing terms, see /license.txt */

/**
 * IcpnaTabZonePlugin Class
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com
 * @package chamilo.plugin.icpnaNumberMessagesPlugin
 */
class IcpnaTabZonePlugin extends Plugin
{
    /**
     * IcpnaTabZonePlugin constructor.
     */
    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'student_zone_url' => 'text',
            'teacher_zone_url' => 'text'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

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
        return 'icpna_tab_zone';
    }

    /**
     * Install plugin
     */
    public function install()
    {
        $setting = $this->get_info();

        $this->saveAdditionalConfiguration($setting);
    }

    /**
     * @param array $params Plugin params
     * @return mixed|void
     */
    public function saveAdditionalConfiguration($params)
    {
        $this->deleteAllData();

        if ($params['tool_enable'] != "true") {
            return;
        }

        $tabUrl = api_get_path(WEB_PLUGIN_PATH) . "icpna_tab_zone/src/zone.php";

        $this->addTab($this->get_lang('TeachersZone'), $tabUrl, parent::TAB_FILTER_NO_STUDENT);
        $this->addTab($this->get_lang('StudentsZone'), $tabUrl, parent::TAB_FILTER_ONLY_STUDENT);
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
        $lastTabNumber = intval($lastTabNumber);

        $this->deleteTab("custom_tab_$lastTabNumber");

        $lastTabNumber--;

        $this->deleteTab("custom_tab_$lastTabNumber");
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        $this->deleteAllData();
    }
}
