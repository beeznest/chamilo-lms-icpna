<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\CourseHomeNotify\Popup;
use Chamilo\PluginBundle\Entity\CourseHomeNotify\PopupRelUser;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PopupsPlugin.
 */
class PopupsPlugin extends Plugin
{
    const SETTING_ENABLED = 'enabled';

    const SHOWN_IN_USERPORTAL = '/user_portal.php';

    /**
     * PopupsPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
            self::SETTING_ENABLED => 'boolean',
        ];

        parent::__construct('0.1', 'Angel Fernando Quiroz Campos', $settings);

        $this->addCourseTool = false;
        $this->isAdminPlugin = true;
    }

    /**
     * @return PopupsPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Installation process.
     *
     * Create table in database. And setup Doctirne entity.
     */
    public function install()
    {
        $pluginEntityPath = $this->getEntityPath();

        if (!is_dir($pluginEntityPath)) {
            if (!is_writable(dirname($pluginEntityPath))) {
                $message = get_lang('ErrorCreatingDir').': '.$pluginEntityPath;
                Display::addFlash(Display::return_message($message, 'error'));

                return;
            }

            mkdir($pluginEntityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $pluginEntityPath, null, ['override']);

        Database::query(
            "CREATE TABLE IF NOT EXISTS popups_popup (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                visible_for LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                shown_in VARCHAR(255) NOT NULL,
                visible TINYINT(1) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );
    }

    /**
     * @return string
     */
    public function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    /**
     * Uninstallation process.
     *
     * Remove Doctrine entity. And drop table in database.
     */
    public function uninstall()
    {
        $pluginEntityPath = $this->getEntityPath();

        $fs = new Filesystem();

        if ($fs->exists($pluginEntityPath)) {
            $fs->remove($pluginEntityPath);
        }

        Database::query("DROP TABLE IF EXISTS popups_popup");
    }
}
