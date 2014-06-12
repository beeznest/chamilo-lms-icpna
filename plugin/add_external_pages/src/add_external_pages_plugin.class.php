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
            'image_path' =>'text',
            'button_name' => 'text'
        );

        parent::__construct('1.0', 'Francis Gonzales', $parameters);
    }

    public function saveAdditionalConfiguration($params)
    {
        //$session = SessionManager::get_sessions_list();
        var_dump($params);exit;
        if ($params['tool_enable'] == "true") {

        } else {

        }
    }
}