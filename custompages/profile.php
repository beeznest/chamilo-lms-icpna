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
/**
 * Removes some unwanted elementend of the form object
 */

if (isset($content['form']->_elementIndex['status'])) {
    $content['form']->removeElement('status');
    $content['form']->removeElement('status');
}

$rootWeb = api_get_path('WEB_PATH');

Display::display_header(get_lang('Registration'));
?>

<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            <?php
                echo $content['social'];
                echo $content['menu']
            ?>
        </div>
    </div>
    <div class="col-md-9">
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
        $('#extra_birthday').change(function () {
            if (childChecker()) {

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

        return birthdayYear + 18 < nowYear;
    }
</script>
<?php

Display::display_footer();
