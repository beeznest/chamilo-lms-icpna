<?php
/* For licensing terms, see /license.txt */

/**
 * Class IcpnaSsoPlugin
 */
class IcpnaSsoPlugin extends Plugin
{
    public $isCoursePlugin = true;
    public $addCourseTool = true;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
            'button_name' => 'text',
            'login_process' => 'text',
            'image_path' => 'text',
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the class
     * @staticvar null $result Object instance
     * @return IcpnaSsoPlugin
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
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

        $settings = $this->getSettings();
        $toolNames = $settings['names'];
        $toolIcons = $settings['icons'];
        $toolUrls = $settings['urls'];

        $em = Database::getManager();

        $courses = $em->getRepository('ChamiloCoreBundle:Course')->findAll();

        foreach ($toolNames as $i => $toolName) {
            if (empty($toolIcons[$i]) || empty($toolUrls[$i])) {
                continue;
            }

            /** @var \Chamilo\CoreBundle\Entity\Course $course */
            foreach ($courses as $course) {
                $tool = $this->createLinkToCourseTool(
                    $toolName,
                    $course->getId(),
                    $toolIcons[$i],
                    $this->get_name()."/start.php?id=".($i + 1)
                );
                $tool->setTarget('_blank');

                $em->persist($tool);
            }
        }

        $em->flush();

        return $this;
    }

    /**
     * Delete all registered data to config the plugin
     * @return void
     */
    private function deleteAllData()
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
            'link LIKE ?' => $this->get_name().'/start.php%',
        );

        Database::delete($toolTable, $whereCondition);
    }

    /**
     * Get the plugin name
     * @return string
     */
    public function get_name()
    {
        return 'icpna_sso';
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        $toolNames = explode(';', $this->get('button_name'));
        $toolIcons = explode(';', $this->get('image_path'));
        $toolUrls = explode(';', $this->get('login_process'));

        $toolNames = array_map('trim', $toolNames);
        $toolIcons = array_map('trim', $toolIcons);
        $toolUrls = array_map('trim', $toolUrls);

        return [
            'names' => array_filter($toolNames),
            'icons' => array_filter($toolIcons),
            'urls' => array_filter($toolUrls),
        ];
    }

    /**
     * Get the login user for sign-in.
     *
     * @return string
     */
    public function getLoginUser()
    {
        $userId = api_get_user_id();
        /** @var \Chamilo\UserBundle\Entity\User $user */
        $user = api_get_user_entity($userId);

        return "ICPNA_".$user->getUsername();
    }

    /**
     * Get the login password for sign-in.
     *
     * @return string
     */
    public function getLoginPassword()
    {
        $userId = api_get_user_id();
        /** @var \Chamilo\UserBundle\Entity\User $user */
        $user = api_get_user_entity($userId);

        $loginReversedUsername = strrev($user->getUsername().'ICPNA');

        $encrypted = sha1($loginReversedUsername);

        $newPassword = substr($encrypted, 13, 14);

        return $newPassword;
    }

    /**
     * Install the plugin.
     *
     * @return void
     */
    public function install()
    {
    }

    /**
     * Unistall the plugin.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->deleteAllData();
    }
}
