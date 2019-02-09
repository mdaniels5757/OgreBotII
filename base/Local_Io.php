<?php

/**
 * http://bytes.com/groups/php/6770-how-read-stdin-using-php
 * @param int $length
 * @return string
 */
function stdin_get($length = 255) {
	$fr = fopen("php://stdin", "r");
	$input = fgets($fr, $length);
	$input = rtrim($input);
	fclose($fr);
	return $input;
}

/**
 * Prints a message to standard error stream.
 * 
 * @param mixed $msg        	
 * @return void
 */
function stderr_print($msg) {
	static $logfd;
	
	if (!is_resource($logfd)) {
		$logfd = fopen('php://stderr', 'w');
	}
	fwrite($logfd, $msg);
}

/**
 *
 * @param int $level        	
 * @param string $message        	
 */
function logger_or_stderr($level, $message) {
	global $logger;
	
	if (@$logger) {
		$logger->log($level, $message, 1);
	} else if ($level <= Level::WARN) {
		$level_string = Level::int_to_level($level);
		stderr_print("$level_string :: $message\n");
	}
}

/**
 *
 * @param string $filename        	
 * @param bool $use_include_path        	
 * @throws CantOpenFileException
 * @return string
 */
function file_get_contents_ensure($filename, $use_include_path = false) {
	global $validator;
	
	if ($validator) {
		$validator->validate_arg($filename, "string");
		$validator->validate_arg($use_include_path, "boolean");
	}
	
	$text = file_get_contents($filename, $use_include_path);
	
	if ($text === false) {
		throw new CantOpenFileException($filename);
	}
	return $text;
}

/**
 *
 * @param string $filename        	
 * @param mixed $data        	
 * @param int $flags
 * @return int the number of bytes returns by file_put_contents
 * @throws CantOpenFileException
 */
function file_put_contents_ensure($filename, $data, $flags = 0) {
	global $validator;
	$validator->validate_arg($filename, "string");
	$bytes = file_put_contents($filename, $data, $flags);
	
	if ($bytes === false) {
		throw new CantWriteToFileException($filename);
	}
	
	return $bytes;
}
/**
 *
 * @param string $filename        	
 * @param string $text        	
 * @return void
 * @throws CantOpenFileException
 * @throws CantWriteToFileException
 */
function write_to_log($filename, $text) {
	global $logger, $validator;
	
	try {
		$validator->validate_arg($filename, "string");
		$validator->validate_arg($text, "string");
	} catch (AssertionFailureException $e) {
		$filename = "$filename";
		$text = "$text";
		ogrebotMail($e);
	}
	
	if (strlen($text) === 0) {
		ogrebotMail("Appending empty text to log ignored.");
		return;
	}
	
	$logger->debug("write_to_log(\"$filename\", \"$text\")");
	
	$log = fopen($filename, 'a');
	
	if (!$log) {
		throw new CantOpenFileException($filename);
	}
	
	if (!fwrite($log, $text)) {
		throw new CantWriteToFileException($filename);
	}
}

/**
 *
 * @param array $var        	
 * @param string $filename        	
 * @param bool $short_name        	
 * @return void
 * @throws ParseException
 */
function load_property_file_into_variable(&$var, $filename, $short_name = true) {
	if ($short_name) {
		$filename = BASE_DIRECTORY . "/properties/$filename.properties";
	}
	
	logger_or_stderr(Level::TRACE, "load_property_file_into_variable(\$var, $filename)");
	
	$var = parse_ini_file($filename);
	if ($var === false) {
		throw new ParseException("Can't parse $filename");
	}
	
	if ($filename === BASE_DIRECTORY . "/properties/secrets.properties") {
		$debug_data = array_fill_keys(array_keys($var), "[SCRUBBED]");
	} else {
		$debug_data = &$var;
	}
	
	log_property_data($debug_data);
}

/**
 *
 * @param string $filename
 * @param bool $short_name
 * @return array $var
 * @throws ParseException
 */
function load_property_file($filename, $short_name = true) {
	load_property_file_into_variable($out, $filename, $short_name);
	return $out;
}

/**
 *
 * @param string $command        	
 * @return int
 */
function ogrebotExec($command) {
	global $logger;
	
	$logger->info("Running:\n$command");
	
	exec($command, $output, $return_var);
	
	$logger->info(" ...done");
	
	if ($return_var !== 0) {
		ogrebotMail("Command $command FAILED. Return status: $return_var.");
	}
	
	return $return_var;
}

/**
 *
 * @param string $command
 * @return string
 * @throws Exception
 */
function ogrebotExecWithOutput(string $command): string {
	global $logger;
	
	$logger->info("Running:\n$command");
	
	exec($command, $output, $return_var);
	
	$logger->info(" ...done");
	
	if ($return_var !== 0) {
		throw new Exception("Command $command FAILED. Return status: $return_var.");
	}
	
	return join("\n", $output);
}


/**
 *
 * @param string $prompt        	
 * @throws IllegalStateException
 */
function read_password_from_stdin($prompt = "Enter password: ") {
	if (php_sapi_name() !== "cli") {
		throw new IllegalStateException("Can't read from stdin for web server request");
	}
	
	$unix = !preg_match('/^win/i', PHP_OS);
	
	if ($unix) {
		echo $prompt;
		try {
			$command = "/usr/bin/env bash -c 'stty -echo; read password && echo \"\$password\"'";
			$password = shell_exec($command);
			echo "\n";
		} catch (Exception $e) {
			// generic handler to account for stty problems after mistyped password
			ogrebotMail($e);
			throw new IllegalStateException($e->getMessage());
		}
	} else {
		stderr_print("Warning: Windows; can't blank password input.\n");
		readline($prompt);
	}
	
	return mb_trim($password);
}

/**
 *
 * @param array $data        	
 * @return void
 */
function log_property_data($data) {
	global $logger;
	
	if (@$logger && $logger->isTraceEnabled()) {
		$logger->trace(count($data) . " entries found.");
		$constantData = "Data:";
		foreach ($data as $key => $val) {
			if (!is_string($val)) {
				$val = str_replace("\n", "\n\t", print_r($val, true));
			}
			$constantData .= "\n  $key => $val";
		}
		$logger->trace($constantData);
	}
}

/**
 *
 * @param string|resource $directory        	
 * @param bool $include_directories
 *        	DEFAULT false
 * @return string[]
 * @throws CantOpenFileException If $directory is a string and the directory can't be opened
 * @throws IllegalArgumentException if $directory is a file, not a directory
 */
function get_all_files_in_directory($directory, $include_directories = false) {
	global $logger, $validator;
	
	$validator->validate_arg($directory, "string");
	$validator->validate_arg($include_directories, "bool");
	
	$logger->debug("Opening files in directory $directory");
	
	$handle = opendir($directory);
	
	if ($handle === null) {
		throw new CantOpenFileException("Can't open directory $directory");
	}
	
	if (!is_dir($directory)) {
		throw new IllegalArgumentException("$directory is not a directory.");
	}
	
	$all_files = array();
	while (($next = readdir($handle)) !== false) {
		if ($next === '.' || $next === '..') {
			$logger->trace("Directory skipped: $next");
			continue;
		}
		if (!$include_directories && is_dir($directory . DIRECTORY_SEPARATOR . $next)) {
			$logger->trace("Directory skipped: $next");
			continue;
		}
		$logger->trace("File found: $next");
		$all_files[] = $next;
	}
	
	closedir($handle);
	
	$logger->debug(count($all_files) . "  files found.");
	
	return $all_files;
}
