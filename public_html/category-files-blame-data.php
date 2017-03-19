<?php
require_once __DIR__ . "/../base/bootstrap.php";

$log_directory = BASE_DIRECTORY . "/" .
	 array_key_or_exception($constants, 'category_files.output_path') . "/";

$all_files_in_directory = get_all_files_in_directory($log_directory);
sort($all_files_in_directory, SORT_STRING);

// get all gallery names
$all_galleries = Category_Files_Log_Entry::get_all_gallery_names(
		str_prepend($all_files_in_directory, $log_directory));
sort($all_galleries, SORT_FLAG_CASE | SORT_STRING);

$dates = preg_replace("/^(\d{4})(\d{2})(\d{2})\.log$/", "$1-$2-$3", $all_files_in_directory);

$post = $env->get_request_args();

$postback_date = @$post['date'];
$postback_gallery = @$post['gallery'];
if ($postback_gallery) {
	$postback_gallery = mb_trim($postback_gallery);
}

$project_data = new ProjectData("wikimedia", "commons", false);

/* @var $entries Category_Files_Log_Entry[] */
$entries = array();
if ($postback_date) {
	
	$postback_date_numerics = preg_replace("/\D/", "", $postback_date);
	Category_Files_Log_Entry::set_prune_to_gallery($postback_gallery);
	try {
		$entries = Category_Files_Log_Entry::parse_file("$log_directory$postback_date_numerics.log");
	} catch (CantOpenFileException $e) {
		die("Log is stale.");
	}
}

header('content-type: application/json; charset=utf-8');

$data = [
		"dates" => $dates,
		"galleries" => $all_galleries,
		"entries" => $entries
];
echo json_encode($data, Environment::prop("environment", "jsonencode.options"));