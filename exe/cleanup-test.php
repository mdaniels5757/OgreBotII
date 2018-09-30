<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $argv, $logger;

$persist_directory = MASS_CLEANUP_DIRECTORY;

function find_first_test_index() {
	global $persist_directory;
	$files = map_array_function_keys(scandir($persist_directory), function ($file_name) {
		if (preg_match("/^(\d+)\.in$/", $file_name, $match)) {
			return [$match[1], ""];
		}
	});
	
	for ($i = 1; isset($files[$i]); $i++);
	
	return $i;
}

$starttime = find_command_line_arg($argv, "date");
if ($starttime === null) {
	$starttime = date('Ymd235959', time()-SECONDS_PER_DAY-date("Z"));
}

$persist = find_command_line_arg($argv, "persist") !== null;

$text = file_get_contents_ensure(ARTIFACTS_DIRECTORY . "/filestuff.txt");

$ci = (new Cleanup_Base())->super_cleanup($text, $starttime, true);
$logger->all("\n\n" . $ci->get_text());

if (in_array(Cleanup_Shared::DUPLICATE_AUTHOR, $ci->get_warnings())){
	$logger->error("Bot returned duplicate author: ". $ci->get_duplicate_authors()[0]);
}

$logger->all("Major change: " . $ci->get_significant_changes());
$logger->all("Problems: " . print_r($ci->get_warnings(), true));


if ($persist) {
	$index = find_first_test_index();
	
	$logger->info("Persisting to $persist_directory/$index.(in|out)");
	
	file_put_contents_ensure("$persist_directory/$index.in", $text);
	file_put_contents_ensure("$persist_directory/$index.out", $ci->get_text());
}
