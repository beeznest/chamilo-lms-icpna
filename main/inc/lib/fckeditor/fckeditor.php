<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2010 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This is the integration file for PHP 5.
 *
 * It defines the FCKeditor class that can be used to create editor
 * instances in PHP pages on server side.
 */

// Code about adaptation of the editor and its plugins has been added by the Chamilo team, 2009-2010.
// For modifying configuration options see myconfig.php and myconfig.js.

/**
 * Check if browser is compatible with FCKeditor.
 * Return true if is compatible.
 *
 * @return boolean
 */

function FCKeditor_IsCompatibleBrowser()
{
    if ( isset( $_SERVER ) ) {
        $sAgent = $_SERVER['HTTP_USER_AGENT'] ;
    }
    else {
        global $HTTP_SERVER_VARS ;
        if ( isset( $HTTP_SERVER_VARS ) ) {
            $sAgent = $HTTP_SERVER_VARS['HTTP_USER_AGENT'] ;
        }
        else {
            global $HTTP_USER_AGENT ;
            $sAgent = $HTTP_USER_AGENT ;
        }
    }

    if ( strpos($sAgent, 'MSIE') !== false && strpos($sAgent, 'mac') === false && strpos($sAgent, 'Opera') === false )
    {
        $iVersion = (float)substr($sAgent, strpos($sAgent, 'MSIE') + 5, 3) ;
        return ($iVersion >= 5.5) ;
    }
    else if ( strpos($sAgent, 'Gecko/') !== false )
    {
        $iVersion = substr($sAgent, strpos($sAgent, 'Gecko/') + 6, 8) ;
        // Special fix for Firefox 17 and followers - see #5752
        if ( preg_match('/^\d{2,3}\.\d{1,4}\s/', $iVersion) ) {
          return true;
        }
        $iVersion = (int)$iVersion;
        return ($iVersion >= 20030210) ;
    }
    else if ( strpos($sAgent, 'Opera/') !== false )
    {
        $fVersion = (float)substr($sAgent, strpos($sAgent, 'Opera/') + 6, 4) ;
        return ($fVersion >= 9.5) ;
    }
    else if ( preg_match( "|AppleWebKit/(\d+)|i", $sAgent, $matches ) )
    {
        $iVersion = $matches[1] ;
        return ( $matches[1] >= 522 ) ;
    }
    else
        return false ;
}

class FCKeditor
{
    /**
     * Name of the FCKeditor instance.
     *
     * @access protected
     * @var string
     */
    public $InstanceName ;
    /**
     * Path to FCKeditor relative to the document root.
     *
     * @var string
     */
    public $BasePath ;
    /**
     * Width of the FCKeditor.
     * Examples: 100%, 600
     *
     * @var mixed
     */
    public $Width ;
    /**
     * Height of the FCKeditor.
     * Examples: 400, 50%
     *
     * @var mixed
     */
    public $Height ;
    /**
     * Name of the toolbar to load.
     *
     * @var string
     */
    public $ToolbarSet ;
    /**
     * Initial value.
     *
     * @var string
     */
    public $Value ;
    /**
     * This is where additional configuration can be passed.
     * Example:
     * $oFCKeditor->Config['EnterMode'] = 'br';
     *
     * @var array
     */
    public $Config ;

    /**
     * Main Constructor.
     * Refer to the _samples/php directory for examples.
     *
     * @param string $instanceName
     */
    public function __construct( $instanceName )
     {
        $this->InstanceName	= $instanceName ;
        $this->BasePath		= '/fckeditor/' ;
        $this->Width		= '100%' ;
        $this->Height		= '200' ;
        $this->ToolbarSet	= 'Default' ;
        $this->Value		= '' ;

        $this->Config		= array() ;
    }

    /**
     * Display FCKeditor.
     *
     */
    public function Create()
    {
        echo $this->CreateHtml() ;
    }

    /**
     * Return the HTML code required to run FCKeditor.
     *
     * @return string
     */
    public function CreateHtml() {
        // Adaptation for the Chamilo LMS

		//@todo why the BasePath is relative ? we should use this constant WEB_PATH
        //$this->BasePath = api_get_path(REL_PATH).'main/inc/lib/fckeditor/';
        $this->BasePath = api_get_path(WEB_PATH).'main/inc/lib/fckeditor/';

        $config = $this->get_custom_configuration();
        $this->read_configuration($config);

        $config = $this->get_default_configuration();
        $this->read_configuration($config);

        if ((api_is_allowed_to_edit() || api_is_platform_admin()) && isset($this->Config['BlockCopyPaste'])) {
            $this->Config['BlockCopyPaste'] = false;
        }

        $HtmlValue = htmlspecialchars( $this->Value ) ;
        $Html = '' ;
        if ( FCKeditor::IsCompatible() ) {
            if ( api_get_setting('server_type') == 'test' )
                $File = 'fckeditor.original.html' ;
            else
                $File = 'fckeditor.html' ;

            $Link = "{$this->BasePath}editor/{$File}?InstanceName={$this->InstanceName}" ;

            if ( $this->ToolbarSet != '' )
                $Link .= "&amp;Toolbar={$this->ToolbarSet}" ;

            // Render the linked hidden field.
            $Html .= "<input type=\"hidden\" id=\"{$this->InstanceName}\" name=\"{$this->InstanceName}\" value=\"{$HtmlValue}\" style=\"display:none\" />" ;

            // Render the configurations hidden field.
            $Html .= "<input type=\"hidden\" id=\"{$this->InstanceName}___Config\" value=\"" . $this->GetConfigFieldString() . "\" style=\"display:none\" />" ;

            // Render the editor IFRAME.
            $Html .= "<iframe id=\"{$this->InstanceName}___Frame\" src=\"{$Link}\" width=\"{$this->Width}\" height=\"{$this->Height}\" frameborder=\"0\" scrolling=\"no\"></iframe>" ;
        } else {
            if ( strpos( $this->Width, '%' ) === false )
                $WidthCSS = $this->Width . 'px' ;
            else
                $WidthCSS = $this->Width ;

            if ( strpos( $this->Height, '%' ) === false )
                $HeightCSS = $this->Height . 'px' ;
            else
                $HeightCSS = $this->Height ;

            $Html .= "<textarea name=\"{$this->InstanceName}\" rows=\"4\" cols=\"40\" style=\"width: {$WidthCSS}; height: {$HeightCSS}\">{$HtmlValue}</textarea>" ;
        }
        return $Html ;
    }

    /**
     * Returns true if browser is compatible with FCKeditor.
     *
     * @return boolean
     */
    public static function IsCompatible()
    {
        return FCKeditor_IsCompatibleBrowser() ;
    }

    /**
     * Get settings from Config array as a single string.
     *
     * @access protected
     * @return string
     */
    public function GetConfigFieldString()
    {
        $sParams = '' ;
        $bFirst = true ;

        foreach ( $this->Config as $sKey => $sValue )
        {
            if ( !$bFirst ) {
                $sParams .= '&amp;' ;
            } else {
                $bFirst = false ;
            }
            if ( is_string( $sValue ) ) {
                $sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $sValue ) ;
            } else {
                $sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $this->to_js( $sValue ) ) ;
            }
        }

        return $sParams ;
    }

    /**
     * Encode characters that may break the configuration string
     * generated by GetConfigFieldString().
     *
     * @access protected
     * @param string $valueToEncode
     * @return string
     */
    public function EncodeConfig( $valueToEncode )
    {
        $chars = array(
            '&' => '%26',
            '=' => '%3D',
            '"' => '%22',
            '%' => '%25'
         ) ;

        return strtr( $valueToEncode,  $chars ) ;
    }

    /**
     * Converts a PHP variable into its Javascript equivalent.
     * The code of this method has been "borrowed" from the funcion drupal_to_js() within the Drupal CMS.
     * @param mixed $var	The variable to be converted into Javascript syntax
     * @return string		Returns a string
     * Note: This function is similar to json_encode(), in addition it produces HTML-safe strings, i.e. with <, > and & escaped.
     * @link http://drupal.org/
     */
    private function to_js( $var ) {
        switch ( gettype( $var ) ) {
            case 'boolean' :
                return $var ? 'true' : 'false' ; // Lowercase necessary!
            case 'integer' :
            case 'double' :
                return (string) $var ;
            case 'resource' :
            case 'string' :
                return '"' . str_replace( array( "\r", "\n", "<", ">", "&" ), array( '\r', '\n', '\x3c', '\x3e', '\x26' ), addslashes( $var ) ) . '"' ;
            case 'array' :
            // Arrays in JSON can't be associative. If the array is empty or if it
            // has sequential whole number keys starting with 0, it's not associative
            // so we can go ahead and convert it as an array.
                if ( empty( $var ) || array_keys( $var ) === range( 0, sizeof( $var ) - 1 ) ) {
                    $output = array() ;
                    foreach ( $var as $v ) {
                        $output[] = $this->to_js( $v ) ;
                    }
                    return '[ ' . implode( ', ', $output ) . ' ]' ;
                }
            // Otherwise, fall through to convert the array as an object.
            case 'object' :
                $output = array() ;
                foreach ( $var as $k => $v ) {
                    $output[] = $this->to_js( strval( $k ) ) . ': ' . $this->to_js( $v ) ;
                }
                return '{ ' . implode(', ', $output) . ' }' ;
            default:
                return 'null' ;
        }
    }

    /**
     * This method reads configuration data for the current editor's instance without overriding settings that already exist.
     * @return array
     */
    function read_configuration(& $config) {
        $toolbar_set = $this->ToolbarSet;
        $toolbar_set_maximized = $this->ToolbarSet.'Maximized';
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'ToolbarSets':
                    if (!empty($toolbar_set) && $toolbar_set != 'Default') {
                        if (is_array($value)) {
                            if (isset($value['Normal'])) {
                                if (!isset($this->Config[$key][$toolbar_set])) {
                                    $this->Config[$key][$toolbar_set] = $value['Normal'];
                                }
                            }
                            if (isset($value['Maximized'])) {
                                if (!isset($this->Config[$key][$toolbar_set_maximized])) {
                                    $this->Config[$key][$toolbar_set_maximized] = $value['Maximized'];
                                }
                            }
                        }
                    }
                    break;
                case 'Width':
                    $this->Config[$key] = (string) $value;
                    $this->Width = $this->Config[$key];
                    break;
                case 'Height':
                    $this->Config[$key] = (string) $value;
                    $this->Height = $this->Config[$key];
                    break;
                default:
                    if (!isset($this->Config[$key])) {
                        $this->Config[$key] = $value;
                    }
            }
        }
    }

    /**
     * This method returns editor's custom configuration settings read from php-files.
     * @return array	Custom configuration data.
     */
    private function & get_custom_configuration() {
        static $config;
        if (!isset($config)) {
            require api_get_path(LIBRARY_PATH).'fckeditor/myconfig.php';
        }
        $toolbar_dir = isset($config['ToolbarSets']['Directory']) ? $config['ToolbarSets']['Directory'] : 'default';
        $return = array_merge($config, $this->get_custom_toolbar_configuration($toolbar_dir));
        return $return;
    }

    /**
     * This method returns editor's toolbar configuration settings read from a php-file.
     * @return array	Toolbar configuration data.
     */
    private function & get_custom_toolbar_configuration($toolbar_dir) {
        static $toolbar_config = array('Default' => array());
        if (!isset($toolbar_config[$this->ToolbarSet])) {
            $toolbar_config[$this->ToolbarSet] = array();
            if (preg_match('/[a-zA-Z_]+/', $toolbar_dir) && preg_match('/[a-zA-Z_]+/', $this->ToolbarSet)) { // A security check.
                // Seeking the toolbar.
                @include api_get_path(LIBRARY_PATH).'fckeditor/toolbars/'.$toolbar_dir.'/'.api_camel_case_to_underscore($this->ToolbarSet).'.php';
                if (!isset($config['ToolbarSets']['Normal'])) {
                    // No toolbar has been found yet.
                    if ($toolbar_dir == 'default') {
                        // It does not exist in default toolbar definitions, giving up.
                        $this->ToolbarSet = 'Default';
                    } else {
                        // The custom toolbar does not exist, then trying to load the default one.
                        @include api_get_path(LIBRARY_PATH).'fckeditor/toolbars/default/'.api_camel_case_to_underscore($this->ToolbarSet).'.php';
                        if (!isset($config['ToolbarSets']['Normal'])) {
                            // It does not exist in default toolbar definitions, giving up.
                            $this->ToolbarSet = 'Default';
                        } else {
                            $toolbar_config[$this->ToolbarSet] = $config;
                        }
                    }
                } else {
                    $toolbar_config[$this->ToolbarSet] = $config;
                }
            } else {
                $this->ToolbarSet = 'Default';
            }
        }
        return $toolbar_config[$this->ToolbarSet];
    }

    /**
     * This method returns automatically determined editor's configuration settings (default settings).
     * @return array
     */
    private function & get_default_configuration() {
        $return_value = array_merge(
            self::get_javascript_custom_configuration_file(),
            self::get_css_configuration(),
            self::get_editor_language(),
            $this->get_repository_configuration(),
            self::get_media_configuration(),
            self::get_user_configuration_data(),
            $this->get_mimetex_plugin_configuration()
        );
        return $return_value;
    }

    /**
     * This method returns the path to the javascript custom configuration file.
     * @return array
     */
    private function & get_javascript_custom_configuration_file() {
        $return_value = array('CustomConfigurationsPath' => api_get_path(WEB_PATH).'main/inc/lib/fckeditor/myconfig.js');
        return $return_value;
    }

    /**
     * This method returns CSS-related configuration data that has been determined by the system.
     * @return array
     */
    private function & get_css_configuration() {
        $config['EditorAreaCSS'] = api_get_path(WEB_PATH).'main/css/'.api_get_setting('stylesheets').'/default.css';
        $config['ToolbarComboPreviewCSS'] = $config['EditorAreaCSS'];
        return $config;
    }

    /**
     * This method determines editor's interface language and returns it as compatible with the editor langiage code.
     * @return array
     */
    private function & get_editor_language() {
        static $config;
        if (!is_array($config)) {
            $code_translation_table = array('' => 'en', 'sr' => 'sr-latn', 'zh' => 'zh-cn', 'zh-tw' => 'zh');
            $editor_lang = strtolower(str_replace('_', '-', api_get_language_isocode()));
            $editor_lang = isset($code_translation_table[$editor_lang]) ? $code_translation_table[$editor_lang] : $editor_lang;
            $editor_lang = file_exists(api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js') ? $editor_lang : 'en';
            $config['DefaultLanguage'] = $editor_lang;
            $config['ContentLangDirection'] = api_get_text_direction($editor_lang);
        }
        return $config;
    }

    /**
     * This method returns default configuration for document repository that is to be used by the editor.
     * @return array
     */
    private function & get_repository_configuration() {

        // Preliminary calculations for assembling required paths.
        $base_path = $this->BasePath;
        $script_name = substr($_SERVER['PHP_SELF'], strlen(api_get_path(REL_PATH)));
        $script_path = explode('/', $script_name);
        $script_path[count($script_path) - 1] = '';
        if (api_is_in_course()) {
            $relative_path_prefix = str_repeat('../', count($script_path) - 1);
        } else {
            $relative_path_prefix = str_repeat('../', count($script_path) - 2);
        }
        $script_path = implode('/', $script_path);
        $script_path = api_get_path(WEB_PATH).$script_path;

        $use_advanced_filemanager = api_get_setting('advanced_filemanager') == 'true';
        // Let javascripts "know" which file manager has been chosen.
        $config['AdvancedFileManager'] = $use_advanced_filemanager;

        if (api_is_in_course()) {
            if (!api_is_in_group()) {
                // 1. We are inside a course and not in a group.
                if (api_is_allowed_to_edit()) {
                    // 1.1. Teacher (tutor and coach are not authorized to change anything in the "content creation" tools)
                    $config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
                    $config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/';
                    $config['BaseHref'] = $script_path;
                } else {
                    // 1.2. Student
                    $current_session_id = api_get_session_id();
                    if($current_session_id==0)
                    {

                        $config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
                        $config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
                        $config['BaseHref'] = $script_path;
                    }
                    else
                    {
                        $config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document//shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id().'/';
                        $config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id().'/';
                        $config['BaseHref'] = $script_path;
                    }
                }
            } else {
                // 2. Inside a course and inside a group.
                global $group_properties;
                $config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
                $config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document'.$group_properties['directory'].'/';
                $config['BaseHref'] = $script_path;
            }
        } else {
            if (api_is_platform_admin() && isset($_SESSION['this_section']) && $_SESSION['this_section'] == 'platform_admin') {
                // 3. Platform administration activities.
                $config['CreateDocumentWebDir'] = api_get_path(WEB_PATH).'home/default_platform_document/';
                $config['CreateDocumentDir'] = api_get_path(WEB_PATH).'home/default_platform_document/'; // A side-effect is in use here.
                $config['BaseHref'] = api_get_path(WEB_PATH).'home/default_platform_document/';
            } else {
                // 4. The user is outside courses.
                $my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'system');
                $config['CreateDocumentWebDir'] = $my_path['dir'].'my_files/';
                $my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'rel');
                $config['CreateDocumentDir'] = $my_path['dir'].'my_files/';
                $config['BaseHref'] = $script_path;
            }
        }

        // URLs for opening the file browser for different resource types (file types):
        if ($use_advanced_filemanager) {
            // Double slashes within the following URLs for the advanced file manager are put intentionally. Please, keep them.
            // for images
            $config['ImageBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
            // for flash
            $config['FlashBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
            // for audio files (mp3)
            $config['MP3BrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
            // for video
            $config['VideoBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
            // for video (flv)
            $config['MediaBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
            // for links (any resource type)
            $config['LinkBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
        } else {
            // for images
            $config['ImageBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Images&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
            // for flash
            $config['FlashBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Flash&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
            // for audio files (mp3)
            $config['MP3BrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=MP3&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
            // for video
            $config['VideoBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Video&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
            // for video (flv)
            $config['MediaBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Video/flv&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
            // for links (any resource type)
            $config['LinkBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=File&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php';
        }

        // URLs for making quick uplods for different resource types (file types).
        // These URLs are used by the dialogs' quick upload tabs:
        // for images
        $config['ImageUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Images';
        // for flash
        $config['FlashUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Flash';
        // for audio files (mp3)
        $config['MP3UploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=MP3';
        // for video
        $config['VideoUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Video';
        // for video (flv)
        $config['MediaUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Video/flv';
        // for links (any resource type)
        $config['LinkUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=File';

        return $config;
    }

    /**
     * This method returns multi-media related configuration data.
     * @return array
     */
    private function & get_media_configuration() {
        $config['FlashPlayerAudio'] = api_get_path(TO_REL, FLASH_PLAYER_AUDIO);
        $config['FlashPlayerVideo'] = api_get_path(TO_REL, FLASH_PLAYER_VIDEO);
        $config['ScriptSWFObject'] = api_get_path(TO_REL, SCRIPT_SWFOBJECT);
        $config['ScriptASCIIMathML'] = api_get_path(TO_REL, SCRIPT_ASCIIMATHML);
        $config['DrawingASCIISVG'] = api_get_path(TO_REL, DRAWING_ASCIISVG);
        return $config;
    }

    /**
     * This method returns current user specific configuration data.
     * @return array
     */
    private function & get_user_configuration_data() {
        $config['UserIsCourseAdmin'] = api_is_allowed_to_edit() ? true : false;
        $config['UserIsPlatformAdmin'] = api_is_platform_admin() ? true : false;
        return $config;
    }

    /**
     * This method returns detected configuration data about editor's MimeTeX plugin.
     * @return array
     */
    private function & get_mimetex_plugin_configuration() {
        static $config;
        if (!isset($config)) {
            $config = array();
            if (is_array($this->Config['LoadPlugin']) && in_array('mimetex', $this->Config['LoadPlugin'])) {
                $server_base = api_get_path(WEB_SERVER_ROOT_PATH);
                $server_base_parts = explode('/', $server_base);
                $url_relative = 'cgi-bin/mimetex' . ( IS_WINDOWS_OS ? '.exe' : '.cgi' );
                if (!isset($this->Config['MimetexExecutableInstalled'])) {
                    $this->Config['MimetexExecutableDetectionMethod'] = 'detect';
                }
                if ($this->Config['MimetexExecutableInstalled'] == 'detect') {
                    $detection_method = isset($this->Config['MimetexExecutableDetectionMethod']) ? $this->Config['MimetexExecutableDetectionMethod'] : 'bootstrap_ip';
                    $detection_timeout = isset($this->Config['MimetexExecutableDetectionTimeout']) ? $this->Config['MimetexExecutableDetectionTimeout'] : 0.05;
                    switch ($detection_method) {
                        case 'bootstrap_ip':
                            $detection_url = $server_base_parts[0] . '//127.0.0.1/';
                            break;
                        case 'localhost':
                            $detection_url = $server_base_parts[0] . '//localhost/';
                            break;
                        case 'ip':
                            $detection_url = $server_base_parts[0] . '//' . $_SERVER['SERVER_ADDR'] . '/';
                            break;
                        default:
                            $detection_url = $server_base_parts[0] . '//' . $_SERVER['SERVER_NAME'] . '/';
                    }
                    $detection_url .= $url_relative . '?' . rand();
                    $config['IsMimetexInstalled'] = self::url_exists($detection_url, $detection_timeout);
                } else {
                    $config['IsMimetexInstalled'] = $this->Config['MimetexExecutableInstalled'];
                }
                $config['MimetexUrl'] = api_add_trailing_slash($server_base) . $url_relative;
            }
            // Cleaning detection related settings, we don't need them anymore.
            unset($this->Config['MimetexExecutableInstalled']);
            unset($this->Config['MimetexExecutableDetectionMethod']);
            unset($this->Config['MimetexExecutableDetectionTimeout']);
        }
        return $config;
    }

    /*
     * Checks whether a given url exists.
     * @param string $url
     * @param int $timeout
     * @return boolean
     * @author Ivan Tcholakov, FEB-2009
     */
    private function url_exists($url, $timeout = 30) {
        $parsed = parse_url($url);
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
        $host = $parsed['host'];
        $port = isset($parsed['port']) ? $parsed['port'] : ($scheme == 'http' ? 80 : ($scheme == 'https' ? 443 : -1 ));

        $file_exists = false;
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            $request = "HEAD ".$url." / HTTP/1.1\r\n";
            $request .= "Host: ".$host."\r\n";
            $request .= "Connection: Close\r\n\r\n";

            @fwrite($fp, $request);
            while (!@feof($fp)) {
                $header = @fgets($fp, 128);
                if(@preg_match('#HTTP/1.1 200 OK#', $header)) {
                    $file_exists = true;
                    break;
                }
            }
        }
        @fclose($fp);
        return $file_exists;
    }
}
