<?php
/*
 * This script insert session extra fields
 */

exit;

require_once '../../main/inc/global.inc.php';

$ef = new ExtraField('user');

if (!$ef->get_handler_field_info_by_field_variable('id_document_type')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'id_document_type',
        'display_text' => 'Documento de identidad (tipo)',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('id_document_number')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'id_document_number',
        'display_text' => 'Documento de identidad (número)',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('middle_name')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'middle_name',
        'display_text' => 'Segundo nombre',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('mothers_name')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'mothers_name',
        'display_text' => 'Apellido materno',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('sex')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_SELECT,
        'variable' => 'sex',
        'display_text' => 'Sexo',
        'visible_to_self' => true,
        'changeable' => true,
        'field_options' => 'M;F'
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('birthdate')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_DATE,
        'variable' => 'birthdate',
        'display_text' => 'Fecha de nacimiento',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('nationality')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'nationality',
        'display_text' => 'Nacionalidad',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('address_department')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'address_department',
        'display_text' => 'Departamento',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('address_province')) {
    $ef->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'address_province',
    'display_text' => 'Provincia',
    'visible_to_self' => true,
    'changeable' => true
]);
}

if (!$ef->get_handler_field_info_by_field_variable('address_district')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'address_district',
        'display_text' => 'Distrito',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('address')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'address',
        'display_text' => 'Dirección',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('mobile_phone_number')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER,
        'variable' => 'mobile_phone_number',
        'display_text' => 'Número de celular',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation',
        'display_text' => 'Ocupación',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_department')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_department',
        'display_text' => 'Dirección del centro de estudios/laboral (departamento)',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_province')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_province',
        'display_text' => 'Dirección del centro de estudios/laboral (provincia)',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_district')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_district',
        'display_text' => 'Dirección del centro de estudios/laboral (distrito)',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_center_name_1')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_center_name_1',
        'display_text' => 'Centro de estudios/laboral',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_center_name_2')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_center_name_2',
        'display_text' => 'Centro de estudios/laboral',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_center_name_3')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_center_name_3',
        'display_text' => 'Centro de estudios/laboral',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('occupation_center_name_4')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'occupation_center_name_4',
        'display_text' => 'Centro de estudios/laboral',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('university_career')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'university_career',
        'display_text' => 'Carrera universitaria',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('guardian_id_document_type')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'guardian_id_document_type',
        'display_text' => 'Documento de identidad del apoderado (tipo)',
        'visible_to_self' => true,
        'changeable' => true,
        'field_options' => 'DNI|Pasaporte|Carné de extranjería'
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('guardian_id_document_number')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'guardian_id_document_number',
        'display_text' => 'Documento de identidad del apoderado (número)',
        'visible_to_self' => true,
        'changeable' => true,
        'field_options' => 'DNI|Pasaporte|Carné de extranjería'
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('guardian_name')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'guardian_name',
        'display_text' => 'Nombre del apoderado',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('guardian_email')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'guardian_email',
        'display_text' => 'Correo electrónico del apoderado',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('urbanization')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'urbanization',
        'display_text' => 'Urbanización',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('type_of_road')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'type_of_road',
        'display_text' => 'Tipo de vía',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

$address = $ef->get_handler_field_info_by_field_variable('address');

if ($address) {
    $ef->update([
        'id' => $address['id'],
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'address',
        'display_text' => 'Nombre de vía',
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('door_number')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'door_number',
        'display_text' => 'Número de puerta',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}

if (!$ef->get_handler_field_info_by_field_variable('indoor_number')) {
    $ef->save([
        'field_type' => ExtraField::FIELD_TYPE_TEXT,
        'variable' => 'indoor_number',
        'display_text' => 'Número de interior',
        'visible_to_self' => true,
        'changeable' => true
    ]);
}
