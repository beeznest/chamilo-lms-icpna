<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = IcpnaUpdateUserPlugin::create();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$return = [];

switch ($action) {
    case 'get_tipodocumento':
        $return = $plugin->getTipodocumento();
        break;
    case 'get_nacionalidad':
        $return = $plugin->getNacionalidad();
        break;
    case 'get_departamento':
        $return = $plugin->getDepartamento();
        break;
    case 'get_provincia':
        $uidid = isset($_REQUEST['uidid']) ? $_REQUEST['uidid'] : null;

        if (!$uidid) {
            break;
        }

        $return = $plugin->getProvincia($uidid);
        break;
    case 'get_distrito':
        $uidid = isset($_REQUEST['uidid']) ? $_REQUEST['uidid'] : null;

        if (!$uidid) {
            break;
        }

        $return = $plugin->getDistrito($uidid);
        break;
    case 'get_ocupacion':
        $return = $plugin->getOcupacion();
        break;
    case 'get_centroestudios':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
        $district = isset($_REQUEST['district']) ? $_REQUEST['district'] : null;

        if (!$type || !$district) {
            break;
        }

        $return = $plugin->getCentroestudios($type, $district);
        break;
    case 'get_carrerauniversitaria':
        $return = $plugin->getCarrerauniversitaria();
        break;
    case 'validate_id_document':
        $uididPersona = isset($_REQUEST['uididpersona']) ? $_REQUEST['uididpersona'] : null;
        $uididType = isset($_REQUEST['uididtipo']) ? $_REQUEST['uididtipo'] : null;
        $docNumber = isset($_REQUEST['number']) ? $_REQUEST['number'] : null;

        if (!isset($uididPersona, $uididType, $docNumber)) {
            break;
        }

        $return = [
            'repeated' => $plugin->validateIdDocument($uididPersona, $uididType, $docNumber)
        ];
        break;
    case 'validate_email':
        $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;

        if (!isset($email)) {
            break;
        }

        $content = file_get_contents(__DIR__.'/black_list/email.json');
        $blackList = json_decode($content, true);

        $return = [
            'valid' => !in_array($email, $blackList)
        ];
        break;
    case 'validate_phone':
        $phone = isset($_REQUEST['phone']) ? $_REQUEST['phone'] : null;

        if (!isset($phone)) {
            break;
        }

        $content = file_get_contents(__DIR__.'/black_list/phone.json');
        $blackList = json_decode($content, true);

        $return = [
            'valid' => !in_array($phone, $blackList)
        ];
        break;
    default:
        $return = [];
}

header('Content-Type: application/json');
echo json_encode($return);
