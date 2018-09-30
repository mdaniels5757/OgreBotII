<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $env, $logger;

//this script takes quite a long time to run
ini_set("max_execution_time", 20 * SECONDS_PER_MINUTE);

$error = null;
try {
	list($project_name, $license_cache_refresh, $limit, $bypass) = (new Array_Parameter_Extractor(
		["project" => true, "nocache" => false, "limit" => false, "bypass" => false]))->extract(
		$env->get_request_args());

	$now_commons_list = Now_Commons_List::get_instance_by_key($project_name);
	

	if ($license_cache_refresh) {
		$logger->info("Setting license cache time to 0.");
		$now_commons_list->set_license_cache_time(0);
	}
	
	if ($limit) {
		$now_commons_list->set_limit($limit);
	}
	
	if (!$bypass) {
		$now_commons_list->set_min_time_between_runs(10 * SECONDS_PER_MINUTE);
	}
	
	try {
		$now_commons_list->generate();
		$file_name = substr(strrchr($now_commons_list->get_base_output_path(), DIRECTORY_SEPARATOR), 
			1);
	} catch (Now_Commons_List_Minimum_Age_Exception $e) {
		$error = $e->getMessage();
	} catch (Exception $e) {
		ogrebotMail($e);
		$error = "Uh oh! An known error has occured. Please try again later. ";
	}
} catch (ArrayIndexNotFoundException $e) {
	$error = "Something went wrong. :( Did you omit or provide a bad project parameter by chance?";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head></head>
<body><?php
if ($error) {
	?><?= sanitize($error) ?><?php
} else {
	?><p>Success!</p>
	<p>Access the galleries <a href="<?= $file_name ?>.htm">here</a>.</p><?php
}?></body>
</html>
