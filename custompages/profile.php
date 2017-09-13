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

// Deprecated since 2015-03-26
/**
 * Code to change the way QuickForm render html
 */
/*
$renderer = & $content['form']->defaultRenderer();
$form_template = <<<EOT

<form {attributes}>
{content}
  <div class="clear">
    &nbsp;
  </div>
  <p><a href="#" class="btn btn-primary" onclick="$('#registration-form').submit()"><span>S'inscrire</span></a></p>
</form>

EOT;
$renderer->setFormTemplate($form_template);

$element_template = <<<EOT
  <div class="field decalle">
    <label>
      <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
    </label>
    <div class="formw">
      <!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error --> {element}
    </div>
  </div>

EOT;
$element_template_wimage = <<<EOT
  <div class="field decalle display">
    <label>
      <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
    </label>
    <div class="formw">
      <!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error --> {element}
      <img src="/custompages/images/perso.jpg" alt="" />
    </div>
  </div>

EOT;
$renderer->setElementTemplate($element_template_wimage,'pass1');
$renderer->setElementTemplate($element_template);

$header_template = <<<EOT
  <div class="row">
    <div class="form_header">{header}</div>
  </div>

EOT;

 */
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
<?php

Display::display_footer();
