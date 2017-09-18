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
$form->addHtml('<div class="alert alert-warning">If you are under age it is necessary to register the data of your guardian, also you need to be agree with our <a data-toggle="modal" data-target="#terms-conditions"><b>TERMS AND CONDITIONS</b></a></div>');
$form->addText('extra_guardian_name', 'Guardian Name');
$form->addText('extra_guardian_email', 'Guardian Email');
$form->addElement('html', '</div>', 'guardian_section');

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
        $form->insertElementBefore($form->removeElement('extra_guardian_document', false), 'guardian_section');
    }
}

$rootWeb = api_get_path('WEB_PATH');

Display::display_header(get_lang('Registration'));
?>

<div class="row">
    <div class="col-md-2">
        <div id="image-message-container">
            <a class="expand-image" href="<?php echo $content['big_image'] ?>">
                <img src="<?php echo $content['normal_image'] ?>" class="img-thumbnail img-responsive">
            </a>
        </div>
    </div>
    <div class="col-md-10">
        <?php if (isset($content['error']) && !empty($content['error'])) {
            echo '<div id="registration-form-error" class="alert alert-danger">'.$content['error'].'</div>';
        }?>
        <div id="registration-form-box" class="form-box">
            <?php
            $content['form']->display();
            ?>
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
                <p class="text-center">DECLARACION DE PROTECCION DE DATOS PERSONALES MAYOR DE 14 Y MENOR A 18 A&Ntilde;OS</p>
                <p class="text-justify" style="text-align: justify;">En aplicaci&oacute;n a lo dispuesto por la Ley 29733 Ley de Protecci&oacute;n de Datos Personales, y el D.S. 003-2013-JUS, y en especial en el art&iacute;culo 28 del D.S.003-2013-JUS, el suscrito menor de edad, titular de mis datos personales, mediante el llenado y/o firma del presente formulario, autorizo de forma expresa e inequ&iacute;voca y por tiempo indefinido que mis datos personales, sean tratados, almacenados, sistematizados y utilizados por el INSTITUTO CULTURAL PERUANO NORTEAMERICANO para fines estad&iacute;sticos, administrativos y de gesti&oacute;n comercial, incluyendo invitaciones a cursos, talleres, charlas y otros eventos que el ICPNA organice, auspicie o participe, siendo que los datos, ser&aacute;n conservados en un banco de datos cuyo titular es el ICPNA, autorizando incluso el flujo transfronterizo con fines acad&eacute;micos y/o destinado a la administraci&oacute;n de ex&aacute;menes internacionales. Asimismo, declaro que estoy informado que ante alguna solicitud de datos personales sensibles, es mi facultad responder o no sobre los mismos e igualmente declaro conocer los efectos y/o consecuencias de proporcionar mis datos personales o de negarme a brindarlos. Igualmente declaro conocer que para ejercer mis derechos como acceso, rectificaci&oacute;n, cancelaci&oacute;n y oposici&oacute;n y otros derechos, sobre mis datos puedo dirigirme a las oficinas, ubicadas en Av. Angamos Oeste 120, Miraflores . Se&ntilde;alo tambi&eacute;n, que al ser yo menor de edad, el ICPNA no me est&aacute; solicitando datos relativos a la actividad profesional o laboral de mis padres, ni su informaci&oacute;n econ&oacute;mica, datos sociol&oacute;gicos o de cualquier otro sobre los dem&aacute;s miembros de mi familia. Asimismo, declaro que estoy informando al ICPNA de la identidad y direcci&oacute;n de mis padres, a fin de que ellos puedan autorizar el tratamiento de mis datos personales, en aquellos casos en que mi propia autorizaci&oacute;n no fuera suficiente y que est&eacute;n referidas al acceso a actividades, vinculadas con bienes o servicios que est&eacute;n restringidos para mayores de edad</p>
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
            studentDocument: function () {
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
            }
        }

        $(document).ready(function () {
            if (childChecker()) {
                $('#guardian_div').show();
            } else {
                $('#guardian_div').hide();
            }

            $('#extra_birthday').change(function () {
                if (childChecker()) {
                    $('#guardian_div').fadeIn();
                } else {
                    $('#guardian_div').fadeOut();
                }
            });

            FrmProfile.studentDocument();

            FrmProfile.$slctDocument.on('change', function () {
                FrmProfile.studentDocument();
            });
        });

        function childChecker() {
            var extraBirthdayFieldValue = $('#extra_birthday').val();

            if (extraBirthdayFieldValue == null) {
                return false;
            }

            var birthday = new Date(extraBirthdayFieldValue);
            var birthdayYear = birthday.getFullYear();
            var now = new Date();
            var nowYear = now.getFullYear();

            return birthdayYear + 18 > nowYear;
        }
    })();
</script>
<?php

Display::display_footer();
