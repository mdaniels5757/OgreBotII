<?php
require_once __DIR__ . "/../base/bootstrap.php";

try {
	$argv = $env->load_command_line_args();
	$update = find_command_line_arg($argv, "update") !== null;
	@list($start, $end) = array_slice($argv, 1);
	if (!$end) {
		$end = substr($start, 0, 8) . "235959";
	}
	
	$co = $wiki_interface->new_wiki("OgreBotCommons");
	$upload_report_writer = new UploadReportWriter($co, $start, $end);
	$upload_report_writer->loadAndWrite($update);
} catch (Exception $e) {
	ogrebotMail($e);
	throw $e;
}

?>
