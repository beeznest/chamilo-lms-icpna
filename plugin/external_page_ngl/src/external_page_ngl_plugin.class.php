<?php

class ExternalPageNGLPlugin extends Plugin {

    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function get_name()
    {
        return 'external_page_ngl';
    }

    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'button_name' => 'text',
            'login_process' => 'text',
            'image_path' => 'text'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    public function saveAdditionalConfiguration($params)
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
            'link like ?' => '%external_page_ngl_plugin=1%'
        );

        Database::delete($toolTable, $whereCondition);

        if ($params['tool_enable'] == "true") {
            $tool = array(
                'name' => $params['button_name'],
                'link' => 'plugin/plugin.php?external_page_ngl_plugin=1',
                'image' => $params['image_path']
            );

            $sql = "SELECT t.* FROM $toolTable t
                INNER JOIN (
                    SELECT c_id, MAX(id) as id
                    FROM $toolTable
                    GROUP BY c_id
                ) tmp ON tmp.c_id = t.c_id AND tmp.id = t.id";

            $sqlResponse = Database::query($sql);

            while ($row = Database::fetch_assoc($sqlResponse)) {
                $attributes = array(
                    'c_id' => $row['c_id'],
                    'name' => $tool['name'],
                    'link' => $tool['link'],
                    'image' => $tool['image'],
                    'visibility' => 2,
                    'admin' => 1,
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
