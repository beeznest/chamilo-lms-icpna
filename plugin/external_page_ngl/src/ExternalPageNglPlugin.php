<?php
/* For licensing terms, see /license.txt */

/**
 * ExternalPageNgl Plugin Class
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com
 * @package chamilo.plugin.externalPageNGL
 */
class ExternalPageNglPlugin extends Plugin
{
    public $isCoursePlugin = true;
    public $addCourseTool = true;

    /**
     * Instance the class
     * @staticvar null $result Object instance
     * @return ExternalPageNglPlugin
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Get the plugin name
     * @return string
     */
    public function get_name()
    {
        return 'external_page_ngl';
    }

    /**
     * Class constructor
     */
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

    /**
     * @inheritDoc
     */
    public function performActionsAfterConfigure()
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);

        Database::delete(
            $toolTable,
            ['link LIKE ?' => '%external_page_ngl_plugin=1%']
        );

        if ($this->get('tool_enable') !== 'true') {
            $this->deleteAllData();

            return $this;
        }

        $toolName = $this->get('button_name');
        $toolImage = $this->get('image_path');

        $em = Database::getManager();

        $sqlResponse = Database::query("
            SELECT t.* FROM $toolTable t
            INNER JOIN (
                SELECT c_id, MAX(id) as id FROM $toolTable GROUP BY c_id
            ) tmp ON tmp.c_id = t.c_id AND tmp.id = t.id
        ");

        while ($row = Database::fetch_assoc($sqlResponse)) {
            $tool = $this->createLinkToCourseTool($toolName, $row['c_id'], $toolImage);
            $tool->setTarget('_blank');

            $em->persist($tool);
        }

        $em->flush();

        return $this;
    }

    /**
     * Get the login user for sign-ing
     * @return string
     */
    public function getLoginUser()
    {
        $userId = api_get_user_id();

        $userExtraFieldValue = new ExtraFieldValue('user');
        $eWorkbookLoginData = $userExtraFieldValue->get_values_by_handler_and_field_variable($userId, 'eworkbooklogin');

        $hasEWorkbookLogin = ($eWorkbookLoginData != false);

        if ($hasEWorkbookLogin) {
            $fieldValue = trim($eWorkbookLoginData['field_value']);

            if (empty($fieldValue)) {
                return $this->generateLoginUser($userId);
            }

            return $fieldValue;
        }

        return $this->generateLoginUser($userId);
    }

    /**
     * Generate a login username for an user
     * @param int $userId The user id
     * @return string The login username
     */
    public function generateLoginUser($userId)
    {
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $userData = Database::select(
            'username',
            $userTable,
            array(
                'where' => array('user_id = ?' => $userId,),
                'order' => 'user_id'
            ),
            'first'
        );

        return 'ICPNA_' . $userData['username'];
    }

    /**
     * Get the login password for sign-ing
     * @return string
     */
    public function getLoginPassword()
    {
        $userId = api_get_user_id();
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $userData = Database::select(
            'username',
            $userTable,
            array(
                'where' => array('user_id = ?' => $userId),
                'order' => 'user_id'
            ),
            'first'
        );

        $loginReversedUsername = strrev($userData['username'] . "ICPNA");

        $encrypted = sha1($loginReversedUsername);

        $newPassword = substr($encrypted, 13, 14);

        return $newPassword;
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->generateLoginExtraField();
    }

    /**
     * Unistall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->deleteAllData();
        $this->removeLoginExtraField();
    }

    /**
     * Generate a user extra field for register the login username when the plugin was installed
     * @return void
     */
    private function generateLoginExtraField()
    {
        $extraField = new ExtraField('user');
        $data = $extraField->get_handler_field_info_by_field_variable('eworkbooklogin');

        if ($data == false) {
            UserManager::create_extra_field('eworkbooklogin', ExtraField::FIELD_TYPE_TEXT, 'E-Workbook Login', '');
        }
    }

    /**
     * Delete the user extra field when the plugin was uninstalled
     * @return void
     */
    private function removeLoginExtraField()
    {
        $extraField = new ExtraField('user');
        $data = $extraField->get_handler_field_info_by_field_variable('eworkbooklogin');

        $extraField->delete($data['id']);
    }

    /**
     * Delete all registered data to config the plugin
     * @return void
     */
    private function deleteAllData()
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
            'link = ?' => 'external_page_ngl/start.php'
        );

        Database::delete($toolTable, $whereCondition);
    }
}
