<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows for specific registration rules (see CustomPages feature of Chamilo)
 * Please contact CBlue regarding any licences issues.
 * Author: noel@cblue.be
 * Copyright: CBlue SPRL, 20XX (GNU/GPLv3)
 * @package chamilo.custompages
 **/

require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';

/** @var \FormValidator $form */
$form = isset($content['form']) ? $content['form'] : null;

Display::display_header(get_lang('EditProfile', null, 'spanish', true));

if (!$form->elementExists('extra_id_document_type') ||
    !$form->elementExists('extra_id_document_number') ||
    !$form->elementExists('extra_middle_name') ||
    !$form->elementExists('extra_mothers_name') ||
    !$form->elementExists('extra_sex') ||
    !$form->elementExists('extra_birthdate') ||
    !$form->elementExists('extra_nationality') ||
    !$form->elementExists('extra_address_department') ||
    !$form->elementExists('extra_address_province') ||
    !$form->elementExists('extra_address_district') ||
    !$form->elementExists('extra_address') ||
    !$form->elementExists('extra_mobile_phone_number') ||
    !$form->elementExists('extra_occupation') ||
    !$form->elementExists('extra_occupation_department') ||
    !$form->elementExists('extra_occupation_province') ||
    !$form->elementExists('extra_occupation_district') ||
    !$form->elementExists('extra_occupation_center_name_1') ||
    !$form->elementExists('extra_occupation_center_name_2') ||
    !$form->elementExists('extra_occupation_center_name_3') ||
    !$form->elementExists('extra_occupation_center_name_4') ||
    !$form->elementExists('extra_university_career') ||
    !$form->elementExists('extra_guardian_id_document_type') ||
    !$form->elementExists('extra_guardian_id_document_number') ||
    !$form->elementExists('extra_guardian_name') ||
    !$form->elementExists('extra_guardian_email')) {
    $form->display();
    Display::display_footer();
    exit;
}

// Translate chamilo default profile elements
$thisElement = $form->getElement('firstname');
$thisElement->_label = get_lang('FirstName', null, 'spanish', true);
$thisElement = $form->getElement('lastname');
$thisElement->_label = get_lang('LastName', null, 'spanish', true);
$thisElement = $form->getElement('username');
$thisElement->_label = get_lang('UserName', null, 'spanish', true);
$thisElement = $form->getElement('official_code');
$thisElement->_label = get_lang('OfficialCode', null, 'spanish', true);
$thisElement = $form->getElement('email');
$thisElement->_label = get_lang('Email', null, 'spanish', true);
$thisElement = $form->getElement('phone');
$thisElement->_label = get_lang('Phone', null, 'spanish', true);
$thisElement = $form->getElement('picture');
$thisElement->_label = get_lang('UpdateImage', null, 'spanish', true);
$thisElement = $form->getElement('language');
$thisElement->_label = get_lang('Language', null, 'spanish', true);
$thisElement = $form->getElement('extra_address');
$thisElement->_label = get_lang('AddressField', null, 'spanish', true);
$thisElement = $form->getElement('extra_sex');
$thisElement->_label = get_lang('UserSex', null, 'spanish', true);
//$thisElement = $form->getElement('password0');
//$thisElement->_label = get_lang('Pass', null, 'spanish', true);
//$thisElement = $form->getElement('password1');
//$thisElement->_label = get_lang('NewPass', null, 'spanish', true);
//$thisElement = $form->getElement('password2');
//$thisElement->_label = get_lang('PassTwo', null, 'spanish', true);

/**
 * Removes some unwanted elementend of the form object
 */
if ($form->elementExists('status')) {
    $form->removeElement('status');
}
if ($form->elementExists('extra_skype')) {
    $form->removeElement('extra_skype');
}
if ($form->elementExists('extra_linkedin_url')) {
    $form->removeElement('extra_linkedin_url');
}
if ($form->elementExists('apply_change')) {
    $form->removeElement('apply_change');
}

$form->removeElement('extra_id_document_type');
$form->addSelect(
    'extra_id_document_type',
    'Documento de identidad',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'slct_extra_id_document_type']
);

/** @var \HTML_QuickForm_select $slctSex */
$slctSex = $form->getElement('extra_sex');
$slctSex->clearOptions();
$slctSex->addOption('Masculino', 'M');
$slctSex->addOption('Femenino', 'F');

$form->removeElement('extra_nationality');
$form->addSelect(
    'extra_nationality',
    'Nacionalidad',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_nationality']
);

$form->removeElement('extra_address_department');
$form->addSelect(
    'extra_address_department',
    'Dirección (departamento)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_address_department']
);

$form->removeElement('extra_address_province');
$form->addSelect(
    'extra_address_province',
    'Dirección (provincia)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_address_province']
);

$form->removeElement('extra_address_district');
$form->addSelect(
    'extra_address_district',
    'Dirección (distrito)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_address_district']
);

$form->removeElement('extra_occupation');
$form->addSelect(
    'extra_occupation',
    'Ocupación',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation']
);

$form->removeElement('extra_occupation_department');
$form->addSelect(
    'extra_occupation_department',
    'Dirección del centro de estudios/laboral (departamento / provincia / distrito)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_department']
);

$form->removeElement('extra_occupation_province');
$form->addSelect(
    'extra_occupation_province',
    '',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_province']
);

$form->removeElement('extra_occupation_district');
$form->addSelect(
    'extra_occupation_district',
    '',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_district']
);

$form->removeElement('extra_occupation_center_name_1');
$form->addSelect(
    'extra_occupation_center_name_1',
    'Centro de estudios (escolar)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_center_name_1']
);

$form->removeElement('extra_occupation_center_name_2');
$form->addSelect(
    'extra_occupation_center_name_2',
    'Centro de estudios (técnico)',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_center_name_2']
);

$form->removeElement('extra_occupation_center_name_3');
$form->addSelect(
    'extra_occupation_center_name_3',
    'Universidad',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_occupation_center_name_3']
);

$form->removeElement('extra_university_career');
$form->addSelect(
    'extra_university_career',
    'Carrera universitaria',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_university_career']
);

$form->removeElement('extra_guardian_id_document_type');
$form->addSelect(
    'extra_guardian_id_document_type',
    'Documento de identidad del apoderado',
    ['' => get_lang('SelectAnOption')],
    ['id' => 'extra_extra_guardian_id_document_type']
);

$occupationName1 = $occupationName2 = $occupationName3 = $universityCarrer = [];

$form->removeElement('extra_guardian_name');
$form->removeElement('extra_guardian_email');

$form->addElement('html', '<div id="guardian_div">');
$form->addElement('html', '
<div class="form-group">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div class="alert alert-warning">
            If you are under age it is necessary to register the data of your guardian
        </div>
    </div>
    <div class="col-md-2"></div>
</div>
', 'guardian_section');
$form->addText('extra_guardian_name', 'Nombre del apoderado');
$form->addText('extra_guardian_email', 'Email del apoderado');
$form->addElement('html', '</div>');
$form->addElement('html', '
<div class="form-group">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div class="terms alert alert-warning">Antes de guardar los datos, debes estar de acuerdo con nuestros
            <button type="button" class="btn btn-link" data-toggle="modal" data-target="#terms-conditions">
                <b>términos y condiciones</b>
            </button>
        </div>
    </div>
    <div class="col-md-2"></div>
</div>
', 'terms');

if (is_profile_editable()) {
    $form->addButtonUpdate(get_lang('SaveSettings'), 'apply_change');
} else {
    $form->freeze();
}

//Sort order of the form elements to match the custom profile page
$currentLanguage = api_get_interface_language();

if ($currentLanguage !== 'english') {
    $form->insertElementBefore(
        $form->removeElement('firstname', false),
        'lastname'
    );
}

$form->insertElementBefore(
    $form->removeElement('extra_id_document_number', false),
    'firstname'
);
$form->insertElementBefore(
    $form->removeElement('extra_id_document_type', false),
    'extra_id_document_number'
);
$form->insertElementBefore(
    $form->removeElement('extra_middle_name', false),
    'lastname'
);
$form->insertElementBefore(
    $form->removeElement('extra_mothers_name', false),
    'username'
);
$form->insertElementBefore(
    $form->removeElement('extra_guardian_id_document_number', false),
    'terms'
);
$form->insertElementBefore(
    $form->removeElement('extra_guardian_id_document_type', false),
    'extra_guardian_id_document_number'
);
$form->insertElementBefore(
    $form->removeElement('extra_guardian_id_document_type', false),
    'extra_guardian_id_document_number'
);
$form->insertElementBefore(
    $form->removeElement('extra_guardian_email', false),
    'extra_guardian_id_document_type'
);
$form->insertElementBefore(
    $form->removeElement('extra_university_career', false),
    'guardian_section'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_center_name_4', false),
    'extra_university_career'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_center_name_3', false),
    'extra_occupation_center_name_4'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_center_name_2', false),
    'extra_occupation_center_name_3'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_center_name_1', false),
    'extra_occupation_center_name_2'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_district', false),
    'extra_occupation_center_name_1'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_province', false),
    'extra_occupation_district'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation_department', false),
    'extra_occupation_province'
);
$form->insertElementBefore(
    $form->removeElement('extra_occupation', false),
    'extra_occupation_department'
);
$form->insertElementBefore(
    $form->removeElement('extra_mobile_phone_number', false),
    'extra_occupation'
);
$form->insertElementBefore(
    $form->removeElement('extra_address', false),
    'extra_mobile_phone_number'
);
?>

<div class="row">
    <div class="col-md-10">
        <?php if (isset($content['error']) && !empty($content['error'])) {
            echo '<div id="registration-form-error" class="alert alert-danger">'.$content['error'].'</div>';
        }?>
        
            <div class="box box-primary">
                <div class="box-body">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo get_lang('ModifProfile', null, 'spanish', true); ?></h3>
                    </div>
                    <div id="registration-form-box" class="form-box well">
                    <?php
                        $content['form']->display();
                    ?>
                </div>
            </div>
        </div>
	
    </div>
    <div class="col-md-2">
        <div id="image-message-container">
            <a class="expand-image" href="<?php echo $content['big_image'] ?>">
                <img src="<?php echo $content['normal_image'] ?>" class="img-thumbnail img-responsive">
            </a>
        </div>
    </div>
</div>
<div id="terms-conditions" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">TERMS AND CONDITIONS</h4>
                </div>
                <div class="modal-body">
                    <p id="title-modal" class="text-center"></p>
                    <p id="text-modal" class="text-justify"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
 </div>
    <script>
        (function () {
            $(document).ready(function () {
                var $slctDocument = $('#slct_extra_id_document_type'),
                    $txtDocument = $('#txt_extra_id_document_number'),
                    $divGuardian = $('#guardian_div'),
                    $modalTitle = $('#title-modal'),
                    $modalText = $('#text-modal'),
                    $slctOccupation = $('#extra_occupation'),
                    $slctOccupationDepartment = $('#extra_occupation_department'),
                    $slctOccupationProvince = $('#extra_occupation_province'),
                    $slctOccupationDistrict = $('#extra_occupation_district'),
                    $slctOccupationName1 = $('#extra_occupation_center_name_1'),
                    $slctOccupationName2 = $('#extra_occupation_center_name_2'),
                    $slctOccupationName3 = $('#extra_occupation_center_name_3'),
                    $txtOccupationName4 = $('#extra_occupation_center_name_4'),
                    $slctUniCarrers = $('#extra_university_career'),
                    $txtGuardianName = $('#profile_extra_guardian_name'),
                    $txtGuardianEmail = $('#profile_extra_guardian_email'),
                    $txtGuardianDocument = $('#txt_extra_guardian_id_document'),
                    $slctAddressDepartment = $('#extra_address_department'),
                    $slctAddressProvince = $('#extra_address_province'),
                    $slctAddressDistrict = $('#extra_address_district'),
                    $slctLocation = $('#extra_address'),
                    $txtEmail = $('#profile_email'),
                    $slctSex = $('#extra_sex'),
                    $slctNationality = $('#extra_nationality'),
                    $txtBirthdate = $('#extra_birthdate'),
                    $txtMobilePhone = $('#profile_extra_mobile_phone_number'),
                    $slctGuardianDocument = $('#extra_extra_guardian_id_document_type'),
                    url = _p.web_plugin + 'icpna_update_user/ajax.php';

                function onMobileNumberLoad () {
                    $txtMobilePhone.val(
                        $txtMobilePhone.val()
                            .replace(/[^\d]+/g, '')
                            .replace(/(\d{2})(\d{9})/, '($1)$2')
                    );
                }

                function onStudentDocument () {
                    switch ($slctDocument.prop('selectedIndex')) {
                        case 1:
                            $txtDocument
                                .attr({
                                    pattern: '\\d{8}',
                                    maxlength: '8',
                                    title: '<?php echo get_lang('OnlyNumbers') ?>',
                                    required: true
                                });
                            break;
                        case 2:
                            $txtDocument
                                .attr({
                                    pattern: '[a-zA-Z0-9]+',
                                    maxlength: '',
                                    title: '<?php echo get_lang('OnlyLettersAndNumbers') ?>',
                                    required: true
                                });
                            break;
                        case 3:
                            $txtDocument
                                .attr({
                                    pattern: '\\d{9}',
                                    maxlength: '9',
                                    title: '<?php echo get_lang('OnlyNumbers') ?>',
                                    required: true
                                });
                            break;
                    }
                }

                function checkAge () {
                    var extraBirthdayFieldValue = $txtBirthdate.val();

                    if (extraBirthdayFieldValue == null) {
                        return false;
                    }

                    var now = new moment(),
                        birthYear = new moment(extraBirthdayFieldValue),
                        age = now.diff(birthYear, 'years');

                    return age;
                }

                function onStudentBirthday () {
                    $txtGuardianName.removeAttr('required');
                    $txtGuardianEmail.removeAttr('required');
                    $txtGuardianDocument.removeAttr('required');

                    var age = checkAge();

                    if (age >= 18) {
                        $divGuardian.hide();
                        $modalTitle.html(
                            '<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MAYOR DE 18 AÑOS DE EDAD</h3>'
                        );
                        $modalText.html(
                            'En aplicación a lo dispuesto por la Ley 29733 Ley de Protección de ' +
                            'Datos Personales, y el D.S. 003-2013-JUS,el suscrito titular de los datos personales, ' +
                            'mediante el llenado y/o firma del presente formulario, autorizo de forma expresa e ' +
                            'inequívoca y por tiempo indefinido que mis datos personales, sean tratados, ' +
                            'almacenados, sistematizados y utilizados por el INSTITUTO CULTURAL PERUANO ' +
                            'NORTEAMERICANO para fines estadísticos, administrativos y de gestión comercial, ' +
                            'incluyendo invitaciones a cursos, talleres, charlas y otros eventos que el ICPNA ' +
                            'organice, auspicie o participe, siendo que los datos, serán conservados en un banco de ' +
                            'datos cuyo titular es el ICPNA, autorizando incluso el flujo transfronterizo con fines ' +
                            'académicos y/o destinado a la administración de exámenes internacionales. Asimismo, ' +
                            'declaro que estoy informado que ante alguna solicitud de datos personales sensibles, es ' +
                            'mi facultad responder o no sobre los mismos e igualmente declaro conocer los efectos ' +
                            'y/o consecuencias de proporcionar mis datos personales o de negarme a brindarlos. ' +
                            'Igualmente declaro conocer que para ejercer mis derechos como acceso, rectificación, ' +
                            'cancelación y oposición y otros derechos, sobre mis datos puedo dirigirme a las ' +
                            'oficinas, ubicadas en Av. Angamos Oeste 120, Miraflores. Declaro conocer los alcances ' +
                            'de la Ley 29733 y su reglamento, para ejercer mis derechos conforme a Ley.'
                        );

                        return;
                    }

                    if (age >= 14 && age < 18) {
                        $txtGuardianName.attr('required', true);
                        $modalTitle.html(
                            '<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MAYOR DE 14 Y MENOR A 18 AÑOS</h3>'
                        );
                        $modalText.html(
                            'En aplicación a lo dispuesto por la Ley 29733 Ley de Protección de Datos Personales, ' +
                            'y el D.S.003-2013-JUS, y en especial en el artículo 28 del D.S.003-2013-JUS, el ' +
                            'suscrito menor de edad, titular de mis datos personales, mediante el llenado y/o firma ' +
                            'del presente formulario, autorizo de forma expresa e inequívoca y por tiempo indefinido ' +
                            'que mis datos personales, sean tratados, almacenados, sistematizados y utilizados por ' +
                            'el INSTITUTO CULTURAL PERUANO NORTEAMERICANO para fines estadísticos, administrativos y ' +
                            'de gestión comercial, incluyendo invitaciones a cursos, talleres, charlas y otros ' +
                            'eventos que el ICPNA organice, auspicie o participe, siendo que los datos, serán ' +
                            'conservados en un banco de datos cuyo titular es el ICPNA, autorizando incluso el flujo ' +
                            'transfronterizo con fines académicos y/o destinado a la administración de exámenes ' +
                            'internacionales. Asimismo, declaro que estoy informado que ante alguna solicitud de ' +
                            'datos personales sensibles, es mi facultad responder o no sobre los mismos e igualmente ' +
                            'declaro conocer los efectos y/o consecuencias de proporcionar mis datos personales o de ' +
                            'negarme a brindarlos. Igualmente declaro conocer que para ejercer mis derechos como ' +
                            'acceso, rectificación, cancelación y oposición y otros derechos, sobre mis datos puedo ' +
                            'dirigirme a las oficinas, ubicadas en Av. Angamos Oeste 120, Miraflores. Señalo ' +
                            'también, que al ser yo menor de edad, el ICPNA no me está solicitando datos relativos a ' +
                            'la actividad profesional o laboral de mis padres, ni su información económica, datos ' +
                            'sociológicos o de cualquier otro sobre los demás miembros de mi familia. Asimismo, ' +
                            'declaro que estoy informando al ICPNA de la identidad y dirección de mis padres, a fin ' +
                            'de que ellos puedan autorizar el tratamiento de mis datos personales, en aquellos casos ' +
                            'en que mi propia autorización no fuera suficiente y que estén referidas al acceso a ' +
                            'actividades, vinculadas con bienes o servicios que estén restringidos para mayores de edad'
                        );

                        return;
                    }

                    $txtGuardianName.attr('required', true);
                    $modalTitle.html(
                        '<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MENOR DE 14 AÑOS DE EDAD</h3>'
                    );
                    $modalText.html(
                        'En aplicación a lo dispuesto por la Ley 29733 Ley de Protección de Datos Personales, y el ' +
                        'D.S. 003-2013-JUS, el suscrito, padre o tutor del titular de los datos personales, mediante ' +
                        'el llenado y/o firma del presente formulario, autorizo de forma expresa e inequívoca y por ' +
                        'tiempo indefinido que los datos personales de mi hijo o menor sujeto a mi tutela sean ' +
                        'tratados, almacenados, sistematizados y utilizados por el INSTITUTO CULTURAL PERUANO ' +
                        'NORTEAMERICANO para fines estadísticos, administrativos y de gestión comercial, incluyendo ' +
                        'invitaciones a cursos, talleres, charlas y otros eventos que el ICPNA organice, auspicie o ' +
                        'participe, siendo que los datos, serán conservados en un banco de datos cuyo titular es el ' +
                        'ICPNA, autorizando incluso el flujo transfronterizo con fines académicos y/o destinado a la ' +
                        'administración de exámenes internacionales. Asimismo, declaro que estoy informado que ante ' +
                        'alguna solicitud de datos personales sensibles, es mi facultad responder o no sobre los ' +
                        'mismos e igualmente declaro conocer los efectos y/o consecuencias de proporcionar mis datos ' +
                        'personales o de negarme a brindarlos. Igualmente declaro conocer que para ejercer mis ' +
                        'derechos como acceso, rectificación, cancelación y oposición y otros derechos, sobre mis ' +
                        'datos puedo dirigirme a las oficinas, ubicadas en Av. Angamos Oeste 120, Miraflores. ' +
                        'Declaro conocer los alcances de la Ley 29733 y su reglamento, para ejercer mis derechos ' +
                        'conforme a Ley y declaro además, que parte integrante de esta declaración y autorización ' +
                        'explícita en todos los términos expuestos, incluyen también el tratamiento de datos ' +
                        'personales del menor de edad sujeto a mi patria potestad, y cuyos datos se encuentran en el ' +
                        'banco de datos del ICPNA.'
                    );

                    $divGuardian.show();
                }

                function onOccupation () {
                    $slctOccupationName1.parents('.form-group').hide();
                    $slctOccupationName2.parents('.form-group').hide();
                    $slctOccupationName3.parents('.form-group').hide();
                    $txtOccupationName4.parents('.form-group').hide();
                    $slctUniCarrers.parents('.form-group').hide();

                    $slctOccupationName1.removeAttr('required');
                    $slctOccupationName2.removeAttr('required');
                    $slctOccupationName3.removeAttr('required');
                    $txtOccupationName4.removeAttr('required');
                    $slctUniCarrers.removeAttr('required');

                    var modifiedIndex = $slctOccupation.prop('childElementCount') > 4 ? 0 : 1;

                    switch ($slctOccupation.prop('selectedIndex')) {
                        case 1 - modifiedIndex:
                            $slctOccupationName1.attr('required', true).parents('.form-group').show();
                            break;
                        case 2 - modifiedIndex:
                            $slctOccupationName2.attr('required', true).parents('.form-group').show();
                            break;
                        case 3 - modifiedIndex:
                            $slctOccupationName3.attr('required', true).parents('.form-group').show();
                            $slctUniCarrers.attr('required', true).parents('.form-group').show();
                            break;
                        case 4 - modifiedIndex:
                            $txtOccupationName4.attr('required', true).parents('.form-group').show();
                            break;
                    }
                }

                function onOccupationLocation () {
                    var firstValue = $slctOccupationDepartment.find('option:selected').data('value') || '',
                        secondValue = $slctOccupationProvince.find('option:selected').data('value') || '',
                        thirdValue = $slctOccupationDistrict.find('option:selected').data('value') || '',
                        modifiedIndex = $slctOccupation.prop('childElementCount') > 4 ? 0 : 1;

                    var ubigeo = firstValue + '' + secondValue + thirdValue;

                    function addOptions($el, options) {
                        $el
                            .empty()
                            .append(
                                $('<option>', {value: '', text: '<?php echo get_lang('SelectAnOption') ?>'})
                            );

                        $.each(options, function (index, option) {
                            var valueParts = option.display_text.split('#'),
                                dataValue = valueParts.length > 1 ? valueParts.shift() : '';

                            $el.append(
                                $('<option>', {
                                    value: option.option_value,
                                    text: valueParts.join(''),
                                    'data-value': dataValue
                                })
                            );
                        });

                        $el.selectpicker('refresh');
                    }

                    switch ($slctOccupation.prop('selectedIndex')) {
                        case 1 - modifiedIndex:
                            $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                                a: 'filter_select_options',
                                type: 'user',
                                field_variable: 'occupation_center_name_1',
                                filter_by: ubigeo
                            }, function (options) {
                                addOptions($slctOccupationName1, options);
                                $slctOccupationName1
                                    .selectpicker('val', ['<?php echo implode("', '", $occupationName1) ?>']);
                            });
                            break;
                        case 2 - modifiedIndex:
                            $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                                a: 'filter_select_options',
                                type: 'user',
                                field_variable: 'occupation_center_name_2',
                                filter_by: ubigeo
                            }, function (options) {
                                addOptions($slctOccupationName2, options);
                                $slctOccupationName2
                                    .selectpicker('val', ['<?php echo implode("', '", $occupationName2) ?>']);
                            });
                            break;
                        case 3 - modifiedIndex:
                            $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                                a: 'filter_select_options',
                                type: 'user',
                                field_variable: 'occupation_center_name_3',
                                filter_by: firstValue + '' + secondValue
                            }, function (options) {
                                addOptions($slctOccupationName3, options);
                                $slctOccupationName3
                                    .selectpicker('val', ['<?php echo implode("', '", $occupationName3) ?>']);
                            });
                            $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                                a: 'filter_select_options',
                                type: 'user',
                                field_variable: 'university_career',
                                filter_by: ''
                            }, function (options) {
                                addOptions($slctUniCarrers, options);
                                $slctUniCarrers
                                    .selectpicker('val', ['<?php echo implode("', '", $universityCarrer) ?>']);
                            });
                            break;
                        case 4 - modifiedIndex:
                            break;
                    }
                }

                (function () {
                    var xhrDocumentIdType = $.getJSON(url, {a: 'get_tipodocumento'}),
                        xhrNationality = $.getJSON(url, {a: 'get_nacionalidad'}),
                        xhrDepartment = $.getJSON(url, {a: 'get_departamento'}),
                        xhrOccupation = $.getJSON(url, {a: 'get_ocupacion'}),
                        xhrStudyCenter = $.getJSON(url, {a: 'get_centroestudios'});

                    $
                        .when
                        .apply($, [
                            xhrDocumentIdType,
                            xhrNationality,
                            xhrDepartment,
                            xhrOccupation,
                            xhrStudyCenter
                        ])
                        .then(function (
                            docTypeResponse,
                            nationalityResponse,
                            departmentResponse,
                            xhrOccupationResponse,
                            xhrStudyCenterResponse
                        ) {
                            $.each(docTypeResponse[0], function (i, option) {
                                $('<option>', option).appendTo($slctDocument);
                                $('<option>', option).appendTo($slctGuardianDocument);
                            });

                            $.each(nationalityResponse[0], function (i, option) {
                                $('<option>', option).appendTo($slctNationality);
                            });

                            $.each(departmentResponse[0], function (i, option) {
                                $('<option>', option).appendTo($slctAddressDepartment);
                                $('<option>', option).appendTo($slctOccupationDepartment);
                            });

                            $.each(xhrOccupationResponse[0], function (i, option) {
                                $('<option>', option).appendTo($slctOccupation);
                            });

                            $.each(xhrStudyCenterResponse[0], function (i, option) {
                                $('<option>', option).appendTo($slctOccupation);
                            });

                            $slctDocument.selectpicker('refresh');
                            $slctGuardianDocument.selectpicker('refresh');
                            $slctNationality.selectpicker('refresh');
                            $slctAddressDepartment.selectpicker('refresh');
                            $slctOccupation.selectpicker('refresh');
                            $slctOccupationDepartment.selectpicker('refresh');
                        });
                })();

                //onStudentDocument();
                //onStudentBirthday();
                //onMobileNumberLoad();
                //onOccupation();
                //onOccupationLocation();

                $slctDocument.attr('required', true).on('change', function () {
                    $txtDocument.val('');
                    onStudentDocument();
                });
                $txtDocument.attr('required', true);
                $txtEmail.attr('required', true);
                $slctSex.attr('required', true);
                $txtBirthdate.attr('required', true).change(function () {
                    onStudentBirthday()
                });
                $slctNationality.attr('required', true);
                $slctAddressDepartment.attr('required', true).on('change', function () {
                    var value = $(this).val();

                    $.getJSON(url, {a: 'get_provincia', uidid: value}, function (response) {
                        $.each(response, function (option) {
                            $('<option>', option).appendTo($slctAddressProvince);
                        });
                    });
                });
                $slctAddressProvince.attr('required', true);
                $slctAddressDistrict.attr('required', true);
                $slctLocation.attr('required', true);
                $txtMobilePhone.attr('required', true);
                $slctOccupation.on('change', function () {
                    onOccupation();
                    onOccupationLocation();
                });
                $slctOccupationDepartment.attr('required', true).on('change', function () {
                    onOccupationLocation();
                });
                $slctOccupationProvince.attr('required', true).on('change', function () {
                    onOccupationLocation();
                });
                $slctOccupationDistrict.attr('required', true).on('change', function () {
                    onOccupationLocation();
                });
            });
        })();
    </script>
<?php

Display::display_footer();
