<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $logger;

$properties = XmlParser::xmlFileToStruct("prune-logs.xml");

/* Prune time normal */
$now = time();
$deleted = 0;
array_walk($properties["PRUNE-LOGS"][0]["elements"]["DIRECTORY"], 
	function ($directory) use ($now, &$deleted) {
		$logger = Environment::get()->get_logger();
		$name = BASE_DIRECTORY . str_replace("/", DIRECTORY_SEPARATOR, 
			"/" . array_key_or_exception($directory, "attributes", "NAME"));
		$time = (int)array_key_or_exception($directory, "attributes", "TIME");
		$empty = (int)array_key_or_value($directory, ["attributes", "TIME-EMPTY"], $time);
		
		$logger->info("Iterating directory $name [time = $time, empty = $empty]");
		
		$all_files = get_all_files_in_directory($name);
		$logger->info(count($all_files) . " files found.");
		array_walk($all_files, 
			function ($file) use ($name, $time, $empty, $logger, $now, &$deleted) {
				$file_name = $name . DIRECTORY_SEPARATOR . $file;
				$modification_time = filemtime($file_name);
				
				if ($modification_time === false) {
					$logger->error("Can't get modification time of $file_name");
					return;
				}				

				$time_diff = $now - $modification_time;
				
				if ($time_diff > $time * SECONDS_PER_HOUR) {
					$logger->trace("Above normal threshold; marking for deletion.");
				} else if ($time_diff > $empty * SECONDS_PER_HOUR && filesize($file_name) === 0) {
					$logger->trace("Above empty threshold; marking for deletion.");
				} else {
					return;
				}
				
				$logger->info("Deleting $file_name...");
				
				if (unlink($file_name)) {
					$deleted++;
				} else {
					$logger->error("Unable to delete $file_name");
				}
			});
	});

$logger->info("$deleted deleted.");