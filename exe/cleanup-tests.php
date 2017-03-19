<?php
require_once __DIR__ . "/../base/bootstrap.php";

$argv = array_slice($env->load_command_line_args(), 1);
$repetitions = find_command_line_arg($argv, "repeat");
$show_output = find_command_line_arg($argv, "output") !== null;
$show_warnings = find_command_line_arg($argv, "show-warnings") !== null;
if ($repetitions === null) {
	$repetitions = 1;
}

$cl_files = array();

$scandir = MASS_CLEANUP_DIRECTORY;
if ($argv) {
	$logger->debug("Loading files from command line.");
	$files_in_dir_sorted = $argv;
} else {
	$logger->debug("Loading all tests from directory.");
	$files_in_dir = scandir($scandir);
	asort($files_in_dir);
	$files_in_dir_sorted = array();
	foreach ($files_in_dir as $filename) {
		if(substr($filename, strlen($filename)-3)===".in") {
			$base = substr($filename, 0, strlen($filename)-3);
			$files_in_dir_sorted[]=$base;
		}
	}
}
$logger->debug(count($files_in_dir_sorted)." files found.");

asort($files_in_dir_sorted);
$starttime = date('Ymd235959', time()-SECONDS_PER_DAY);
$logger_level = $logger->getDebugLevel();
$cleanup_base = new Cleanup_Base();
foreach ($files_in_dir_sorted as $base) {
	$inname = $scandir. DIRECTORY_SEPARATOR . "$base.in";
	$outname = $scandir. DIRECTORY_SEPARATOR . "$base.out";
	
	$intext = file_get_contents_ensure($inname);
	$outtext = file_get_contents_ensure($outname);

	$pass_fail = "$base: ";
	for ($i = 0; $i < $repetitions; $i++) {
		if (!$show_warnings) {
			$logger->setDebugLevel(Level::ERROR);
		}
		$ci = $cleanup_base->super_cleanup($intext, $starttime, true);
		if (!$show_warnings) {
			$logger->setDebugLevel($logger_level);
		}
	}
	$trim_new = mb_trim(str_replace("\r\n", "\n", $ci->get_text()));
	$trim_old = mb_trim(str_replace("\r\n", "\n", $outtext));
	if ($trim_new === $trim_old) {
		$pass_fail.= "PASS";
	} else {
		$pass_fail.= "FAIL. Diff below:\n";
		$logger->info($pass_fail);
		$outtmpname = $scandir. DIRECTORY_SEPARATOR . "$base.tmp";
		$outtmpfile = file_put_contents_ensure($outtmpname, $ci->get_text());
		$output = "";
		if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
			exec("fc \"$outname\" \"$outtmpname\"", $output, $returned);
		} else {
			exec("diff \"$outname\" \"$outtmpname\" -y --suppress-common-lines", $output, $returned);
		}
		unlink($outtmpname);

		foreach ($output as $line) {
			$pass_fail.= "  $line\n";
		}
		$pass_fail.="\n\n";
	}
	if ($show_output) {
		$logger->info($ci->get_text());
		if (in_array(Cleanup_Shared::DUPLICATE_AUTHOR, $ci->get_warnings())) {
			$logger->info("Bot returned duplicate author: " . $ci->get_duplicate_authors()[0]);
		}
		
		$logger->info("Major change: " . $ci->get_significant_changes());
		$logger->info("Problems: " . print_r($ci->get_warnings(), true));
		$logger->info($output);
	}
	$logger->info($pass_fail);
}
