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
$form->addHtml('<div class="alert alert-warning">If you are under age it is necessary to register the data of your guardian</div>');
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
<script>
    (function () {
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
