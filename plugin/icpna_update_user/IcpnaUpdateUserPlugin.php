<?php
/* For licensing terms, see /license.txt */

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
            ['chrTipoOcupacion' => $type, 'uididdistrito' => $district]
        );

        foreach ($tableResult as $item) {
            $return[] = [
                'value' => (string) $item->uididcentroestudios,
                'text' => (string) $item->vchdescripcioncentroestudios
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
}