<?php
require_once __DIR__ . "/../base/bootstrap.php";

/* parse command line variables */
$argv = $env->load_command_line_args();
//$argv = array("", "--new=0", "--start=20150426000000", "--end=20150426055959");
$new = boolean_or_exception(find_command_line_arg($argv, "new", true, false));
$start = find_command_line_arg($argv, "start", true, false);
$end = (new NewUploadsRangeCalculator())->get_end($start);

/* includes */
$wrapper = new NewUploadsRunnerWrapper();

try {
	$wrapper->run($start, $end, $new);
} catch (Exception $e) {
	ogrebotMail($e);
}

?>
