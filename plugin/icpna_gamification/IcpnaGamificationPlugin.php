<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;

/**
 * Class IcpnaGamificationPlugin.
 */
class IcpnaGamificationPlugin extends Plugin
{
    const SETTING_ACTIVE = 'active';
    const SETTING_WS_URL = 'ws_url';

    /**
     * @var bool
     */
    public $addCourseTool = true;

    /**
     * @var array
     */
    private $patternFilters = [
        'ADU' => [
            'B(0[1-9]|1[0-2])',
            'I(0[1-4])',
            'AB(0[2-9]|1[0-2])D',
            'AI(0[2-4])D',
            'B(0[1-9]|1[0-2])CR',
            'B(0[1-9]|1[0-2])AM',
            'B(0[1-9]|1[0-2])AT',
            'B(0[1-9]|1[0-2])MW',
            'I(0[1-4])MW',
            'B(0[1-9]|1[0-2])TT',
            'I(0[1-4])TT',
            'B(0[1-9]|1[0-2])SA',
            'I(0[1-4])SA',
            'B(0[1-9]|1[0-2])SI',
            'I(0[1-4])SI',
        ],
        'NIN' => [
            'JR(0[2-4])D',
            'JR(0[1-8])S',
            'PT(0[1-6])S',
            'T(0[1-6])S',
            'JR([1-8])AD',
            'PT([1-6])AD',
            'T([1-6])AD',
        ],
    ];

    /**
     * @var bool
     */
    private $isCourseValid = false;

    /**
     * @var float
     */
    private $attendancePercent;

    /**
     * @var float
     */
    private $activitiesPercent;

    /**
     * @var string
     */
    private $studentType;

    /**
     * IcpnaGamificationPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
            self::SETTING_ACTIVE => 'boolean',
            self::SETTING_WS_URL => 'text',
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $settings);
    }

    /**
     * @return IcpnaGamificationPlugin|null
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
        return 'icpna_gamification';
    }

    /**
     * Process for uninstall plugin
     */
    public function uninstall()
    {
        $this->removeCourseTools();
    }

    /**
     * @return IcpnaGamificationPlugin
     */
    public function performActionsAfterConfigure()
    {
        $isActive = $this->get('active') === 'true';

        $this->removeCourseTools();

        if ($isActive) {
            $this->addCourseTools();
        }

        return $this;
    }

    private function removeCourseTools()
    {
        Database::getManager()
            ->createQuery('
                DELETE FROM ChamiloCourseBundle:CTool t
                WHERE t.category = :category
                    AND t.link LIKE :link
            ')
            ->execute([
                'category' => 'plugin',
                'link' => $this->get_name().'/start.php',
            ]);
    }

    private function addCourseTools()
    {
        $em = Database::getManager();

        $name = $this->get_lang('LevelUp');

        $courses = $em->createQuery('SELECT c.code, c.id FROM ChamiloCoreBundle:Course c ORDER BY c.id ASC')
            ->getArrayResult();

        $allPatternFilters = array_merge($this->patternFilters['ADU'], $this->patternFilters['NIN']);

        foreach ($courses as $course) {
            foreach ($allPatternFilters AS $patternFilter) {
                $addTool = api_preg_match("/^$patternFilter$/", $course['code']);

                if (!$addTool) {
                    continue;
                }

                $this->createLinkToCourseTool($name, $course['id']);
            }
        }
    }

    /**
     * @return bool
     */
    public function isCourseValid()
    {
        return $this->isCourseValid;
    }

    /**
     * @return string
     */
    public function getStudentType()
    {
        return $this->studentType;
    }

    /**
     * @param int    $sessionId
     * @param string $username
     */
    public function getGamificationData($sessionId, $username)
    {
        $efv = new ExtraFieldValue('session');
        $programa = $efv->get_values_by_handler_and_field_variable($sessionId, 'uididprograma');
        $sede = $efv->get_values_by_handler_and_field_variable($sessionId, 'sede');
        $wsUrl = $this->get(self::SETTING_WS_URL);

        $client = new SoapClient($wsUrl);
        $clientResult = $client
            ->obtieneDatosGam([$username, $programa['value'], $sede['value']])
            ->obtieneDatosGamResult
            ->any;

        $clientResult = strstr($clientResult, '<diffgr:diffgram');

        $xml = new SimpleXMLElement($clientResult);
        $datosgam = $xml->DocumentElement->datosgam;

        $this->isCourseValid = (boolean) $datosgam->cursogam;
        $this->attendancePercent = (float) $datosgam->asistencia;
        $this->activitiesPercent = (float) $datosgam->ejercicios;
        $this->studentType = (string) $datosgam->tipoalumno;
    }

    /**
     * @return string
     */
    public function getAttendanceImage()
    {
        $path = api_get_path(WEB_PLUGIN_PATH).$this->get_name()."/resources/{$this->studentType}/att/";

        if ($this->attendancePercent >= 10 && $this->attendancePercent < 20) {
            return $path.'1019.png';
        }

        if ($this->attendancePercent == 20) {
            return $path.'20.png';
        }

        if ($this->attendancePercent > 20 && $this->attendancePercent < 30) {
            return $path.'2129.png';
        }

        if ($this->attendancePercent >= 30 && $this->attendancePercent < 40) {
            return $path.'3039.png';
        }

        if ($this->attendancePercent == 40) {
            return $path.'40.png';
        }

        if ($this->attendancePercent > 40 && $this->attendancePercent < 50) {
            return $path.'4149.png';
        }

        if ($this->attendancePercent >= 50 && $this->attendancePercent < 60) {
            return $path.'5059.png';
        }

        if ($this->attendancePercent == 60) {
            return $path.'60.png';
        }

        if ($this->attendancePercent > 60 && $this->attendancePercent < 70) {
            return $path.'6169.png';
        }

        if ($this->attendancePercent >= 70 && $this->attendancePercent < 80) {
            return $path.'7079.png';
        }

        if ($this->attendancePercent == 80) {
            return $path.'80.png';
        }

        if ($this->attendancePercent > 80 && $this->attendancePercent < 90) {
            return $path.'8189.png';
        }

        if ($this->attendancePercent >= 90 && $this->attendancePercent < 100) {
            return $path.'9099.png';
        }

        if ($this->attendancePercent == 100) {
            return $path.'100.png';
        }

        return $path.'09.png';
    }

    /**
     * @return string
     */
    public function getAttendanceText()
    {
        if ($this->attendancePercent > 80 && $this->attendancePercent < 100) {
            return $this->get_lang('AttendanceText8199');
        }

        if ($this->attendancePercent === 100) {
            return $this->get_lang('AttendanceText100');
        }

        return $this->get_lang('AttendanceText080');
    }

    /**
     * @return string
     */
    public function getActivitiesImage()
    {
        $path = api_get_path(WEB_PLUGIN_PATH).$this->get_name()."/resources/{$this->studentType}/act/";

        if ($this->activitiesPercent >= 10 && $this->activitiesPercent < 20) {
            return $path.'1019.png';
        }

        if ($this->activitiesPercent == 20) {
            return $path.'20.png';
        }

        if ($this->activitiesPercent > 20 && $this->activitiesPercent < 30) {
            return $path.'2129.png';
        }

        if ($this->activitiesPercent >= 30 && $this->activitiesPercent < 40) {
            return $path.'3039.png';
        }

        if ($this->activitiesPercent == 40) {
            return $path.'40.png';
        }

        if ($this->activitiesPercent > 40 && $this->activitiesPercent < 50) {
            return $path.'4149.png';
        }

        if ($this->activitiesPercent >= 50 && $this->activitiesPercent < 60) {
            return $path.'5059.png';
        }

        if ($this->activitiesPercent == 60) {
            return $path.'60.png';
        }

        if ($this->activitiesPercent > 60 && $this->activitiesPercent < 70) {
            return $path.'6169.png';
        }

        if ($this->activitiesPercent >= 70 && $this->activitiesPercent < 80) {
            return $path.'7079.png';
        }

        if ($this->activitiesPercent == 80) {
            return $path.'80.png';
        }

        if ($this->activitiesPercent > 80 && $this->activitiesPercent < 90) {
            return $path.'8189.png';
        }

        if ($this->activitiesPercent >= 90 && $this->activitiesPercent < 100) {
            return $path.'9099.png';
        }

        if ($this->activitiesPercent == 100) {
            return $path.'100.png';
        }

        return $path.'09.png';
    }

    /**
     * @return string
     */
    public function getActivitiesText()
    {
        if ($this->activitiesPercent > 60 && $this->activitiesPercent <= 80) {
            return $this->get_lang('ActivitiesText6180');
        }

        if ($this->activitiesPercent > 80 && $this->activitiesPercent <= 100) {
            return $this->get_lang('ActivitiesText81100');
        }

        return $this->get_lang('ActivitiesText060');
    }
}