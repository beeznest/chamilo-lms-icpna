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

$form = isset($content['form']) ? $content['form'] : null;

/**
 * Removes some unwanted elementend of the form object
 */

if (isset($form->_elementIndex['status'])) {
    $form->removeElement('status');
    $form->removeElement('status');
}
if (isset($form->_elementIndex['extra_skype'])) {
    $form->removeElement('extra_skype');
}
if (isset($form->_elementIndex['extra_linkedin_url'])) {
    $form->removeElement('extra_linkedin_url');
}

if (isset($form->_elementIndex['apply_change'])) {
    $form->removeElement('apply_change');
}

if (isset($form->_elementIndex['extra_guardian_name'])) {
    $form->removeElement('extra_guardian_name');
}

if (isset($form->_elementIndex['extra_guardian_email'])) {
    $form->removeElement('extra_guardian_email');
}

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
');
$form->addText('extra_guardian_name', 'Guardian Name');
$form->addText('extra_guardian_email', 'Guardian Email');
$form->addElement('html', '</div>', 'guardian_section');
$form->addElement('html', '
<div class="form-group">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div class="terms alert alert-warning">Before Saving you need to be agreed with our
            <button type="button" class="btn btn-link" data-toggle="modal" data-target="#terms-conditions">
                <b>TERMS AND CONDITIONS</b>
            </button>
        </div>
    </div>
    <div class="col-md-2"></div>
</div>
');

if (is_profile_editable()) {
    $form->addButtonUpdate(get_lang('SaveSettings'), 'apply_change');
} else {
    $form->freeze();
}

$elements = $form->getElements();

foreach ($elements as $element) {

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'firstname') {
        $form->insertElementBefore($form->removeElement('extra_document_type', false), 'firstname');
    }

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'lastname') {
        $form->insertElementBefore($form->removeElement('extra_middle_name', false), 'lastname');
    }

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'lastname') {
        $form->insertElementBefore($form->removeElement('extra_middle_name', false), 'lastname');
    }

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'username') {
        $form->insertElementBefore($form->removeElement('extra_mother_name', false), 'username');
    }

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'guardian_section') {
        //$form->insertElementBefore($form->removeElement('extra_guardian_document', false), 'guardian_section');
    }
}

$rootWeb = api_get_path('WEB_PATH');

Display::display_header(get_lang('Registration'));
?>

<div class="row">
    <div class="col-md-10">
        <?php if (isset($content['error']) && !empty($content['error'])) {
            echo '<div id="registration-form-error" class="alert alert-danger">'.$content['error'].'</div>';
        }?>
        
            <div class="box box-primary">
                <div class="box-body">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo get_lang('ModifProfile'); ?></h3>
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
            var FrmProfile = {
                $slctDocument: $('#slct_extra_document_type'),
                $txtDocument: $('#txt_extra_document_type'),
                $txtBirthday: $('#extra_birthday'),
                $divGuardian: $('#guardian_div'),
                $modalTitle: $('#title-modal'),
                $modalText: $('#text-modal'),
                $slctOcupation: $('#extra_ocupation'),
                $slctOcupationLocationFirst: $('#first_extrastudy_job_center_ubication'),
                $slctOcupationLocationSecond: $('#second_extrastudy_job_center_ubication'),
                $slctOcupationLocationThird: $('#thrid_extrastudy_job_center_ubication'),
                $txtOcupationName1: $('#extra_study_center_name_o1'),
                $txtOcupationName2: $('#extra_study_center_name_o2'),
                $txtOcupationName3: $('#extra_study_center_name_o3'),
                $txtOcupationName4: $('#extra_study_center_name_o4'),
                $slctUniCarrers: $('#extra_university_careers'),
                onStudentDocument: function () {
                    this.$txtDocument.val('');

                    switch (this.$slctDocument.prop('selectedIndex')) {
                        case 1:
                            this.$txtDocument
                                .attr('pattern', '\\d{8}')
                                .attr('maxlength', '8');
                            break;
                        case 2:
                            this.$txtDocument
                                .attr('pattern', '')
                                .attr('maxlength', '');
                            break;
                        case 3:
                            this.$txtDocument
                                .attr('pattern', '\\d{9}')
                                .attr('maxlength', '9');
                            break;
                    }
                },
                checkAge: function () {
                    var extraBirthdayFieldValue = this.$txtBirthday.val();

                    if (extraBirthdayFieldValue == null) {
                        return false;
                    }

                    var now = new moment(),
                        birthYear = new moment(extraBirthdayFieldValue),
                        age = now.diff(birthYear, 'years');

                    return age;
                },
                onStudentBirthday: function () {
                    var age = this.checkAge();

                    if (age >= 18) {
                        this.$divGuardian.hide();
                        this.$modalTitle.html('<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MAYOR DE 18 AÑOS DE EDAD</h3>');
                        this.$modalText.html(
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
                        this.$modalTitle.html(
                            '<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MAYOR DE 14 Y MENOR A 18 AÑOS</h3>'
                        );
                        this.$modalText.html(
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

                    this.$modalTitle.html('<h3>DECLARACION DE PROTECCION DE DATOS PERSONALES MENOR DE 14 AÑOS DE EDAD</h3>');
                    this.$modalText.html(
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

                    this.$divGuardian.show();
                },
                onOcupation: function () {
                    this.$txtOcupationName1.parents('.form-group').hide();
                    this.$txtOcupationName2.parents('.form-group').hide();
                    this.$txtOcupationName3.parents('.form-group').hide();
                    this.$txtOcupationName4.parents('.form-group').hide();
                    this.$slctUniCarrers.parents('.form-group').hide();

                    switch (this.$slctOcupation.prop('selectedIndex')) {
                        case 1:
                            this.$txtOcupationName1.parents('.form-group').show();
                            break;
                        case 2:
                            this.$txtOcupationName2.parents('.form-group').show();
                            break;
                        case 3:
                            this.$txtOcupationName3.parents('.form-group').show();
                            this.$slctUniCarrers.parents('.form-group').show();
                            break;
                        case 4:
                            this.$txtOcupationName4.parents('.form-group').show();
                            break;
                    }
                },
                onOcupationLocation: function () {
                    var firstValue = this.$slctOcupationLocationFirst.find('option:selected').data('value') || '',
                        secondValue = this.$slctOcupationLocationSecond.find('option:selected').data('value') || '',
                        thirdValue = this.$slctOcupationLocationThird.find('option:selected').data('value') || '';

                    var ubigeo = firstValue + '' + secondValue + thirdValue;

                    this.$txtOcupationName1.find('option').show();
                    this.$txtOcupationName1.find('option:not([data-value="' + ubigeo + '"])').hide();
                    this.$txtOcupationName1.selectpicker('refresh');

                    this.$txtOcupationName2.find('option').show();
                    this.$txtOcupationName2.find('option:not([data-value="' + ubigeo + '"])').hide();
                    this.$txtOcupationName2.selectpicker('refresh');

                    this.$txtOcupationName3.find('option').show();
                    this.$txtOcupationName3.find('option:not([data-value="' + ubigeo + '"])').hide();
                    this.$txtOcupationName3.selectpicker('refresh');
                }
            };

            $(document).ready(function () {
                FrmProfile.onStudentBirthday();
                FrmProfile.$txtBirthday.change(function () {
                    FrmProfile.onStudentBirthday()
                });

                FrmProfile.onStudentDocument();
                FrmProfile.$slctDocument.on('change', function () {
                    FrmProfile.onStudentDocument();
                });

                FrmProfile.onOcupation();
                FrmProfile.$slctOcupation.on('change', function () {
                    FrmProfile.onOcupation();
                });

                FrmProfile.$slctOcupationLocationFirst.on('change', function () {
                    FrmProfile.onOcupationLocation();
                });
                FrmProfile.$slctOcupationLocationSecond.on('change', function () {
                    FrmProfile.onOcupationLocation();
                });
                FrmProfile.$slctOcupationLocationThird.on('change', function () {
                    FrmProfile.onOcupationLocation();
                });
            });
        })();
    </script>
<?php

Display::display_footer();
