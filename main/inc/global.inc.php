<?php
/* For licensing terms, see /license.txt */

/**
 * It is recommended that ALL Chamilo scripts include this important file.
 * This script manages
 * - http get, post, post_files, session, server-vars extraction into global namespace;
 *   (which doesn't occur anymore when servertype config setting is set to test,
 *    and which will disappear completely in Dokeos 1.6.1)
 * - include of /conf/configuration.php;
 * - include of several libraries: main_api, database, display, text, security;
 * - selecting the main database;
 * - include of language files.
 *
 * @package chamilo.include
 * @todo isn't configuration.php renamed to configuration.inc.php yet?
 * @todo use the $_configuration array for all the needed variables
 * @todo remove the code that displays the button that links to the install page
 * 		but use a redirect immediately. By doing so the $already_installed variable can be removed.
 * @todo make it possible to enable / disable the tracking through the Chamilo config page.
 *
 */

 //@todo will be removed before a stable release

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
define('START', $starttime);

// Showing/hiding error codes in global error messages.
define('SHOW_ERROR_CODES', false);

// Determine the directory path where this current file lies.
// This path will be useful to include the other intialisation files.
$includePath = dirname(__FILE__);

// @todo Isn't this file renamed to configuration.inc.php yet?
// Include the main Chamilo platform configuration file.
$main_configuration_file_path = $includePath.'/conf/configuration.php';

if (!empty($_GET['r']) && (int)$_GET['r'] > 0) {
    setcookie('room', $_GET['r'], time()+3600*24*365*10, '/'); //Ten Years
    $uri = preg_replace('/r='.$_GET['r'].'/','',$_SERVER['REQUEST_URI']);
    header('location: '.$uri);
    exit();
}

$already_installed = false;
if (file_exists($main_configuration_file_path)) {
    require_once $main_configuration_file_path;
    $already_installed = true;
} else {
    $_configuration = array();
}

//Redirects to the main/install/ page
if (!$already_installed) {
    $global_error_code = 2;
    // The system has not been installed yet.
    require $includePath.'/global_error_message.inc.php';
    die();
}

// Ensure that _configuration is in the global scope before loading
// main_api.lib.php. This is particularly helpful for unit tests
if (!isset($GLOBALS['_configuration'])) {
    $GLOBALS['_configuration'] = $_configuration;
}

// Code for trnasitional purposes, it can be removed right before the 1.8.7 release.
if (empty($_configuration['system_version'])) {
    $_configuration['system_version']   = (!empty($_configuration['dokeos_version'])?$_configuration['dokeos_version']:'');
    $_configuration['system_stable']    = (!empty($_configuration['dokeos_stable'])?$_configuration['dokeos_stable']:'');
    $_configuration['software_url']     = 'http://www.chamilo.org/';
}

// For backward compatibility.
$_configuration['dokeos_version']       = $_configuration['system_version'];
$_configuration['dokeos_stable']        = $_configuration['system_stable'];
$userPasswordCrypted                    = (!empty($_configuration['password_encryption']) ? $_configuration['password_encryption'] : 'sha1');

// Include the main Chamilo platform library file.
require_once $includePath.'/lib/main_api.lib.php';


// Specification for usernames:
// 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
// 2. Empty username is formally valid, but it is reserved for the anonymous user.
// 3. Checking the login_is_email portal setting in order to accept 100 chars maximum

$default_username_length = 40;
if (api_get_setting('login_is_email') == 'true') {
    $default_username_length = 100;
}

define('USERNAME_MAX_LENGTH', $default_username_length);

// Do not over-use this variable. It is only for this script's local use.
$lib_path = api_get_path(LIBRARY_PATH);

// Fix bug in IIS that doesn't fill the $_SERVER['REQUEST_URI'].
api_request_uri();

// Add the path to the pear packages to the include path
ini_set('include_path', api_create_include_path_setting());

// This is for compatibility with MAC computers.
ini_set('auto_detect_line_endings', '1');

//Include the libraries that are necessary everywhere
//require_once dirname(__FILE__).'/autoload.inc.php';

//Fixes Htmlpurifier autoloader issue with composer
define('HTMLPURIFIER_PREFIX', $lib_path.'htmlpurifier/library');

//mpdf constants
define("_MPDF_TEMP_PATH", api_get_path(SYS_ARCHIVE_PATH));
define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');

//Composer autoloader
require_once __DIR__.'../../../vendor/autoload.php';

//Start Silex
use Silex\Application;

$app = new Application();
$app['configuration_file'] = $main_configuration_file_path;

//require_once __DIR__.'/../../resources/config/prod.php';
require_once __DIR__.'/../../resources/config/dev.php';

//Setting HttpCacheService provider in order to use do: $app['http_cache']->run();
/*
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => $app['http_cache.cache_dir'].'/',
));*/

//$app->register(new Silex\Provider\SessionServiceProvider());

//URL generator provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(),array(
    'locale_fallback' => 'en'
));

//Form provider
$app->register(new Silex\Provider\FormServiceProvider());

//Monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => api_get_path(SYS_ARCHIVE_PATH).'chamilo_development.log',
    'monolog.name'    => 'chamilo',
));

/*
//Monolog examples
$app['monolog']->addDebug('Testing the Monolog logging.');
$app['monolog']->addInfo('Testing the Monolog logging.');
$app['monolog']->addError('Testing the Monolog logging.');
*/
$app['translator.messages'] = array();

//Setting the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        api_get_path(SYS_CODE_PATH).'template', //template folder
        api_get_path(SYS_PLUGIN_PATH)             //plugin folder
    ),
    'twig.form.templates' => array('form_div_layout.html.twig', 'default/form/form_custom_template.tpl'),
    'twig.options'          => array(
        'debug'             => $app['debug'],
        'charset'           => 'utf-8',
        'strict_variables'  => false,
        'autoescape'        => false,
        'cache'             => $app['debug'] ? false : $app['cache.path'].'twig',
        'optimizations' => -1, // turn on optimizations with -1
    )
));

//Setting Twig options
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter('get_lang', new Twig_Filter_Function('get_lang'));
    $twig->addFilter('get_path', new Twig_Filter_Function('api_get_path'));
    $twig->addFilter('get_setting', new Twig_Filter_Function('api_get_setting'));
    $twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
    $twig->addFilter('return_message', new Twig_Filter_Function('Display::return_message_and_translate'));
    $twig->addFilter('display_page_header', new Twig_Filter_Function('Display::page_header_and_translate'));
    $twig->addFilter('display_page_subheader', new Twig_Filter_Function('Display::page_subheader_and_translate'));
    $twig->addFilter('icon', new Twig_Filter_Function('Template::get_icon_path'));
    $twig->addFilter('format_date', new Twig_Filter_Function('Template::format_date'));
    return $twig;
}));

//Setting Doctrine service provider (DBAL)
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $_configuration['main_database'],
        'user'      => $_configuration['db_user'],
        'password'  => $_configuration['db_password'],
        'host'      => $_configuration['db_host'],
    )
));

//Setting Doctrine ORM
$app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider, array(
    "orm.proxies_dir" => $app['db.orm.proxies_dir'],
    "orm.em.options" => array(
        "mappings" => array(
            array(
                "type" => "annotation",
                "namespace" => "Entity",
                "path" => api_get_path(INCLUDE_PATH).'Entity',
            )
        ),
    ),
));

//Setting Doctrine2 extensions

$timestampableListener = new \Gedmo\Timestampable\TimestampableListener();
$app['db.event_manager']->addEventSubscriber($timestampableListener);

$sluggableListener = new \Gedmo\Sluggable\SluggableListener();
$app['db.event_manager']->addEventSubscriber($sluggableListener);

$sortableListener = new \Gedmo\Sortable\SortableListener();
$app['db.event_manager']->addEventSubscriber($sortableListener);


//Testing with another silex service provider
/*
// Register Doctrine ORM
$app->register(new Nutwerk\Provider\DoctrineORMServiceProvider(), array(
    'db.orm.proxies_dir'           => $app['db.orm.proxies_dir'],
    'db.orm.proxies_namespace'     => 'DoctrineProxy',
    'db.orm.cache'                 =>
        !$app['debug'] && extension_loaded('apc') ? new ApcCache() : new ArrayCache(),
    'db.orm.auto_generate_proxies' => true,
    'db.orm.entities'              => array(array(
        'type'      => 'annotation',       // entity definition
        'path'      => api_get_path(INCLUDE_PATH).'Entity',
        'namespace' => 'Entity', // your classes namespace
    )),
));*/


//Creating Chamilo service provider
use Silex\ServiceProviderInterface;

class ChamiloServiceProvider implements ServiceProviderInterface {
    public function register(Application $app) {
        //Template
        $app['template'] = $app->share(function() use($app){
            return new Template(null, $app);
        });
    }
    public function boot(Application $app) {
    }
}

//Registering Chamilo service provider
$app->register(new ChamiloServiceProvider(), array());

//Controllers
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app['pages.controller'] = $app->share(function() use ($app) {
    return new PagesController($app['pages.repository']);
});

//Manage error messages
/*
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        //return;
    }
    if (isset($code)) {
        switch ($code) {
             case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                //$message = 'We are sorry, but something went terribly wrong.';
                $message = $e->getMessage();
        }
    } else {
        $code = null;
        $message = null;
    }
    //$code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    $app['template']->assign('error_code', $code);
    $app['template']->assign('error_message', $message);
    $response = $app['template']->render_layout('error.tpl');
    return new Response($response);
});
 *
 */

/*
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
ErrorHandler::register();
 */

//Filters
$app->before(function() use ($app) {
    //Check the PHP version
    if (api_check_php_version() == false) {
        $app->abort(500, "Incorrect PHP version"); //error 1
    }

    if (!file_exists($app['configuration_file'])) {
        $app->abort(500, "Chamilo has not been installed"); //error 2
    }
    //$app['request']->getSession()->start();
});

$app->after(function() {
});

$app->finish(function() use ($app) {
    //@todo will be removed before a stable release
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];

    $message = "Page loaded in:".($mtime-START);
    $app['monolog']->addInfo($message);
    $message = "memory_get_usage: ".format_file_size(memory_get_usage(true));
    $app['monolog']->addInfo($message);
    $message = "memory_get_peak_usage: ".format_file_size(memory_get_peak_usage(true));
    $app['monolog']->addInfo($message);
});

$app['template.show_header']        = true;
$app['template.show_footer']        = true;
$app['template.show_learnpath']     = true;
$app['template.hide_global_chat']   = true;
$app['template.load_plugins']       = true;

//Default template style
$app['template_style'] = 'default';
$app['default_layout'] = 'layout_1_col.tpl';


//Database constants
require_once $lib_path.'database.constants.inc.php';
require_once $lib_path.'fileManage.lib.php';
require_once $lib_path.'text.lib.php';
require_once $lib_path.'array.lib.php';
require_once $lib_path.'events.lib.inc.php';
require_once $lib_path.'online.inc.php';


/*  DATABASE CONNECTION  */

// @todo: this shouldn't be done here. It should be stored correctly during installation.
if (empty($_configuration['statistics_database']) && $already_installed) {
    $_configuration['statistics_database'] = $_configuration['main_database'];
}

global $database_connection;
// Connect to the server database and select the main chamilo database.
if (!($conn_return = @Database::connect(
    array(
        'server'        => $_configuration['db_host'],
        'username'      => $_configuration['db_user'],
        'password'      => $_configuration['db_password'],
        'persistent'    => $_configuration['db_persistent_connection'] // When $_configuration['db_persistent_connection'] is set, it is expected to be a boolean type.
    )))) {
    $app->abort(500, "Database is unavailable"); //error 3
}

if (!$_configuration['db_host']) {
    $app->abort(500, "Database is unavailable"); //error 3
}


/* RETRIEVING ALL THE CHAMILO CONFIG SETTINGS FOR MULTIPLE URLs FEATURE*/
if (!empty($_configuration['multiple_access_urls'])) {
    $_configuration['access_url'] = 1;
    $access_urls = api_get_access_urls();

    $protocol = ((!empty($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') ? 'https' : 'http').'://';
    $request_url1 = $protocol.$_SERVER['SERVER_NAME'].'/';
    $request_url2 = $protocol.$_SERVER['HTTP_HOST'].'/';

    foreach ($access_urls as & $details) {
        if ($request_url1 == $details['url'] or $request_url2 == $details['url']) {
            $_configuration['access_url'] = $details['id'];
        }
    }
} else {
    $_configuration['access_url'] = 1;
}

// The system has not been designed to use special SQL modes that were introduced since MySQL 5.
Database::query("set session sql_mode='';");

if (!Database::select_db($_configuration['main_database'], $database_connection)) {
    $app->abort(500, "Database is unavailable"); //error 3
}

/*   Initialization of the default encodings */

// The platform's character set must be retrieved at this early moment.
$sql = "SELECT selected_value FROM settings_current WHERE variable = 'platform_charset';";

$result = Database::query($sql);
while ($row = @Database::fetch_array($result)) {
    $charset = $row[0];
}
if (empty($charset)) {
    $charset = 'UTF-8';
}

// Preserving the value of the global variable $charset.
$charset_initial_value = $charset;

// Initialization of the internationalization library.
api_initialize_internationalization();
// Initialization of the default encoding that will be used by the multibyte string routines in the internationalization library.
api_set_internationalization_default_encoding($charset);

// Initialization of the database encoding to be used.
Database::query("SET SESSION character_set_server='utf8';");
Database::query("SET SESSION collation_server='utf8_general_ci';");

if (api_is_utf8($charset)) {
    // See Bug #1802: For UTF-8 systems we prefer to use "SET NAMES 'utf8'" statement in order to avoid a bizarre problem with Chinese language.
    Database::query("SET NAMES 'utf8';");
} else {
    Database::query("SET CHARACTER SET '" . Database::to_db_encoding($charset) . "';");
}
Database::query("SET NAMES 'utf8';");

// Start session after the internationalization library has been initialized
//@todo user silex session provider instead of a custom class
Chamilo::session()->start($already_installed);

$settings_refresh_info = api_get_settings_params_simple(array('variable = ?' => 'settings_latest_update'));
$settings_latest_update = $settings_refresh_info ? $settings_refresh_info['selected_value'] : null;

// Commented because avoid refresh plugin values - refs BT#8274
// $_setting = isset($_SESSION['_setting']) ? $_SESSION['_setting'] : null;
// $_plugins = isset($_SESSION['_plugins']) ? $_SESSION['_plugins'] : null;

if (!isset($_setting)) {
    api_set_settings_and_plugins();
} else {
    if (isset($_setting['settings_latest_update']) && $_setting['settings_latest_update'] != $settings_latest_update) {
        api_set_settings_and_plugins();
        $_setting = isset($_SESSION['_setting']) ? $_SESSION['_setting'] : null;
        $_plugins = isset($_SESSION['_plugins']) ? $_SESSION['_plugins'] : null;
    }
}

// Load allowed tag definitions for kses and/or HTMLPurifier.
require_once $lib_path.'formvalidator/Rule/allowed_tags.inc.php';
// Load HTMLPurifier.
//require_once $lib_path.'htmlpurifier/library/HTMLPurifier.auto.php'; // It will be loaded later, in a lazy manner.

// Before we call local.inc.php, let's define a global $this_section variable
// which will then be usable from the banner and header scripts
$this_section = SECTION_GLOBAL;

// include the local (contextual) parameters of this course or section
require $includePath.'/local.inc.php';

//Include Chamilo Mail conf this is added here because the api_get_setting works

//Fixes bug in Chamilo 1.8.7.1 array was not set
$administrator['email'] = isset($administrator['email']) ? $administrator['email'] : 'admin@example.com';
$administrator['name']  = isset($administrator['name']) ? $administrator['name'] : 'Admin';

$mail_conf = api_get_path(CONFIGURATION_PATH).'mail.conf.php';

if (file_exists($mail_conf)) {
	require_once $mail_conf;
}

$mail_settings = array();

$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
     'swiftmailer.options' =>  array(
        'host' => $platform_email['SMTP_HOST'],
        'port' => $platform_email['SMTP_PORT'],
        'username' => $platform_email['SMTP_USER'],
        'password' => $platform_email['SMTP_PASS'],
        'encryption' => null,
        'auth_mode' => null
    )
));

//if (isset($platform_email['SMTP_MAILER']) && $platform_email['SMTP_MAILER'] == 'smtp') {
$app['mailer'] = $app->share(function ($app) {
    return new \Swift_Mailer($app['swiftmailer.transport']);
});


// check and modify the date of user in the track.e.online table
if (!$x = strpos($_SERVER['PHP_SELF'], 'whoisonline.php')) {
    LoginCheck(isset($_user['user_id']) ? $_user['user_id'] : '');
}

//error_reporting(-1);
if (api_get_setting('server_type') == 'test') {
    //error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
} else {
    /*
    Server type is not test
    - normal error reporting level
    - full fake register globals block
    */
    error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

    // TODO: These obsolete variables $HTTP_* to be check whether they are actually used.
    if (!isset($HTTP_GET_VARS)) { $HTTP_GET_VARS = $_GET; }
    if (!isset($HTTP_POST_VARS)) { $HTTP_POST_VARS = $_POST; }
    if (!isset($HTTP_POST_FILES)) { $HTTP_POST_FILES = $_FILES; }
    if (!isset($HTTP_SESSION_VARS)) { $HTTP_SESSION_VARS = $_SESSION; }
    if (!isset($HTTP_SERVER_VARS)) { $HTTP_SERVER_VARS = $_SERVER; }

    // Register SESSION variables into $GLOBALS
    if (sizeof($HTTP_SESSION_VARS)) {
        if (!is_array($_SESSION)) {
            $_SESSION = array();
        }
        foreach ($HTTP_SESSION_VARS as $key => $val) {
            $_SESSION[$key] = $HTTP_SESSION_VARS[$key];
            $GLOBALS[$key] = $HTTP_SESSION_VARS[$key];
        }
    }

    // Register SERVER variables into $GLOBALS
    if (sizeof($HTTP_SERVER_VARS)) {
        $_SERVER = array();
        foreach ($HTTP_SERVER_VARS as $key => $val) {
            $_SERVER[$key] = $HTTP_SERVER_VARS[$key];
            if (!isset($_SESSION[$key]) && $key != 'includePath' && $key != 'rootSys' && $key!= 'lang_path' && $key!= 'extAuthSource' && $key!= 'thisAuthSource' && $key!= 'main_configuration_file_path' && $key!= 'phpDigIncCn' && $key!= 'drs') {
                $GLOBALS[$key]=$HTTP_SERVER_VARS[$key];
            }
        }
    }
}

/*	LOAD LANGUAGE FILES SECTION */

// if we use the javascript version (without go button) we receive a get
// if we use the non-javascript version (with the go button) we receive a post
$user_language = '';
if (!empty($_GET['language'])) {
    $user_language = $_GET['language'];
}

if (!empty($_POST['language_list'])) {
    $user_language = str_replace('index.php?language=', '', $_POST['language_list']);
}

// Include all files (first english and then current interface language)

$langpath = api_get_path(SYS_LANG_PATH);

/* This will only work if we are in the page to edit a sub_language */
if (isset($this_script) && $this_script == 'sub_language') {
    require_once '../admin/sub_language.class.php';
    // getting the arrays of files i.e notification, trad4all, etc
    $language_files_to_load = SubLanguageManager:: get_lang_folder_files_list(api_get_path(SYS_LANG_PATH).'english', true);
    //getting parent info
    $parent_language = SubLanguageManager::get_all_information_of_language($_REQUEST['id']);
    //getting sub language info
    $sub_language = SubLanguageManager::get_all_information_of_language($_REQUEST['sub_language_id']);

    $english_language_array = $parent_language_array = $sub_language_array = array();

    foreach ($language_files_to_load as $language_file_item) {
        $lang_list_pre = array_keys($GLOBALS);
        //loading english
        $path = $langpath.'english/'.$language_file_item.'.inc.php';
        if (file_exists($path)) {
            include $path;
        }

        $lang_list_post = array_keys($GLOBALS);
        $lang_list_result = array_diff($lang_list_post, $lang_list_pre);
        unset($lang_list_pre);

        //  english language array
        $english_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach ($lang_list_result as $item) {
            unset(${$item});
        }
        $parent_file = $langpath.$parent_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';

        if (file_exists($parent_file) && is_file($parent_file)) {
            include_once $parent_file;
        }
        //  parent language array
        $parent_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach($lang_list_result as $item) {
            unset(${$item});
        }

        $sub_file = $langpath.$sub_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';
        if (file_exists($sub_file) && is_file($sub_file)) {
            include $sub_file;
        }

        //  sub language array
        $sub_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach($lang_list_result as $item) {
            unset(${$item});
        }
    }
}

// Checking if we have a valid language. If not we set it to the platform language.

$valid_languages = api_get_languages();

if (!empty($valid_languages)) {

    if (!in_array($user_language, $valid_languages['folder'])) {
        $user_language = api_get_setting('platformLanguage');
    }
    $language_priority1 = api_get_setting('languagePriority1');
    $language_priority2 = api_get_setting('languagePriority2');
    $language_priority3 = api_get_setting('languagePriority3');
    $language_priority4 = api_get_setting('languagePriority4');

    if (in_array($user_language, $valid_languages['folder']) && (isset($_GET['language']) || isset($_POST['language_list']))) {
        $user_selected_language = $user_language; // $_GET['language'];
        $_SESSION['user_language_choice'] = $user_selected_language;
        $platformLanguage = $user_selected_language;
    }

    if (!empty($language_priority4) && api_get_language_from_type($language_priority4) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority4);
    } else {
        $language_interface = api_get_setting('platformLanguage');
    }

    if (!empty($language_priority3) && api_get_language_from_type($language_priority3) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority3);
    } else {
        if (isset($_SESSION['user_language_choice'])) {
            $language_interface = $_SESSION['user_language_choice'];
        }
    }

    if (!empty($language_priority2) && api_get_language_from_type($language_priority2) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority2);
    } else {
        if (isset($_user['language'])) {
            $language_interface = $_user['language'];
        }
    }
    if (!empty($language_priority1) && api_get_language_from_type($language_priority1) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority1);
    } else {
        if ($_course['language']) {
            $language_interface = $_course['language'];
        }
    }
}

// Sometimes the variable $language_interface is changed
// temporarily for achieving translation in different language.
// We need to save the genuine value of this variable and
// to use it within the function get_lang(...).
$language_interface_initial_value = $language_interface;

/**
 * Include all necessary language files
 * - trad4all
 * - notification
 * - custom tool language files
 */
$language_files = array();
$language_files[] = 'trad4all';
$language_files[] = 'notification';
$language_files[] = 'accessibility';

if (isset($language_file)) {
    if (!is_array($language_file)) {
        $language_files[] = $language_file;
    } else {
        $language_files = array_merge($language_files, $language_file);
    }
}
// if a set of language files has been properly defined
if (is_array($language_files)) {
    // if the sub-language feature is on
    if (api_get_setting('allow_use_sub_language') == 'true') {
        require_once api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php';
        $parent_path = SubLanguageManager::get_parent_language_path($language_interface);
        foreach ($language_files as $index => $language_file) {
            // include English
            include $langpath.'english/'.$language_file.'.inc.php';
            // prepare string for current language and its parent
            $lang_file = $langpath.$language_interface.'/'.$language_file.'.inc.php';
            $parent_lang_file = $langpath.$parent_path.'/'.$language_file.'.inc.php';
            // load the parent language file first
            if (file_exists($parent_lang_file)) {
                include $parent_lang_file;
            }
            // overwrite the parent language translations if there is a child
            if (file_exists($lang_file)) {
                include $lang_file;
            }
        }
    } else {
        // if the sub-languages feature is not on, then just load the
        // set language interface
        foreach ($language_files as $index => $language_file) {
            // include English
            include $langpath.'english/'.$language_file.'.inc.php';
            // prepare string for current language
            $langfile = $langpath.$language_interface.'/'.$language_file.'.inc.php';
            if (file_exists($langfile)) {
                include $langfile;
            }
        }
    }
}

// The global variable $charset has been defined in a language file too (trad4all.inc.php), this is legacy situation.
// So, we have to reassign this variable again in order to keep its value right.
$charset = $charset_initial_value;

// The global variable $text_dir has been defined in the language file trad4all.inc.php.
// For determing text direction correspondent to the current language we use now information from the internationalization library.
$text_dir = api_get_text_direction();

//Update of the logout_date field in the table track_e_login (needed for the calculation of the total connection time)

if (!isset($_SESSION['login_as']) && isset($_user)) {
    // if $_SESSION['login_as'] is set, then the user is an admin logged as the user

    $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    $sql_last_connection = "SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='".$_user["user_id"]."' ORDER BY login_date DESC LIMIT 0,1";

    $q_last_connection = Database::query($sql_last_connection);
    if (Database::num_rows($q_last_connection) > 0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');

        // is the latest logout_date still relevant?
        $sql_logout_date = "SELECT logout_date FROM $tbl_track_login WHERE login_id=$i_id_last_connection";
        $q_logout_date = Database::query($sql_logout_date);
        $res_logout_date = convert_sql_date(Database::result($q_logout_date,0,'logout_date'));

        if ($res_logout_date < time() - $_configuration['session_lifetime']) {
            // it isn't, we should create a fresh entry
            event_login();
            // now that it's created, we can get its ID and carry on
            $q_last_connection = Database::query($sql_last_connection);
            $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
        }

        $s_sql_update_logout_date = "UPDATE $tbl_track_login SET logout_date=NOW() WHERE login_id='$i_id_last_connection'";
        Database::query($s_sql_update_logout_date);
    }
}

// Add language_measure_frequency to your main/inc/conf/configuration.php in
// order to generate language variables frequency measurements (you can then
// see them through main/cron/lang/langstats.php)
// The langstat object will then be used in the get_lang() function.
// This block can be removed to speed things up a bit as it should only ever
// be used in development versions.
if (isset($_configuration['language_measure_frequency']) && $_configuration['language_measure_frequency'] == 1) {
    require_once api_get_path(SYS_CODE_PATH).'/cron/lang/langstats.class.php';
    $langstats = new langstats();
}

//Default quota for the course documents folder
$default_quota = api_get_setting('default_document_quotum');
//Just in case the setting is not correctly set
if (empty($default_quota)) {
    $default_quota = 100000000;
}
define('DEFAULT_DOCUMENT_QUOTA', $default_quota);

return $app;
