<?php
/**
 * Created by PhpStorm.
 * User: fgonzales
 * Date: 12/06/14
 * Time: 11:25 AM
 */

class AddExternalPagesPlugin extends Plugin
{
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    public function get_name()
    {
        return 'add_external_pages';
    }

    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'button_name' => 'text',
            'web_path' => 'text',
            'image_path' =>'text',
            'sso_enable' => 'boolean',
        );

        parent::__construct('1.0', 'Francis Gonzales', $parameters);
    }

    /**
     * funcion poco claro cuando se usa.
     */
    function install() {
/*
        //Installing course settings
        $setting = $this->get_info();
        //
        // Esta funcion no pasa por crear iconos para home principal:
        // $isCoursePlugin = isset($setting['is_course_plugin']) ? $setting['is_course_plugin'] : '';
        // $this->install_course_fields_in_all_courses($isCoursePlugin);
        //

        $isCoursePlugin = isset($setting['is_course_plugin']) ? $setting['is_course_plugin'] : '';
        $this->install_course_fields_in_all_courses($isCoursePlugin);
        $this->saveAdditionalConfiguration($setting);
*/

    }


    /**
     * Funcion recorre todo tool list y agrega icono.
     * @param array $params config
     * @return mixed|void
     */
    public function saveAdditionalConfiguration($params)
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
                'link like ?' => '%add_page_plugin=1%'
        );
        Database::delete($toolTable, $whereCondition);
        if ($params['tool_enable'] == "true") {
            $imagesPath = explode(';', $params['image_path']);
            $buttons = explode(';', $params['button_name']);
            $countButtons = count($buttons);
            $toolTmp = array();
            for ($i = 0; $i < $countButtons; $i++) {
                $toolTmp[] = array(
                    'name' => $buttons[$i],
                    'link' => 'plugin/plugin.php?id=' . $i . '&add_page_plugin=1',
                    'image' => $imagesPath[$i]
                );
            }
            // verifica que existan registros en esta tabla para agregar los parametros del plugin.
            $sql = "SELECT t.* FROM $toolTable t
                INNER JOIN (
                    SELECT c_id, MAX(id) as id
                    FROM $toolTable
                    GROUP BY c_id
                ) tmp ON tmp.c_id = t.c_id AND tmp.id = t.id";

            $sqlResponse = Database::query($sql);
            while ($row = Database::fetch_assoc($sqlResponse)) {
                foreach ($toolTmp as $tool) {
                    $attributes = array(
                        'c_id' => $row['c_id'],
                        'name' => $tool['name'],
                        'link' => $tool['link'],
                        'image' => $tool['image'],
                        'visibility' => 1,
                        'admin' => 0,
                        'address' => '',
                        'added_tool' => 0,
                        'category' => 'interaction',
                        'session_id' => $row['session_id']
                    );
                    Database::insert($toolTable, $attributes);
                }
            }
        }
    }
}