<?php 

require_once __DIR__ . "/../base/bootstrap.php";
global $env, $logger;

class Do_Cleanup_Json_Object_Instance {
	/**
	 * 
	 * @var string
	 */
	public $file;
	
	/**
	 * 
	 * @var bool
	 */
	public $changed;
	
	/**
	 * 
	 * @var float
	 */
	public $percent;
	
}

class Do_Cleanup_Json_Object {
	
	/**
	 * 
	 * @var bool
	 */
	public $startup;
	
	/**
	 * 
	 * @var int
	 */
	public $count;
	
	/**
	 * 
	 * @var Do_Cleanup_Json_Object_Instance[]
	 */
	public $lines = [];
	
	
	/**
	 * 
	 * @var int
	 */
	public $lineNum;
	
	/**
	 * 
	 * @var bool
	 */
	public $complete;
	
	/**
	 *
	 * @var bool
	 */
	public $error;
	
}

$POST = $env->get_request_args();

$error = false;
$json_object = new Do_Cleanup_Json_Object();
try {
	$key = array_key_or_exception($POST, "request_key");
	$start_line = array_key_or_exception($POST, "line");
	$start = time();
	$file_name = replace_named_variables(DO_CLEANUP_FILE, ["request_key" => $key]);

	$logger->debug();
	$logger->debug("\$start_line = $start_line");
	$logger->debug("\$file_name = $file_name");
	
	//in case the process has yet to start properly...
	while(!file_exists($file_name)) {
		if (time() - $start > 60) {
			throw new IllegalStateException(
				"Process didn't start within 60 seconds. Filename: $file_name");
		}
		$logger->debug("File does not exist yet. Sleeping...");
		sleep(1);
	}
	$logger->debug("File found.");
	
	$handle = fopen($file_name, "r");
	if (!$handle) {
		throw new CantOpenFileException($file_name);
	}
	
	
	$json_object->lineNum = 0;
	while (($line = fgets($handle)) !== false) {
		if ($json_object->lineNum >= $start_line) {
			$line = mb_trim($line);
			if ($logger->isTraceEnabled()) {
				$logger->trace($line);
				if ($logger->isInsaneEnabled()) {
					$logger->insane(unpack("C*", $line));
				}
			}
			if ($line === "startup") {
				$json_object->startup = true;
				$logger->debug("Startup.");
			} else if (preg_match("/^\|started (\d+)$/", $line, $match)) {
				$json_object->count = (int)$match[1];
				$logger->debug("Started, $json_object->count lines found.");
			} else if ($line === "complete") {
				$json_object->complete = true;
				$logger->debug("Complete.");
			} else if ($line === "error") {
				$json_object->error = true;
				$logger->error("Error :(");
			} else if (preg_match("/^(.*?)\|(.+?)\|([\d\.]+)$/", $line, $match)) {
				$instance = new Do_Cleanup_Json_Object_Instance();
				$instance->changed = $match[1] ? true : false;
				$instance->file = $match[2];
				$instance->percent = (float)$match[3];
				$json_object->lines[] = $instance;
			} else {
				// read in the middle of a write??
				$logger->error("Skipped(!)");
				$logger->debug($line);
				break;
			}
		}
		$json_object->lineNum++;
	}
	
	$logger->debug("Handle closed.");
	fclose($handle);
	
	if ($json_object->complete || $json_object->error) {
		$success = unlink($file_name);
		if ($success) {
			$logger->debug("$file_name deleted.");
		} else {
			$logger->error("\$file_name NOT deleted.");
		}
	}
	
} catch (Exception $e) {
	$json_object->error = true;
}

header('content-type: application/json; charset=utf-8');
$json_encode = json_encode($json_object);
$logger->debug($json_encode);	
echo $json_encode;


?>