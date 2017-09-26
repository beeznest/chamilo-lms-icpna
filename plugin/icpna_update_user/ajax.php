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
    default:
        $return = [];
}

header('Content-Type: application/json');
echo json_encode($return);
