<?php 
/* For licensing terms, see /license.txt */
/**
 * Redirect script
 * @package chamilo.custompages
 */
/**
 * Initialization
 */
//require_once('main/inc/global.inc.php'); 
require_once('language.php');
/**
 * Homemade micro-controller
 */
if (isset($_GET['loginFailed'])){
  if (isset($_GET['error'])) {
    switch ($_GET['error']) {
    case 'account_expired':
      $error_message = custompages_get_lang('AccountExpired');
      break;
    case 'account_inactive':
      $error_message = custompages_get_lang('AccountInactive');
      break;
    case 'user_password_incorrect':
      $error_message = custompages_get_lang('InvalidId');
      break;
    case 'access_url_inactive':
      $error_message = custompages_get_lang('AccountURLInactive');
      break;
    default : 
      $error_message = custompages_get_lang('InvalidId');
    }
  } else { 
    $error_message = get_lang('InvalidId');
  }
}

    $rootWeb = api_get_path('WEB_PATH');
/**
 * HTML output
 */
?>


<!DOCTYPE html>
<html>
<head>
    <title>V-learning</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="<?php echo $rootWeb ?>custompages/css/bootstrap.css" rel="stylesheet" media="screen">
    <link href="<?php echo $rootWeb ?>custompages/css/bootstrap-responsive.css" rel="stylesheet">
    <link href="<?php echo $rootWeb ?>custompages/css/font-awesome.min.css" rel="stylesheet" media="screen">
    <link href="<?php echo $rootWeb ?>custompages/css/style.css" rel="stylesheet" media="screen">
    <script src="<?php echo $rootWeb ?>custompages/js/jquery.js"></script>
    <script src="<?php echo $rootWeb ?>custompages/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if (top.location != location)
                top.location.href = document.location.href ;

            // Handler pour la touche retour
            $('input').keyup(function(e) {
                if (e.keyCode == 13) {
                    $('#login-form').submit();
                }
            });
        });
    </script>
</head>
<body>
<div class="page">
    <div class="container">
        <div class="row">

            <div class="span4 offset4">

                <!-- Login user -->
                <div id="login-user">
                    <div class="logo">
                        <img src="<?php echo $rootWeb ?>custompages/img/logo-vlearning.png">
                    </div>
                    <?php if (isset($content['info']) && !empty($content['info'] )) {
                        echo $content['info'];
                    }?>
                    <?php if (isset($error_message)) {
                        echo '<div class="alert alert-error">'.$error_message.'</div>';
                    }?>
                    <div id="login-form">
                        <form action="<?php echo api_get_path(WEB_PATH)?>index.php" method="post" id="login-form">
                            <div class="form-login">
                                <!-- Inicia el formulario de ingreso -->

                                <div class="input-prepend">
                                    <label for="username"><?php echo custompages_get_lang('User');?>:</label>
                                    <span class="add-on"><i class="fa fa-user"></i></span>
                                    <input type="text" id="username" name="login" value=""autofocus="1">
                                </div>
                                <div class="input-prepend">
                                    <label for="password"><?php echo custompages_get_lang('langPass');?>:</label>
                                    <span class="add-on"><i class="fa fa-lock"></i></span>
                                    <input type="password" id="password" name="password">
                                </div>
                                <div class="pass">
                                    <a href="<?php echo api_get_path(WEB_PATH)?>main/auth/inscription.php"><?php echo custompages_get_lang('langReg')?></a><br />
                                    <a href="<?php echo api_get_path(WEB_PATH)?>main/auth/lostPassword.php"><?php echo custompages_get_lang('langLostPassword')?></a>
                                </div>
                            </div>
                            <div class="ingresa">
                                <div id="entrar" class="btn btn-large btn-primary" type="button"  onclick="document.forms['login-form'].submit();"><?php echo custompages_get_lang('LoginEnter');?></div>
                            </div>
                        </form>
                    </div>
                    <!-- Termina el formulario de ingreso -->
                    <div class="logo-movistar"><img src="<?php echo $rootWeb ?>custompages/img/logo-movistar217x115.png"></div>
                </div>
                <!-- fin login user -->
            </div>
            <div class="span4">
                <div class="logo-icpna">
                    <img src="<?php echo $rootWeb ?>custompages/img/logo-icpna.png" class="pull-right">
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>