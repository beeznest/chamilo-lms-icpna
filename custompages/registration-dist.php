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

if (isset($form->_elementIndex['official_code'])) {
    $form->removeElement('official_code');
}

if (isset($form->_elementIndex['extra_linkedin_url'])) {
    $form->removeElement('extra_linkedin_url');
}

if (isset($form->_elementIndex['submit'])) {
    $form->removeElement('submit');
}

if (isset($form->_elementIndex['extra_guardian_name'])) {
    $form->removeElement('extra_guardian_name');
}

if (isset($form->_elementIndex['extra_guardian_email'])) {
    $form->removeElement('extra_guardian_email');
}

$form->addElement('html', '<div id="guardian_div">');
$form->addText('extra_guardian_name', 'Guardian Name');
$form->addText('extra_guardian_email', 'Guardian Email');
$form->addElement('html', '</div>', 'guardian_section');

$form->addButtonCreate(get_lang('RegisterUser'));

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

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'email') {
        $form->insertElementBefore($form->removeElement('extra_mother_name', false), 'email');
        $form->insertElementBefore($form->removeElement('username', false), 'email');
    }

    if (isset($element->_attributes['name']) && $element->_attributes['name'] == 'guardian_section') {
        $form->insertElementBefore($form->removeElement('extra_guardian_document', false), 'guardian_section');
    }
}

$rootWeb = api_get_path('WEB_PATH');

Display::display_header(get_lang('Registration'));
?>

    <div class="row">
        <div class="col-md-12">
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
    <script>
        $(document).ready(function () {

            $('#guardian_div').hide();

            $('#extra_birthday').change(function () {
                console.log(childChecker());
                if (childChecker()) {
                    $('#guardian_div').show();
                } else {
                    $('#guardian_div').hide();
                }
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
    </script>
<?php

Display::display_footer();
