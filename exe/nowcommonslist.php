<?php
require_once __DIR__ . "/../base/bootstrap.php";

global $env, $logger;

$argv = $env->load_command_line_args();
$project_name = find_command_line_arg($argv, "project", false, false, "en.wikipedia");
$license_cache_refresh = find_command_line_arg($argv, "nocache", false, false) !== null;
$limit = find_command_line_arg($argv, "limit");

$now_commons_list = Now_Commons_List::get_instance_by_key($project_name);
if ($license_cache_refresh) {
	$logger->info("Setting license cache time to 0.");
	$now_commons_list->set_license_cache_time(0);
}

if ($limit) {
	$now_commons_list->set_limit($limit);
}

$now_commons_list->generate();