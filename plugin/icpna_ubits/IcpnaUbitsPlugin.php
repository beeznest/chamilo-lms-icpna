<?php
/* For licensing terms, see /license.txt */

/**
 * Class IcpnaUbitsPlugin.
 */
class IcpnaUbitsPlugin extends Plugin
{
    /**
     * IcpnaUbitsPlugin constructor.
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'Angel Fernando Quiroz';
        $settings = [
            'enable_tool' => 'boolean',
            'login_url' => 'text',
            'uuid' => 'text',
            'courses' => 'text',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * @return IcpnaUbitsPlugin|null
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'icpna_ubits';
    }

    /**
     * Clean and filter the "courses" setting.
     *
     * @return array
     */
    public function getCourseCodesSetting()
    {
        $courseCodes = explode(';', $this->get('courses'));
        $courseCodes = array_map('trim', $courseCodes);
        $courseCodes = array_filter($courseCodes);

        return $courseCodes;
    }

    /**
     * Save configuration.
     *
     * Delete links to course tools.
     * Enable the plugin in the courses indicated in configuration.
     *
     * @return IcpnaUbitsPlugin
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function performActionsAfterConfigure()
    {
        $this->deleteLinksToCourseTool();

        if ($this->get('enable_tool') !== 'true') {
            return $this;
        }

        $courseCodes = $this->getCourseCodesSetting();

        if (empty($courseCodes)) {
            return $this;
        }

        $em = Database::getManager();

        $result = $em
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c WHERE c.code IN (:codes)')
            ->setParameter('codes', $courseCodes)
            ->getResult();

        foreach ($result as $courseInfo) {
            $tool = $this->createLinkToCourseTool($this->get_lang('UBITS'), $courseInfo['id']);
            $tool->setTarget('_blank');

            $em->persist($tool);
        }

        $em->flush();

        return $this;
    }

    /**
     * Uninstall plugin.
     *
     * Delete links to course tools.
     *
     */
    public function uninstall()
    {
        $this->deleteLinksToCourseTool();
    }

    /**
     * Delete links to course tools added by this plugin.
     */
    private function deleteLinksToCourseTool()
    {
        $toolTable = Database::get_course_table(TABLE_TOOL_LIST);
        $whereCondition = array(
            'link LIKE ?' => $this->get_name().'/start.php%',
        );

        Database::delete($toolTable, $whereCondition);
    }
}
