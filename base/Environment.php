<?php
/**
 * 
 * @author magog
 *
 */
class Environment {
	
	/**
	 * 
	 * @var Environment
	 */
	private static $instance;

	/**
	 * 
	 * @var string[]|null
	 */
	private $argv;
	
	/**
	 * 
	 * @var array[]
	 */
	private $properties;
	
	/**
	 * 
	 * @var string
	 */
	private $error_types;
	
	/**
	 * 
	 * @var Logger
	 */
	private $logger;
	
	/**
	 * 
	 * @var string
	 */
	private $properties_hooks;
	
	/**
	 * 
	 * @var callable[]
	 */
	private $variables_hooks;
	
	/**
	 * 
	 * @var Validator
	 */
	private $validator;
	
	/**
	 * 
	 * @var Wiki_Interface
	 */
	private $wiki_interface;

	/**
	 * 
	 * @var String_Utils
	 */
	private $string_utils;
	
	/**
	 * 
	 * @var Remote_Io
	 */
	private $remote_io;
	
	/**
	 * @return Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * @return Validation
	 */
	public function get_validator() {
		return $this->validator;
	}
	
	/**
	 * @return Wiki_Interface
	 */
	public function get_wiki_interface() {
		return $this->wiki_interface;
	}
	
	/**
	 * 
	 * @return Remote_Io
	 */
	public function get_remote_io() {
		return $this->remote_io;
	}

	/**
	 * 
	 * @return String_Utils
	 */
	public function get_string_utils() {
		return $this->string_utils;
	}
	
	/**
	 * 
	 * @throws ArrayIndexNotFoundException
	 * @throws ParseException
	 */
	private function __construct() {
		$environment_name = trim(file_get_contents_ensure(BASE_DIRECTORY . 
				"/properties/environment"));
		
		$this->properties_hooks = ["environment" => "environment.$environment_name",
				"constants" => "constants", "messages" => "messages"];
		$this->variables_hooks = ["validator" => function () {
			return new Validation();
		},"logger" => function () {
			return Logger::construct_from_file("logger-" . $this->get_prop("environment", "environment") .
						".xml");
		},"argv" => [$this,"parse_command_line_options"],"wiki_interface" => function () {
			return new Wiki_Interface();
		}, "string_utils" => function() {
			return new String_Utils();
		}, "remote_io" => function() {
			return new Remote_Io();
		}];
		
		array_map([Hook_Register::class, "register"], array_merge(["start","end"], 
				array_keys($this->properties_hooks), array_keys($this->variables_hooks)));
		
		Hook_Register::add("argv", function() use ($environment_name) {
			$this->logger->trace("Environment: $environment_name");
		});
	}
	
	
	/**
	 * @return void
	 */
	private function setup() {
		$this->setup_hooks();
		
		Hook_Register::trigger("start");
		$this->register_properties_file($this->properties_hooks);
		array_walk($this->properties_hooks, function ($filename, $variable) {
			if ($filename !== $variable) {
				$this->properties[$variable] = &$this->properties[$filename];
				unset($this->properties[$filename]);
			}
			$GLOBALS[$variable] = &$this->properties[$variable]; // backwards compatibility
			Hook_Register::trigger($variable);
		});
		array_walk($this->variables_hooks, function (callable $callable, $variable) {
			$this->$variable = $callable();
			$GLOBALS[$variable] = &$this->$variable; // backwards compatibility
			Hook_Register::trigger($variable);
		});
		Hook_Register::trigger("end");
	}
	
	/**
	 * @return self
	 */
	public static function get() {
		return self::$instance;
	}
	
	/**
	 * meant to be run at startup by this file only
	 * @throws IllegalStateException
	 * @return self
	 */
	public static function init() {
		if (self::$instance !== null) {
			return self::$instance;
		}
		
		self::$instance = new self();
		self::$instance->setup();
		
		return self::$instance;
	}
	
	/**
	 *
	 * @param int $errno        	
	 * @param string $errstr        	
	 * @param string $errfile        	
	 * @param int $errline        	
	 * @param array $errcontext        	
	 * @return void
	 */
	private function error_handler($errno, $errstr, $errfile = null, $errline = null, 
		array $errcontext = null) {
		
		// ignore undefined errors suppressed with @ sign
		$error_reporting = error_reporting();
		if (!$error_reporting) {
			return;
		}
		
		$email = false;
		$levelMsg = $this->error_types[$errno];
		switch ($errno) {
			case E_ERROR :
			case E_RECOVERABLE_ERROR :
				$email = true;
				break;
			case E_DEPRECATED :
			case E_USER_DEPRECATED :
				$level = Level::DEBUG;
				break;
			case E_WARNING :
			case E_NOTICE :
				$level = Level::WARN;
				break;
			default :
				$level = Level::ERROR;
		}
		
		$error_message = "$levelMsg: $errstr";
		if ($errfile) {
			$error_message .= " in $errfile";
			
			if ($errline) {
				$error_message .= " on line $errline";
			}
		}
		
		if ($email) {
			ogrebotMail($error_message);
		} else {
			if (@$this->logger && $this->logger->isTraceEnabled()) {
				$error_message .= get_backtrace_string(get_backtrace(true, 2));
			}
			logger_or_stderr($level, $error_message);
		}
	}
	
	/**
	 *
	 * @return void
	 */
	private function shutdown_handler() {
		$error = error_get_last();
		
		if ($error) {
			$this->error_handler($error["type"], $error["message"], @$error["file"], @$error["line"]);
		} else {
			logger_or_stderr(Level::TRACE, "Script completed normally.");
		}
	}
	
	private function setup_hooks() {
		//must execute these before everything else...
		Hook_Register::add("start", function() {
			mb_internal_encoding("UTF-8");
			
			$this->error_types = array_flip(prune_array_to_keys(get_defined_constants(), ["E_ERROR","E_WARNING",
						"E_PARSE","E_NOTICE","E_CORE_ERROR","E_CORE_WARNING","E_COMPILE_ERROR",
						"E_COMPILE_WARNING","E_USER_ERROR","E_USER_WARNING","E_USER_NOTICE","E_STRICT",
						"E_RECOVERABLE_ERROR","E_DEPRECATED","E_USER_DEPRECATED"]));
		});
		
		Hook_Register::add("environment", function() {
			$this->properties["environment"]['live'] = !!$this->properties["environment"]['live'];
		});
		Hook_Register::add("constants", function() {
			$this->properties["constants"]["illegal_pagename_re"] = 
				"/" . preg_quote($this->properties["constants"]["illegal_pagename_chars"], "/") . "/";
			$email_to = array_key_or_exception($this->properties["constants"], "error.email.to");
		});
		
		Hook_Register::add("end", function() {

			$error_handler = new ReflectionMethod(self::class, "error_handler");
			$error_handler->setAccessible(true);
			
			$shutdown_handler = new ReflectionMethod(self::class, "shutdown_handler");
			$shutdown_handler->setAccessible(true);
			
			set_error_handler($error_handler->getClosure($this));
			register_shutdown_function($shutdown_handler->getClosure($this));

			if (defined('HHVM_VERSION')) {
				//HHVM bug: can't combine this string (???)
				$this->logger->debug("HHVM detected.");
				$this->logger->debug(HHVM_VERSION);
			}
			
			$old_memory = ini_get("memory_limit");
			ini_set('memory_limit', '1536M');
			
			$backtrack_limit = ini_get("pcre.backtrack_limit");
			if ($backtrack_limit < 100000000) {
				ini_set("pcre.backtrack_limit", "100000000");
				$this->logger->trace("Backtrack limit increased from $backtrack_limit to 10000000");
			} else {
				$this->logger->trace("Backtrack limit at $backtrack_limit (not increased)");
			}
			
			$this->wiki_interface->set_live_edits(!!$this->get_prop("environment", "live_edits"));

			$this->load_wikimedia_site_regexes();
			
			Peachy::set_password_manager(new OgreBot_Password_Manager());
		});
	}

	/**
	 * 
	 * @param string|string[] $filenames
	 * @return void
	 */
	public function register_properties_file($filenames) {
		if (!is_array($filenames)) {
			$filenames = [$filenames];
		}
		array_walk($filenames, function($filename) {
			load_property_file_into_variable($this->properties[$filename], $filename);
		});
	}
		
	/**
	 * @param bool $force_post
	 * @return string[]
	 */
	public function get_request_args($force_post = false) {
		
		$args = $force_post ? $_POST : $_REQUEST;
		if ($this->logger->isDebugEnabled()) {
			if ($args) {
				if ($this->logger->isTraceEnabled()) {
					$this->logger->trace($args);
				}
			} else {
				$this->logger->debug("Empty request args.");
			}
		}
		return $args;
	}
	
	/**
	 *
	 * @param bool $shift
	 *        	Whether or not to shift the first value off the array. DEFAULT: false
	 * @return string[]
	 */
	
	public function load_command_line_args($shift = false) {
		return $shift && $this->argv ? array_slice($this->argv, 1) : $this->argv;
	}
	
	/**
	 * TODO move this to where it's needed
	 * @return void
	 */
	private function load_wikimedia_site_regexes() {		
		// load dynamic regex constants
		$regexes = preg_quote_all($this->get_prop("constants", 'wikimedia_sites'));
		$this->properties["constants"]['wikimedia_sites_regex'] = "/^https?:\/\/(.+\.)?(" . 
			implode('|', $regexes) . ")$/i";
	}
	
	/**
	 *
	 * @return string[]|null
	 */
	private function parse_command_line_options() {
		global $argv;
		
		if (!isset($argv)) {
			return [];
		}
		
		$local_argv = $argv;

		// Check for command line args overriding logger level and output location
		$levelText = find_command_line_arg($local_argv, "LEVEL");
		if ($levelText !== null) {
			try {
				$level = Level::level_to_int($levelText);
				$this->logger->setDebugLevel($level);
			} catch (InvalidArgumentException $e) {
				$this->logger->error(
					"Unrecognized level in command line argument:" . " $levelText. Ignoring.");
			}
		}
	
		$debug_std_out = find_command_line_arg($local_argv, "DEBUGSTDOUT") !== null;
		if ($debug_std_out) {
			try {
				$this->logger->addOutputStreams("STDOUT");
			} catch (Exception $e) {
				$this->logger->error($e);
			}
		}
	
		$debug_std_err = find_command_line_arg($local_argv, "DEBUGSTDERR") !== null;
		if ($debug_std_err) {
			try {
				$this->logger->addOutputStreams("STDERR");
			} catch (Exception $e) {
				$this->logger->error($e);
			}
		}
	
		$no_live = find_command_line_arg($local_argv, "NOLIVE") !== null |
			find_command_line_arg($local_argv, "NO-LIVE") !== null;
		
		if ($no_live) {
			$this->logger->info("Live edits deactivated (NOLIVE)");
			Hook_Register::add("end", function () {
				$this->wiki_interface->set_live_edits(false);
			});
		}
	
		$cache = find_command_line_arg($local_argv, "CACHE");
		if ($cache) {
			if (preg_match("/^\s*(\d+)\s*(seconds|minutes|hours|days|months|years)\s*$/i", $cache,
				$matches)) {
						
					$multiplier = 1;
					switch (strtolower($matches[2])) {
						case 'years' :
							$multiplier *= 12;
						case 'months' :
							$multiplier *= 30;
						case 'days' :
							$multiplier *= 24;
						case 'hours' :
							$multiplier *= 60;
						case 'minutes' :
							$multiplier *= 60;
					}
					try {
						Http_Cache_Reader::set_default_cache_time($matches[1] * $multiplier);
					} catch (IllegalArgumentException $e) {
						$this->logger->error(
							"Illegal time. Must be less than " . DEFAULT_URL_CACHE_TIME . " seconds");
					}
				} else {
					throw new IllegalArgumentException(
						"Unrecognized cache time. Proper format: " .
						"([int time])(seconds|minutes|hours|days|months|years)");
				}
		}
	
		$mem = find_command_line_arg($local_argv, "MEM");
		if ($mem) {
			if (preg_match("/^\d+(?:\.\d*)?(?i:G|M|K)?$/i", $mem)) {
				$this->logger->info("Overriding memory limit to $mem");
				ini_set('memory_limit', $mem);
			} else {
				throw new IllegalArgumentException(
					"Unrecognized memory limit. Proper format: " . "/^\d+(?:\.\d*)?(?i:G|M|K)?$/i");
			}
		}
		
		return $local_argv;
	}
	
	/**
	 * 
	 * @param string|string[] $properties
	 * @return mixed
	 * @throws IllegalArgumentException
	 */
	public function get_prop($properties) {
		if (!is_array($properties)) {
			$properties = func_get_args();
		}
		
		$next = &$this->properties;
		
		$first_properties_file = current($properties);
		$first_key = isset($this->properties[$first_properties_file]) ? $this->properties[$first_properties_file] : null;
		if ($first_key === null) {
			$this->register_properties_file($first_properties_file);
		}
		foreach ($properties as $property) {
			$next = &$next[$property];
			if ($next === null) {
				throw new IllegalArgumentException("Constant not found: " . print_r($properties, true));
			}
		}
		return $next;
	}
	
	/**
	 * retrieve multiple properties
	 * @param string|numeric $base
	 * @param array $properties
	 * @return array
	 * @throws IllegalArgumentException
	 */
	public static function props($base, array $all_properties) {
		return array_map(
			function ($properties) use ($base) {
				if (is_array($properties)) {
					array_unshift($properties, $base);
				} else {
					$properties = [$base, $properties];
				}
				return self::$instance->get_prop($properties);
			}, $all_properties);
	}
	
	/**
	 * Shorthand for get_prop() 
	 * @param string|string[] $properties
	 * @return mixed
	 * @throws IllegalArgumentException
	 */
	public static function prop($properties) {
		if (!is_array($properties)) {
			$properties = func_get_args();
		}
		return self::$instance->get_prop($properties);
	}
}