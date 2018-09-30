<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $env, $logger;

$argv = $env->load_command_line_args(true);
$download = find_command_line_arg($argv, "download");
$count = find_command_line_arg($argv, "count");
$clean = find_command_line_arg($argv, "clean") !== null;
$clean_dir = find_command_line_arg($argv, "clean-dir") !== null;
$lazy = find_command_line_arg($argv, "lazy") !== null;
$rebase = find_command_line_arg($argv, "rebase") !== null;
$test = find_command_line_arg($argv, "test") !== null;

$cleanup_type = $lazy ? Cleanup_Mass_Tester::NO_REFRESH_TMP : ($clean ? Cleanup_Mass_Tester::CLEAN : Cleanup_Mass_Tester::REFRESH_TEMP);

$mass_tester = new Cleanup_Mass_Tester(MASS_CLEANUP_DIRECTORY . DIRECTORY_SEPARATOR . "tests", 
	$cleanup_type);

if ($download && intval($count)) {
	if (preg_match("/^20\d{6}(\,20\d{6})*$/", $download)) {
		$mass_tester->download(explode(",", $download), $count);
	} else {
		$logger->warn("Unrecognized date format: $download_match");
	}
}
if ($rebase) {
	$mass_tester->create_base();
}

if ($test) {
	$mass_tester->test();
}

$mass_tester->save();

if ($clean_dir) {
	$mass_tester->clean_dir();
}