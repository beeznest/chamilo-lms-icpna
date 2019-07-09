<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

class IcpnaUpdateUserPluginHook extends HookObserver implements HookUpdateUserObserverInterface
{

    public static $plugin;

    /**
     * Constructor. Calls parent, mainly.
     */
    protected function __construct()
    {
        self::$plugin = IcpnaUpdateUserPlugin::create();
        parent::__construct(
            'plugin/icpna_update_user/IcpnaUpdateUserPluginHook.php',
            'icpna_update_user'
        );
    }

    /**
     * @param User  $user
     * @param array $info
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateUserInfo(User $user, array $info)
    {
        $em = Database::getManager();

        $user
            ->setFirstname($info['firstname'])
            ->setLastname($info['lastname']);

        $em->persist($user);
        $em->flush();
    }

    /**
     * @param User  $user
     * @param array $origExtraInfo
     * @param array $newInfo
     */
    private function updateUserExtraInfo(User $user, array $origExtraInfo, array $newInfo)
    {
        $userData = array_merge(
            $origExtraInfo,
            [
                'item_id' => $user->getId(),
                'extra_middle_name' => $newInfo['extra_middle_name'],
                'extra_mothers_name' => $newInfo['extra_mothers_name'],
            ]
        );

        $extraField = new ExtraFieldValue('user');
        $extraField->saveFieldValues($userData);
    }

    /**
     * @param User $user
     */
    private function updateSessionUserInfo(User $user)
    {
        $_u = array_merge(
            ChamiloSession::read('_user', []),
            [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]
        );

        ChamiloSession::write('_user', $_u);
    }

    /**
     * @param \HookUpdateUserEventInterface $hook
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function hookUpdateUser(HookUpdateUserEventInterface $hook)
    {
        $data = $hook->getEventData();

        if ($data['type'] !== HOOK_EVENT_TYPE_POST) {
            return false;
        }

        $wsUrl = self::$plugin->get(IcpnaUpdateUserPlugin::SETTING_WEB_SERVICE);

        if (empty($wsUrl)) {
            return false;
        }

        /** @var User $user */
        $user = $data['user'];

        if (User::STUDENT !== $user->getStatus()) {
            return false;
        }

        $extraData = UserManager::get_extra_user_data($user->getId(), true);

        if (!isset($extraData['extra_uididpersona'])) {
            return false;
        }

        $wsUserInfo = IcpnaUpdateUserPlugin::create()->getUserInfo($extraData['extra_uididpersona']);

        if (empty($wsUserInfo)) {
            return false;
        }

        $this->updateUserInfo($user, $wsUserInfo);
        $this->updateUserExtraInfo($user, $extraData, $wsUserInfo);
        $this->updateSessionUserInfo($user);

        try {
            $client = new SoapClient($wsUrl);
            $result = $client
                ->actualizadatospersonales([
                    'p_uididpersona' => strtoupper($extraData['extra_uididpersona']),
                    'p_uididdocumentoidentidad' => strtoupper($extraData['extra_id_document_type']),
                    'p_vchDocumentoNumero' => $extraData['extra_id_document_number'],
                    'p_vchPrimerNombre' => $user->getFirstname(),
                    'p_vchSegundoNombre' => $wsUserInfo['extra_middle_name'],
                    'p_vchPaterno' => $user->getLastname(),
                    'p_vchMaterno' => $wsUserInfo['extra_mothers_name'],
                    'p_chrSexo' => $extraData['extra_sex'],
                    'p_sdtFechaNacimiento' => $extraData['extra_birthdate'],
                    'p_uididpaisorigen' => strtoupper($extraData['extra_nationality']),
                    'p_uidIdDepartamento' => strtoupper($extraData['extra_address_department']),
                    'p_uidIdProvincia' => strtoupper($extraData['extra_address_province']),
                    'p_uidIdDistrito' => strtoupper($extraData['extra_address_district']),
                    'p_vchDireccionPersona' => $extraData['extra_address'],
                    'p_vchEmailPersona' => $user->getEmail(),
                    'p_vchTelefonoPersona' => $user->getPhone(),
                    'p_vchcelularPersona' => $extraData['extra_mobile_phone_number'],
                    'p_strNombrePadre' => $extraData['extra_guardian_name'],
                    'p_vchEmailApoderado' => $extraData['extra_guardian_email'],
                    'p_uidIdDocumentoIdentidadPadre' => strtoupper($extraData['extra_guardian_id_document_type']),
                    'p_vchDocumentoNumeroPadre' => $extraData['extra_guardian_id_document_number'],
                    'p_vchNombreUrbanizacion' => $extraData['extra_urbanization'],
                    'p_uidIdTipoVia' => $extraData['extra_type_of_road'],
                    'p_chrNroPuerta' => $extraData['extra_door_number'],
                    'p_chrNroInterior' => $extraData['extra_indoor_number'],
                ])
                ->actualizadatospersonalesResult
                ->any;

            $xml = strstr($result, '<diffgr:diffgram');

            $xmlResult = new SimpleXMLElement($xml);

            if (!isset($xmlResult->NewDataSet)) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return false;
        }
    }
}
