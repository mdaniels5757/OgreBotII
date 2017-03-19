<?php
class String_Utils {
	
	const STRING_DIRECTION_EMPTY_STRING = 0;
	const STRING_DIRECTION_LTR = 1;
	const STRING_DIRECTION_RTL = 2;
	const STRING_DIRECTION_MIXED = 3;
	const STRING_DIRECTION_INVALID = 4;
	
	/**
	 * TODO convert this to a constant for PHP 5.6
	 * @var int
	 */
	private $JSON_ENCODE_OPTIONS ;
	
	/**
	 * 
	 * @var string
	 */
	const DEFAULT_RAND_CHARS = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	/**
	 * 
	 * @var int[]
	 */
	private $direction_codes;
	
	/**
	 * 
	 */
	public function __construct() {
		$this->JSON_ENCODE_OPTIONS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP |
			 JSON_UNESCAPED_UNICODE;
	}
	
	/**
	 * @return void
	 */
	private function lazy_load_direction_codes() {
		load_property_file_into_variable($codes, "unicode");
		$this->direction_codes = array_map(function($line) {
			list($index, $type_as_text) = explode(";", $line);
				
			switch ($type_as_text) {
				case "neutral":
					$type = self::STRING_DIRECTION_EMPTY_STRING;
					break;
				case "ltr":
					$type = self::STRING_DIRECTION_LTR;
					break;
				case "rtl":
					$type = self::STRING_DIRECTION_RTL;
					break;
				default:
					$type = self::STRING_DIRECTION_INVALID;
			}
				
			return [$index, $type];
				
		}, $codes["points"]);
	}
	
	/**
	 * 
	 * @param string $string
	 * @return int
	 */
	public function get_string_direction($string) {
		if ($this->direction_codes === null) {
			$this->lazy_load_direction_codes();
		}
		
		$strlen = strlen($string);
		$code_points = unpack('V*', iconv('UTF-8', 'UTF-32LE', $string));
		
		$type = self::STRING_DIRECTION_EMPTY_STRING;
		foreach ($code_points as $point) {
			$type = $type | $this->get_point_direction($point);
		}
		return $type;
	}
	
	/**
	 * 
	 * @param int $point
	 * @return 
	 */
	private function get_point_direction($point) {
		$lowest_i = 0;
		$highest_i = count($this->direction_codes);
		while (true) {
			$next_i = floor(($highest_i - $lowest_i) / 2) + $lowest_i;
			if ($next_i === $lowest_i) {
				return $this->direction_codes[$next_i][1];
			}
			
			$next_code = $this->direction_codes[$next_i][0];
			
			if ($next_code < $point) {
				$lowest_i = $next_i;
			} else if ($next_code > $point) {
				$highest_i = $next_i - 1;
			} else {
				return $this->direction_codes[$next_i][1];
			}
		}
	}
	
	/**
	 * 
	 * @param number $length
	 * @param string $chars
	 * @return string
	 */
	public function get_rand_chars($length = 10, $chars = self::DEFAULT_RAND_CHARS) {
		$strlen = strlen($chars) - 1;
		
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[mt_rand(0, $strlen)];
		}
		return $result;
	}
	
	/**
	 * 
	 * @param mixed $object
	 * @param number $options
	 * @return string
	 */
	public function encode_json_for_html($object, $options = 0) {
		return json_encode($object, $this->JSON_ENCODE_OPTIONS | $options) ;
	}
}


/**
 * 
 * @param string $str
 * @return string
 */
function escape_preg_replacement($str) {
	return strtr($str, ["\\" => "\\\\", "\$" => "\\\$"]);
}

/**
 *
 * @param string $regex        	
 * @param string $replace        	
 * @param string $subject        	
 * @return string
 */
function replace_until_no_changes($regex, $replace, $subject, $flags = "m") {
	do {
		$pre = $subject;
		$subject = preg_replace("/$regex/u$flags", $replace, $subject);
	} while ($pre !== $subject);
	
	return $subject;
}

/**
 * thanks to andyb on stackoverflow: http://stackoverflow.com/q/6265596
 * 
 * @param string $roman        	
 * @return int
 */
function roman_to_numeral($roman) {
	$romans = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 
		'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
	
	$result = "";
	foreach ($romans as $key => $value) {
		while (strpos($roman, $key) === 0) {
			$result += $value;
			$roman = substr($roman, strlen($key));
		}
	}
	return $result;
}

/**
 *
 * @param string $str        	
 * @return string
 */
function wiki_urlencode($str) {
	return preg_replace("/\+/", "%20", urlencode($str));
}
/**
 *
 * @param string $str        	
 * @return string
 */
function wiki_url_encode_underbar($str) {
	return preg_replace("/\+/", "_", urlencode($str));
}

/**
 *
 * @param string|string[] $str        	
 * @return string|string[]
 */
function mb_trim($str) {
	return preg_replace("/^$GLOBALS[MB_WS_RE_OPT]([\s\S]*?)$GLOBALS[MB_WS_RE_OPT]$/u", "$1", $str);
}

/**
 * Not multibyte-string safe!
 * 
 * @param string $haystack        	
 * @param string $needle        	
 * @param int $startindex        	
 * @param bool $case_sensitive        	
 * @return boolean
 */
function str_starts_with($haystack, $needle, $startindex = 0, $case_sensitive = false) {
	$length_haystack = strlen($haystack);
	$length_needle = strlen($needle);
	
	if ($length_needle > $length_haystack) {
		return false;
	}
	
	if ($case_sensitive) {
		for($i = $startindex; $i < $length_needle; $i++) {
			if (strcasecmp($haystack[$i], $needle[$i])) {
				return false;
			}
		}
	} else {
		for($i = $startindex; $i < $length_needle; $i++) {
			if ($haystack[$i] !== $needle[$i]) {
				return false;
			}
		}
	}
	
	return true;
}

/**
 * Not multibyte-string safe!
 * 
 * @param string $haystack        	
 * @param string $needle        	
 * @return bool
 */
function str_ends_with($haystack, $needle) {
	$length_haystack = strlen($haystack);
	$length_needle = strlen($needle);
	
	if ($length_needle > $length_haystack) {
		return false;
	}
	
	$diff = $length_haystack - $length_needle;
	for($i = 0; $i < $length_needle; $i++) {
		if ($haystack[$i + $diff] !== $needle[$i]) {
			return false;
		}
	}
	
	return true;
}

/**
 * A function to alter large numbers into easily readable form with the use of a demarcator
 * ("," in the US, "." in some other parts of the world).
 * Thus 4233 would become "4,233"
 * under the default parameters
 * 
 * @param string $number        	
 * @param string $demarcator        	
 * @param int $interval        	
 * @return numeric
 */
function parse_number($number, $demarcator = ",", $interval = 3) {
	$re = "/(\d)(\d{" . "$interval})(?!\d)/";
	while (preg_match($re, $number)) {
		$number = preg_replace($re, "$1$demarcator$2", $number);
	}
	return $number;
}

/**
 * Works for any multibyte string (including length <= 1)
 * 
 * @param string $str        	
 * @return string
 */
function ucfirst_utf8($str) {
	static $ucfirstCache = [];
	
	$cached = @$ucfirstCache[$str];
	if ($cached !== null) {
		return $cached;
	}
	
	$val = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str) - 1);
	
	// cache is too big
	if (count($ucfirstCache) >= MAX_UCFIRST_CACHE) {
		$ucfirstCache = [];
	}
	
	$ucfirstCache[$str] = $val;
	
	return $val;
}

/**
 *
 * @param string $str        	
 * @return string
 */
function lcfirst_utf8($str) {
	return mb_strtolower(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str) - 1);
}

/**
 *
 * @param string $str        	
 * @param boolean $error_badname        	
 * @return string
 */
function basepagename_ns6($str, $error_badname = false) {
	global $MB_WS_RE_OPT;
	return cleanup_wikilink(
		preg_replace("/^$MB_WS_RE_OPT(?i:file|image|datei)" . "$MB_WS_RE_OPT:(.*)$/u", "$1", $str), 
		$error_badname);
}

/**
 *
 * @param string|string[] $input        	
 * @param bool $break_cr
 *        	DEFAULT true
 * @return string|string[]
 */
function sanitize($input, $break_cr = true) {
	$output = preg_replace_callback("/[\"'<>&]/u", 
		function ($matches) {
			$match = $matches[0];
			if ($match === '"') {
				return "&quot;";
			}
			if ($match === '\'') {
				return "&#39;";
			}
			if ($match === '<') {
				return "&lt;";
			}
			if ($match === '>') {
				return "&gt;";
			}
			return "&amp;";
		}, $input);
	
	if ($break_cr) {
		$output = preg_replace("/\r?\n/u", "<br>", $output);
	}
	return $output;
}

/**
 * it is the caller's responsibility to clean if $sanitized_title non-null
 * 
 * @param string $pagetitle        	
 * @param string $site_identifier        	
 * @param bool $exists        	
 * @param bool $remove_ns        	
 * @param string|null $sanitized_title        	
 * @param string|null $style        	
 * @param bool $nbsp        	
 * @param string|null $action        	
 * @return string
 */
function wikilink($pagetitle, $site_identifier, $exists = true, $remove_ns = false, $sanitized_title = NULL, 
	$style = NULL, $nbsp = false, $action = NULL) {
	if ($style === NULL) {
		$style = "text-decoration:none;";
	}
	if ($sanitized_title === NULL) {
		$sanitized_title = sanitize(str_replace("_", " ", $pagetitle));
		if ($nbsp) {
			$sanitized_title = str_replace("/ /u", "&nbsp;", $sanitized_title);
		}
		if ($remove_ns) {
			$sanitized_title = substr($sanitized_title, strpos($sanitized_title, ":") + 1);
		}
	}
	
	if (!$exists) {
		if ($style && $style[strlen($style) - 1] !== ';') {
			$style = "$style;";
		}
		$style = $style . "color:#dd0000;";
	}
	if ($style) {
		$style_text = " style=\"$style\"";
	} else {
		$style_text = "";
	}
	
	$href = get_page_url($site_identifier, $pagetitle, $action, true, !$exists);
	
	$link = "<a$style_text href=\"$href\">$sanitized_title</a>";
	
	return $link;
}

/**
 *
 * @param string $site_identifier        	
 * @param string $pageTitle        	
 * @param string|null $action        	
 * @param bool $forceSecure        	
 * @param bool $redlink        	
 * @return string
 */
function get_page_url($site_identifier, $pageTitle, $action = null, $forceSecure = false, $redlink = false) {
	global $environment;
	
	static $scheme = null;
	
	if ($scheme === null) {
		$scheme = $environment['environment'] === 'local' ? "https:" : "";
	}
	
	if ($redlink && $action === null) {
		$action = "edit&redlink=1";
	}
	
	$url = get_base_url($site_identifier, $action !== null, $forceSecure) .
		 encodePageTitle($pageTitle);
	
	if ($action !== null) {
		$url .= "&action=$action";
	}
	
	$url = preg_replace("/^https?\:/", $scheme, $url);
	
	return $url;
}

/**
 *
 * @param string $site_identifier        	
 * @param bool $isAction        	
 * @param bool $forceSecure        	
 */
function get_base_url($site_identifier, $isAction, $forceSecure) {
	switch ($site_identifier) {
		case "wts.wikivoyage" :
			$site_identifier = "wts.wikivoyage-old";
		case "en.wikivoyage-old" :
		case "fr.wikivoyage-old" :
		case "nl.wikivoyage-old" :
		case "ru.wikivoyage-old" :
		case "sv.wikivoyage-old" :
		case "wts.wikivoyage-old" :
			$baseurl = "http://$site_identifier.org/";
			$action_prefix = "w/index.php?title=";
			$standard_prefix = "wiki/";
			break;
		case "www.wikivoyage-old-shared" :
			$baseurl = "http://www.wikivoyage-old.org/";
			$action_prefix = "w/shared/index.php?title=";
			$standard_prefix = "shared/";
			break;
		default :
			$action_prefix = "w/index.php?title=";
			$standard_prefix = "wiki/";
			$baseurl = ($forceSecure ? "https:" : "") . "//$site_identifier.org/";
	}
	
	return $baseurl . ($isAction ? $action_prefix : $standard_prefix);
}

/**
 *
 * @param string $title        	
 * @return string
 */
function encodePageTitle($title) {
	return str_replace("%2F", "/", 
		str_replace("%3A", ':', rawurlencode(str_replace(" ", "_", $title))));
}

/**
 *
 * @param string[] $query        	
 * @return string
 */
function query_to_string($query) {
	global $validator;
	
	if ($query === null) {
		return "[null]";
	}
	
	$validator->validate_arg($query, "array", false);
	
	return implode("&", 
		array_map_pass_key($query, function ($key, $val) {
			return "$key=$val";
		}));
}

/**
 *
 * @param string $timestamp        	
 * @param bool $toTimestamp        	
 * @return number|null
 */
function parseMediawikiTimestamp($timestamp, $toTimestamp = true) {
	// 2013-10-30T19:47:48Z
	if (preg_match("/^(\d{4})\-(\d{2})\-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})Z/", $timestamp, $match)) {
		$val = "$match[1]$match[2]$match[3]$match[4]$match[5]$match[6]";
		return $toTimestamp ? strtotime($val) : $val;
	}
	return null;
}

/**
 *
 * @param string $timestamp        	
 * @return int|null
 */
function parseMediawikiTimestampRaw($timestamp) {
	global $logger;
	
	if (preg_match("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/", $timestamp, $match)) {
		$result = strtotime($timestamp);
		$logger->debug("parseMediawikiTimestampRaw($timestamp) => $result");
	} else {
		$result = null;
		$logger->warn("Illegal timestamp: $timestamp");
	}
	return $result;
}

/**
 *
 * @param int $timestamp        	
 * @return string
 */
function unixTimestampToMediawikiTimestamp($timestamp) {
	return date('YmdHis', $timestamp);
}

/**
 *
 * @param string $str        	
 * @param int $index        	
 * @return string
 * @throws StringIndexNotFoundException
 */
function mb_charAt($str, $index) {
	global $validator;
	
	$validator->validate_arg($str, "string", false);
	$validator->validate_arg($index, "integer");
	
	$len = mb_strlen($str);
	if ($index < 0) {
		if ($len < $index * -1) {
			throw new StringIndexNotFoundException("Out of bounds: $index, string length: $len");
		}
		return mb_substr($str, $index, 1);
	} else {
		if ($len <= $index) {
			var_dump($len);
			var_dump($index);
			throw new StringIndexNotFoundException("Out of bounds: $index, string length: $len");
		}
		return mb_substr($str, $index, 1);
	}
}

/**
 * Get a unique key for this specific server request
 * @return string
 */
function get_request_key() {
	if (@$_REQUEST) {
		$request_key = isset($_REQUEST['request_key']) ? $_REQUEST['request_key'] :
			md5(serialize($_REQUEST));
		return $request_key;
	}
	return "";
}

/**
 *
 * @param string $subject        	
 * @param string[] $moreVariables        	
 * @return string
 */
function replace_named_variables_defaults($subject, $moreVariables = []) {
	global $defaultNamedVariables, $validator;
	
	if (@$defaultNamedVariables == null) {
		$defaultNamedVariables = ["year" => date("Y"), "month" => date("m"), "day" => date("d"), 
			"hour" => date("H"), "minute" => date("i"), "seconds" => date("s"), 
			'request_key' => get_request_key()];
	}
	
	$validator->validate_arg($subject, "string");
	$validator->validate_arg($moreVariables, "array");
	
	if (count($moreVariables) > 0) {
		$allVariables = array_merge($defaultNamedVariables, $moreVariables);
	} else {
		$allVariables = &$defaultNamedVariables;
	}
	return replace_named_variables($subject, $allVariables);
}

/**
 *
 * @param string $subject        	
 * @param string[] $variables        	
 * @param bool $keep_token        	
 * @return string
 */
function replace_named_variables($subject, $variables, $keep_token = true) {
	global $logger, $validator;
	
	$validator->validate_arg($subject, "string");
	$validator->validate_arg($variables, "array");
	$validator->validate_arg($keep_token, "bool");
	
	// break apart current string into tokens
	$escaped_char = $keep_token ? "\\" : "";
	$string_tokens = [];
	$escapes = [];
	$this_token = "";
	$len = mb_strlen($subject);
	for($i = 0; $i < $len; $i++) {
		$chr = mb_charAt($subject, $i);
		if ($chr == '\\') {
			if ($i == $len - 1) {
				logger_or_stderr(LEVEL::WARN, "Unterminated string escape: $subject");
				$this_token .= '\\';
			} else {
				$nextChr = mb_charAt($subject, ++$i);
				switch ($nextChr) {
					case '$' :
					case '\\' :
						$string_tokens[] = $this_token;
						$this_token = "";
						$escapes[] = "$escaped_char$nextChr";
						break;
					default :
						logger_or_stderr(LEVEL::WARN, 
							"Invalid character following escape: $nextChr; $subject");
						$this_token .= "\\$nextChr";
				}
			}
		} else {
			$this_token .= $chr;
		}
	}
	$string_tokens[] = $this_token;
	$escapes[] = ""; // for ease of code below
	
	foreach ($string_tokens as $i => $token) {
		// convert our token
		foreach ($variables as $var => $val) {
			if (!preg_match("/^[A-Za-z_]+$/u", $var)) {
				continue;
			}
			
			$token = preg_replace("/(\\$$var(?![A-Za-z_])|\\$\{$var\})/u", $val, $token);
		}
		$string_tokens[$i] = $token;
	}
	
	// put humpty dumpty back together again: readd escapes
	$out = "";
	foreach ($string_tokens as $i => $token) {
		$out .= $token . $escapes[$i];
	}
	
	return $out;
}

/**
 *
 * @param string $str        	
 * @return string
 */
function phpEscapeString($str) {
	// known safe characters
	$encode = !preg_match("/^[\w\`\~\!\@\#\%\^\&\*\(\)\{\}\[\]\:\;\,\.\/\?\>\<\|\=\+\-]*$/u", $str);
	
	if ($encode) {
		return "urldecode(\"" . urlencode($str) . "\")";
	} else {
		return "\"$str\"";
	}
}

/**
 *
 * @param Exception $exception        	
 * @return string
 */
function exceptionToString(Exception $exception) {
	global $validator;
	$validator->validate_arg($exception, "Exception");
	return $exception->getMessage() . ": " . $exception->getTraceAsString();
}

/**
 *
 * @param mixed $var        	
 * @return bool
 * @throws InvalidArgumentException
 */
function boolean_or_exception($var) {
	$val = filter_var($var, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	
	if ($val === null) {
		throw new InvalidArgumentException("Couldn't filter variable: " . print_r($var, true));
	}
	
	return $val;
}

/**
 *
 * @param int $size        	
 * @return string
 */
function readableByteSize($size) {
	global $validator;
	
	$validator->validate_arg($size, "int", true);
	
	if ($size === null) {
		$sizeText = "[unknown]";
	} else {
		if ($size < 1024) {
			$sizeText = $size . 'B';
		} else if ($size < 1024 * 1024) {
			$sizeText = round($size / 1024, 0) . 'KB';
		} else if ($size < 1024 * 1024 * 1024) {
			$sizeText = round($size / 1024 / 1024, 2) . 'MB';
		} else if ($size < 1024 * 1024 * 1024 * 1024) {
			$sizeText = round($size / 1024 / 1024 / 1024, 2) . 'GB';
		} else {
			$sizeText = round($size / 1024 / 1024 / 1024 / 1024, 2) . 'TB';
		}
	}
	
	return $sizeText;
}

/**
 *
 * @param string|string[] $title        	
 * @param bool $case_sensitive
 *        	DEFAULT true
 * @return string
 */
function regexify_template($title, $case_sensitive = true) {
	global $validator;
	
	if (is_array($title)) {
		$join = join("|", 
			array_map("regexify_template", $title, array_fill(0, count($title), $case_sensitive)));
		return "(?:$join)";
	}
	
	$validator->validate_arg($title, "string");
	$validator->validate_args_condition($title, "string length > 0", strlen($title) > 0);
	
	$regex = mb_trim($title);
	$regex = preg_quote($regex, '/');
	$regex = preg_replace("/[\s_]+/u", "[\\t _]+", $regex);
	
	if (!$case_sensitive) {
		return $regex;
	}
	
	$first_char = mb_charAt($regex, 0);
	$lower = mb_strtolower($first_char);
	$upper = mb_strtoupper($first_char);
	if ($lower !== $upper) {
		$regex = "[$upper$lower]" . mb_substr($regex, 1);
	}
	return $regex;
}

/**
 *
 * @param string $filename        	
 * @return string
 */
function get_short_filename($filename) {
	return substr(strrchr($filename, DIRECTORY_SEPARATOR), 1, -4);
}

/**
 *
 * @param string $string_page_text        	
 * @return string[]
 */
function read_configuration_page_lines($string_page_text) {
	global $logger, $validator;
	
	$validator->validate_arg($string_page_text, "string");
	
	$logger->debug("read_configuration_page(" . strlen($string_page_text) . ")");
	
	// remove all text before the notice
	if (preg_match("/$\s*<\!\-\-\s*do not remove this line\s*-->\s*\n/imu", $string_page_text, 
		$match, PREG_OFFSET_CAPTURE)) {
		$offset = $match[0][1] + strlen($match[0][0]);
		$logger->trace("Removing $offset characters.");
		$string_page_text = substr($string_page_text, $offset);
	} else {
		$logger->warn("Can't find <do not remove this line> text");
	}
	
	// remove comments
	$string_page_text = preg_replace("/<\!\-\-[\s\S]*?\-\->/u", "", $string_page_text);
	
	return array_filter(
		preg_replace("/^\s*\*?\s*(.*?)\s*$/u", "$1", 
			explode("\n", str_replace("\r\n", "\n", $string_page_text))), 
		function ($line) {
			return $line !== "";
		});
}

/**
 *
 * @param string|string[] $string        	
 * @param int $spaces        	
 * @param string $input        	
 * @return string|string[]
 */
function indent($string, $spaces, $input = " ") {
	return preg_replace("/^/um", str_repeat($input, $spaces), $string);
}

/**
 *
 * @param string[] $strings        	
 * @param string $content        	
 * @return string[]
 */
function str_prepend($strings, $content) {
	return array_map(function ($string) use($content) {
		return "$content$string";
	}, $strings);
}

/**
 *
 * @param string[] $strings        	
 * @param string $content        	
 * @return string[]
 */
function str_append($strings, $content) {
	return array_map(function ($string) use($content) {
		return "$string$content";
	}, $strings);
}

/**
 * run a preg_replace iteratively on a string until
 * it produces no more changes
 * 
 * @param string $regex        	
 * @param string $replace        	
 * @param string $subject        	
 * @return string the string
 */
function iter_replace($regex, $replace, $subject, &$flag = null) {
	do {
		$pre = $subject;
		$subject = preg_replace($regex, $replace, $subject);
	} while (($pre !== $subject) && ($flag = true));
	
	return $subject;
}

/**
 *
 * @param string $regex        	
 * @param string $replace        	
 * @param string $subject        	
 * @param bool $flag        	
 * @return string
 */
function preg_replace_track($regex, $replace, &$subject, &$flag) {
	if (preg_match($regex, $subject)) {
		$flag = true; // track major changes
		$subject = preg_replace($regex, $replace, $subject);
	}
	return $subject;
}

/**
 *
 * @param string $needle        	
 * @param string $replace        	
 * @param string $subject        	
 * @param bool $flag        	
 * @return string
 */
function str_replace_track($needle, $replace, &$subject, &$flag) {
	$subject2 = str_replace($needle, $replace, $subject);
	if ($subject2 !== $subject) {
		$flag = true;
		$subject = $subject2;
	}
	return $subject;
}

/**
 *
 * @param string[] $strings        	
 * @param string $escape        	
 * @return string[]
 */
function preg_quote_all($strings, $delimiter = "/") {
	if ($delimiter === null) {
		$callback = "preg_quote";
	} else {
		$callback = function ($string) use($delimiter) {
			return preg_quote($string, $delimiter);
		};
	}
	return array_map($callback, $strings);
}

/**
 *
 * @param string $string        	
 * @return boolean
 */
function is_empty($string) {
	global $MB_WS_RE_OPT;
	return $string === null || preg_match("/^$MB_WS_RE_OPT$/u", $string);
}

/**
 *
 * @param int $time        	
 * @return string
 */
function seconds_to_human_readable_time($time) {
	global $logger, $validator;
	
	$logger->trace("seconds_to_human_readable_time($time)");
	$validator->validate_arg($time, "numeric");
	
	$seconds = $time % SECONDS_PER_MINUTE;
	$time = intval($time / SECONDS_PER_MINUTE);
	
	$minutes = $time % MINUTES_PER_HOUR;
	$time = intval($time / MINUTES_PER_HOUR);
	
	$hours = $time % HOURS_PER_DAY;
	$time = intval($time / HOURS_PER_DAY);
	
	$days = $time;
	
	$text = "";
	if ($days > 0) {
		$plural = $days === 1 ? "" : "s";
		$text .= "$days day$plural, ";
		$show_hours = true;
	}
	
	if ($hours > 0 || @$show_hours) {
		$plural = $hours === 1 ? "" : "s";
		$text .= "$hours hour$plural, ";
		$show_minutes = true;
	}
	
	if ($minutes > 0 || @$show_minutes) {
		$plural = $minutes === 1 ? "" : "s";
		$text .= "$minutes minute$plural ";
	}
	
	if ($text != "") {
		$text .= "and ";
	}
	
	$plural = $seconds === 1 ? "" : "s";
	$text .= "$seconds second$plural";
	
	$logger->trace("seconds_to_human_readable_time($seconds) => \"$text\"");
	
	return $text;
}

/**
 * 
 * @param string $string
 * @return string
 */
function encode_cdata($string) {
	$split = preg_split('/\]\]>/', $string);
	return join("", preg_replace("/^[\s\S]*[&\"'<>][\s\S]*$/", "<![CDATA[$0]]>", $split));
}