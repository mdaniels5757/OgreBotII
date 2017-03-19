<?php
abstract class Refactor {
	
	/**
	 *
	 * @var Refactor[]
	 */
	private static $classes;
	
	/**
	 *
	 * @return string
	 */
	abstract protected function get_extension();
	
	/**
	 *
	 * @return string
	 */
	private function get_minify_command_key() {
		$extension = $this->get_extension();
		return "scripts.$extension.command";
	}
	
	/**
	 *
	 * @return Refactor[]
	 */
	private static function get_all_refactor_classes() {
		global $logger;
		if (self::$classes === null) {
			self::$classes = map_array_function_keys(
				Classloader::get_all_instances_of_type("Refactor"), 
				function ($instance) {
					return [$instance->get_extension(), $instance];
				});
		}
		return self::$classes;
	}
	
	/**
	 * Override as needed
	 * 
	 * @param string $file_name        	
	 * @param string[] $command_line_args        	
	 * @return boolean
	 */
	private function cleanupFile($file_name, array $command_line_args) {
		global $constants, $logger, $validator;
		
		$validator->validate_arg($file_name, "string");
		$validator->validate_arg_array($command_line_args, "string");
		
		$extension = $this->get_extension();
		$full_name_input = BASE_DIRECTORY . "/public_html/$extension/$file_name.$extension";
		$full_name_minified = BASE_DIRECTORY . "/public_html/$extension/$file_name.min.$extension";
		
		$logger->info("Loading $full_name_input.");
		try {
			$contents = file_get_contents_ensure($full_name_input);
		} catch (CantOpenFileException $e) {
			$logger->error($e);
			return false;
		}
		
		$logger->debug("  ...preprocessing");
		$contents_updated = $this->preProcess($contents, $command_line_args);
		
		if ($contents_updated !== null && $contents_updated !== $contents) {
			$logger->debug("  ...file was changed. Updating it.");
			try {
				file_put_contents_ensure($full_name_input, $contents_updated);
				$logger->debug("  ...file updated after proprocessing.");
			} catch (CantWriteToFileException $e) {
				$logger->error($e);
				return false;
			}
		}
		
		if (find_command_line_arg($command_line_args, "minify") !== null) {
			$logger->info(" ...minifying to $full_name_minified");
			$key = $this->get_minify_command_key();
			$logger->debug("  ...key[$key]");
			
			$raw_command = array_key_or_exception($constants, $key);
			$logger->debug("  ...command[$raw_command]");
			
			$command = replace_named_variables($raw_command, 
				["base_dir" => BASE_DIRECTORY, "in_file" => $full_name_input, 
					"out_file" => $full_name_minified]);
			exec($command, $output, $return);
			
			if ($return !== 0) {
				$logger->error("  ...compilation failed. Return status: $return.");
				return false;
			}
			
			if (!$output) {
				$allOutput = "[empty])";
			} else {
				$allOutput = "";
				foreach ($output as $line) {
					$allOutput .= "\n    $line.";
				}
			}
			$logger->info("  ...done. Script output: $allOutput");
		} else {
			$logger->debug("  ...not minifying...");
		}
		
		return true;
	}
	
	/**
	 * Override as needed
	 * 
	 * @param string $input_string        	
	 * @param string[] $command_line_args        	
	 * @return void
	 */
	protected function preProcess($input_string, $command_line_args) {
	}
	
	/**
	 *
	 * @return void
	 */
	public static function run() {
		global $env, $logger;
		
		$args = $env->load_command_line_args();
		
		$variables = [];
		$types = find_command_line_arg($args, "types", true);
		if ($types === "all") {
			$refactorers = self::get_all_refactor_classes();
		} else {
			$refactorers = array_map(
				function ($type_string) {
					$type_string = mb_trim($type_string);
					try {
						$refactorer = array_key_or_exception(self::get_all_refactor_classes(), 
							$type_string);
						return $refactorer;
					} catch (ArrayIndexNotFoundException $e) {
						throw new IllegalArgumentException("Unrecognized type: $type_string");
					}
				}, explode(",", $types));
		}
		
		/* @var $refactorer Refactor */
		$logger->info(
			"Updating types: " . implode(", ", 
				array_map(function ($refactorer) {
					return $refactorer->get_extension();
				}, $refactorers)));
		
		$files = find_command_line_arg($args, "files", true);
		$files_array = explode(",", $files);
		
		$logger->info("Updating " . count($files_array) . " files.");
		
		foreach ($refactorers as $refactorer) {
			foreach ($files_array as $file) {
				$refactorer->cleanupFile(trim($file), $args);
			}
		}
		
		$logger->info("Done.");
	}
}