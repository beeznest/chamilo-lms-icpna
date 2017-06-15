<?php
/* For licensing terms, see /license.txt */

/**
 * Class AddExternalPagesPlugin
 */
class AddExternalPagesPlugin extends Plugin
{
    /**
     * @return \AddExternalPagesPlugin|null
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'add_external_pages';
    }

    /**
     * AddExternalPagesPlugin constructor.
     */
    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'button_name' => 'text',
            'web_path' => 'text',
            'image_path' =>'text',
            'sso_enable' => 'boolean',
        );

        parent::__construct('1.0', 'Francis Gonzales, Anibal Copitan', $parameters);
    }

    /**
     * Actions to install plugin
     */
    public function install() {
        // comment this lines for create one icon in menu principal
        //$this->install_course_fields_in_all_courses($setting['is_course_plugin']);
    }

    /**
     * Actions to uninstall plugin
     */
    public function uninstall() {
        $this->deleteAllData();
    }

    /**
     * @inheritDoc
     */
    public function performActionsAfterConfigure()
    {
        $this->deleteAllData();

        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);

        if ($this->get('tool_enable') !== 'true') {
            return $this;
        }

        $imagesPath = explode(';', $this->get('image_path'));
        $buttons = explode(';', $this->get('button_name'));
        $countButtons = count($buttons);
        $toolTmp = array();

        for ($i = 0; $i < $countButtons; $i++) {
            $toolTmp[] = array(
                'name' => $buttons[$i],
                'link' => $this->get_name()."/start.php?id=".$i,
                'image' => $imagesPath[$i]
            );
        }
        // verifica que existan registros en esta tabla para agregar los parametros del plugin.
        $sqlResponse = Database::query("
            SELECT t.* FROM $toolTable t
            INNER JOIN (
                SELECT c_id, MAX(id) as id
                FROM $toolTable
                GROUP BY c_id
            ) tmp ON tmp.c_id = t.c_id AND tmp.id = t.id
        ");
        while ($row = Database::fetch_assoc($sqlResponse)) {
            foreach ($toolTmp as $tool) {
                $this->createLinkToCourseTool($tool['name'], $row['c_id'], $tool['image'], $tool['link']);
            }
        }

        return $this;
    }

    /*
     * Delete data generador for this plugin into table c_tool
     */
    private function deleteAllData() {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
            'link LIKE ?' => '%'.$this->get_name().'/start.php?id=%'
        );
        Database::delete($toolTable, $whereCondition);
    }
}
