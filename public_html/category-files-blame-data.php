<?php
require_once __DIR__ . "/../base/bootstrap.php";

$log_directory = BASE_DIRECTORY . "/" .
	 array_key_or_exception($constants, 'category_files.output_path') . "/";

$post = $env->get_request_args();

$postback_date_numerics = preg_replace("/\D/", "", $post['date']);
Category_Files_Log_Entry::set_prune_to_gallery(@$post['gallery']);
try {
	$entries = Category_Files_Log_Entry::parse_file("$log_directory$postback_date_numerics.log");
} catch (CantOpenFileException $e) {
	die("Log is stale.");
}

header('content-type: application/json; charset=utf-8');

echo json_encode($entries, Environment::prop("environment", "jsonencode.options"));