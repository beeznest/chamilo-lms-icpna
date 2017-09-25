<?php
/*
 * This script insert session extra fields
 */

exit;

require_once '../../main/inc/global.inc.php';

$idDocument = new ExtraField('user');
$idDocument->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'id_document_type',
    'display_text' => 'Documento de identidad (tipo)',
    'visible_to_self' => true,
    'changeable' => true
]);

$idDocument = new ExtraField('user');
$idDocument->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'id_document_number',
    'display_text' => 'Documento de identidad (número)',
    'visible_to_self' => true,
    'changeable' => true
]);

$middleName = new ExtraField('user');
$middleName->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'middle_name',
    'display_text' => 'Segundo nombre',
    'visible_to_self' => true,
    'changeable' => true
]);

$mothersName = new ExtraField('user');
$mothersName->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'mothers_name',
    'display_text' => 'Apellido materno',
    'visible_to_self' => true,
    'changeable' => true
]);

$sex = new ExtraField('user');
$sex->save([
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'sex',
    'display_text' => 'Sexo',
    'visible_to_self' => true,
    'changeable' => true,
    'field_options' => 'M;F'
]);

$birthdate = new ExtraField('user');
$birthdate->save([
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'variable' => 'birthdate',
    'display_text' => 'Fecha de nacimiento',
    'visible_to_self' => true,
    'changeable' => true
]);

$nationality = new ExtraField('user');
$nationality->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'nationality',
    'display_text' => 'Nacionalidad',
    'visible_to_self' => true,
    'changeable' => true
]);

$addressDepartment = new ExtraField('user');
$addressDepartment->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'address_department',
    'display_text' => 'Departamento',
    'visible_to_self' => true,
    'changeable' => true
]);

$addressProvince = new ExtraField('user');
$addressProvince->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'address_province',
    'display_text' => 'Provincia',
    'visible_to_self' => true,
    'changeable' => true
]);

$addressDistrict = new ExtraField('user');
$addressDistrict->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'address_district',
    'display_text' => 'Distrito',
    'visible_to_self' => true,
    'changeable' => true
]);

$address = new ExtraField('user');
$address->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'address',
    'display_text' => 'Dirección',
    'visible_to_self' => true,
    'changeable' => true
]);

$address = new ExtraField('user');
$address->save([
    'field_type' => ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER,
    'variable' => 'mobile_phone_number',
    'display_text' => 'Número de celular',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupation = new ExtraField('user');
$occupation->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation',
    'display_text' => 'Ocupación',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationDepartment = new ExtraField('user');
$occupationDepartment->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_department',
    'display_text' => 'Dirección del centro de estudios/laboral (departamento)',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationProvince = new ExtraField('user');
$occupationProvince->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_province',
    'display_text' => 'Dirección del centro de estudios/laboral (provincia)',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationDistrict = new ExtraField('user');
$occupationDistrict->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_district',
    'display_text' => 'Dirección del centro de estudios/laboral (distrito)',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationCenterName1 = new ExtraField('user');
$occupationCenterName1->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_center_name_1',
    'display_text' => 'Centro de estudios/laboral',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationCenterName2 = new ExtraField('user');
$occupationCenterName2->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_center_name_2',
    'display_text' => 'Centro de estudios/laboral',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationCenterName3 = new ExtraField('user');
$occupationCenterName3->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_center_name_3',
    'display_text' => 'Centro de estudios/laboral',
    'visible_to_self' => true,
    'changeable' => true
]);

$occupationCenterName4 = new ExtraField('user');
$occupationCenterName4->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'occupation_center_name_4',
    'display_text' => 'Centro de estudios/laboral',
    'visible_to_self' => true,
    'changeable' => true
]);

$universityCareer = new ExtraField('user');
$universityCareer->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'university_career',
    'display_text' => 'Carrera universitaria',
    'visible_to_self' => true,
    'changeable' => true
]);

$guardianIdDocument = new ExtraField('user');
$guardianIdDocument->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'guardian_id_document_type',
    'display_text' => 'Documento de identidad del apoderado (tipo)',
    'visible_to_self' => true,
    'changeable' => true,
    'field_options' => 'DNI|Pasaporte|Carné de extranjería'
]);

$guardianIdDocument = new ExtraField('user');
$guardianIdDocument->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'guardian_id_document_number',
    'display_text' => 'Documento de identidad del apoderado (número)',
    'visible_to_self' => true,
    'changeable' => true,
    'field_options' => 'DNI|Pasaporte|Carné de extranjería'
]);

$guardianName = new ExtraField('user');
$guardianName->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'guardian_name',
    'display_text' => 'Nombre del apoderado',
    'visible_to_self' => true,
    'changeable' => true
]);

$guardianEmail = new ExtraField('user');
$guardianEmail->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'guardian_email',
    'display_text' => 'Correo electrónico del apoderado',
    'visible_to_self' => true,
    'changeable' => true
]);
