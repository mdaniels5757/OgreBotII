<?php

class Oldver_Shared {
	
	const NO_ESCAPE_PREFIX = "noEscape/";
	
	/**
	 * 
	 * @var bool
	 */
	private static $loaded_messages = false;
	
	/**
	 * @param string $key
	 * @param string[]|null $args
	 * @return string
	 */
	public static function msg($key, $args = null) {
		global $msg, $validator;
		
		$validator->validate_arg($key, "string", true);
		$validator->validate_arg($args, "array", true);
		
		if (!$key) {
			return null;
		}
		
		self::load_messages();
		
		$message = $msg[$key];
		
		if (!$message) {
			//empty value; probably an error
			array_key_or_exception($msg, $key);
		}
		
		if ($args) {
			$message = replace_named_variables($message, $args);
		}
		
		if (str_starts_with($message, self::NO_ESCAPE_PREFIX)) {
			$message = substr($message, strlen(self::NO_ESCAPE_PREFIX));
		} else {
			$message = sanitize($message, false);
		}
		
		$message = preg_replace("/\s+/", " ", $message);
		
		return $message;
	}
	
	/**
	 * @return void
	 */
	public static function load_messages() {
		global $msg;
		
		if (!self::$loaded_messages) {
			self::$loaded_messages = true;
			load_property_file_into_variable($msg, "oldver_messages");
		}
	}
}