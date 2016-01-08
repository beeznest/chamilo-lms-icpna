<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$type = $_REQUEST['type'];
$src  = Security::remove_XSS($_REQUEST['source']);
if (empty($type) || empty($src)) {
    api_not_allowed();
}

$iframe = '';
switch ($type) {
    case 'youtube':
        if ($_SERVER['HTTPS']) {
            $src = 'https://www.youtube.com/embed/'.$src;
        } else {
            $src = 'http://www.youtube.com/embed/'.$src;
        }
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe class="youtube-player" type="text/html" width="640" height="385" src="'.$src.'" frameborder="0"></iframe>';
        $iframe .= '</div>';
        break;
    case 'vimeo':
        if ($_SERVER['HTTPS']) {
            $src = 'https://player.vimeo.com/video/'.$src;
        } else {
            $src = 'http://player.vimeo.com/video/'.$src;
        }
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe src="'.$src.'" width="640" height="385" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        $iframe .= '</div>';
        break;
    case 'nonhttps':
        $iframe = '<a href="' . $src . '" target="_blank" style="font-family: arial; color: #666;">' . $src . '</a>';
        break;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
</head>
<body>
<?php echo $iframe; ?>
</body>
</html>
