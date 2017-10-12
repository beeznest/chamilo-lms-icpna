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

$formErrros = $form->_errors;

Display::display_header(get_lang('EditInformation', null, 'es-icpna', true));

if (!$form->elementExists('extra_id_document_type') || !$form->elementExists('extra_id_document_number')
    || !$form->elementExists('extra_middle_name')
    || !$form->elementExists('extra_mothers_name')
    || !$form->elementExists('extra_sex')
    || !$form->elementExists('extra_birthdate')
    || !$form->elementExists('extra_nationality')
    || !$form->elementExists('extra_address_department')
    || !$form->elementExists('extra_address_province')
    || !$form->elementExists('extra_address_district')
    || !$form->elementExists('extra_address')
    || !$form->elementExists('extra_mobile_phone_number')
    || !$form->elementExists('extra_occupation')
    || !$form->elementExists('extra_occupation_department')
    || !$form->elementExists('extra_occupation_province')
    || !$form->elementExists('extra_occupation_district')
    || !$form->elementExists('extra_occupation_center_name_1')
    || !$form->elementExists('extra_occupation_center_name_2')
    || !$form->elementExists('extra_occupation_center_name_3')
    || !$form->elementExists('extra_occupation_center_name_4')
    || !$form->elementExists('extra_university_career')
    || !$form->elementExists('extra_guardian_id_document_type')
    || !$form->elementExists('extra_guardian_id_document_number')
    || !$form->elementExists('extra_guardian_name')
    || !$form->elementExists('extra_guardian_email')) {
    $form->display();
    Display::display_footer();
    exit;
}

$plugin = IcpnaUpdateUserPlugin::create();
$efv = new ExtraFieldValue('user');
$uididpersona = $efv->get_values_by_handler_and_field_variable(api_get_user_id(), 'uididpersona');
$form->addHidden('extra_uididpersona', $uididpersona['value']);
$form->setDefaults(
    $plugin->getUserInfo($uididpersona['value'])
);
$defaultValues = $form->exportValues();

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
if ($form->elementExists('official_code')) {
    $form->removeElement('official_code');
}
if ($form->elementExists('username')) {
    $form->removeElement('username');
}
if ($form->elementExists('language')) {
    $form->removeElement('language');
}

$formRenderer = $form->defaultRenderer();

$formRenderer->setElementTemplate(
    '
        <div class="form-group {error_class}">
            <label {label-for} class="col-sm-2 control-label {extra_label_class}" >{label}</label>
            <div class="col-sm-4">
                {element}
                <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
            </div>
    ',
    'extra_id_document_type'
);
$formRenderer->setElementTemplate(
    '
            <div class="col-sm-4">
                {element}
                <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
            </div>
        </div>        
    ',
    'extra_id_document_number'
);

$formRenderer->setElementTemplate(
    '
        <div class="form-group {error_class}">
            <label {label-for} class="col-sm-2 control-label {extra_label_class}" >{label}</label>
            <div class="col-sm-8">
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
    ',
    'extra_address_department'
);
$formRenderer->setElementTemplate(
    '
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
    ',
    'extra_address_province'
);
$formRenderer->setElementTemplate(
    '
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
            </div>
        </div>
    ',
    'extra_address_district'
);

$formRenderer->setElementTemplate(
    '
        <div class="form-group {error_class}">
            <label {label-for} class="col-sm-2 control-label {extra_label_class}" >{label}</label>
            <div class="col-sm-8">
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
    ',
    'extra_occupation_department'
);
$formRenderer->setElementTemplate(
    '
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
    ',
    'extra_occupation_province'
);
$formRenderer->setElementTemplate(
    '
                <p>
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </p>
            </div>
        </div>
    ',
    'extra_occupation_district'
);

$formRenderer->setElementTemplate(
    '
        <div id="guardian_div">
            <div class="form-group">
                <div class="col-sm-8 col-sm-offset-2">
                    <div class="alert alert-warning">
                    Si eres menor de edad, es necesario registrar los datos de tu padre, madre o apoderado
                    </div>
                </div>
            </div>
            <div class="form-group {error_class}">
                <label {label-for} class="col-sm-2 control-label {extra_label_class}" >{label}</label>
                <div class="col-sm-8">
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </div>
            </div>
    ',
    'extra_guardian_name'
);

$formRenderer->setElementTemplate(
    '
            <div class="form-group {error_class}">
                <label {label-for} class="col-sm-2 control-label {extra_label_class}" >{label}</label>
                <div class="col-sm-4">
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </div>
    ',
    'extra_guardian_id_document_type'
);
$formRenderer->setElementTemplate(
    '
                <div class="col-sm-4">
                    {element}
                    <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error -->
                </div>
            </div>
        </div><!-- #guardian_div -->
    ',
    'extra_guardian_id_document_number'
);

$form->removeElement('extra_id_document_type');
$form->addSelect(
    'extra_id_document_type',
    'Documento de identidad',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_id_document_type']
);

/** @var \HTML_QuickForm_select $slctSex */
$slctSex = $form->getElement('extra_sex');
$slctSex->clearOptions();
$slctSex->addOption(get_lang('SelectAnOption', null, 'spanish', true), '');
$slctSex->addOption('Masculino', 'M');
$slctSex->addOption('Femenino', 'F');

$form->removeElement('extra_nationality');
$form->addSelect(
    'extra_nationality',
    'Nacionalidad',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_nationality']
);

$form->removeElement('extra_address_department');
$form->addSelect(
    'extra_address_department',
    'Dirección (Departamento / Provincia / Distrito)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_address_department']
);

$form->removeElement('extra_address_province');
$form->addSelect(
    'extra_address_province',
    'Dirección (Provincia)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_address_province']
);

$form->removeElement('extra_address_district');
$form->addSelect(
    'extra_address_district',
    'Dirección (Distrito)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_address_district']
);

$form->removeElement('extra_occupation');
$form->addSelect(
    'extra_occupation',
    'Ocupación',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation']
);

$form->removeElement('extra_occupation_department');
$form->addSelect(
    'extra_occupation_department',
    'Dirección del centro de estudios/laboral (Departamento / Provincia / Distrito)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_department']
);

$form->removeElement('extra_occupation_province');
$form->addSelect(
    'extra_occupation_province',
    '',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_province']
);

$form->removeElement('extra_occupation_district');
$form->addSelect(
    'extra_occupation_district',
    '',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_district']
);

$form->removeElement('extra_occupation_center_name_1');
$form->addSelect(
    'extra_occupation_center_name_1',
    'Centro de estudios (escolar)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_center_name_1']
);

$form->removeElement('extra_occupation_center_name_2');
$form->addSelect(
    'extra_occupation_center_name_2',
    'Centro de estudios (técnico)',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_center_name_2']
);

$form->removeElement('extra_occupation_center_name_3');
$form->addSelect(
    'extra_occupation_center_name_3',
    'Universidad',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_occupation_center_name_3']
);

$form->removeElement('extra_university_career');
$form->addSelect(
    'extra_university_career',
    'Carrera universitaria',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_university_career']
);

$form->removeElement('extra_guardian_id_document_type');
$form->addSelect(
    'extra_guardian_id_document_type',
    'Documento de identidad del apoderado',
    ['' => get_lang('SelectAnOption', null, 'spanish', true)],
    ['id' => 'extra_guardian_id_document_type']
);

$form->removeElement('extra_guardian_name');
$form->addText('extra_guardian_name', 'Nombre y apellido del apoderado', false);

$form->removeElement('extra_guardian_email');
$form->addText('extra_guardian_email', 'Email del apoderado', false);
$form->addElement('html', '
<div class="form-group">
    <div class="col-md-8 col-md-offset-2">
        <div class="terms alert alert-warning">
            Para continuar debe aceptar nuestros
            <button type="button" class="btn btn-link btn-xs" data-toggle="modal" data-target="#terms-conditions">
                <b>términos y condiciones</b>
            </button>
            <div class="checkbox">
                <label>
                    <input type="checkbox" required id="chk_terms">
                    '.get_lang('IAcceptTermsAndConditions', null, 'spanish', true).'
                </label>
            </div>
        </div>
    </div>
</div>
', 'terms');

if (is_profile_editable()) {
    $form->addButtonUpdate(get_lang('SaveSettings', null, 'spanish', true), 'apply_change');
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
    'email'
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
    'extra_guardian_name'
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
    $form->removeElement('phone', false),
    'extra_mobile_phone_number'
);
$form->insertElementBefore(
    $form->removeElement('extra_address', false),
    'phone'
);

// Translate chamilo default profile elements
$form->getElement('firstname')->_label = get_lang('FirstName', null, 'es-icpna', true);
$form->getElement('firstname')->setAttribute('maxlength', 30);
$form->getElement('firstname')->setAttribute('pattern', '[a-zA-ZñÑ\s\-]+');
$form->getElement('firstname')->setAttribute('title', get_lang('OnlyLetters', null, 'spanish', true));
$form->getElement('extra_middle_name')->setAttribute('maxlength', 30);
$form->getElement('extra_middle_name')->setAttribute('pattern', '[a-zA-ZñÑ\s\-]+');
$form->getElement('extra_middle_name')->setAttribute('title', get_lang('OnlyLetters', null, 'spanish', true));
$form->getElement('lastname')->_label = get_lang('LastName', null, 'es-icpna', true);
$form->getElement('lastname')->setAttribute('maxlength', 30);
$form->getElement('lastname')->setAttribute('pattern', '[a-zA-ZñÑ\s\-]+');
$form->getElement('lastname')->setAttribute('title', get_lang('OnlyLetters', null, 'spanish', true));
$form->getElement('extra_mothers_name')->setAttribute('maxlength', 30);
$form->getElement('extra_mothers_name')->setAttribute('pattern', '[a-zA-ZñÑ\s\-]+');
$form->getElement('extra_mothers_name')->setAttribute('title', get_lang('OnlyLetters', null, 'spanish', true));
$form->getElement('email')->_label = get_lang('Email', null, 'spanish', true);
$form->getElement('email')->setAttribute('maxlength', 50);
$form->getElement('email')->setAttribute('type', 'email');
$form->getElement('picture')->_label = [
    get_lang('UpdateImage', null, 'spanish', true),
    get_lang('OnlyImagesAllowed', null, 'spanish', true)
];
$form->getElement('picture')->setAttribute('title', get_lang('OnlyImagesAllowed', null, 'spanish', true));
$form->getElement('phone')->_label = [
    get_lang('Phone', null, 'spanish', true),
    'Ejemplo: 017110000'
];
$form->getElement('phone')->setAttribute('maxlength', 12);
$form->getElement('phone')->setAttribute('pattern', '\\d{1,12}');
$form->getElement('extra_mobile_phone_number')->_label = [
    'Número de celular ('.get_lang('CountryDialCode', null, 'spanish', true).')',
    'Ejemplo: 987654321'
];
$form->getElement('extra_mobile_phone_number')->setAttribute('maxlength', 12);
$form->getElement('extra_mobile_phone_number')->setAttribute('placeholder', '978654321');
$form->getElement('extra_address')->_label = get_lang('AddressField', null, 'spanish', true);
$form->getElement('extra_address')->setAttribute('maxlength', 100);
$form->getElement('extra_sex')->_label = get_lang('UserSex', null, 'spanish', true);
$form->getElement('extra_guardian_name')->setAttribute('maxlength', 60);
$form->getElement('extra_guardian_name')->setAttribute('pattern', '[a-zA-ZñÑ\s\-]+');
$form->getElement('extra_guardian_email')->setAttribute('maxlength', 50);
?>

    <div class="row">
        <div class="col-md-10">
            <?php foreach ($formErrros as $name => $error) { ?>
                <div id="registration-form-error" class="alert alert-danger">
                    <strong><?php
                        $label = $form->getElement($name)->getLabel();

                        if (is_array($label)) {
                            echo implode('<br>', $label);
                        } else {
                            echo $label.'<br>'.$error;
                        }
                        ?></strong>
                </div>
            <?php } ?>
            <div class="box box-primary">
                <div class="box-body">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo get_lang('EditInformation', null, 'es-icpna', true); ?></h3>
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
                    <h4 class="modal-title">Política de Privacidad de Datos</h4>
                </div>
                <div class="modal-body">
                    <p id="title-modal" class="text-center"></p>
                    <p id="text-modal" class="text-justify"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo get_lang('Close', null, 'spanish', true) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            $.fn.required = function (isRequired) {
                var label = this
                    .parents('.form-group')
                    .find('label');

                if (isRequired) {
                    this.attr('required', true);
                    label
                        .not(':has(span.form_required)')
                        .prepend('<span class="form_required">* </span>');

                    return this;
                }

                this.removeAttr('required');
                label.find('span.form_required').remove();

                return this;
            };

            $(document).ready(function () {
                var $slctDocument = $('#extra_id_document_type'),
                    $txtDocument = $('#extra_id_document_number'),
                    $txtFirstname = $('#profile_firstname'),
                    $txtLastname = $('#profile_lastname'),
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
                    $slctGuardianDocument = $('#extra_guardian_id_document_type'),
                    $txtGuardianDocument = $('#extra_guardian_id_document_number'),
                    $slctAddressDepartment = $('#extra_address_department'),
                    $slctAddressProvince = $('#extra_address_province'),
                    $slctAddressDistrict = $('#extra_address_district'),
                    $slctLocation = $('#extra_address'),
                    $txtEmail = $('#profile_email'),
                    $slctSex = $('#extra_sex'),
                    $slctNationality = $('#extra_nationality'),
                    $txtBirthdate = $('#extra_birthdate'),
                    $txtPhone = $('#profile_phone'),
                    $txtMobilePhone = $('#profile_extra_mobile_phone_number'),
                    url = _p.web_plugin + 'icpna_update_user/ajax.php',
                    $form = $('form#profile');

                $form.get(0).onsubmit = null;
                $form.on('submit', function (e) {
                    if (!$('#chk_terms').prop('checked')) {
                        e.preventDefault();

                        return;
                    }

                    addProgress('profile');
                });

                function onDocumentIdTypeSelected(selectedIndex, $el) {
                    switch (selectedIndex) {
                        case 1:
                            $el.attr({
                                pattern: '\\d{8}',
                                maxlength: '8',
                                title: '<?php echo get_lang('OnlyNumbers', null, 'spanish', true) ?>'
                            });
                            break;
                        case 2:
                            $el.attr({
                                pattern: '[a-zA-Z0-9]{1,12}',
                                maxlength: '12',
                                title: '<?php echo get_lang('OnlyLettersAndNumbers', null, 'spanish', true) ?>'
                            });
                            break;
                        case 3:
                            $el.attr({
                                pattern: '[a-zA-Z0-9]{1,12}',
                                maxlength: '12',
                                title: '<?php echo get_lang('OnlyLettersAndNumbers', null, 'spanish', true) ?>'
                            });
                            break;
                    }
                }

                function checkAge() {
                    var extraBirthdayFieldValue = $txtBirthdate.val();

                    if (extraBirthdayFieldValue == null) {
                        return false;
                    }

                    var now = new moment(),
                        birthYear = new moment(extraBirthdayFieldValue),
                        age = now.diff(birthYear, 'years');

                    return age;
                }

                function onStudentBirthday() {
                    $txtGuardianName.val('').required(false);
                    $txtGuardianEmail.val('').required(false);
                    $slctGuardianDocument.val('').required(false);
                    $txtGuardianDocument.val('').required(false);

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

                    $txtGuardianName.required(true);
                    $txtGuardianEmail.required(true);
                    $slctGuardianDocument.required(true);
                    $txtGuardianDocument.required(true);

                    if (age >= 14 && age < 18) {
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

                        $divGuardian.show();

                        return;
                    }

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

                function onDepartmentSelected(selectedValue, $el) {
                    return $.getJSON(url, {a: 'get_provincia', uidid: selectedValue}, function (response) {
                        addOptions(response, $el);
                    });
                }

                function onProvinceSelected(selectedValue, $el) {
                    return $.getJSON(url, {a: 'get_distrito', uidid: selectedValue}, function (response) {
                        addOptions(response, $el);
                    });
                }

                function onOccupationSelected(selectedIndex) {
                    $slctOccupationName1.parents('.form-group').hide();
                    $slctOccupationName2.parents('.form-group').hide();
                    $slctOccupationName3.parents('.form-group').hide();
                    $txtOccupationName4.parents('.form-group').hide();
                    $slctUniCarrers.parents('.form-group').hide();

                    $slctOccupationName1.required(false).empty();
                    $slctOccupationName2.required(false).empty();
                    $slctOccupationName3.required(false).empty();
                    $txtOccupationName4.required(false);
                    $slctUniCarrers.required(false);

                    var type = $slctOccupation.find('option:selected').data('type') || '';

                    return $.getJSON(url, {
                        a: 'get_centroestudios',
                        type: type,
                        district: $slctOccupationDistrict.val()
                    }, function (response) {
                        switch (selectedIndex) {
                            case 1:
                                addOptions(response, $slctOccupationName1);
                                $slctOccupationName1.required(true).parents('.form-group').show();
                                break;
                            case 2:
                                addOptions(response, $slctOccupationName2);
                                $slctOccupationName2.required(true).parents('.form-group').show();
                                break;
                            case 3:
                                addOptions(response, $slctOccupationName3);
                                $slctOccupationName3.required(true).parents('.form-group').show();
                                $slctUniCarrers.required(true).parents('.form-group').show();
                                break;
                            case 4:
                            //no break
                            case 5:
                                $txtOccupationName4.required(true).parents('.form-group').show();
                                break;
                        }
                    });
                }

                function addOptions(options, $el) {
                    $el.empty();

                    $('<option>', {
                        value: '',
                        text: '<?php echo get_lang('SelectAnOption', null, 'spanish', true) ?>'
                    }).appendTo($el);

                    $.each(options, function (i, option) {
                        $('<option>', option).appendTo($el);
                    });

                    $el.selectpicker('refresh');
                }

                function validateDocumentNumbers() {
                    if (!$txtGuardianDocument.get(0).setCustomValidity) {
                        return;
                    }

                    var studentDocumentType = $slctDocument.prop('selectedIndex'),
                        studentDocumentNumber = $txtDocument.val().trim(),
                        guardianDocumentType = $slctGuardianDocument.prop('selectedIndex'),
                        guardianDocumentNumber = $txtGuardianDocument.val().trim();

                    if (studentDocumentNumber == guardianDocumentNumber
                        && studentDocumentType == guardianDocumentType) {
                        $txtGuardianDocument.get(0).setCustomValidity(
                            "El número de documento no puede ser igual al del estudiante: " +
                            $slctDocument.prop('selectedOptions')[0].text + ' - ' + studentDocumentNumber
                        );

                        return;
                    }

                    $txtGuardianDocument.get(0).setCustomValidity('');
                }

                (function () {
                    var defaultValues = <?php echo json_encode($defaultValues); ?>;

                    var xhrDocumentIdType = $.getJSON(url, {a: 'get_tipodocumento'}),
                        xhrNationality = $.getJSON(url, {a: 'get_nacionalidad'}),
                        xhrDepartment = $.getJSON(url, {a: 'get_departamento'}),
                        xhrOccupation = $.getJSON(url, {a: 'get_ocupacion'}),
                        xhrUniCareers = $.getJSON(url, {a: 'get_carrerauniversitaria'});

                    $
                        .when
                        .apply($, [
                            xhrDocumentIdType,
                            xhrNationality,
                            xhrDepartment,
                            xhrOccupation,
                            xhrUniCareers
                        ])
                        .then(function (
                            docTypeResponse,
                            nationalityResponse,
                            departmentResponse,
                            xhrOccupationResponse,
                            xhrUniCareersResponse
                        ) {
                            addOptions(docTypeResponse[0], $slctDocument);
                            addOptions(docTypeResponse[0], $slctGuardianDocument);
                            addOptions(nationalityResponse[0], $slctNationality);
                            addOptions(departmentResponse[0], $slctAddressDepartment);
                            addOptions(departmentResponse[0], $slctOccupationDepartment);
                            addOptions(xhrOccupationResponse[0], $slctOccupation);
                            addOptions(xhrUniCareersResponse[0], $slctUniCarrers);

                            $slctDocument.selectpicker('val', defaultValues.extra_id_document_type);
                            $slctGuardianDocument.selectpicker('val', defaultValues.extra_guardian_id_document_type);
                            $slctNationality.selectpicker('val', defaultValues.extra_nationality);
                            $slctAddressDepartment.selectpicker('val', defaultValues.extra_address_department);
                            $slctOccupation.selectpicker('val', defaultValues.extra_occupation);
                            $slctOccupationDepartment.selectpicker('val', defaultValues.extra_occupation_department);
                            $slctUniCarrers.selectpicker('val', defaultValues.extra_university_career);

                            onDocumentIdTypeSelected($slctDocument.prop('selectedIndex'), $txtDocument);
                            onDocumentIdTypeSelected($slctGuardianDocument.prop('selectedIndex'), $txtGuardianDocument);
                            onStudentBirthday();
                            onDepartmentSelected(defaultValues.extra_address_department, $slctAddressProvince)
                                .done(function () {
                                    $slctAddressProvince
                                        .selectpicker('val', defaultValues.extra_address_province);

                                    onProvinceSelected(defaultValues.extra_address_province, $slctAddressDistrict)
                                        .done(function () {
                                            $slctAddressDistrict
                                                .selectpicker('val', defaultValues.extra_address_district);
                                        });
                                });
                            onDepartmentSelected(defaultValues.extra_occupation_department, $slctOccupationProvince)
                                .done(function () {
                                    $slctOccupationProvince
                                        .selectpicker('val', defaultValues.extra_occupation_province);

                                    onProvinceSelected(defaultValues.extra_occupation_province, $slctOccupationDistrict)
                                        .done(function () {
                                            $slctOccupationDistrict
                                                .selectpicker('val', defaultValues.extra_occupation_district);

                                            onOccupationSelected($slctOccupation.prop('selectedIndex'))
                                                .done(function () {
                                                    $slctOccupationName1
                                                        .selectpicker('val', defaultValues.extra_occupation_center_name_1);
                                                    $slctOccupationName2
                                                        .selectpicker('val', defaultValues.extra_occupation_center_name_2);
                                                    $slctOccupationName3
                                                        .selectpicker('val', defaultValues.extra_occupation_center_name_3);
                                                });
                                        })
                                });

                            $('span.form_required + small')
                                .text('<?php echo get_lang('ThisFieldIsRequired', null, 'spanish', true) ?>');

                            validateDocumentNumbers();
                        });
                })();

                $slctDocument.required(true).on('change', function () {
                    $txtDocument.val('');
                    onDocumentIdTypeSelected(this.selectedIndex, $txtDocument);
                });
                $txtDocument.required(true);
                $txtFirstname.required(true);
                $txtLastname.required(true);
                $txtEmail.required(true);
                $slctSex.required(true);
                $txtBirthdate.required(true).change(function () {
                    onStudentBirthday();
                });
                $slctNationality.required(true);
                $slctAddressDepartment.required(true).on('change', function () {
                    onDepartmentSelected(this.value, $slctAddressProvince);
                    $slctAddressDistrict.empty().selectpicker('refresh');
                });
                $slctAddressProvince.required(true).on('change', function () {
                    onProvinceSelected(this.value, $slctAddressDistrict);
                });
                $slctAddressDistrict.required(true);
                $slctLocation.required(true);
                $txtMobilePhone.required(true).attr({
                    'pattern': '\\d{1,12}',
                    'title': $txtMobilePhone.attr('placeholder')
                });
                $slctOccupation.required(true).on('change', function () {
                    onOccupationSelected(this.selectedIndex);

                    $slctOccupationName1.empty().selectpicker('refresh');
                    $slctOccupationName2.empty().selectpicker('refresh');
                    $slctOccupationName3.empty().selectpicker('refresh');
                    $txtOccupationName4.val('');
                });
                $slctOccupationDepartment.required(true).on('change', function () {
                    $slctOccupationDistrict.empty().selectpicker('refresh');
                    $slctOccupationName1.empty().selectpicker('refresh');
                    $slctOccupationName2.empty().selectpicker('refresh');
                    $slctOccupationName3.empty().selectpicker('refresh');

                    onDepartmentSelected(this.value, $slctOccupationProvince);
                });
                $slctOccupationProvince.required(true).on('change', function () {
                    $slctOccupationName1.empty().selectpicker('refresh');
                    $slctOccupationName2.empty().selectpicker('refresh');
                    $slctOccupationName3.empty().selectpicker('refresh');

                    onProvinceSelected(this.value, $slctOccupationDistrict);
                });
                $slctOccupationDistrict.required(true).on('change', function () {
                    onOccupationSelected($slctOccupation.prop('selectedIndex'));
                });
                $slctGuardianDocument.on('change', function () {
                    $txtGuardianDocument.val('');
                    onDocumentIdTypeSelected(this.selectedIndex, $txtGuardianDocument);
                });
                $txtGuardianDocument.on('change', function () {
                    validateDocumentNumbers();
                });
            });
        })();
    </script>
<?php

Display::display_footer();
