<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package chamilo.help
 */
/**
 * Code
 */

// Language file that needs to be included
$language_file = 'help';
require_once '../inc/global.inc.php';
$help_name = Security::remove_XSS($_GET['open']);

?>
<a class="btn" href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq'); ?></a>

<div class="page-header">
    <h3>Preguntas Frecuentes de Afiliación</h3>
</div>

<?php 
$faq_content = @(string)file_get_contents(api_get_path(SYS_PATH).'home/faq.html');
	$faq_content = api_to_system_encoding($faq_content, api_detect_encoding(strip_tags($faq_content)));
	echo $faq_content;