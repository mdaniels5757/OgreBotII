<?php
class Classloader {
	
	const AUTOLOAD_FUNCTION_NAME = "_autoload";
	
	/**
	 *
	 * @var string[]
	 */
	private static $paths = ["base", "base/entity", "base/exception", "base/exception/instance", 
		"base/Lazy_Storage", "base/logger", "base/logger/formatter", "base/Page_Parser", 
		"base/Project_Data", "base/Page_Parser/unparsed-element-type", "base/remote", 
		"base/Template_Utils", "base/Template_Utils/Template_Interface", "base/UserData", 
		"BranchMapCreator", "BranchMapCreator/Factory", "BranchMapCreator/dto", 
		"BranchMapCreator/svg_creator", "category-files", "cleanup", 
		"cleanup/Cleanup_Template_Logic", "cleanup/instance", "cleanup/mass-tester", 
		"cleanup/package", "cleanup/parsers", "CommonsHelper", "CommonsHelper/debug", "cron", 
		"cron/cron-date-factory", "FCC_Database_Serializer", "fileinfo", "fileinfo/upload-history", 
		"generate-getters-setters", "identity", "mass-cleanup", "newuploads", "nowcommonslist", 
		"oldver", "Png_Svg_Service", "refactor", "refresh-configs", "relink", "relink/types", 
		"UploadReport", "UploadReport/newuploadhandler", "util"];
	
	/**
	 *
	 * @var string[]
	 */
	private static $abstract_class_paths = [
		"Unparsed_Element_Type" => "base/Page_Parser/unparsed-element-type", 
		"Abstract_Cleanup" => "mass-cleanup", "Now_Commons_List" => "nowcommonslist/types", 
		"Refactor" => "refactor", "FCC_Database_Serializer" => "FCC_Database_Serializer", 
		"Latitude_Longitude_Svg_Creator" => "BranchMapCreator/svg_creator", 
		"Cleanup_Module" => "cleanup/parsers", 
		"Upload_History_Wiki_Text_Writer" => "fileinfo/upload-history"];
	
	/**
	 *
	 * @var string[]
	 */
	private static $files_by_path;
	
	/**
	 * 
	 * @param string $path
	 * @param string $class
	 * @return void
	 */
	private static function load_class($path, $class) {
		require_once $path;
		if (method_exists($class, self::AUTOLOAD_FUNCTION_NAME)) {
			$method = (new ReflectionClass($class))->getMethod(self::AUTOLOAD_FUNCTION_NAME);
			if ($method->isStatic()) {
				$method->setAccessible(true);
				$method->invoke(null);
			}
		}
	}
	
	/**
	 * 
	 * @param mixed $class_or_this
	 * @param String $method
	 * @return Closure
	 */
	public static function get_closure($class_or_this, $method) {
		$static = is_string($class_or_this);
	
		$reflection_method = (new ReflectionClass(
			$static ? $class_or_this : get_class($class_or_this)))->getMethod($method);
		$reflection_method->setAccessible(true);
		
		return $reflection_method->getClosure($static ? null : $class_or_this);
	}
	
	/**
	 *
	 * @param string $class        	
	 */
	public static function autoload($class) {
		
		// attempt to find in the calling directory
		$backtrace_a = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		if (defined("HHVM_VERSION") && !@$backtrace_a[1] === null) {
			$backtrace = $backtrace_a[0];
		} else if (isset($backtrace_a[1]["file"])) {
			$backtrace = $backtrace_a[1];
		} else {
			$backtrace = $backtrace_a[2];
		}
		
		$directory = pathinfo(@$backtrace["file"], PATHINFO_DIRNAME);
		if ($directory) {
			$path = "$directory/$class.php";
			if (file_exists($path)) {
				self::load_class($path, $class);
				return true;
			}
		}
		
		// attempt to find in predefined directories
		if (self::$files_by_path === null) {
			$unflattened_filenames = array_map(
				function ($path) {
					return self::php_files_in_directory(BASE_DIRECTORY . DIRECTORY_SEPARATOR . $path);
				}, self::$paths);
			self::$files_by_path = array_merge_all($unflattened_filenames);
		}
		
		if (isset(self::$files_by_path[$class])) {
			self::load_class(self::$files_by_path[$class], $class);
			return true;
		}
		
		return false;
	}
	
	/**
	 *
	 * @param string $path        	
	 * @return void
	 * @throws CantOpenFileException
	 */
	public static function include_directory($path) {
		$files_in_directory = self::php_files_in_directory($path);
		array_walk($files_in_directory, function ($file) {
			preg_match("/.+\/(.+?)\.php$/", self::normalize($file), $class);
			self::load_class($file, $class[1]);
		});
	}
	
	/**
	 *
	 * @param string $path        	
	 * @return string[]
	 */
	private static function php_files_in_directory($path) {
		return map_array_function_keys(scandir($path), 
			function ($filename) use($path) {
				if (preg_match("/(.+)\.php$/", $filename, $match)) {
					$full = $path . DIRECTORY_SEPARATOR . $filename;
					if (is_file($full)) {
						return [$match[1], $full];
					}
				}
			});
	}
	
	/**
	 *
	 * @param string $class        	
	 * @param bool $abstract
	 *        	DEFAULT false
	 * @return string[]
	 * @throws ArrayIndexNotFoundException if $class isn't registered
	 */
	public static function get_all_class_names_of_type($class, $allow_abstract = false) {
		global $logger, $validator;
		
		$validator->validate_arg($class, "string");
		$validator->validate_arg($allow_abstract, "bool");
		
		$class_path = array_key_or_exception(self::$abstract_class_paths, $class);
		if ($class_path) {
			self::include_directory(BASE_DIRECTORY . '/' . $class_path);
		}
		
		$classes = array();
		
		foreach (get_declared_classes() as $declared_class) {
			if (is_subclass_of($declared_class, $class)) {
				
				if (!$allow_abstract) {
					$reflection_class = new ReflectionClass($declared_class);
					if ($reflection_class->isAbstract()) {
						$logger->debug("Ignoring abstract $declared_class subclass of $class");
						continue;
					}
				}
				
				$logger->debug("Found $declared_class subclass of $class");
				$classes[] = $declared_class;
			}
		}
		
		return $classes;
	}
	
	/**
	 *
	 * @param string $class        	
	 * @param array|callable|null $args        	
	 * @return object[]
	 * @throws ArrayIndexNotFoundException if $class isn't registered
	 */
	public static function get_all_instances_of_type($class, $args = null) {
		$callable = is_callable($args) || $args instanceof Closure ? $args : null;
		$class_names = self::get_all_class_names_of_type($class, false);
		
		$instances = map_array_function_keys($class_names, 
			function ($class_name) use($callable, $args) {
				if ($callable) {
					$next_args = $callable($class_name);
				} else if (is_array($args)) {
					$next_args = $args;
				} else {
					$next_args = [];
				}
				return [$class_name, 
					(new ReflectionClass($class_name))->newInstanceArgs($next_args)];
			});
		
		return $instances;
	}
	
	/**
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function normalize($path) {
		return str_replace(DIRECTORY_SEPARATOR, "/", $path);
	}
	
	/**
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function get_system_path($path) {
		return str_replace("/", DIRECTORY_SEPARATOR, $path);
	}
}

spl_autoload_register(['Classloader', 'autoload'], false, true);