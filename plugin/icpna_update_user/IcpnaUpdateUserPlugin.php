<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

class IcpnaUpdateUserPlugin extends Plugin
{
    const SETTING_ENABLE = 'enable_hook';
    const SETTING_WEB_SERVICE = 'web_service';

    /**
     * IcpnaUpdateUserPlugin constructor.
     */
    protected function __construct()
    {
        $options = [
            self::SETTING_ENABLE => 'boolean',
            self::SETTING_WEB_SERVICE => 'text'
        ];

        parent::__construct('1.1', 'Angel Fernando Quiroz Campos', $options);
    }

    /**
     * @return \IcpnaUpdateUserPlugin|null
     */
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
        return 'icpna_update_user';
    }

    /**
     * @inheritDoc
     */
    public function performActionsAfterConfigure()
    {
        if ($this->get(self::SETTING_ENABLE) !== 'true') {
            $this->uninstallHook();

            return $this;
        }

        $this->installHook();

        return $this;
    }

    /**
     * Install hook for update user
     */
    private function installHook()
    {
        HookUpdateUser::create()->attach(
            IcpnaUpdateUserPluginHook::create()
        );
    }

    /**
     * Uninstall hook for update user
     */
    private function uninstallHook()
    {
        HookUpdateUser::create()->detach(
            IcpnaUpdateUserPluginHook::create()
        );
    }

    /**
     * @param string $functionName
     * @param array $params
     * @return array
     */
    private function getTableResult($functionName, $params = [])
    {
        $wsUrl = $this->get(self::SETTING_WEB_SERVICE);

        if (empty($wsUrl)) {
            return [];
        }

        $resultName = $functionName.'Result';

        try {
            $client = new SoapClient($wsUrl);
            $result = $client
                ->$functionName($params)
                ->$resultName
                ->any;

            $xml = strstr($result, '<diffgr:diffgram');

            $xmlResult = new SimpleXMLElement($xml);

            if (!isset($xmlResult->NewDataSet)) {
                return [];
            }

            return $xmlResult->NewDataSet->Table;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Call to tipodocumento function
     * @return array
     */
    public function getTipodocumento()
    {
        $return = [];
        $tableResult = $this->getTableResult('tipodocumento');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIdDocumentoIdentidad,
                'text' => (string) $item->vchNombreDocumento
            ];
        }

        return $return;
    }

    /**
     * Call to nacionalidad function
     * @return array
     */
    public function getNacionalidad()
    {
        $return = [];
        $tableResult = $this->getTableResult('nacionalidad');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uididpais,
                'text' => (string) $item->vchnombrepais
            ];
        }

        return $return;
    }

    /**
     * Call to departamento function
     * @return array
     */
    public function getDepartamento()
    {
        $return = [];
        $tableResult = $this->getTableResult('departamento');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIdDepartamento,
                'text' => (string) $item->vchNombreDepartamento
            ];
        }

        return $return;
    }

    /**
     * Call to provincia function
     * @param string $uididDepartamento
     * @return array
     */
    public function getProvincia($uididDepartamento)
    {
        $return = [];
        $tableResult = $this->getTableResult('provincia', ['uididdepartamento' => $uididDepartamento]);

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIdprovincia,
                'text' => (string) $item->vchNombreprovincia
            ];
        }

        return $return;
    }

    /**
     * Call to distrito function
     * @param string $uididProvincia
     * @return array
     */
    public function getDistrito($uididProvincia)
    {
        $return = [];
        $tableResult = $this->getTableResult('distrito', ['uididprovincia' => $uididProvincia]);

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIddistrito,
                'text' => (string) $item->vchNombredistrito
            ];
        }

        return $return;
    }

    /**
     * Call to ocupacion function
     * @return array
     */
    public function getOcupacion()
    {
        $return = [];
        $tableResult = $this->getTableResult('ocupacion');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uididocupacion,
                'text' => (string) $item->vchDescripcionOcupacion,
                'data-type' => (string) $item->chrTipoOcupacion
            ];
        }

        return $return;
    }

    /**
     * Call to centroestudios function
     * @param string $type
     * @param string $district
     * @return array
     */
    public function getCentroestudios($type, $district)
    {
        $return = [];
        $tableResult = $this->getTableResult(
            'centroestudios',
            ['chrTipoOcupacion' => $type, 'uididdistritocentroestudios' => $district]
        );

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIdCentroEstudios,
                'text' => (string) $item->vchDescripcionCentroEstudios
            ];
        }

        return $return;
    }

    /**
     * Call to centroestudios function
     * @return array
     */
    public function getCarrerauniversitaria()
    {
        $return = [];
        $tableResult = $this->getTableResult('carrerauniversitaria');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uididcarrerauniversitaria,
                'text' => (string) $item->vchcarrerauniversitaria
            ];
        }

        return $return;
    }

    /**
     * Convert SOAP result values to string
     * @param \SimpleXMLElement $objResult
     * @return array
     */
    private static function stringifyResult(SimpleXMLElement $objResult)
    {
        $toJson = json_encode($objResult);
        $toArray = json_decode($toJson, true);

        foreach ($toArray as &$item) {
            if (is_array($item) && empty($item)) {
                $item = '';
            }

            if (!is_array($item)) {
                $item = trim($item);
            }
        }

        return $toArray;
    }

    /**
     * Call to obtienedatospersonales function
     * @param $uididPersona
     * @return array
     */
    public function getUserInfo($uididPersona)
    {
        $tableResult = $this->getTableResult('obtienedatospersonales', ['uididpersona' => $uididPersona]);
        $tableResult = self::stringifyResult($tableResult);

        $birthdate = $tableResult['sdtFechaNacimiento'];
        $birthdate = explode('T', $birthdate);

        $return = [
            'extra_id_document_type' => $tableResult['uidIdDocumentoIdentidad'],
            'extra_id_document_number' => $tableResult['vchDocumentoNumero'],
            'firstname' => $tableResult['vchPrimerNombre'],
            'extra_middle_name' => $tableResult['vchSegundoNombre'],
            'lastname' => $tableResult['vchPaterno'],
            'extra_mothers_name' => $tableResult['vchMaterno'],
            'extra_sex' => $tableResult['chrSexo'],
            'extra_birthdate' => $birthdate[0],
            'extra_nationality' => $tableResult['uididpaisorigen'],
            'extra_address_department' => $tableResult['uidIdDepartamento'],
            'extra_address_province' => $tableResult['uidIdProvincia'],
            'extra_address_district' => $tableResult['uidIdDistrito'],
            'extra_urbanization' => $tableResult['vchNombreUrbanizacion'],
            'extra_type_of_road' => $tableResult['uidIdTipoVia'],
            'extra_address' => $tableResult['vchDireccionPersona'],
            'extra_door_number' => $tableResult['chrNroPuerta'],
            'extra_indoor_number' => $tableResult['chrNroInterior'],
            'email' => $tableResult['vchEmailPersona'],
            'phone' => $tableResult['vchTelefonoPersona'],
            'extra_mobile_phone_number' => $tableResult['vchcelularPersona'],
            'extra_occupation' => $tableResult['uidIdOcupacion'],
            'extra_occupation_department' => $tableResult['uididdepartamentocentroestudios'],
            'extra_occupation_province' => $tableResult['uididprovinciacentroestudios'],
            'extra_occupation_district' => $tableResult['uididdistritocentroestudios'],
            'extra_occupation_center_name_1' => '4378e853-269b-4040-9e9d-a175c8e62bf5' === $tableResult['uidIdOcupacion']
                ? $tableResult['uidIdCentroEstudios']
                : '',
            'extra_occupation_center_name_2' => '051052f7-71c0-4721-8983-9c112bef88d7' === $tableResult['uidIdOcupacion']
                ? $tableResult['uidIdCentroEstudios']
                : '',
            'extra_occupation_center_name_3' => '84478ce8-2971-43b1-86b8-93405fb0453b' === $tableResult['uidIdOcupacion']
                ? $tableResult['uidIdCentroEstudios']
                : '',
            'extra_occupation_center_name_4' => '4c0762b7-bc8c-4ff2-b145-6bd8e96f0f47' === $tableResult['uidIdOcupacion']
                ? $tableResult['vchcentrolaboral']
                : '',
            'extra_university_career' => isset($tableResult['uididcarrerauniversitaria'])
                ? $tableResult['uididcarrerauniversitaria']
                : '',
        ];

        $return['extra_guardian_name'] = $tableResult['strNombrePadre'];
        $return['extra_guardian_id_document_type'] = $tableResult['uidIdDocumentoIdentidadPadre'];
        $return['extra_guardian_email'] = filter_var($tableResult['vchEmailApoderado'], FILTER_VALIDATE_EMAIL)
            ? $tableResult['vchEmailApoderado']
            : '';
        $return['extra_guardian_id_document_number'] = $tableResult['vchDocumentoNumeroPadre'];

        if ($tableResult['vchDocumentoNumero'] == $tableResult['vchDocumentoNumeroPadre']) {
            $return['extra_guardian_id_document_number'] = '';
        }

        return $return;
    }

    /**
     * Call to validaDocumentoIdentidad function
     * @param string $uididPersona
     * @param string $uididType
     * @param string $docNumber
     * @return bool
     */
    public function validateIdDocument($uididPersona, $uididType, $docNumber)
    {
        $wsUrl = $this->get(self::SETTING_WEB_SERVICE);

        if (empty($wsUrl)) {
            return true;
        }

        try {
            $client = new SoapClient($wsUrl);
            $result = $client
                ->validaDocumentoIdentidad([
                    'uididpersona' => $uididPersona,
                    'uididdocumentoidentidad' => $uididType,
                    'vchnumerodocumento' => $docNumber
                ])
                ->validaDocumentoIdentidadResult;

            return (bool) $result;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Call to validaDatosCompletos function
     * Web service returns 0 if the profile is completed, otherwiswe return 1
     * When webservice doesn't works then this function return true
     * @param string $uididpersona
     * @return bool Return true if profile is completed or when webservice fail
     */
    public function profileIsCompleted($uididpersona)
    {
        $wsUrl = $this->get(self::SETTING_WEB_SERVICE);

        if (empty($wsUrl)) {
            return true;
        }

        try {
            $client = new SoapClient($wsUrl);
            $result = $client
                ->validaDatosCompletos([
                    'uididpersona' => $uididpersona,
                ])
                ->validaDatosCompletosResult;

            return !$result;
        } catch (Exception $e) {
            //When webservice doesn't works then return true to avoid blocking access to users
            return true;
        }
    }

    /**
     * Redirect to redirect.php file to validate profile
     */
    public function redirect()
    {
        if ($this->get(self::SETTING_ENABLE) !== 'true') {
            return;
        }

        if (ChamiloApi::isAjaxRequest()) {
            return;
        }

        $userId = api_get_user_id();

        if (!$userId) {
            return;
        }

        if (!api_is_student()) {
            return;
        }

        $filter = [
            '/main/auth/profile.php',
            '/plugin/icpna_update_user/redirect.php'
        ];

        if (in_array($_SERVER['PHP_SELF'], $filter)) {
            return;
        }

        $efv = new ExtraFieldValue('user');
        $uididpersona = $efv->get_values_by_handler_and_field_variable($userId, 'uididpersona');

        $profileIsCompleted = $this->profileIsCompleted($uididpersona['value']);

        if ($profileIsCompleted) {
            return;
        }

        header('Location: '.api_get_path(WEB_CODE_PATH).'auth/profile.php');
        exit;
    }

    /**
     * Call to tipovia function
     * @return array
     */
    public function getTipoVia()
    {
        $return = [];
        $tableResult = $this->getTableResult('tipovia');

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uidIdTipoVia,
                'text' => (string) $item->vchNombreTipoVia,
            ];
        }

        return $return;
    }

    /**
     * @param string $strBirthdate Date of birthdate
     * @return bool
     */
    public static function isLegalAge($strBirthdate)
    {
        $birthdate = new DateTime($strBirthdate);
        $now = new DateTime();
        $interval = $now->diff($birthdate);
        $ageInDays = (int) $interval->format('%a');
        $adult = 18 * 365.25;
        $remainingDaysToBeAdult = $adult - $ageInDays;

        return $remainingDaysToBeAdult >= 0;
    }
}
