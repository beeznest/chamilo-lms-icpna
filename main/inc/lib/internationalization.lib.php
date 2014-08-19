<?php
/* For licensing terms, see /license.txt */

/**
 * File: internationalization.lib.php
 * Internationalization library for Chamilo 1.8.7 LMS
 * A library implementing internationalization related functions.
 * License: GNU General Public License Version 3 (Free Software Foundation)
 * @author Ivan Tcholakov, <ivantcholakov@gmail.com>, 2009, 2010
 * @author More authors, mentioned in the correpsonding fragments of this source.
 * @package chamilo.library
 */


/**
 * Constants
 */

// Special tags for marking untranslated variables.
define('SPECIAL_OPENING_TAG', '[=');
define('SPECIAL_CLOSING_TAG', '=]');

// Predefined date formats in Chamilo provided by the language sub-system.
// To be used as a parameter for the function api_format_date()

define('TIME_NO_SEC_FORMAT',    0);	// 15:23
define('DATE_FORMAT_SHORT',     1); // Aug 25, 09
define('DATE_FORMAT_LONG',      2);	// Monday August 25, 09
define('DATE_FORMAT_LONG_NO_DAY',     10);	// August 25, 2009
define('DATE_TIME_FORMAT_LONG', 3);	// Monday August 25, 2009 at 03:28 PM

define('DATE_FORMAT_NUMBER',        4);	// 25.08.09
define('DATE_TIME_FORMAT_LONG_24H', 5); // August 25, 2009 at 15:28
define('DATE_TIME_FORMAT_SHORT', 6);	// Aug 25, 2009 at 03:28 PM
define('DATE_TIME_FORMAT_SHORT_TIME_FIRST', 7);	// 03:28 PM, Aug 25 2009
define('DATE_FORMAT_NUMBER_NO_YEAR',  8);	// 25.08 dd-mm
define('DATE_FORMAT_ONLY_DAYNAME',  9);	// Monday, Sunday, etc

// Formatting person's name.
define('PERSON_NAME_COMMON_CONVENTION', 0);	// Formatting a person's name using the pattern as it has been
                                            // configured in the internationalization database for every language.
                                            // This (default) option would be the most used.
// The followind options may be used in limited number of places for overriding the common convention:
define('PERSON_NAME_WESTERN_ORDER', 1);		// Formatting a person's name in Western order: first_name last_name
define('PERSON_NAME_EASTERN_ORDER', 2);		// Formatting a person's name in Eastern order: last_name first_name
define('PERSON_NAME_LIBRARY_ORDER', 3);		// Contextual: formatting person's name in library order: last_name, first_name
define('PERSON_NAME_EMAIL_ADDRESS', PERSON_NAME_WESTERN_ORDER);		// Contextual: formatting a person's name assotiated with an email-address. Ivan: I am not sure how seems email servers an clients would interpret name order, so I assign the Western order.
define('PERSON_NAME_DATA_EXPORT', PERSON_NAME_EASTERN_ORDER);		// Contextual: formatting a person's name for data-exporting operarions. For backward compatibility this format has been set to Eastern order.

// The following constants are used for tunning language detection functionality.
// We reduce the text for language detection to the given number of characters
// for increaseing speed and to decrease memory consumption.
define ('LANGUAGE_DETECT_MAX_LENGTH', 2000);
// Maximum allowed difference in so called delta-points for aborting certain language detection.
// The value 80000 is good enough for speed and detection accuracy.
// If you set the value of $max_delta too low, no language will be recognized.
// $max_delta = 400 * 350 = 140000 is the best detection with lowest speed.
define ('LANGUAGE_DETECT_MAX_DELTA', 140000);


/**
 * Initialization
 */

/**
 * Initialization of some internal default valies in the internationalization library.
 * @return void
 * Note: This function should be called only once in the global initialization script.
 */
function api_initialize_internationalization() {
    if (MBSTRING_INSTALLED) {
        @ini_set('mbstring.func_overload', 0);
        @ini_set('mbstring.encoding_translation', 0);
        @ini_set('mbstring.http_input', 'pass');
        @ini_set('mbstring.http_output', 'pass');
        @ini_set('mbstring.language', 'neutral');
    }
    api_set_internationalization_default_encoding('UTF-8');
}

/**
 * Sets the internal default encoding for the multi-byte string functions.
 * @param string $encoding		The specified default encoding.
 * @return string				Returns the old value of the default encoding.
 */
function api_set_internationalization_default_encoding($encoding) {
    $encoding = api_refine_encoding_id($encoding);
    $result = _api_mb_internal_encoding();
    _api_mb_internal_encoding($encoding);
    _api_mb_regex_encoding($encoding);
    _api_iconv_set_encoding('iconv_internal_encoding', $encoding);
    return $result;
}


/**
 * Language support
 */

// These variables are for internal purposes only, they serve the function api_is_translated().
$_api_is_translated = false;
$_api_is_translated_call = false;

/**
 * Returns a translated (localized) string, called by its identificator.
 * @param string $variable				This is the identificator (name) of the translated string to be retrieved.
 * @param string $reserved				This parameter has been reserved for future use.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns the requested string in the correspondent language.
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @author Ivan Tcholakov, 2009-2010 (caching functionality, additional parameter $language, other adaptations).
 *
 * Notes:
 * 1. If the name of a given language variable has the prefix "lang" it may be omited, i.e. get_lang('Yes') == get_lang('Yes').
 * 2. Untranslated variables might be indicated by special opening and closing tags  -  [=  =]
 * The special tags do not show up in these two cases:
 * - when the system has been switched to "production server mode";
 * - when a special platform setting 'hide_dltt_markup' is set to "true" (the name of this setting comes from history);
 * 3. Translations are created many contributors through using a special tool: Chamilo Translation Application.
 * @link http://translate.chamilo.org/
 */
function get_lang($variable, $reserved = null, $language = null) {
    global
        // For serving some old hacks:
        // By manipulating this global variable the translation may be done in different languages too (not the elegant way).
        $language_interface,
        // Because of possibility for manipulations of the global variable $language_interface, we need its initial value.
        $language_interface_initial_value,
        // For serving the function is_translated()
        $_api_is_translated, $_api_is_translated_call;

    global $used_lang_vars, $_configuration;
    // add language_measure_frequency to your main/inc/conf/configuration.php in order to generate language
    // variables frequency measurements (you can then see them trhough main/cron/lang/langstats.php)
    // The $langstats object is instanciated at the end of main/inc/global.inc.php
    if (isset($_configuration['language_measure_frequency']) && $_configuration['language_measure_frequency'] == 1) {
      require_once api_get_path(SYS_CODE_PATH).'/cron/lang/langstats.class.php';
      global $langstats;
      $langstats->add_use($variable,'');
    }
    if (!isset ($used_lang_vars)) {
    	$used_lang_vars = array();
    }

    // Caching results from some API functions, for speed.
    static $initialized, $encoding, $is_utf8_encoding, $langpath, $test_server_mode, $show_special_markup;
    if (!isset($initialized)) {
        $encoding = api_get_system_encoding();
        $is_utf8_encoding = api_is_utf8($encoding);
        $langpath = api_get_path(SYS_LANG_PATH);
        $test_server_mode = api_get_setting('server_type') == 'test';
        $show_special_markup = api_get_setting('hide_dltt_markup') != 'true' || $test_server_mode;
        $initialized = true;
    }

    // Combining both ways for requesting specific language.
    if (empty($language)) {
        $language = $language_interface;
    }
    $lang_postfix = isset($is_interface_language) && $is_interface_language ? '' : '('.$language.')';

    $is_interface_language = $language == $language_interface_initial_value;

    // This is a cache for already translated language variables. By using it, we avoid repetitive translations, gaining speed.
    static $cache;

    // Looking up into the cache for existing translation.
    if (isset($cache[$language][$variable]) && !$_api_is_translated_call) {
        // There is a previously saved translation, returning it.
        //return $cache[$language][$variable];
        $ret = $cache[$language][$variable];
        $used_lang_vars[$variable.$lang_postfix] = $ret;
        return $ret;
    }

    $_api_is_translated = false;

    // There is no cached translation, we have to retrieve it:
    // - from a global variable (the faster way) - on production server mode;
    // - from a local variable after reloading the language files - on test server mode or when requested language is different than the genuine interface language.
    $read_global_variables = $is_interface_language && !$test_server_mode && !$_api_is_translated_call;

    // Reloading the language files when it is necessary.
    if (!$read_global_variables) {
        global $language_files;
        if (isset($language_files)) {
            $parent_language = null;
            if (api_get_setting('allow_use_sub_language') == 'true') {
                require_once api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php';
                $parent_language = SubLanguageManager::get_parent_language_path($language);
            }
            if (!is_array($language_files)) {
                if (isset($parent_language)) {
                    @include "$langpath$parent_language/$language_files.inc.php";
                }
                @include "$langpath$language/$language_files.inc.php";
            } else {
                foreach ($language_files as &$language_file) {
                    if (isset($parent_language)) {
                        @include "$langpath$parent_language/$language_file.inc.php";
                    }
                    @include "$langpath$language/$language_file.inc.php";
                }
            }
        }
    }

    // Translation mode for production servers.
    if (!$test_server_mode) {
        if ($read_global_variables) {
            if (isset($GLOBALS[$variable])) {
                $langvar = $GLOBALS[$variable];
                $_api_is_translated = true;
            } elseif (isset($GLOBALS["lang$variable"])) {
                $langvar = $GLOBALS["lang$variable"];
                $_api_is_translated = true;
            } else {
                $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
            }
        } else {
            if (isset($$variable)) {
                $langvar = $$variable;
                $_api_is_translated = true;
            } elseif (isset(${"lang$variable"})) {
                $langvar = ${"lang$variable"};
                $_api_is_translated = true;
            } else {
                $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
            }
        }
        if (empty($langvar) || !is_string($langvar)) {
            $_api_is_translated = false;
            $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
        }
        //return $cache[$language][$variable] = $is_utf8_encoding ? $langvar : api_utf8_decode($langvar, $encoding);
        $ret = $cache[$language][$variable] = $is_utf8_encoding ? $langvar : api_utf8_decode($langvar, $encoding);
        $used_lang_vars[$variable.$lang_postfix] = $ret;
        return $ret;
    }

    // Translation mode for test/development servers.
    if (!is_string($variable)) {
        //return $cache[$language][$variable] = SPECIAL_OPENING_TAG.'get_lang(?)'.SPECIAL_CLOSING_TAG;
        $ret = $cache[$language][$variable] = SPECIAL_OPENING_TAG.'get_lang(?)'.SPECIAL_CLOSING_TAG;
        $used_lang_vars[$variable.$lang_postfix] = $ret;
        return $ret;
    }
    if (isset($$variable)) {
        $langvar = $$variable;
        $_api_is_translated = true;
    } elseif (isset(${"lang$variable"})) {
        $langvar = ${"lang$variable"};
        $_api_is_translated = true;
    } else {
        $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
    }
    if (empty($langvar) || !is_string($langvar)) {
        $_api_is_translated = false;
        $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
    }
    //return $cache[$language][$variable] = $is_utf8_encoding ? $langvar : api_utf8_decode($langvar, $encoding);
    $ret = $cache[$language][$variable] = $is_utf8_encoding ? $langvar : api_utf8_decode($langvar, $encoding);
    $used_lang_vars[$variable.$lang_postfix] = $ret;
    return $ret;
}

/**
 * Checks whether exists a translated (localized) string.
 * @param string $variable				This is the identificator (name) of the translated string to be checked.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return bool							Returns TRUE if translation exists, FALSE otherwise.
 * @author Ivan Tcholakov, 2010.
 */
function api_is_translated($variable, $language = null) {
    global $_api_is_translated, $_api_is_translated_call;
    $_api_is_translated_call = true;
    get_lang($variable, $language);
    $_api_is_translated_call = false;
    return $_api_is_translated;
}

/**
 * Gets the current interface language.
 * @param bool $purified (optional)	When it is true, a purified (refined) language value will be returned, for example 'french' instead of 'french_unicode'.
 * @return string					The current language of the interface.
 */
function api_get_interface_language($purified = false, $check_sub_language = false) {
    global $language_interface;

    if (empty($language_interface)) {
        return 'english';
    }

    //1. Checking if current language is supported
    $language_is_supported = api_is_language_supported($language_interface);

    if ($check_sub_language && !$language_is_supported) {
        static $parent_language_name = null;

        if (!isset($parent_language_name)) {
            //2. The current language is a sub language so we grab the father's setting according to the internalization_database/name_order_convetions.php file
            $language_id   = api_get_language_id($language_interface);
            $language_info = api_get_language_info($language_id);
            if (!empty($language_id) && !empty($language_info)) {
                $language_info = api_get_language_info($language_info['parent_id']);
                $parent_language_name = $language_info['english_name'];
                if (!empty($parent_language_name)) {
                    return $parent_language_name;
                }
            }
            return 'english';
        } else {
            return $parent_language_name;
        }
    } else {
        //2. Normal way
        $interface_language = $purified ? api_purify_language_id($language_interface) : $language_interface;
    }
    return $interface_language;
}

/**
 * Checks whether a given language identificator represents supported by *this library* language.
 * @param string $language		The language identificator to be checked ('english', 'french', 'spanish', ...).
 * @return bool $language		TRUE if the language is supported, FALSE otherwise.
 */
function api_is_language_supported($language) {
    static $supported = array();
    if (!isset($supported[$language])) {
        $supported[$language] = in_array(api_purify_language_id($language), array_keys(_api_non_utf8_encodings()));
    }
    return $supported[$language];
}

/**
 * Validates the input language identificator in order always to return a language that is enabled in the system.
 * This function is to be used for data import when provided language identificators should be validated.
 * @param string $language		The language identificator to be validated.
 * @return string				Returns the input language identificator. If the input language is not enabled, platform language is returned then.
 */
function api_get_valid_language($language) {
    static $enabled_languages;
    if (!isset($enabled_languages)) {
        $enabled_languages_info = api_get_languages();
        $enabled_languages = $enabled_languages_info['folder'];
    }
    $language = str_replace('_km', '_KM', strtolower(trim($language)));
    if (empty($language) || !in_array($language, $enabled_languages) || !api_is_language_supported($language)) {
        $language = api_get_setting('platformLanguage');
    }
    return $language;
}

/**
 * Returns a purified language id, without possible suffixes that will disturb language identification in certain cases.
 * @param string $language	The input language identificator, for example 'french_unicode'.
 * @param string			The same purified or filtered language identificator, for example 'french'.
 */
function api_purify_language_id($language) {
    static $purified = array();
    if (!isset($purified[$language])) {
        $purified[$language] = trim(str_replace(array('_unicode', '_latin', '_corporate', '_org', '_km'), '', strtolower($language)));
    }
    return $purified[$language];
}

/**
 * Gets language isocode column from the language table, taking the given language as a query parameter.
 * @param string $language		This is the name of the folder containing translations for the corresponding language (e.g arabic, english).
 * @param string $default_code	This is the value to be returned if there was no code found corresponding to the given language.
 * If $language is omitted, interface language is assumed then.
 * @return string			The found isocode or null on error.
 * Returned codes are according to the following standards (in order of preference):
 * -  ISO 639-1 : Alpha-2 code (two-letters code - en, fr, es, ...)
 * -  RFC 4646  : five-letter code based on the ISO 639 two-letter language codes
 *    and the ISO 3166 two-letter territory codes (pt-BR, ...)
 * -  ISO 639-2 : Alpha-3 code (three-letters code - ast, fur, ...)
 */
function api_get_language_isocode($language = null, $default_code = 'en') {
    static $iso_code = array();
    if (empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($iso_code[$language])) {
        if (!class_exists('Database')) {
            return $default_code; // This might happen, in case of calling this function early during the global initialization.
        }
        $sql_result = Database::query("SELECT isocode FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)." WHERE dokeos_folder = '$language'");
        if (Database::num_rows($sql_result)) {
            $result = Database::fetch_array($sql_result);
            $iso_code[$language] = trim($result['isocode']);
        } else {
            $language_purified_id = api_purify_language_id($language);
            $iso_code[$language] = isset($iso_code[$language_purified_id]) ? $iso_code[$language_purified_id] : null;
        }
        if (empty($iso_code[$language])) {
            $iso_code[$language] = $default_code;
        }
    }
    return $iso_code[$language];
}


/**
 * Gets language isocode column from the language table
 *
 * @return array    An array with the current isocodes
 *
 * */
function api_get_platform_isocodes() {
    $iso_code = array();
    $sql_result = Database::query("SELECT isocode FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)." ORDER BY isocode ");
    if (Database::num_rows($sql_result)) {
        while ($row = Database::fetch_array($sql_result)) {;
            $iso_code[] = trim($row['isocode']);
        }
    }
    return $iso_code;
}


/**
 * Gets text direction according to the given language.
 * @param string $language	This is the name of the folder containing translations for the corresponding language (e.g 'arabic', 'english', ...).
 * ISO-codes are acceptable too ('ar', 'en', ...). If $language is omitted, interface language is assumed then.
 * @return string			The correspondent to the language text direction ('ltr' or 'rtl').
 */
function api_get_text_direction($language = null) {
    static $text_direction = array();

    /*
     * Not necessary to validate the language because the list if rtl/ltr is harcoded
     *
    /*
     $language_is_supported = api_is_language_supported($language);
    if (!$language_is_supported || empty($language)) {
        $language = api_get_interface_language(false, true);
    }*/
    if (empty($language)) {
    	$language = api_get_interface_language();
    }
    if (!isset($text_direction[$language])) {
        $text_direction[$language] = in_array(api_purify_language_id($language),
            array(
                'arabic',
                'ar',
                'dari',
                'prs',
                'hebrew',
                'he',
                'iw',
                'pashto',
                'ps',
                'persian',
                'fa',
                'ur',
                'yiddish',
                'yid'
            )
        ) ? 'rtl' : 'ltr';
    }
    return $text_direction[$language];
}

/**
 * This function checks whether a given language can use Latin 1 encoding.
 * In the past (Chamilo 1.8.6.2), the function was used in the installation script only once.
 * It is not clear whether this function would be use useful for something else in the future.
 * @param string $language	The checked language.
 * @return bool				TRUE if the given language can use Latin 1 encoding (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...), FALSE otherwise.
 */
function api_is_latin1_compatible($language) {
    static $latin1_languages;
    if (!isset($latin1_languages)) {
        $latin1_languages = _api_get_latin1_compatible_languages();
    }
    $language = api_purify_language_id($language);
    return in_array($language, $latin1_languages);
}


/**
 * Language recognition
 * Based on the publication:
 * W. B. Cavnar and J. M. Trenkle. N-gram-based text categorization.
 * Proceedings of SDAIR-94, 3rd Annual Symposium on Document Analysis
 * and Information Retrieval, 1994.
 * @link http://citeseer.ist.psu.edu/cache/papers/cs/810/http:zSzzSzwww.info.unicaen.frzSz~giguetzSzclassifzSzcavnar_trenkle_ngram.pdf/n-gram-based-text.pdf
 */

function api_detect_language(&$string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (empty($string)) {
        return false;
    }
    $result_array = &_api_compare_n_grams(_api_generate_n_grams(api_substr($string, 0, LANGUAGE_DETECT_MAX_LENGTH, $encoding), $encoding), $encoding);
    if (empty($result_array)) {
        return false;
    }
    list($key, $delta_points) = each($result_array);
    return strstr($key, ':', true);
}


/**
 * Date and time conversions and formats
 */

/**
 * Returns an alphabetized list of timezones in an associative array that can be used to populate a select
 *
 * @return array List of timezone identifiers
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_timezones() {
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    sort($timezone_identifiers);
    $out = array();
    foreach ($timezone_identifiers as $tz) {
        $out[$tz] = $tz;
    }
    $null_option = array('' => '');
    $result = array_merge($null_option, $out);
    return $result;
}

/**
 * Returns the timezone to be converted to/from, based on user or admin preferences
 *
 * @return string The timezone chosen
 */
function _api_get_timezone() {
    $user_id = api_get_user_id();
    // First, get the default timezone of the server
    $to_timezone = date_default_timezone_get();
    // Second, see if a timezone has been chosen for the platform
    $timezone_value = api_get_setting('timezone_value', 'timezones');
    if ($timezone_value != null) {
        $to_timezone = $timezone_value;
    }

    // If allowed by the administrator
    $use_users_timezone = api_get_setting('use_users_timezone', 'timezones');

    if ($use_users_timezone == 'true') {
        // Get the timezone based on user preference, if it exists
        $timezone_user = UserManager::get_extra_user_data_by_field($user_id, 'timezone');

        if (isset($timezone_user['timezone']) && $timezone_user['timezone'] != null) {
            $to_timezone = $timezone_user['timezone'];
        }
    }
    return $to_timezone;
}

/**
 * Returns the given date as a DATETIME in UTC timezone. This function should be used before entering any date in the DB.
 *
 * @param mixed The date to be converted (can be a string supported by date() or a timestamp)
 * @param bool if the date is not correct return null instead of the current date
 * @return string The DATETIME in UTC to be inserted in the DB, or null if the format of the argument is not supported
 *
 * @author Julio Montoya - Adding the 2nd parameter
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_utc_datetime($time = null, $return_null_if_invalid_date = false) {
    $from_timezone = _api_get_timezone();

    $to_timezone = 'UTC';
    if (is_null($time) || empty($time) || $time == '0000-00-00 00:00:00') {
        if ($return_null_if_invalid_date) {
            return null;
        }
        return gmdate('Y-m-d H:i:s');
    }
    // If time is a timestamp, return directly in utc
    if (is_numeric($time)) {
        $time = intval($time);
        return gmdate('Y-m-d H:i:s', $time);
    }
    try {
        $date = new DateTime($time, new DateTimezone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Returns a DATETIME string converted to the right timezone
 * @param mixed The time to be converted
 * @param string The timezone to be converted to. If null, the timezone will be determined based on user preference, or timezone chosen by the admin for the platform.
 * @param string The timezone to be converted from. If null, UTC will be assumed.
 * @return string The converted time formatted as Y-m-d H:i:s
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_local_time($time = null, $to_timezone = null, $from_timezone = null, $return_null_if_invalid_date = false) {
    // Determining the timezone to be converted from
    if (is_null($from_timezone)) {
        $from_timezone = 'UTC';
    }
    // Determining the timezone to be converted to
    if (is_null($to_timezone)) {
        $to_timezone = _api_get_timezone();
    }
    // If time is a timestamp, convert it to a string
    if (is_null($time) || empty($time) || $time == '0000-00-00 00:00:00') {
        if ($return_null_if_invalid_date) {
            return null;
        }
        $from_timezone = 'UTC';
        $time = gmdate('Y-m-d H:i:s');
    }
    if (is_numeric($time)) {
        $time = intval($time);
        $from_timezone = 'UTC';
        $time = gmdate('Y-m-d H:i:s', $time);
    }
    try {
        $date = new DateTime($time, new DateTimezone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Converts a string into a timestamp safely (handling timezones), using strtotime
 *
 * @param string String to be converted
 * @param string Timezone (if null, the timezone will be determined based on user preference, or timezone chosen by the admin for the platform)
 * @return int Timestamp
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_strtotime($time, $timezone = null) {
    $system_timezone = date_default_timezone_get();
    if (!empty($timezone)) {
        date_default_timezone_set($timezone);
    }
    $timestamp = strtotime($time);
    date_default_timezone_set($system_timezone);
    return $timestamp;
}



/**
 * Returns formated date/time, correspondent to a given language.
 * The given date should be in the timezone chosen by the administrator and/or user. Use api_get_local_time to get it.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Christophe Gesche<gesche@ipm.ucl.ac.be>
 *         originally inspired from from PhpMyAdmin
 * @author Ivan Tcholakov, 2009, code refactoring, adding support for predefined date/time formats.
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 *
 * @param mixed Timestamp or datetime string
 * @param mixed Date format (string or int; see date formats in the Chamilo system: TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG)
 * @param string $language (optional)		Language indentificator. If it is omited, the current interface language is assumed.
 * @return string							Returns the formatted date.
 *
 * @link http://php.net/manual/en/function.strftime.php
 */
function api_format_date($time, $format = null, $language = null) {

    $system_timezone = date_default_timezone_get();
    date_default_timezone_set(_api_get_timezone());

    if (is_string($time)) {
        $time = strtotime($time);
    }

    if (is_null($format)) {
        $format = DATE_TIME_FORMAT_LONG;
    }

    $datetype = null;
    $timetype = null;

    if (is_int($format)) {
        switch ($format) {
            case DATE_FORMAT_ONLY_DAYNAME:
                $date_format = get_lang('dateFormatOnlyDayName', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
        			$datetype = IntlDateFormatter::SHORT;
        			$timetype = IntlDateFormatter::NONE;
        		}
                break;
            case DATE_FORMAT_NUMBER_NO_YEAR:
                $date_format = get_lang('dateFormatShortNumberNoYear', '', $language);
        		if (IS_PHP_53 && INTL_INSTALLED) {
        			$datetype = IntlDateFormatter::SHORT;
        			$timetype = IntlDateFormatter::NONE;
        		}
                break;
        	case DATE_FORMAT_NUMBER:
        		$date_format = get_lang('dateFormatShortNumber', '', $language);
        		if (IS_PHP_53 && INTL_INSTALLED) {
        			$datetype = IntlDateFormatter::SHORT;
        			$timetype = IntlDateFormatter::NONE;
        		}
        		break;
            case TIME_NO_SEC_FORMAT:
                $date_format = get_lang('timeNoSecFormat', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::NONE;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_FORMAT_SHORT:
                $date_format = get_lang('dateFormatShort', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::LONG;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_FORMAT_LONG:
                $date_format = get_lang('dateFormatLong', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_TIME_FORMAT_LONG:
                $date_format = get_lang('dateTimeFormatLong', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_FORMAT_LONG_NO_DAY:
                $date_format = get_lang('dateFormatLongNoDay', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
			case DATE_TIME_FORMAT_SHORT:
                $date_format = get_lang('dateTimeFormatShort', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
			case DATE_TIME_FORMAT_SHORT_TIME_FIRST:
                $date_format = get_lang('dateTimeFormatShortTimeFirst', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_TIME_FORMAT_LONG_24H:
                $date_format = get_lang('dateTimeFormatLong24H', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            default:
                $date_format = get_lang('dateTimeFormatLong', '', $language);
                if (IS_PHP_53 && INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
        }
    } else {
        $date_format = $format;
    }

    //if (IS_PHP_53 && INTL_INSTALLED && $datetype !== null && $timetype !== null) {
    if (0) {
        //if using PHP 5.3 format dates like: $dateFormatShortNumber, can't be used
        //
        // Use ICU
        if (is_null($language)) {
            $language = api_get_language_isocode();
        }
        /*$date_formatter = datefmt_create($language, $datetype, $timetype, date_default_timezone_get());
        $formatted_date = api_to_system_encoding(datefmt_format($date_formatter, $time), 'UTF-8');*/

        $date_formatter = new IntlDateFormatter($language, $datetype, $timetype, date_default_timezone_get());
        //$date_formatter->setPattern($date_format);
        $formatted_date = api_to_system_encoding($date_formatter->format($time), 'UTF-8');

    } else {
        // We replace %a %A %b %B masks of date format with translated strings
        $translated = &_api_get_day_month_names($language);
        $date_format = str_replace(array('%A', '%a', '%B', '%b'),
        array($translated['days_long'][(int)strftime('%w', $time )],
            $translated['days_short'][(int)strftime('%w', $time)],
            $translated['months_long'][(int)strftime('%m', $time) - 1],
            $translated['months_short'][(int)strftime('%m', $time) - 1]),
        $date_format);
        $formatted_date = api_to_system_encoding(strftime($date_format, $time), 'UTF-8');
    }
    date_default_timezone_set($system_timezone);
    return $formatted_date;
}

/**
 * Returns the difference between the current date (date(now)) with the parameter $date in a string format like "2 days, 1 hour"
 * Example: $date = '2008-03-07 15:44:08';
 * 			date_to_str($date) it will return 3 days, 20 hours
 * The given date should be in the timezone chosen by the user or administrator. Use api_get_local_time() to get it...
 *
 * @param  string The string has to be the result of a date function in this format -> date('Y-m-d H:i:s', time());
 * @return string The difference between the current date and the parameter in a literal way "3 days, 2 hour" *
 * @author Julio Montoya
 */

function date_to_str_ago($date) {

    static $initialized = false;
    static $today, $yesterday;
    static $min_decade, $min_year, $min_month, $min_week, $min_day, $min_hour, $min_minute;
    static $min_decades, $min_years, $min_months, $min_weeks, $min_days, $min_hours, $min_minutes;
    static $sec_time_time, $sec_time_sing, $sec_time_plu;

    $system_timezone = date_default_timezone_get();
    date_default_timezone_set(_api_get_timezone());

    if (!$initialized) {
        $today = get_lang('Today');
        $yesterday = get_lang('Yesterday');

        $min_decade = get_lang('MinDecade');
        $min_year = get_lang('MinYear');
        $min_month = get_lang('MinMonth');
        $min_week = get_lang('MinWeek');
        $min_day = get_lang('MinDay');
        $min_hour = get_lang('MinHour');
        $min_minute = get_lang('MinMinute');

        $min_decades = get_lang('MinDecades');
        $min_years = get_lang('MinYears');
        $min_months = get_lang('MinMonths');
        $min_weeks = get_lang('MinWeeks');
        $min_days = get_lang('MinDays');
        $min_hours = get_lang('MinHours');
        $min_minutes = get_lang('MinMinutes');

        // original 1
        //$sec_time=array('century'=>3.1556926*pow(10,9),'decade'=>315569260,'year'=>31556926,'month'=>2629743.83,'week'=>604800,'day'=>86400,'hour'=>3600,'minute'=>60,'second'=>1);
        //$sec_time=array(get_lang('MinDecade')=>315569260,get_lang('MinYear')=>31556926,get_lang('MinMonth')=>2629743.83,get_lang('MinWeek')=>604800,get_lang('MinDay')=>86400,get_lang('MinHour')=>3600,get_lang('MinMinute')=>60);
        $sec_time_time = array(315569260, 31556926, 2629743.83, 604800, 86400, 3600, 60);
        $sec_time_sing = array($min_decade, $min_year, $min_month, $min_week, $min_day, $min_hour, $min_minute);
        $sec_time_plu = array($min_decades, $min_years, $min_months, $min_weeks, $min_days, $min_hours, $min_minutes);
        $initialized = true;
    }

    $dst_date = is_string($date) ? strtotime($date) : $date;
    // For avoiding calling date() several times
    $date_array = date('s/i/G/j/n/Y', $dst_date);
    $date_split = explode('/', $date_array);

    $dst_s = $date_split[0];
    $dst_m = $date_split[1];
    $dst_h = $date_split[2];
    $dst_day = $date_split[3];
    $dst_mth = $date_split[4];
    $dst_yr = $date_split[5];

    $dst_date = mktime($dst_h, $dst_m, $dst_s, $dst_mth, $dst_day, $dst_yr);
    $time = $offset = time() - $dst_date; // Seconds between current days and today.

    // Here start the functions sec_to_str()
    $act_day = date('d');
    $act_mth = date('n');
    $act_yr = date('Y');

    if ($dst_day == $act_day && $dst_mth == $act_mth && $dst_yr == $act_yr) {
        return $today;
    }

    if ($dst_day == $act_day - 1 && $dst_mth == $act_mth && $dst_yr == $act_yr) {
        return $yesterday;
    }

    $str_result = array();
    $time_result = array();
    $key_result = array();

    $str = '';
    $i = 0;
    for ($i = 0; $i < count($sec_time_time); $i++) {
        $seconds = $sec_time_time[$i];
        if ($seconds > $time) {
            continue;
        }
        $current_value = intval($time/$seconds);

        if ($current_value != 1) {
            $date_str = $sec_time_plu[$i];
        } else {
            $date_str = $sec_time_sing[$i];

        }
        $key_result[] = $sec_time_sing[$i];

        $str_result[] = $current_value.' '.$date_str;
        $time_result[] = $current_value;
        $str .= $current_value.$date_str;
        $time %= $seconds;
    }

    if ($key_result[0] == $min_day && $key_result[1]== $min_minute) {
        $key_result[1] = ' 0 '.$min_hours;
        $str_result[0] = $time_result[0].' '.$key_result[0];
        $str_result[1] = $key_result[1];
    }

    if ($key_result[0] == $min_year && ($key_result[1] == $min_day || $key_result[1] == $min_week)) {
        $key_result[1] = ' 0 '.$min_months;
        $str_result[0] = $time_result[0].' '.$key_result[0];
        $str_result[1] = $key_result[1];
    }

    if (!empty($str_result[1])) {
        $str = $str_result[0].', '.$str_result[1];
    } else {
        $str = $str_result[0];
    }

    date_default_timezone_set($system_timezone);
    return $str;
}

/**
 * Converts a date to the right timezone and localizes it in the format given as an argument
 * @param mixed The time to be converted
 * @param mixed Format to be used (TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG)
 * @param string Timezone to be converted from. If null, UTC will be assumed.
 * @return string Converted and localized date
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_convert_and_format_date($time = null, $format = null, $from_timezone = null) {
    // First, convert the datetime to the right timezone
    $time = api_get_local_time($time, null, $from_timezone);
    // Second, localize the date
    return api_format_date($time, $format);
}

/**
 * Returns an array of translated week days in short names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of week days (short names).
 * Example: api_get_week_days_short('english') means array('Sun', 'Mon', ... 'Sat').
 * Note: For all languges returned days are in the English order.
 */
function api_get_week_days_short($language = null) {
    $days = &_api_get_day_month_names($language);
    return $days['days_short'];
}

/**
 * Returns an array of translated week days.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of week days.
 * Example: api_get_week_days_long('english') means array('Sunday, 'Monday', ... 'Saturday').
 * Note: For all languges returned days are in the English order.
 */
function api_get_week_days_long($language = null) {
    $days = &_api_get_day_month_names($language);
    return $days['days_long'];
}

/**
 * Returns an array of translated months in short names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of months (short names).
 * Example: api_get_months_short('english') means array('Jan', 'Feb', ... 'Dec').
 */
function api_get_months_short($language = null) {
    $months = &_api_get_day_month_names($language);
    return $months['months_short'];
}

/**
 * Returns an array of translated months.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of months.
 * Example: api_get_months_long('english') means array('January, 'February' ... 'December').
 */
function api_get_months_long($language = null) {
    $months = &_api_get_day_month_names($language);
    return $months['months_long'];
}


/**
 * Name order conventions
 */

/**
 * Builds a person (full) name depending on the convention for a given language.
 * @param string $first_name			The first name of the preson.
 * @param string $last_name				The last name of the person.
 * @param string $title					The title of the person.
 * @param int/string $format (optional)	The person name format. It may be a pattern-string (for example '%t %l, %f' or '%T %F %L', ...) or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)	The language indentificator. If it is omited, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							The result is sort of full name of the person.
 * Sample results:
 * Peter Ustinoff or Dr. Peter Ustinoff     - the Western order
 * Ustinoff Peter or Dr. Ustinoff Peter     - the Eastern order
 * Ustinoff, Peter or - Dr. Ustinoff, Peter - the library order
 * Note: See the file chamilo/main/inc/lib/internationalization_database/name_order_conventions.php where you can revise the convention for your language.
 * @author Carlos Vargas <carlos.vargas@dokeos.com> - initial implementation.
 * @author Ivan Tcholakov
 */
function api_get_person_name($first_name, $last_name, $title = null, $format = null, $language = null, $encoding = null) {
    static $valid = array();
    if (empty($format)) {
        $format = PERSON_NAME_COMMON_CONVENTION;
    }
    //We check if the language is supported, otherwise we check the interface language of the parent language of sublanguage
    $language_is_supported = api_is_language_supported($language);
    if (!$language_is_supported || empty($language)) {
        $language = api_get_interface_language(false, true);
    }

    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!isset($valid[$format][$language])) {
        if (is_int($format)) {
            switch ($format) {
                case PERSON_NAME_COMMON_CONVENTION:
                    $valid[$format][$language] = _api_get_person_name_convention($language, 'format');
                    break;
                case PERSON_NAME_WESTERN_ORDER:
                    $valid[$format][$language] = '%t %f %l';
                    break;
                case PERSON_NAME_EASTERN_ORDER:
                    $valid[$format][$language] = '%t %l %f';
                    break;
                case PERSON_NAME_LIBRARY_ORDER:
                    $valid[$format][$language] = '%t %l, %f';
                    break;
                default:
                    $valid[$format][$language] = '%t %f %l';
                    break;
            }
        } else {
            $valid[$format][$language] = _api_validate_person_name_format($format);
        }
    }
    $format = $valid[$format][$language];
    $person_name = str_replace(array('%f', '%l', '%t'), array($first_name, $last_name, $title), $format);
    if (strpos($format, '%F') !== false || strpos($format, '%L') !== false || strpos($format, '%T') !== false) {
        $person_name = str_replace(array('%F', '%L', '%T'), array(api_strtoupper($first_name, $encoding), api_strtoupper($last_name, $encoding), api_strtoupper($title, $encoding)), $person_name);
    }
    return _api_clean_person_name($person_name);
}

/**
 * Checks whether a given format represents person name in Western order (for which first name is first).
 * @param int/string $format (optional)	The person name format. It may be a pattern-string (for example '%t. %l, %f') or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)	The language indentificator. If it is omited, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @return bool							The result TRUE means that the order is first_name last_name, FALSE means last_name first_name.
 * Note: You may use this function for determing the order of the fields or columns "First name" and "Last name" in forms, tables and reports.
 * @author Ivan Tcholakov
 */
function api_is_western_name_order($format = null, $language = null) {
    static $order = array();
    if (empty($format)) {
        $format = PERSON_NAME_COMMON_CONVENTION;
    }

    $language_is_supported = api_is_language_supported($language);
    if (!$language_is_supported || empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($order[$format][$language])) {
        $test_name = api_get_person_name('%f', '%l', '%t', $format, $language);
        $order[$format][$language] = stripos($test_name, '%f') <= stripos($test_name, '%l');
    }
    return $order[$format][$language];
}

/**
 * Returns a directive for sorting person names depending on a given language and based on the options in the internationalization "database".
 * @param string $language (optional)	The input language. If it is omited, the current interface language is assumed.
 * @return bool							Returns boolean value. TRUE means ORDER BY first_name, last_name; FALSE means ORDER BY last_name, first_name.
 * Note: You may use this function:
 * 2. for constructing the ORDER clause of SQL queries, related to first_name and last_name;
 * 3. for adjusting php-implemented sorting in tables and reports.
 * @author Ivan Tcholakov
 */
function api_sort_by_first_name($language = null) {
    static $sort_by_first_name = array();

    $language_is_supported = api_is_language_supported($language);
    if (!$language_is_supported || empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($sort_by_first_name[$language])) {
        $sort_by_first_name[$language] = _api_get_person_name_convention($language, 'sort_by');
    }
    return $sort_by_first_name[$language];
}


/**
 * A safe way to calculate binary lenght of a string (as number of bytes)
 */

/**
 * Calculates binary lenght of a string, as number of bytes, regardless the php-setting mbstring.func_overload.
 * This function should work for all multi-byte related changes of PHP5 configuration.
 * @param string $string	The input string.
 * @return int				Returns the length of the input string (or binary data) as number of bytes.
 */
function api_byte_count(& $string) {
    static $use_mb_strlen;
    if (!isset($use_mb_strlen)) {
        $use_mb_strlen = MBSTRING_INSTALLED && ((int) ini_get('mbstring.func_overload') & 2);
    }
    if ($use_mb_strlen) {
        return mb_strlen($string, '8bit');
    }
    return strlen($string);
}


/**
 * Multibyte string conversion functions
 */

/**
 * Converts character encoding of a given string.
 * @param string $string					The string being converted.
 * @param string $to_encoding				The encoding that $string is being converted to.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function mb_convert_encoding() for human-language strings.
 * @link http://php.net/manual/en/function.mb-convert-encoding
 */
function api_convert_encoding($string, $to_encoding, $from_encoding = null) {
    if (empty($from_encoding)) {
        $from_encoding = _api_mb_internal_encoding();
    }
    if (api_equal_encodings($to_encoding, $from_encoding)) {
        return $string; // When conversion is not needed, the string is returned directly, without validation.
    }
    if (_api_mb_supports($to_encoding) && _api_mb_supports($from_encoding)) {
        return @mb_convert_encoding($string, $to_encoding, $from_encoding);
    }
    if (_api_iconv_supports($to_encoding) && _api_iconv_supports($from_encoding)) {
        return @iconv($from_encoding, $to_encoding, $string);
    }
    if (api_is_utf8($to_encoding) && api_is_latin1($from_encoding, true)) {
        return utf8_encode($string);
    }
    if (api_is_latin1($to_encoding, true) && api_is_utf8($from_encoding)) {
        return utf8_decode($string);
    }
    if (_api_convert_encoding_supports($to_encoding) && _api_convert_encoding_supports($from_encoding)) {
        return _api_convert_encoding($string, $to_encoding, $from_encoding);
    }
    return $string; // Here the function gives up.
}

/**
 * Converts a given string into UTF-8 encoded string.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function utf8_encode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-encode
 */
function api_utf8_encode($string, $from_encoding = null) {
    if (empty($from_encoding)) {
        $from_encoding = _api_mb_internal_encoding();
    }
    if (api_is_utf8($from_encoding)) {
        return $string; // When conversion is not needed, the string is returned directly, without validation.
    }
    if (_api_mb_supports($from_encoding)) {
        return @mb_convert_encoding($string, 'UTF-8', $from_encoding);
    }
    if (_api_iconv_supports($from_encoding)) {
        return @iconv($from_encoding, 'UTF-8', $string);
    }
    if (api_is_latin1($from_encoding, true)) {
        return utf8_encode($string);
    }
    if (_api_convert_encoding_supports($from_encoding)) {
        return _api_convert_encoding($string, 'UTF-8', $from_encoding);
    }
    return $string; // Here the function gives up.
}

/**
 * Converts a given string from UTF-8 encoding to a specified encoding.
 * @param string $string					The string being converted.
 * @param string $to_encoding (optional)	The encoding that $string is being converted to. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function utf8_decode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-decode
 */
function api_utf8_decode($string, $to_encoding = null) {
    if (empty($to_encoding)) {
        $to_encoding = _api_mb_internal_encoding();
    }
    if (api_is_utf8($to_encoding)) {
        return $string; // When conversion is not needed, the string is returned directly, without validation.
    }
    if (_api_mb_supports($to_encoding)) {
        return @mb_convert_encoding($string, $to_encoding, 'UTF-8');
    }
    if (_api_iconv_supports($to_encoding)) {
        return @iconv('UTF-8', $to_encoding, $string);
    }
    if (api_is_latin1($to_encoding, true)) {
        return utf8_decode($string);
    }
    if (_api_convert_encoding_supports($to_encoding)) {
        return _api_convert_encoding($string, $to_encoding, 'UTF-8');
    }
    return $string; // Here the function gives up.
}

/**
 * Converts a given string into the system ecoding (or platform character set).
 * When $from encoding is omited on UTF-8 platforms then language dependent encoding
 * is guessed/assumed. On non-UTF-8 platforms omited $from encoding is assumed as UTF-8.
 * When the parameter $check_utf8_validity is true the function checks string's
 * UTF-8 validity and decides whether to try to convert it or not.
 * This function is useful for problem detection or making workarounds.
 * @param string $string						The string being converted.
 * @param string $from_encoding (optional)		The encoding that $string is being converted from. It is guessed when it is omited.
 * @param bool $check_utf8_validity (optional)	A flag for UTF-8 validity check as condition for making conversion.
 * @return string								Returns the converted string.
 */
function api_to_system_encoding($string, $from_encoding = null, $check_utf8_validity = false) {
    $system_encoding = api_get_system_encoding();
    if (empty($from_encoding)) {
        if (api_is_utf8($system_encoding)) {
            $from_encoding = api_get_non_utf8_encoding();
        } else {
            $from_encoding = 'UTF-8';
        }
    }
    if (api_equal_encodings($system_encoding, $from_encoding)) {
        return $string;
    }
    if ($check_utf8_validity) {
        if (api_is_utf8($system_encoding)) {
            if (api_is_valid_utf8($string)) {
                return $string;
            }
        }
        elseif (api_is_utf8($from_encoding)) {
            if (!api_is_valid_utf8($string)) {
                return $string;
            }
        }
    }
    return api_convert_encoding($string, $system_encoding, $from_encoding);
}

/**
 * Converts all applicable characters to HTML entities.
 * @param string $string				The input string.
 * @param int $quote_style (optional)	The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)	The encoding (of the input string) used in conversion. If it is omited, the platform character set is assumed.
 * @return string						Returns the converted string.
 * This function is aimed at replacing the function htmlentities() for human-language strings.
 * @link http://php.net/manual/en/function.htmlentities
 */
function api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!api_is_utf8($encoding) && _api_html_entity_supports($encoding)) {
        return htmlentities($string, $quote_style, $encoding);
    }
    switch($quote_style) {
        case ENT_COMPAT:
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            break;
        case ENT_QUOTES:
            $string = str_replace(array('&', '\'', '"', '<', '>'), array('&amp;', '&#039;', '&quot;', '&lt;', '&gt;'), $string);
            break;
    }
    if (_api_mb_supports($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        $string = @mb_convert_encoding(api_utf8_encode($string, $encoding), 'HTML-ENTITIES', 'UTF-8');
        if (!api_is_utf8($encoding)) { // Just in case.
            $string = api_utf8_decode($string, $encoding);
        }
    }
    elseif (_api_convert_encoding_supports($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = _api_convert_encoding($string, 'UTF-8', $encoding);
        }
        $string = implode(array_map('_api_html_entity_from_unicode', _api_utf8_to_unicode($string)));
        if (!api_is_utf8($encoding)) { // Just in case.
            $string = _api_convert_encoding($string, $encoding, 'UTF-8');
        }
    }

    return $string;
}

/**
 * Convers HTML entities into normal characters.
 * @param string $string				The input string.
 * @param int $quote_style (optional)	The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)	The encoding (of the result) used in conversion. If it is omited, the platform character set is assumed.
 * @return string						Returns the converted string.
 * This function is aimed at replacing the function html_entity_decode() for human-language strings.
 * @link http://php.net/html_entity_decode
 */
function api_html_entity_decode($string, $quote_style = ENT_COMPAT, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_html_entity_supports($encoding)) {
        return html_entity_decode($string, $quote_style, $encoding);
    }
    if (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        $string = html_entity_decode($string, $quote_style, 'UTF-8');
        if (!api_is_utf8($encoding)) {
            return api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    return $string; // Here the function guves up.
}

/**
 * This function encodes (conditionally) a given string to UTF-8 if XmlHttp-request has been detected.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_xml_http_response_encode($string, $from_encoding = null) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (empty($from_encoding)) {
            $from_encoding = _api_mb_internal_encoding();
        }
        if (!api_is_utf8($from_encoding)) {
            return api_utf8_encode($string, $from_encoding);
        }
    }
    return $string;
}

/**
 * This function converts a given string to the encoding that filesystem uses for representing file/folder names.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_file_system_encode($string, $from_encoding = null) {
    if (empty($from_encoding)) {
        $from_encoding = _api_mb_internal_encoding();
    }
    return api_convert_encoding($string, api_get_file_system_encoding(), $from_encoding);
}

/**
 * This function converts a given string from the encoding that filesystem uses for representing file/folder names.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_file_system_decode($string, $to_encoding = null) {
    if (empty($to_encoding)) {
        $to_encoding = _api_mb_internal_encoding();
    }
    return api_convert_encoding($string, $to_encoding, api_get_file_system_encoding());
}

/**
 * Transliterates a string with arbitrary encoding into a plain ASCII string.
 *
 * Example:
 * echo api_transliterate(api_html_entity_decode(
 * 	'&#1060;&#1105;&#1076;&#1086;&#1088; '.
 * 	'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
 * 	'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
 * 	ENT_QUOTES, 'UTF-8'), 'X', 'UTF-8');
 * The output should be: Fyodor Mihaylovich Dostoevkiy
 *
 * @param string $string					The input string.
 * @param string $unknown (optional)		Replacement character for unknown characters and illegal UTF-8 sequences.
 * @param string $from_encoding (optional)	The encoding of the input string. If it is omited, the platform character set is assumed.
 * @return string							Plain ASCII output.
 *
 * Based on Drupal's module "Transliteration", version 6.x-2.1, 09-JUN-2009:
 * @author Stefan M. Kudwien (smk-ka)
 * @author Daniel F. Kudwien (sun)
 * @link http://drupal.org/project/transliteration
 *
 * See also MediaWiki's UtfNormal.php and CPAN's Text::Unidecode library
 * @link http://www.mediawiki.org
 * @link http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm).
 *
 * Adaptation for Chamilo 1.8.7, 2010
 * Initial implementation for Dokeos 1.8.6.1, 12-JUN-2009
 * @author Ivan Tcholakov
 */
function api_transliterate($string, $unknown = '?', $from_encoding = null) {
    static $map = array();
    $string = api_utf8_encode($string, $from_encoding);
    // Screen out some characters that eg won't be allowed in XML.
    $string = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $unknown, $string);
    // ASCII is always valid NFC!
    // If we're only ever given plain ASCII, we can avoid the overhead
    // of initializing the decomposition tables by skipping out early.
    if (api_is_valid_ascii($string)) {
        return $string;
    }
    static $tail_bytes;
    if (!isset($tail_bytes)) {
        // Each UTF-8 head byte is followed by a certain
        // number of tail bytes.
        $tail_bytes = array();
        for ($n = 0; $n < 256; $n++) {
            if ($n < 0xc0) {
                $remaining = 0;
            }
            elseif ($n < 0xe0) {
                $remaining = 1;
            }
            elseif ($n < 0xf0) {
                $remaining = 2;
            }
            elseif ($n < 0xf8) {
                $remaining = 3;
            }
            elseif ($n < 0xfc) {
                $remaining = 4;
            }
            elseif ($n < 0xfe) {
                $remaining = 5;
            } else {
                $remaining = 0;
            }
            $tail_bytes[chr($n)] = $remaining;
        }
    }

    // Chop the text into pure-ASCII and non-ASCII areas;
    // large ASCII parts can be handled much more quickly.
    // Don't chop up Unicode areas for punctuation, though,
    // that wastes energy.
    preg_match_all('/[\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*/', $string, $matches);
    $result = '';
    foreach ($matches[0] as $str) {
        if ($str{0} < "\x80") {
            // ASCII chunk: guaranteed to be valid UTF-8
            // and in normal form C, so skip over it.
            $result .= $str;
            continue;
        }
        // We'll have to examine the chunk byte by byte to ensure
        // that it consists of valid UTF-8 sequences, and to see
        // if any of them might not be normalized.
        //
        // Since PHP is not the fastest language on earth, some of
        // this code is a little ugly with inner loop optimizations.
        $head = '';
        $chunk = api_byte_count($str);
        // Counting down is faster. I'm *so* sorry.
        $len = $chunk + 1;
        for ($i = -1; --$len; ) {
            $c = $str{++$i};
            if ($remaining = $tail_bytes[$c]) {
                // UTF-8 head byte!
                $sequence = $head = $c;
                do {
                    // Look for the defined number of tail bytes...
                    if (--$len && ($c = $str{++$i}) >= "\x80" && $c < "\xc0") {
                    // Legal tail bytes are nice.
                    $sequence .= $c;
                    } else {
                        if ($len == 0) {
                            // Premature end of string!
                            // Drop a replacement character into output to
                            // represent the invalid UTF-8 sequence.
                            $result .= $unknown;
                            break 2;
                        } else {
                            // Illegal tail byte; abandon the sequence.
                            $result .= $unknown;
                            // Back up and reprocess this byte; it may itself
                            // be a legal ASCII or UTF-8 sequence head.
                            --$i;
                            ++$len;
                            continue 2;
                        }
                    }
                } while (--$remaining);
                $n = ord($head);
                if ($n <= 0xdf) {
                    $ord = ($n - 192) * 64 + (ord($sequence{1}) - 128);
                }
                else if ($n <= 0xef) {
                    $ord = ($n - 224) * 4096 + (ord($sequence{1}) - 128) * 64 + (ord($sequence{2}) - 128);
                }
                else if ($n <= 0xf7) {
                    $ord = ($n - 240) * 262144 + (ord($sequence{1}) - 128) * 4096 + (ord($sequence{2}) - 128) * 64 + (ord($sequence{3}) - 128);
                }
                else if ($n <= 0xfb) {
                    $ord = ($n - 248) * 16777216 + (ord($sequence{1}) - 128) * 262144 + (ord($sequence{2}) - 128) * 4096 + (ord($sequence{3}) - 128) * 64 + (ord($sequence{4}) - 128);
                }
                else if ($n <= 0xfd) {
                    $ord = ($n - 252) * 1073741824 + (ord($sequence{1}) - 128) * 16777216 + (ord($sequence{2}) - 128) * 262144 + (ord($sequence{3}) - 128) * 4096 + (ord($sequence{4}) - 128) * 64 + (ord($sequence{5}) - 128);
                }
                // Lookup and replace a character from the transliteration database.
                $bank = $ord >> 8;
                // Check if we need to load a new bank
                if (!isset($map[$bank])) {
                    $file = dirname(__FILE__).'/internationalization_database/transliteration/' . sprintf('x%02x', $bank) . '.php';
                    if (file_exists($file)) {
                        $map[$bank] = include ($file);
                    } else {
                        $map[$bank] = array('en' => array());
                    }
                }
                $ord = $ord & 255;
                $result .= isset($map[$bank]['en'][$ord]) ? $map[$bank]['en'][$ord] : $unknown;

                $head = '';
            }
            elseif ($c < "\x80") {
                // ASCII byte.
                $result .= $c;
                $head = '';
            }
            elseif ($c < "\xc0") {
                // Illegal tail bytes.
                if ($head == '') {
                    $result .= $unknown;
                }
            } else {
                // Miscellaneous freaks.
                $result .= $unknown;
                $head = '';
            }
        }
    }
    return $result;
}


/**
 * Common multibyte string functions
 */

/**
 * Takes the first character in a string and returns its Unicode codepoint.
 * @param string $character				The input string.
 * @param string $encoding (optional)	The encoding of the input string. If it is omitted, the platform character set will be used by default.
 * @return int							Returns: the codepoint of the first character; or 0xFFFD (unknown character) when the input string is empty.
 * This is a multibyte aware version of the function ord().
 * @link http://php.net/manual/en/function.ord.php
 * Note the difference with the original funtion ord(): ord('') returns 0, api_ord('') returns 0xFFFD (unknown character).
 */
function api_ord($character, $encoding) {
    return _api_utf8_ord(api_utf8_encode($character, $encoding));
}

/**
 * Takes a Unicode codepoint and returns its correspondent character, encoded in given encoding.
 * @param int $codepoint				The Unicode codepoint.
 * @param string $encoding (optional)	The encoding of the returned character. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the corresponding character, encoded as it has been requested.
 * This is a multibyte aware version of the function chr().
 * @link http://php.net/manual/en/function.chr.php
 */
function api_chr($codepoint, $encoding) {
    return api_utf8_decode(_api_utf8_chr($codepoint), $encoding);
}

/**
 * This function returns a string or an array with all occurrences of search in subject (ignoring case) replaced with the given replace value.
 * @param mixed $search					String or array of strings to be found.
 * @param mixed $replace				String or array of strings used for replacement.
 * @param mixed $subject				String or array of strings being searced.
 * @param int $count (optional)			The number of matched and replaced needles will be returned in count, which is passed by reference.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						String or array as a result.
 * Notes:
 * If $subject is an array, then the search and replace is performed with every entry of subject, the return value is an array.
 * If $search and $replace are arrays, then the function takes a value from each array and uses it to do search and replace on subject.
 * If $replace has fewer values than search, then an empty string is used for the rest of replacement values.
 * If $search is an array and $replace is a string, then this replacement string is used for every value of search.
 * This function is aimed at replacing the function str_ireplace() for human-language strings.
 * @link http://php.net/manual/en/function.str-ireplace
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * Adaptation for Chamilo 1.8.7, 2010
 * Initial implementation Dokeos LMS, August 2009
 * @author Ivan Tcholakov
 */
function api_str_ireplace($search, $replace, $subject, & $count = null, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (api_is_encoding_supported($encoding)) {
        if (!is_array($search) && !is_array($replace)) {
            if (!api_is_utf8($encoding)) {
                $search = api_utf8_encode($search, $encoding);
            }
            $slen = api_byte_count($search);
            if ( $slen == 0 ) {
                return $subject;
            }
            if (!api_is_utf8($encoding)) {
                $replace = api_utf8_encode($replace, $encoding);
                $subject = api_utf8_encode($subject, $encoding);
            }
            $lendif = api_byte_count($replace) - api_byte_count($search);
            $search = api_strtolower($search, 'UTF-8');
            $search = preg_quote($search);
            $lstr = api_strtolower($subject, 'UTF-8');
            $i = 0;
            $matched = 0;
            while (preg_match('/(.*)'.$search.'/Us', $lstr, $matches) ) {
                if ($i === $count) {
                    break;
                }
                $mlen = api_byte_count($matches[0]);
                $lstr = substr($lstr, $mlen);
                $subject = substr_replace($subject, $replace, $matched + api_byte_count($matches[1]), $slen);
                $matched += $mlen + $lendif;
                $i++;
            }
            if (!api_is_utf8($encoding)) {
                $subject = api_utf8_decode($subject, $encoding);
            }
            return $subject;
        } else {
            foreach (array_keys($search) as $k) {
                if (is_array($replace)) {
                    if (array_key_exists($k, $replace)) {
                        $subject = api_str_ireplace($search[$k], $replace[$k], $subject, $count);
                    } else {
                        $subject = api_str_ireplace($search[$k], '', $subject, $count);
                    }
                } else {
                    $subject = api_str_ireplace($search[$k], $replace, $subject, $count);
                }
            }
            return $subject;
        }
    }
    if (is_null($count)) {
        return str_ireplace($search, $replace, $subject);
    }
    return str_ireplace($search, $replace, $subject, $count);
}

/**
 * Converts a string to an array.
 * @param string $string				The input string.
 * @param int $split_length				Maximum character-length of the chunk, one character by default.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array						The result array of chunks with the spcified length.
 * Notes:
 * If the optional split_length parameter is specified, the returned array will be broken down into chunks
 * with each being split_length in length, otherwise each chunk will be one character in length.
 * FALSE is returned if split_length is less than 1.
 * If the split_length length exceeds the length of string, the entire string is returned as the first (and only) array element.
 * This function is aimed at replacing the function str_split() for human-language strings.
 * @link http://php.net/str_split
 */
function api_str_split($string, $split_length = 1, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (empty($string)) {
        return array();
    }
    if ($split_length < 1) {
        return false;
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return str_split($string, $split_length);
    }
    if (api_is_encoding_supported($encoding)) {
        $len = api_strlen($string);
        if ($len <= $split_length) {
            return array($string);
        }
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        if (preg_match_all('/.{'.$split_length.'}|[^\x00]{1,'.$split_length.'}$/us', $string, $result) === false) {
            return array();
        }
        if (!api_is_utf8($encoding)) {
            global $_api_encoding;
            $_api_encoding = $encoding;
            $result = _api_array_utf8_decode($result[0]);
        }
        return $result[0];
    }
    return str_split($string, $split_length);
}

/**
 * Finds position of first occurrence of a string within another, case insensitive.
 * @param string $haystack				The string from which to get the position of the first occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset					The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions stripos() and mb_stripos() for human-language strings.
 * @link http://php.net/manual/en/function.stripos
 * @link http://php.net/manual/en/function.mb-stripos
 */
function api_stripos($haystack, $needle, $offset = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_mb_supports($encoding)) {
        return @mb_stripos($haystack, $needle, $offset, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (MBSTRING_INSTALLED) {
            if (!api_is_utf8($encoding)) {
                $haystack = api_utf8_encode($haystack, $encoding);
                $needle = api_utf8_encode($needle, $encoding);
            }
            return @mb_stripos($haystack, $needle, $offset, 'UTF-8');
        }
        return api_strpos(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $offset, $encoding);
    }
    return stripos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another, case insensitive.
 * @param string $haystack					The string from which to get the first occurrence.
 * @param mixed $needle						The string to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions stristr() and mb_stristr() for human-language strings.
 * @link http://php.net/manual/en/function.stristr
 * @link http://php.net/manual/en/function.mb-stristr
 */
function api_stristr($haystack, $needle, $before_needle = false, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_mb_supports($encoding)) {
        return @mb_stristr($haystack, $needle, $before_needle, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (MBSTRING_INSTALLED) {
            if (!api_is_utf8($encoding)) {
                $haystack = api_utf8_encode($haystack, $encoding);
                $needle = api_utf8_encode($needle, $encoding);
            }
            $result = @mb_stristr($haystack, $needle, $before_needle, 'UTF-8');
            if ($result === false) {
                return false;
            }
            if (!api_is_utf8($encoding)) {
                return api_utf8_decode($result, $encoding);
            }
            return $result;
        }
        $result = api_strstr(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $before_needle, $encoding);
        if ($result === false) {
            return false;
        }
        if ($before_needle) {
            return api_substr($haystack, 0, api_strlen($result, $encoding), $encoding);
        }
        return api_substr($haystack, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), null, $encoding);
    }
    if (!IS_PHP_53) {
        return stristr($haystack, $needle);
    }
    return stristr($haystack, $needle, $before_needle);
}

/**
 * Returns length of the input string.
 * @param string $string				The string which length is to be calculated.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns the number of characters within the string. A multi-byte character is counted as 1.
 * This function is aimed at replacing the functions strlen() and mb_strlen() for human-language strings.
 * @link http://php.net/manual/en/function.strlen
 * @link http://php.net/manual/en/function.mb-strlen
 * Note: When you use strlen() to test for an empty string, you needn't change it to api_strlen().
 * For example, in lines like the following:
 * if (strlen($string) > 0)
 * if (strlen($string) != 0)
 * there is no need the original function strlen() to be changed, it works correctly and faster for these cases.
 */
function api_strlen($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return strlen($string);
    }
    if (_api_mb_supports($encoding)) {
        return @mb_strlen($string, $encoding);
    }
    if (_api_iconv_supports($encoding)) {
        return @iconv_strlen($string, $encoding);
    }
    if (api_is_utf8($encoding)) {
        return api_byte_count(preg_replace("/[\x80-\xBF]/", '', $string));
    }
    return strlen($string);
}

/**
 * Finds position of first occurrence of a string within another.
 * @param string $haystack				The string from which to get the position of the first occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strpos() and mb_strpos() for human-language strings.
 * @link http://php.net/manual/en/function.strpos
 * @link http://php.net/manual/en/function.mb-strpos
 */
function api_strpos($haystack, $needle, $offset = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return strpos($haystack, $needle, $offset);
    }
    elseif (_api_mb_supports($encoding)) {
        return @mb_strpos($haystack, $needle, $offset, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $haystack = api_utf8_encode($haystack, $encoding);
            $needle = api_utf8_encode($needle, $encoding);
        }
        if (MBSTRING_INSTALLED) {
            return @mb_strpos($haystack, $needle, $offset, 'UTF-8');
        }
        if (empty($offset)) {
            $haystack = explode($needle, $haystack, 2);
            if (count($haystack) > 1) {
                return api_strlen($haystack[0]);
            }
            return false;
        }
        $haystack = api_substr($haystack, $offset);
        if (($pos = api_strpos($haystack, $needle)) !== false ) {
            return $pos + $offset;
        }
        return false;
    }
    return strpos($haystack, $needle, $offset);
}

/**
 * Finds the last occurrence of a character in a string.
 * @param string $haystack					The string from which to get the last occurrence.
 * @param mixed $needle						The string which first character is to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if the first character from $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence to the end.
 * This function is aimed at replacing the functions strrchr() and mb_strrchr() for human-language strings.
 * @link http://php.net/manual/en/function.strrchr
 * @link http://php.net/manual/en/function.mb-strrchr
 */
function api_strrchr($haystack, $needle, $before_needle = false, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_is_single_byte_encoding($encoding)) {
        if (!$before_needle) {
            return strrchr($haystack, $needle);
        }
        $result = strrchr($haystack, $needle);
        if ($result === false) {
            return false;
        }
        return api_substr($haystack, 0, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), $encoding);
    }
    elseif (_api_mb_supports($encoding)) {
        return @mb_strrchr($haystack, $needle, $before_needle, $encoding);
    }
    elseif (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $haystack = api_utf8_encode($haystack, $encoding);
            $needle = api_utf8_encode($needle, $encoding);
        }
        $result = @mb_strrchr($haystack, $needle, $before_needle, 'UTF-8');
        if ($result === false) {
            return false;
        }
        if (!api_is_utf8($encoding)) {
            return api_utf8_decode($result, $encoding);
        }
        return $result;
    }
    if (!$before_needle) {
        return strrchr($haystack, $needle);
    }
    $result = strrchr($haystack, $needle);
    if ($result === false) {
        return false;
    }
    return api_substr($haystack, 0, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), $encoding);
}

/**
 * Reverses a string.
 * @param string $string				The string to be reversed.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the reversed string.
 * This function is aimed at replacing the function strrev() for human-language strings.
 * @link http://php.net/manual/en/function.strrev
 */
function api_strrev($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (empty($string)) {
        return '';
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return strrev($string);
    }
    if (api_is_encoding_supported($encoding)) {
        return implode(array_reverse(api_str_split($string, 1, $encoding)));
    }
    return strrev($string);
}

/**
 * Finds the position of last occurrence (case insensitive) of a string in a string.
 * @param string $haystack				The string from which to get the position of the last occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		$offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence (case insensitive) of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strripos() and mb_strripos() for human-language strings.
 * @link http://php.net/manual/en/function.strripos
 * @link http://php.net/manual/en/function.mb-strripos
 */
function api_strripos($haystack, $needle, $offset = 0, $encoding = null) {
    return api_strrpos(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $offset, $encoding);
}

/**
 * Finds the position of last occurrence of a string in a string.
 * @param string $haystack				The string from which to get the position of the last occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		$offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strrpos() and mb_strrpos() for human-language strings.
 * @link http://php.net/manual/en/function.strrpos
 * @link http://php.net/manual/en/function.mb-strrpos
 */
function api_strrpos($haystack, $needle, $offset = 0, $encoding = null) {

    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return strrpos($haystack, $needle, $offset);
    }
    if (_api_mb_supports($encoding) && IS_PHP_52) {
        return @mb_strrpos($haystack, $needle, $offset, $encoding);
    } elseif (api_is_encoding_supported($encoding)) {

        if (!api_is_utf8($encoding)) {
            $haystack 	= api_utf8_encode($haystack, $encoding);
            $needle 	= api_utf8_encode($needle, $encoding);
        }
        // In PHP 5.1 the $offset parameter didn't exist see http://php.net/manual/en/function.mb-strrpos.php
        if (MBSTRING_INSTALLED && IS_PHP_SUP_OR_EQ_51) {
            //return @mb_strrpos($haystack, $needle, $offset, 'UTF-8');
            //@todo fix the missing $offset parameter
            return @mb_strrpos($haystack, $needle, 'UTF-8');
        }
        if (MBSTRING_INSTALLED && IS_PHP_SUP_OR_EQ_52) {
        	return @mb_strrpos($haystack, $needle, $offset, 'UTF-8');
        }

        // This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
        $found = false;
        $haystack = _api_utf8_to_unicode($haystack);
        $haystack_count = count($haystack);
        $matches = array_count_values($haystack);
        $needle = _api_utf8_to_unicode($needle);
        $needle_count = count($needle);
        $position = $offset;
        while (($found === false) && ($position < $haystack_count)) {
            if (isset($needle[0]) && $needle[0] === $haystack[$position]) {
                for ($i = 1; $i < $needle_count; $i++) {
                    if ($needle[$i] !== $haystack[$position + $i]) {
                        if ($needle[$i] === $haystack[($position + $i) -1]) {
                            $position--;
                            $found = true;
                            continue;
                        }
                    }
                }
                if (!$offset && isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
                    $matches[$needle[0]] = $matches[$needle[0]] - 1;
                } elseif ($i === $needle_count) {
                    $found = true;
                    $position--;
                }
            }
            $position++;
        }
        return ($found) ? $position : false;
    }
    return strrpos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another.
 * @param string $haystack					The string from which to get the first occurrence.
 * @param mixed $needle						The string to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions strstr() and mb_strstr() for human-language strings.
 * @link http://php.net/manual/en/function.strstr
 * @link http://php.net/manual/en/function.mb-strstr
 */
function api_strstr($haystack, $needle, $before_needle = false, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (!is_string($needle)) {
        $needle = (int)$needle;
        if (api_is_utf8($encoding)) {
            $needle = _api_utf8_chr($needle);
        } else {
            $needle = chr($needle);
        }
    }
    if ($needle == '') {
        return false;
    }
    if (_api_is_single_byte_encoding($encoding)) {
        // Adding the missing parameter $before_needle to the original function strstr(), PHP_VERSION < 5.3
        if (!$before_needle) {
            return strstr($haystack, $needle);
        }
        if (!IS_PHP_53) {
            $result = explode($needle, $haystack, 2);
            if ($result === false || count($result) < 2) {
                return false;
            }
            return $result[0];
        }
        return strstr($haystack, $needle, $before_needle);
    }
    if (_api_mb_supports($encoding)) {
        return @mb_strstr($haystack, $needle, $before_needle, $encoding);
    }
    elseif (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $haystack = api_utf8_encode($haystack, $encoding);
            $needle = api_utf8_encode($needle, $encoding);
        }
        $result = @mb_strstr($haystack, $needle, $before_needle, 'UTF-8');
        if ($result !== false) {
            if (!api_is_utf8($encoding)) {
                return api_utf8_decode($result, $encoding);
            }
            return $result;
        }
        return false;
    }
    // Adding the missing parameter $before_needle to the original function strstr(), PHP_VERSION < 5.3
    if (!$before_needle) {
        return strstr($haystack, $needle);
    }
    if (!IS_PHP_53) {
        $result = explode($needle, $haystack, 2);
        if ($result === false || count($result) < 2) {
            return false;
        }
        return $result[0];
    }
    return strstr($haystack, $needle, $before_needle);
}

/**
 * Makes a string lowercase.
 * @param string $string				The string being lowercased.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the string with all alphabetic characters converted to lowercase.
 * This function is aimed at replacing the functions strtolower() and mb_strtolower() for human-language strings.
 * @link http://php.net/manual/en/function.strtolower
 * @link http://php.net/manual/en/function.mb-strtolower
 */
function api_strtolower($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_mb_supports($encoding)) {
        return @mb_strtolower($string, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        if (MBSTRING_INSTALLED) {
            $string = @mb_strtolower($string, 'UTF-8');
        } else {
            // This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
            $codepoints = _api_utf8_to_unicode($string);
            $length = count($codepoints);
            $matched = false;
            $result = array();
            for ($i = 0 ; $i < $length; $i++) {
                $codepoint = $codepoints[$i];
                if ($codepoint < 128) {
                    $str = strtolower(chr($codepoint));
                    $strlen = api_byte_count($str);
                    for ($ii = 0 ; $ii < $strlen; $ii++) {
                        $lower = ord($str[$ii]);
                    }
                    $result[] = $lower;
                    $matched = true;
                } else {
                    $matched = false;
                    $properties = &_api_utf8_get_letter_case_properties($codepoint, 'upper');
                    if (!empty($properties)) {
                        foreach ($properties as $key => $value) {
                            if ($properties[$key]['upper'] == $codepoint && count($properties[$key]['lower'][0]) === 1) {
                                $result[] = $properties[$key]['lower'][0];
                                $matched = true;
                                break 1;
                            }
                        }
                    }
                }
                if ($matched === false) {
                    $result[] = $codepoint;
                }
            }
            $string = _api_utf8_from_unicode($result);
        }
        if (!api_is_utf8($encoding)) {
            return api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    return strtolower($string);
}

/**
 * Makes a string uppercase.
 * @param string $string				The string being uppercased.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the string with all alphabetic characters converted to uppercase.
 * This function is aimed at replacing the functions strtoupper() and mb_strtoupper() for human-language strings.
 * @link http://php.net/manual/en/function.strtoupper
 * @link http://php.net/manual/en/function.mb-strtoupper
 */
function api_strtoupper($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_mb_supports($encoding)) {
        return @mb_strtoupper($string, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        if (MBSTRING_INSTALLED) {
            $string = @mb_strtoupper($string, 'UTF-8');
        } else {
            // This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
            $codepoints = _api_utf8_to_unicode($string);
            $length = count($codepoints);
            $matched = false;
            $replaced = array();
            $result = array();
            for ($i = 0 ; $i < $length; $i++) {
                $codepoint = $codepoints[$i];
                if ($codepoint < 128) {
                    $str = strtoupper(chr($codepoint));
                    $strlen = api_byte_count($str);
                    for ($ii = 0 ; $ii < $strlen; $ii++) {
                        $lower = ord($str[$ii]);
                    }
                    $result[] = $lower;
                    $matched = true;
                } else {
                    $matched = false;
                    $properties = &_api_utf8_get_letter_case_properties($codepoint);
                    $property_count = count($properties);
                    if (!empty($properties)) {
                        foreach ($properties as $key => $value) {
                            $matched = false;
                            $replace = 0;
                            if ($length > 1 && count($properties[$key]['lower']) > 1) {
                                $j = 0;
                                for ($ii = 0; $ii < count($properties[$key]['lower']); $ii++) {
                                    $next_codepoint = $next_codepoints[$i + $ii];
                                    if (isset($next_codepoint) && ($next_codepoint == $properties[$key]['lower'][$j + $ii])) {
                                        $replace++;
                                    }
                                }
                                if ($replace == count($properties[$key]['lower'])) {
                                    $result[] = $properties[$key]['upper'];
                                    $replaced = array_merge($replaced, array_values($properties[$key]['lower']));
                                    $matched = true;
                                    break 1;
                                }
                            } elseif ($length > 1 && $property_count > 1) {
                                $j = 0;
                                for ($ii = 1; $ii < $property_count; $ii++) {
                                    $next_codepoint = $next_codepoints[$i + $ii - 1];
                                    if (in_array($next_codepoint, $properties[$ii]['lower'])) {
                                        for ($jj = 0; $jj < count($properties[$ii]['lower']); $jj++) {
                                            $next_codepoint = $next_codepoints[$i + $jj];
                                            if (isset($next_codepoint) && ($next_codepoint == $properties[$ii]['lower'][$j + $jj])) {
                                                $replace++;
                                            }
                                        }
                                        if ($replace == count($properties[$ii]['lower'])) {
                                            $result[] = $properties[$ii]['upper'];
                                            $replaced = array_merge($replaced, array_values($properties[$ii]['lower']));
                                            $matched = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                            if ($properties[$key]['lower'][0] == $codepoint) {
                                $result[] = $properties[$key]['upper'];
                                $matched = true;
                                break 1;
                            }
                        }
                    }
                }
                if ($matched === false && !in_array($codepoint, $replaced, true)) {
                    $result[] = $codepoint;
                }
            }
            $string = _api_utf8_from_unicode($result);
        }
        if (!api_is_utf8($encoding)) {
            return api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    return strtoupper($string);
}

/**
// Gets part of a string.
 * @param string $string				The input string.
 * @param int $start					The first position from which the extracted part begins.
 * @param int $length					The length in character of the extracted part.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the part of the string specified by the start and length parameters.
 * Note: First character's position is 0. Second character position is 1, and so on.
 * This function is aimed at replacing the functions substr() and mb_substr() for human-language strings.
 * @link http://php.net/manual/en/function.substr
 * @link http://php.net/manual/en/function.mb-substr
 */
function api_substr($string, $start, $length = null, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    // Passing null as $length would mean 0. This behaviour has been corrected here.
    if (is_null($length)) {
        $length = api_strlen($string, $encoding);
    }
    if (_api_is_single_byte_encoding($encoding)) {
        return substr($string, $start, $length);
    }
    if (_api_mb_supports($encoding)) {
        return @mb_substr($string, $start, $length, $encoding);
    }
    elseif (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        if (MBSTRING_INSTALLED) {
            $string = @mb_substr($string, $start, $length, 'UTF-8');
        } else {
            // The following branch of code is from the Drupal CMS, see the function drupal_substr().
            $strlen = api_byte_count($string);
            // Find the starting byte offset
            $bytes = 0;
            if ($start > 0) {
                // Count all the continuation bytes from the start until we have found
                // $start characters
                $bytes = -1; $chars = -1;
                while ($bytes < $strlen && $chars < $start) {
                    $bytes++;
                    $c = ord($string[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
            }
            else if ($start < 0) {
                // Count all the continuation bytes from the end until we have found
                // abs($start) characters
                $start = abs($start);
                $bytes = $strlen; $chars = 0;
                while ($bytes > 0 && $chars < $start) {
                    $bytes--;
                    $c = ord($string[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
            }
            $istart = $bytes;
            // Find the ending byte offset
            if ($length === NULL) {
                $bytes = $strlen - 1;
            }
            else if ($length > 0) {
                // Count all the continuation bytes from the starting index until we have
                // found $length + 1 characters. Then backtrack one byte.
                $bytes = $istart; $chars = 0;
                while ($bytes < $strlen && $chars < $length) {
                    $bytes++;
                    $c = ord($string[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
                $bytes--;
            }
            else if ($length < 0) {
                // Count all the continuation bytes from the end until we have found
                // abs($length) characters
                $length = abs($length);
                $bytes = $strlen - 1; $chars = 0;
                while ($bytes >= 0 && $chars < $length) {
                    $c = ord($string[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                    $bytes--;
                }
            }
            $iend = $bytes;
            $string = substr($string, $istart, max(0, $iend - $istart + 1));
        }
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    return substr($string, $start, $length);
}

/**
 * Counts the number of substring occurrences.
 * @param string $haystack				The string being checked.
 * @param string $needle				The string being found.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							The number of times the needle substring occurs in the haystack string.
 * @link http://php.net/manual/en/function.mb-substr-count.php
 */
function api_substr_count($haystack, $needle, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_mb_supports($encoding)) {
        return @mb_substr_count($haystack, $needle, $encoding);
    }
    return substr_count($haystack, $needle);
}

/**
 * Replaces text within a portion of a string.
 * @param string $string				The input string.
 * @param string $replacement			The replacement string.
 * @param int $start					The position from which replacing will begin.
 * Notes:
 * If $start is positive, the replacing will begin at the $start'th offset into the string.
 * If $start is negative, the replacing will begin at the $start'th character from the end of the string.
 * @param int $length (optional)		The position where replacing will end.
 * Notes:
 * If given and is positive, it represents the length of the portion of the string which is to be replaced.
 * If it is negative, it represents the number of characters from the end of string at which to stop replacing.
 * If it is not given, then it will default to api_strlen($string); i.e. end the replacing at the end of string.
 * If $length is zero, then this function will have the effect of inserting replacement into the string at the given start offset.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						The result string is returned.
 * This function is aimed at replacing the function substr_replace() for human-language strings.
 * @link http://php.net/manual/function.substr-replace
 */
function api_substr_replace($string, $replacement, $start, $length = null, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_is_single_byte_encoding($encoding)) {
        if (is_null($length)) {
            return substr_replace($string, $replacement, $start);
        }
        return substr_replace($string, $replacement, $start, $length);
    }
    if (api_is_encoding_supported($encoding)) {
        if (is_null($length)) {
            $length = api_strlen($string);
        }
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
            $replacement = api_utf8_encode($replacement, $encoding);
        }
        $string = _api_utf8_to_unicode($string);
        array_splice($string, $start, $length, _api_utf8_to_unicode($replacement));
        $string = _api_utf8_from_unicode($string);
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    if (is_null($length)) {
        return substr_replace($string, $replacement, $start);
    }
    return substr_replace($string, $replacement, $start, $length);
}

/**
 * Makes a string's first character uppercase.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns a string with the first character capitalized, if that character is alphabetic.
 * This function is aimed at replacing the function ucfirst() for human-language strings.
 * @link http://php.net/manual/en/function.ucfirst
 */
function api_ucfirst($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
       return api_strtoupper(api_substr($string, 0, 1, $encoding), $encoding) . api_substr($string, 1, api_strlen($string, $encoding), $encoding);
}

/**
 * Uppercases the first character of each word in a string.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the modified string.
 * This function is aimed at replacing the function ucwords() for human-language strings.
 * @link http://php.net/manual/en/function.ucwords
 */
function api_ucwords($string, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (_api_mb_supports($encoding)) {
        return @mb_convert_case($string, MB_CASE_TITLE, $encoding);
    }
    if (api_is_encoding_supported($encoding)) {
        if (!api_is_utf8($encoding)) {
            $string = api_utf8_encode($string, $encoding);
        }
        if (MBSTRING_INSTALLED) {
            $string = @mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
        } else {
            // The following fragment (branch) of code is based on the function utf8_ucwords() by Harry Fuecks
            // See http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php
            // Note: [\x0c\x09\x0b\x0a\x0d\x20] matches - form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns.
            // This corresponds to the definition of a "word" defined at http://www.php.net/ucwords
            $pattern = '/(^|([\x0c\x09\x0b\x0a\x0d\x20]+))([^\x0c\x09\x0b\x0a\x0d\x20]{1})[^\x0c\x09\x0b\x0a\x0d\x20]*/u';
            $string = preg_replace_callback($pattern, '_api_utf8_ucwords_callback', $string);
        }
        if (!api_is_utf8($encoding)) {
            return api_utf8_decode($string, $encoding);
        }
        return $string;
    }
    return ucwords($string);
}


/**
 * String operations using regular expressions
 */

/**
 * Performs a regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param array &$matches (optional)	If matches is provided, then it is filled with the results of search (as an array).
 * 										$matches[0] will contain the text that matched the full pattern, $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
 * @param int $flags (optional)			Could be PREG_OFFSET_CAPTURE. If this flag is passed, for every occurring match the appendant string offset will also be returned.
 * 										Note that this changes the return value in an array where every element is an array consisting of the matched string at index 0 and its string offset into subject at index 1.
 * @param int $offset (optional)		Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean					Returns the number of times pattern matches or FALSE if an error occurred.
 * @link http://php.net/preg_match
 */
function api_preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    return preg_match(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $matches, $flags, $offset);
}

/**
 * Performs a global regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param array &$matches (optional)	Array of all matches in multi-dimensional array ordered according to $flags.
 * @param int $flags (optional)			Can be a combination of the following flags (note that it doesn't make sense to use PREG_PATTERN_ORDER together with PREG_SET_ORDER):
 * PREG_PATTERN_ORDER - orders results so that $matches[0] is an array of full pattern matches, $matches[1] is an array of strings matched by the first parenthesized subpattern, and so on;
 * PREG_SET_ORDER - orders results so that $matches[0] is an array of first set of matches, $matches[1] is an array of second set of matches, and so on;
 * PREG_OFFSET_CAPTURE - If this flag is passed, for every occurring match the appendant string offset will also be returned. Note that this changes the value of matches
 * in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * If no order flag is given, PREG_PATTERN_ORDER is assumed.
 * @param int $offset (optional)		Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean					Returns the number of full pattern matches (which might be zero), or FALSE if an error occurred.
 * @link http://php.net/preg_match_all
 */
function api_preg_match_all($pattern, $subject, &$matches, $flags = PREG_PATTERN_ORDER, $offset = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (is_null($flags)) {
        $flags = PREG_PATTERN_ORDER;
    }
    return preg_match_all(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $matches, $flags, $offset);
}

/**
 * Performs a regular expression search and replace, UTF-8 aware when it is applicable.
 * @param string|array $pattern			The pattern to search for. It can be either a string or an array with strings.
 * @param string|array $replacement		The string or an array with strings to replace.
 * @param string|array $subject			The string or an array with strings to search and replace.
 * @param int $limit					The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count					If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string|null			returns an array if the subject parameter is an array, or a string otherwise.
 * If matches are found, the new subject will be returned, otherwise subject will be returned unchanged or NULL if an error occurred.
 * @link http://php.net/preg_replace
 */
function api_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    $is_utf8 = api_is_utf8($encoding);
    if (is_array($pattern)) {
        foreach ($pattern as &$p) {
            $p = $is_utf8 ? $p.'u' : $p;
        }
    } else {
        $pattern = $is_utf8 ? $pattern.'u' : $pattern;
    }
    return preg_replace($pattern, $replacement, $subject, $limit, $count);
}

/**
 * Performs a regular expression search and replace using a callback function, UTF-8 aware when it is applicable.
 * @param string|array $pattern			The pattern to search for. It can be either a string or an array with strings.
 * @param function $callback			A callback that will be called and passed an array of matched elements in the $subject string. The callback should return the replacement string.
 * @param string|array $subject			The string or an array with strings to search and replace.
 * @param int $limit (optional)			The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count (optional)		If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string					Returns an array if the subject parameter is an array, or a string otherwise.
 * @link http://php.net/preg_replace_callback
 */
function api_preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (is_array($pattern)) {
        foreach ($pattern as &$p) {
            $p = api_is_utf8($encoding) ? $p.'u' : $p;
        }
    } else {
        $pattern = api_is_utf8($encoding) ? $pattern.'u' : $pattern;
    }
    return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
}

/**
 * Splits a string by a regular expression, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param int $limit (optional)			If specified, then only substrings up to $limit are returned with the rest of the string being placed in the last substring. A limit of -1, 0 or null means "no limit" and, as is standard across PHP.
 * @param int $flags (optional)			$flags can be any combination of the following flags (combined with bitwise | operator):
 * PREG_SPLIT_NO_EMPTY - if this flag is set, only non-empty pieces will be returned;
 * PREG_SPLIT_DELIM_CAPTURE - if this flag is set, parenthesized expression in the delimiter pattern will be captured and returned as well;
 * PREG_SPLIT_OFFSET_CAPTURE - If this flag is set, for every occurring match the appendant string offset will also be returned.
 * Note that this changes the return value in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array						Returns an array containing substrings of $subject split along boundaries matched by $pattern.
 * @link http://php.net/preg_split
 */
function api_preg_split($pattern, $subject, $limit = -1, $flags = 0, $encoding = null) {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    return preg_split(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $limit, $flags);
}


/**
 * Obsolete string operations using regular expressions, to be deprecated
 */

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
 * Executes a regular expression match with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The searched string.
 * @param array $regs (optional)	If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed					1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions ereg() and mb_ereg() for human-language strings.
 * @link http://php.net/manual/en/function.ereg
 * @link http://php.net/manual/en/function.mb-ereg
 */
function api_ereg($pattern, $string, & $regs = null) {
    $count = func_num_args();
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if ($count < 3) {
            return @mb_ereg($pattern, $string);
        }
        return @mb_ereg($pattern, $string, $regs);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        global $_api_encoding;
        $_api_encoding = $encoding;
        _api_mb_regex_encoding('UTF-8');
        if ($count < 3) {
            $result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
        } else {
            $result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
            $regs = _api_array_utf8_decode($regs);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    if ($count < 3) {
        return ereg($pattern, $string);
    }
    return ereg($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_replace() with Perl-compatible regular expression syntax.
 *
 * Scans string for matches to pattern, then replaces the matched text with replacement, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern				The regular expression pattern.
 * @param string $replacement			The replacement text.
 * @param string $string				The searched string.
 * @param string $option (optional)		Matching condition.
 * If i is specified for the matching condition parameter, the case will be ignored.
 * If x is specified, white space will be ignored.
 * If m is specified, match will be executed in multiline mode and line break will be included in '.'.
 * If p is specified, match will be executed in POSIX mode, line break will be considered as normal character.
 * If e is specified, replacement string will be evaluated as PHP expression.
 * @return mixed						The modified string is returned. If no matches are found within the string, then it will be returned unchanged. FALSE will be returned on error.
 * This function is aimed at replacing the functions ereg_replace() and mb_ereg_replace() for human-language strings.
 * @link http://php.net/manual/en/function.ereg-replace
 * @link http://php.net/manual/en/function.mb-ereg-replace
 */
function api_ereg_replace($pattern, $replacement, $string, $option = null) {
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if (is_null($option)) {
            return @mb_ereg_replace($pattern, $replacement, $string);
        }
        return @mb_ereg_replace($pattern, $replacement, $string, $option);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        _api_mb_regex_encoding('UTF-8');
        if (is_null($option)) {
            $result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
        } else {
            $result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    return ereg_replace($pattern, $replacement, $string);
}

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
 * Executes a regular expression match, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The searched string.
 * @param array $regs (optional)	If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed					1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions eregi() and mb_eregi() for human-language strings.
 * @link http://php.net/manual/en/function.eregi
 * @link http://php.net/manual/en/function.mb-eregi
 */
function api_eregi($pattern, $string, & $regs = null) {
    $count = func_num_args();
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if ($count < 3) {
            return @mb_eregi($pattern, $string);
        }
        return @mb_eregi($pattern, $string, $regs);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        global $_api_encoding;
        $_api_encoding = $encoding;
        _api_mb_regex_encoding('UTF-8');
        if ($count < 3) {
            $result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
        } else {
            $result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
            $regs = _api_array_utf8_decode($regs);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    if ($count < 3) {
        return eregi($pattern, $string);
    }
    return eregi($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_replace() with Perl-compatible regular expression syntax.
 *
 * Scans string for matches to pattern, then replaces the matched text with replacement, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern				The regular expression pattern.
 * @param string $replacement			The replacement text.
 * @param string $string				The searched string.
 * @param string $option (optional)		Matching condition.
 * If i is specified for the matching condition parameter, the case will be ignored.
 * If x is specified, white space will be ignored.
 * If m is specified, match will be executed in multiline mode and line break will be included in '.'.
 * If p is specified, match will be executed in POSIX mode, line break will be considered as normal character.
 * If e is specified, replacement string will be evaluated as PHP expression.
 * @return mixed						The modified string is returned. If no matches are found within the string, then it will be returned unchanged. FALSE will be returned on error.
 * This function is aimed at replacing the functions eregi_replace() and mb_eregi_replace() for human-language strings.
 * @link http://php.net/manual/en/function.eregi-replace
 * @link http://php.net/manual/en/function.mb-eregi-replace
 */
function api_eregi_replace($pattern, $replacement, $string, $option = null) {
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if (is_null($option)) {
            return @mb_eregi_replace($pattern, $replacement, $string);
        }
        return @mb_eregi_replace($pattern, $replacement, $string, $option);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        _api_mb_regex_encoding('UTF-8');
        if (is_null($option)) {
            $result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
        } else {
            $result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    return eregi_replace($pattern, $replacement, $string);
}

/**
 * Note: Try to avoid using this function. Use api_preg_split() with Perl-compatible regular expression syntax.
 *
 * Splits a multibyte string using regular expression pattern and returns the result as an array.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The string being split.
 * @param int $limit (optional)		If this optional parameter $limit is specified, the string will be split in $limit elements as maximum.
 * @return array					The result as an array.
 * This function is aimed at replacing the functions split() and mb_split() for human-language strings.
 * @link http://php.net/manual/en/function.split
 * @link http://php.net/manual/en/function.mb-split
 */
function api_split($pattern, $string, $limit = null) {
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if (is_null($limit)) {
            return @mb_split($pattern, $string);
        }
        return @mb_split($pattern, $string, $limit);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        global $_api_encoding;
        $_api_encoding = $encoding;
        _api_mb_regex_encoding('UTF-8');
        if (is_null($limit)) {
            $result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
        } else {
            $result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $limit);
        }
        $result = _api_array_utf8_decode($result);
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    if (is_null($limit)) {
        return split($pattern, $string);
    }
    return split($pattern, $string, $limit);
}


/**
 * String comparison
 */

/**
 * Performs string comparison, case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcasecmp
 */
function api_strcasecmp($string1, $string2, $language = null, $encoding = null) {
    return api_strcmp(api_strtolower($string1, $encoding), api_strtolower($string2, $encoding), $language, $encoding);
}

/**
 * Performs string comparison, case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strcmp($string1, $string2, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            $result = collator_compare($collator, api_utf8_encode($string1, $encoding), api_utf8_encode($string2, $encoding));
            return $result === false ? 0 : $result;
        }
    }
    return strcmp($string1, $string2);
}

/**
 * Performs string comparison in so called "natural order", case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcasecmp
 */
function api_strnatcasecmp($string1, $string2, $language = null, $encoding = null) {
    return api_strnatcmp(api_strtolower($string1, $encoding), api_strtolower($string2, $encoding), $language, $encoding);
}

/**
 * Performs string comparison in so called "natural order", case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strnatcmp($string1, $string2, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            $result = collator_compare($collator, api_utf8_encode($string1, $encoding), api_utf8_encode($string2, $encoding));
            return $result === false ? 0 : $result;
        }
    }
    return strnatcmp($string1, $string2);
}


/**
 * Sorting arrays
 */

/**
 * Sorts an array with maintaining index association, elements will be arranged from the lowest to the highest.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function asort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.asort.php
 * @link http://php.net/manual/en/collator.asort.php
 */
function api_asort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if (api_is_utf8($encoding)) {
                $sort_flag = ($sort_flag == SORT_LOCALE_STRING) ? SORT_STRING : $sort_flag;
                return collator_asort($collator, $array, _api_get_collator_sort_flag($sort_flag));
            }
            elseif ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uasort($array, '_api_cmp');
            }
        }
    }
    return asort($array, $sort_flag);
}

/**
 * Sorts an array with maintaining index association, elements will be arranged from the highest to the lowest (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function arsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.arsort.php
 */
function api_arsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uasort($array, '_api_rcmp');
            }
        }
    }
    return arsort($array, $sort_flag);
}

/**
 * Sorts an array using natural order algorithm.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natsort.php
 */
function api_natsort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uasort($array, '_api_cmp');
        }
    }
    return natsort($array);
}

/**
 * Sorts an array using natural order algorithm in reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_natrsort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uasort($array, '_api_rcmp');
        }
    }
    return uasort($array, '_api_strnatrcmp');
}

/**
 * Sorts an array using natural order algorithm, case-insensitive.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natcasesort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natcasesort.php
 */
function api_natcasesort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uasort($array, '_api_casecmp');
        }
    }
    return natcasesort($array);
}

/**
 * Sorts an array using natural order algorithm, case-insensitive, reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_natcasersort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uasort($array, '_api_casercmp');
        }
    }
    return uasort($array, '_api_strnatcasercmp');
}

/**
 * Sorts an array by keys, elements will be arranged from the lowest key to the highest key.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how keys of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - keys will be compared as numbers;
 * SORT_STRING - keys will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - keys will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function ksort() for sorting human-language key strings.
 * @link http://php.net/manual/en/function.ksort.php
 */
function api_ksort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uksort($array, '_api_cmp');
            }
        }
    }
    return ksort($array, $sort_flag);
}

/**
 * Sorts an array by keys, elements will be arranged from the highest key to the lowest key (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how keys of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - keys will be compared as numbers;
 * SORT_STRING - keys will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - keys will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function krsort() for sorting human-language key strings.
 * @link http://php.net/manual/en/function.krsort.php
 */
function api_krsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uksort($array, '_api_rcmp');
            }
        }
    }
    return krsort($array, $sort_flag);
}

/**
 * Sorts an array by keys using natural order algorithm.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatsort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uksort($array, '_api_cmp');
        }
    }
    return uksort($array, 'strnatcmp');
}

/**
 * Sorts an array by keys using natural order algorithm in reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatrsort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uksort($array, '_api_rcmp');
        }
    }
    return uksort($array, '_api_strnatrcmp');
}

/**
 * Sorts an array by keys using natural order algorithm, case insensitive.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatcasesort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uksort($array, '_api_casecmp');
        }
    }
    return uksort($array, 'strnatcasecmp');
}

/**
 * Sorts an array by keys using natural order algorithm, case insensitive, reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatcasersort(&$array, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uksort($array, '_api_casercmp');
        }
    }
    return uksort($array, '_api_strnatcasercmp');
}

/**
 * Sorts an array, elements will be arranged from the lowest to the highest.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function sort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.sort.php
 * @link http://php.net/manual/en/collator.sort.php
 */
function api_sort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if (api_is_utf8($encoding)) {
                $sort_flag = ($sort_flag == SORT_LOCALE_STRING) ? SORT_STRING : $sort_flag;
                return collator_sort($collator, $array, _api_get_collator_sort_flag($sort_flag));
            }
            elseif ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return usort($array, '_api_cmp');
            }
        }
    }
    return sort($array, $sort_flag);
}

/**
 * Sorts an array, elements will be arranged from the highest to the lowest (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function rsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.rsort.php
 */
function api_rsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return usort($array, '_api_rcmp');
            }
        }
    }
    return rsort($array, $sort_flag);
}


/**
 * Common sting operations with arrays
 */

/**
 * Checks if a value exists in an array, a case insensitive version of in_array() function with extended multibyte support.
 * @param mixed $needle					The searched value. If needle is a string, the comparison is done in a case-insensitive manner.
 * @param array $haystack				The array.
 * @param bool $strict (optional)		If is set to TRUE then the function will also check the types of the $needle in the $haystack. The default value if FALSE.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE if $needle is found in the array, FALSE otherwise.
 * @link http://php.net/manual/en/function.in-array.php
 */
function api_in_array_nocase($needle, $haystack, $strict = false, $encoding = null) {
    if (is_array($needle)) {
        foreach ($needle as $item) {
            if (api_in_array_nocase($item, $haystack, $strict, $encoding)) return true;
        }
        return false;
    }
    if (!is_string($needle)) {
        return in_array($needle, $haystack, $strict);
    }
    $needle = api_strtolower($needle, $encoding);
    if (!is_array($haystack)) {
        return false;
    }
    foreach ($haystack as $item) {
        if ($strict && !is_string($item)) {
            continue;
        }
        if (api_strtolower($item, $encoding) == $needle) {
            return true;
        }
    }
    return false;
}


/**
 * Encoding management functions
 */

/**
 * This function unifies the encoding identificators, so they could be compared.
 * @param string/array $encoding	The specified encoding.
 * @return string					Returns the encoding identificator modified in suitable for comparison way.
 */
function api_refine_encoding_id($encoding) {
    if (is_array($encoding)){
        return array_map('api_refine_encoding_id', $encoding);
    }
    return strtoupper(str_replace('_', '-', $encoding));
}

/**
 * This function checks whether two $encoding are equal (same, equvalent).
 * @param string/array $encoding1		The first encoding
 * @param string/array $encoding2		The second encoding
 * @param bool $strict					When this parameter is TRUE the comparison ignores aliases of encodings. When the parameter is FALSE, aliases are taken into account.
 * @return bool							Returns TRUE if the encodings are equal, FALSE otherwise.
 */
function api_equal_encodings($encoding1, $encoding2, $strict = false) {
    static $equal_encodings = array();
    if (is_array($encoding1)) {
        foreach ($encoding1 as $encoding) {
            if (api_equal_encodings($encoding, $encoding2, $strict)) {
                return true;
            }
        }
        return false;
    }
    elseif (is_array($encoding2)) {
        foreach ($encoding2 as $encoding) {
            if (api_equal_encodings($encoding1, $encoding, $strict)) {
                return true;
            }
        }
        return false;
    }
    if (!isset($equal_encodings[$encoding1][$encoding2][$strict])) {
        $encoding_1 = api_refine_encoding_id($encoding1);
        $encoding_2 = api_refine_encoding_id($encoding2);
        if ($encoding_1 == $encoding_2) {
            $result = true;
        } else {
            if ($strict) {
                $result = false;
            } else {
                $alias1 = _api_get_character_map_name($encoding_1);
                $alias2 = _api_get_character_map_name($encoding_2);
                $result = !empty($alias1) && !empty($alias2) && $alias1 == $alias2;
            }
        }
        $equal_encodings[$encoding1][$encoding2][$strict] = $result;
    }
    return $equal_encodings[$encoding1][$encoding2][$strict];
}

/**
 * This function checks whether a given encoding is UTF-8.
 * @param string $encoding		The tested encoding.
 * @return bool					Returns TRUE if the given encoding id means UTF-8, otherwise returns false.
 */
function api_is_utf8($encoding) {
    static $result = array();
    if (!isset($result[$encoding])) {
        $result[$encoding] = api_equal_encodings($encoding, 'UTF-8');
    }
    return $result[$encoding];
}

/**
 * This function checks whether a given encoding represents (is an alias of) ISO Latin 1 character set.
 * @param string/array $encoding		The tested encoding.
 * @param bool $strict					Flag for check precision. ISO-8859-1 is always Latin 1. When $strict is false, ISO-8859-15 is assumed as Latin 1 too.
 * @return bool							Returns TRUE if the given encoding id means Latin 1 character set, otherwise returns false.
 */
function api_is_latin1($encoding, $strict = false) {
    static $latin1 = array();
    static $latin1_strict = array();
    if ($strict) {
        if (!isset($latin1_strict[$encoding])) {
            $latin1_strict[$encoding] = api_equal_encodings($encoding, array('ISO-8859-1', 'ISO8859-1', 'CP819', 'LATIN1'));
        }
        return $latin1_strict[$encoding];
    }
    if (!isset($latin1[$encoding])) {
        $latin1[$encoding] = api_equal_encodings($encoding, array(
            'ISO-8859-1', 'ISO8859-1', 'CP819', 'LATIN1',
            'ISO-8859-15', 'ISO8859-15', 'CP923', 'LATIN0', 'LATIN-9',
            'WINDOWS-1252', 'CP1252', 'WIN-1252', 'WIN1252'
        ));
    }
    return $latin1[$encoding];
}

/**
 * This function returns the encoding, currently used by the system.
 * @return string	The system's encoding.
 * Note: The value of api_get_setting('platform_charset') is tried to be returned first,
 * on the second place the global variable $charset is tried to be returned. If for some
 * reason both attempts fail, then the libraly's internal value will be returned.
 */
function api_get_system_encoding() {
    static $system_encoding;
    if (!isset($system_encoding)) {
        $encoding_setting = api_get_setting('platform_charset');
        if (empty($encoding_setting)) {
            global $charset;
            if (empty($charset)) {
                return _api_mb_internal_encoding();
            }
            return $charset;
        }
        $system_encoding = $encoding_setting;
    }
    return $system_encoding;
}

/**
 * This function returns the encoding, currently used by the file system.
 * @return string	The file system's encoding, it depends on the locale that OS currently uses.
 * @link http://php.net/manual/en/function.setlocale.php
 * Note: For Linux systems, to see all installed locales type in a terminal  locale -a
 */
function api_get_file_system_encoding() {
    static $file_system_encoding;
    if (!isset($file_system_encoding)) {
        $locale = setlocale(LC_CTYPE, '0');
        $seek_pos = strpos($locale, '.');
        if ($seek_pos !== false) {
            $file_system_encoding = substr($locale, $seek_pos + 1);
            if (IS_WINDOWS_OS) {
                $file_system_encoding = 'CP'.$file_system_encoding;
            }
        }
        // Dealing with some aliases.
        $file_system_encoding = str_ireplace('utf8', 'UTF-8', $file_system_encoding);
        $file_system_encoding = preg_replace('/^CP65001$/', 'UTF-8', $file_system_encoding);
        $file_system_encoding = preg_replace('/^CP(125[0-9])$/', 'WINDOWS-\1', $file_system_encoding);
        $file_system_encoding = str_replace('WINDOWS-1252', 'ISO-8859-15', $file_system_encoding);
        if (empty($file_system_encoding)) {
            if (IS_WINDOWS_OS) {
                // Not expected for Windows, this assignment is here just in case.
                $file_system_encoding = api_get_system_encoding();
            } else {
                // For Ububntu and other UTF-8 enabled Linux systems this fits with the default settings.
                $file_system_encoding = 'UTF-8';
            }
        }
    }
    return $file_system_encoding;
}

/**
 * Checks whether a specified encoding is supported by this API.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_is_encoding_supported($encoding) {
    static $supported = array();
    if (!isset($supported[$encoding])) {
        $supported[$encoding] = _api_mb_supports($encoding) || _api_iconv_supports($encoding) || _api_convert_encoding_supports($encoding);
    }
    return $supported[$encoding];
}

/**
 * Returns in an array the most-probably used non-UTF-8 encoding for the given language.
 * The first (leading) value is actually used by the system at the moment.
 * @param string $language (optional)	The specified language, the default value is the user intrface language.
 * @return string						The correspondent encoding to the specified language.
 * Note: See the file chamilo/main/inc/lib/internationalization_database/non_utf8_encodings.php
 * if you wish to revise the leading non-UTF-8 encoding for your language.
 */
function api_get_non_utf8_encoding($language = null) {

    $language_is_supported = api_is_language_supported($language);
    if (!$language_is_supported || empty($language)) {
        $language = api_get_interface_language(false, true);
    }

    $language = api_purify_language_id($language);
    $encodings = & _api_non_utf8_encodings();
    if (is_array($encodings[$language])) {
        if (!empty($encodings[$language][0])) {
            return $encodings[$language][0];
        }
        return null;
    }
    return null;
}

/**
 * Return a list of valid encodings for setting platform character set.
 * @return array	List of valid encodings, preferably IANA-registared.
 */
function api_get_valid_encodings() {
    $encodings = & _api_non_utf8_encodings();
    if (!is_array($encodings)) {
        $encodings = array('english', array('ISO-8859-15'));
    }
    $result1 = array(); $result2 = array(); $result3 = array();
    foreach ($encodings as $value) {
        $encoding = api_refine_encoding_id(trim($value[0]));
        if (!empty($encoding)) {
            if (strpos($encoding, 'ISO-') === 0) {
                $result1[] = $encoding;
            } elseif (strpos($encoding, 'WINDOWS-') === 0) {
                $result2[] = $encoding;
            } else {
                $result3[] = $encoding;
            }
        }
    }
    $result1 = array_unique($result1);
    $result2 = array_unique($result2);
    $result3 = array_unique($result3);
    natsort($result1);
    natsort($result2);
    natsort($result3);
    return array_merge(array('UTF-8'), $result1, $result2, $result3);
}

/**
 * Detects encoding of plain text.
 * @param string $string				The input text.
 * @param string $language (optional)	The language of the input text, provided if it is known.
 * @return string						Returns the detected encoding.
 */
function api_detect_encoding($string, $language = null) {
    // Testing against valid UTF-8 first.
    if (api_is_valid_utf8($string)) {
        return 'UTF-8';
    }
    $result = null;
    $delta_points_min = LANGUAGE_DETECT_MAX_DELTA;
    // Testing non-UTF-8 encodings.
    $encodings = api_get_valid_encodings();
    foreach ($encodings as & $encoding) {
        if (api_is_encoding_supported($encoding) && !api_is_utf8($encoding)) {
            $result_array = & _api_compare_n_grams(_api_generate_n_grams(api_substr($string, 0, LANGUAGE_DETECT_MAX_LENGTH, $encoding), $encoding), $encoding);
            if (!empty($result_array)) {
                list($key, $delta_points) = each($result_array);
                if ($delta_points < $delta_points_min) {
                    $pos = strpos($key, ':');
                    $result_encoding = api_refine_encoding_id(substr($key, $pos + 1));
                    if (api_equal_encodings($encoding, $result_encoding)) {
                        if ($string == api_utf8_decode(api_utf8_encode($string, $encoding), $encoding)) {
                            $delta_points_min = $delta_points;
                            $result = $encoding;
                        }
                    }
                }
            }
        }
    }
    // "Broken" UTF-8 texts are to be detected as UTF-8.
    // This functionality is enabled when language of the text is known.
    $language = api_purify_language_id((string)$language);
    if (!empty($language)) {
        $encoding = 'UTF-8';
        $result_array = & _api_compare_n_grams(_api_generate_n_grams(api_substr($string, 0, LANGUAGE_DETECT_MAX_LENGTH, $encoding), $encoding), $encoding);
        if (!empty($result_array)) {
            list($key, $delta_points) = each($result_array);
            if ($delta_points < $delta_points_min) {
                $pos = strpos($key, ':');
                $result_encoding = api_refine_encoding_id(substr($key, $pos + 1));
                $result_language = substr($key, 0, $pos);
                if ($language == $result_language && api_is_utf8($result_encoding)) {
                    $delta_points_min = $delta_points;
                    $result = $encoding;
                }
            }
        }
    }
    return $result;
}


/**
 * String validation functions concerning certain encodings
 */

/**
 * Checks a string for UTF-8 validity.
 *
 * @deprecated Use Encoding::utf8()->is_valid() instead
 */
function api_is_valid_utf8(&$string) {
    return Encoding::utf8()->is_valid($string);
}

/**
 * Checks whether a string contains 7-bit ASCII characters only.
 * @param string $string	The string to be tested/validated.
 * @return bool				Returns TRUE when the tested string contains 7-bit ASCII characters only, FALSE othewise.
 */
function api_is_valid_ascii(&$string) {
    if (MBSTRING_INSTALLED) {
        return @mb_detect_encoding($string, 'ASCII', true) == 'ASCII' ? true : false;
    }
    return !preg_match('/[^\x00-\x7F]/S', $string);
}

/**
 *
 * Experimental translation feature for Chamilo
 *
 * Install this in Ubuntu
 *
 * sudo locale-gen es_ES
 * sudo apt-get install php-gettext
 *
 * Install Spanish locale: $ sudo locale-gen es_ES
 * Install English locale: $ sudo locale-gen en_US
 *
 * To view the list of locales installed in ubuntu
 * locale -a
 *
 * In Debian check this file More info: http://algorytmy.pl/doc/php/ref.gettext.php
 * sudo vim /etc/locale.gen
 *
 * Translate po files using this GUI
 * sudo apt-get install poedit
 *
 * Some help here:
 *
 * Config getext
 * http://zez.org/article/articleview/42/3/
 *  *
 * Using getext in ubuntu
 * http://www.sourcerally.net/regin/49-How-to-get-PHP-and-gettext-working-%28ubuntu,-debian%29
 *
 * Getext tutorial
 * http://mel.melaxis.com/devblog/2005/08/06/localizing-php-web-sites-using-gettext/
 *
 */
function setting_gettext() {
    $domain = 'default';
    $locale = api_get_language_isocode();
    $locale = 'es_ES';
    putenv("LC_ALL=$locale");
    setlocale(LC_ALL, $locale);
    bindtextdomain($domain, api_get_path(SYS_LANG_PATH));
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
}

/**
 * Functions for internal use behind this API
 */

require_once dirname(__FILE__).'/internationalization_internal.lib.php';
