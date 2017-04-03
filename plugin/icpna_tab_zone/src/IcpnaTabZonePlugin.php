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
            'enable_student_zone' => 'boolean',
            'student_zone_url' => 'text',
            'enable_teacher_zone' => 'boolean',
            'teacher_zone_url' => 'text'
        );

        parent::__construct('1.1', 'Angel Fernando Quiroz Campos', $parameters);
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
        if ($params['tool_enable'] != "true") {
            return;
        }

        $this->deleteTabs();

        $tabUrl = api_get_path(WEB_PLUGIN_PATH) . "icpna_tab_zone/src/zone.php";

        if ($params['enable_student_zone'] === 'true') {
            $this->addTab($this->get_lang('StudentsZone'), $tabUrl, parent::TAB_FILTER_ONLY_STUDENT);
        }

        if ($params['enable_teacher_zone'] === 'true') {
            $this->addTab($this->get_lang('TeachersZone'), $tabUrl, parent::TAB_FILTER_NO_STUDENT);
        }
    }

    /**
     * Delete the custom tabs created by this plugin
     */
    private function deleteTabs()
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

        $lastTabNumber = str_replace(
            ['custom_tab_', parent::TAB_FILTER_ONLY_STUDENT, parent::TAB_FILTER_NO_STUDENT],
            '',
            $lastTabSubKey
        );
        $lastTabNumber = intval($lastTabNumber);

        $this->deleteTab("custom_tab_$lastTabNumber");

        --$lastTabNumber;

        $this->deleteTab("custom_tab_$lastTabNumber");
    }

    /**
     * Delete data generador for this plugin
     */
    private function deleteAllData()
    {
        $this->deleteTabs();
    }

    /**
     * Uninstall plugin
     */
    public function uninstall()
    {
        $this->deleteAllData();
    }
}
