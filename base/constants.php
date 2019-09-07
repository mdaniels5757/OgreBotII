<?php

date_default_timezone_set('UTC');

assert_options (ASSERT_ACTIVE, 1);
assert_options (ASSERT_WARNING, 1);

define("BASE_DIRECTORY",  pathinfo(__DIR__, PATHINFO_DIRNAME));
define("ARTIFACTS_DIRECTORY", BASE_DIRECTORY . DIRECTORY_SEPARATOR . "artifacts");
define("MASS_CLEANUP_DIRECTORY", ARTIFACTS_DIRECTORY . DIRECTORY_SEPARATOR . "cleanup-tests");

define("TMP_DIRECTORY", BASE_DIRECTORY.DIRECTORY_SEPARATOR."tmp");
define("TMP_DIRECTORY_SLASH", TMP_DIRECTORY.DIRECTORY_SEPARATOR);
define("CACHE_DIRECTORY", TMP_DIRECTORY_SLASH."cache/");
define("LOG_DIRECTORY", BASE_DIRECTORY.DIRECTORY_SEPARATOR."log");
define("_PHP_MINOR_VERSION", floatval(phpversion()));

define ("HOURS_PER_DAY", 24);
define ("MINUTES_PER_HOUR", 60);
define ("MINUTES_PER_DAY", HOURS_PER_DAY * MINUTES_PER_HOUR);
define ("SECONDS_PER_MINUTE", 60);
define ("SECONDS_PER_HOUR", MINUTES_PER_HOUR * SECONDS_PER_MINUTE);
define ("SECONDS_PER_DAY" , SECONDS_PER_HOUR * 24);

define("GET_URL_FILENAME_TABLE", "files.properties");
define("DEFAULT_URL_CACHE_TIME", SECONDS_PER_DAY*365);

define("MAX_UCFIRST_CACHE", 1024);

/*******
 * shared
 *******/
define ("COMMONS_LISTED_SUCCESS", 0);
define ("COMMONS_LISTED_ZERO", 1);
define ("COMMONS_LISTED_MULTIPLE", 2);
define ("COMMONS_LISTED_ILLEGAL_CHAR", 4);

define ("EDIT_CONFLICT_ERROR", 0x08000000) || die("Unable to set constant");
define ("API_ERROR", 0x10000000) || die("Unable to set constant");
define ("PROTECTED_PAGE_ERROR", 0x20000000) || die("Unable to set constant");
define ("CURL_ERROR", 0x40000000) || die("Unable to set constant");
define ("UNKNOWN_ERROR", 0x80000000) || die("Unable to set constant");


/************
 * Edit constants
 ************/
define("EDIT_MINOR", 0x1);
define("EDIT_NO_BOT", 0x2);
define("EDIT_NO_FORCE", 0x4);
define("EDIT_NO_PEND", 0x0);
define("EDIT_APPEND", 0x8);
define("EDIT_PREPEND", 0x10);
define("EDIT_NO_CREATE", 0x20);
define("EDIT_CREATE_ONLY", 0x40);

/*************
 * Get usage constants
 *************/
//only list redirects
define("USAGE_REDIRECTS_ONLY", 0x1);
//only list redirects
define("USAGE_NON_REDIRECTS_ONLY", 0x2);
//list pages linking through redirects
define("USAGE_PAGES_THROUGH_REDIRECTS", 0x4);

define ("REPLACE_IMAGE_SUCCESS", 0) || die("Unable to set constant");

//image is not used on this page. may still be falsely reported as used elsewhere if transcluded via template and not updated.
// If this result is returned, no other flags will be set.
define ("REPLACE_IMAGE_NO_LINKED", 0x1)  || die("Unable to set constant");

//old image is linked on page, but no longer exists on server, and as such resolution cannot be verified. Will only be returned if
//  $override_noexist==false; if the override is true, then it will be replaced, and the same resolution as second image assumed.
define ("REPLACE_IMAGE_OLD_NO_EXIST", 0x2) || die("Unable to set constant");

//new image does not exist on server.
define ("REPLACE_IMAGE_NEW_NO_EXIST", 0x4) || die("Unable to set constant");

//a file exists on the local server blocking the commons image
define ("REPLACE_IMAGE_CONFLICT", 0x8) || die("Unable to set constant");

//the second image is of a lower resolution or the dimensions are otherwise compatible (see $pixel_change_tolerance below)
define ("REPLACE_IMAGE_LOWER_RESOLUTION", 0x10) || die("Unable to set constant");
define ("REPLACE_IMAGE_INCOMPATIBLE_RESOLUTION", 0x20) || die("Unable to set constant");
define ("REPLACE_IMAGE_NO_PIXELAGE", 0x40) || die("Unable to set constant");

define ("REPLACE_IMAGE_DIFFERENT_MIME", 0x80) || die("Unable to set constant");
define ("REPLACE_IMAGE_COMMONS_BAD_MIME", 0x100) || die("Unable to set constant");

//frames only allow native pixelage; use of divs is too hard right now
define ("REPLACE_IMAGE_FRAME", 0x200) || die("Unable to set constant");

//{{KeepLocal}} template on page
define ("REPLACE_IMAGE_KEEP_LOCAL", 0x400) || die("Unable to set constant");

//Unable to parse text
define ("REPLACE_IMAGE_UNKNOWN", 0x8000000) || die("Unable to set constant");

//API returns error
define ("REPLACE_IMAGE_API_ERROR", API_ERROR) || die("Unable to set constant");

//API returns error
define ("REPLACE_IMAGE_PROTECTED_ERROR", PROTECTED_PAGE_ERROR) || die("Unable to set constant");


/**********
 * cleanup
 **********/

define("MAX_CURL_ATTEMPTS", 5);

/***
 * 
 * Namespaces
 */
define("FILE_NAMESPACE", 6);
define("CATEGORY_NAMESPACE", 14);


define("NOW_COMMONS_LIST_LICENSE_CACHE_FILE", TMP_DIRECTORY."/now_commons_list.licenses.cache");

/* regex thanks to eyelidlessness and user244966 on SO: http://stackoverflow.com/q/190405 */
define('URL_FORMAT',
		'/^(http):\/\/'.                                           // protocol
		'((([a-z0-9]\.|[a-z0-9][a-z0-9-]*[a-z0-9]\.)*'.            // domain segments AND
		'[a-z][a-z0-9-]*[a-z0-9]'.                                 // top level domain OR
		'|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'.
		'(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])'.                 // IP address
		')(:\d+)?'.                                                // port
		')(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'. // path
		'(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)'.      // query string
		'?)?)?'.                                                   // path and query string optional
		'$/i');

if (!defined("PHP_INT_MIN")) {
	define('PHP_INT_MIN', ~PHP_INT_MAX);
}

define("ALWAYS_FAIL_REGEX", "/a^/");

$MB_WS_RE = "\p{Z}\p{Cf}\x{200e}\x{200f}\s";
$MB_WS_RE_OPT = "[$MB_WS_RE]*";

/********
 * do_cleanup constants
 ********/
define("DO_CLEANUP_FILE", TMP_DIRECTORY . "/do_cleanup_progress.\$request_key.txt");

define("OGREBOT_USERAGENT", "OgreBot, Peachy framework");

define("METADATA", "metadata");
define("SPECIAL_INSTRUCTIONS", "SpecialInstructions");