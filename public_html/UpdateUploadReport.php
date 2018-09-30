<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $env, $wiki_interface;

//this script takes quite a long time to run
ini_set("max_execution_time", 20 * SECONDS_PER_MINUTE);

$POST = $env->get_request_args(true);
$start = @$POST['start'] . "000000";
$unix_time = strtotime($start);
$error = "";
if (!preg_match("/^20\d{12}$/", $start)) {
	$error = "No start date specified or date format not recognized.";
} else if ($unix_time > strtotime("+20 minutes")) {
	$error = "Gallery cannot be in the future, and must have been live for 20 minutes.";
} else {
	$end = substr($start, 0, 8) . "235959";
	
	$co = $wiki_interface->new_wiki("OgreBotCommons");
	$upload_report_writer = new UploadReportWriter($co, $start, $end);
	
	try {
		$upload_report_writer->loadAndWrite(true);
	} catch (Exception $e) {
		ogrebotMail($e);
		$error = "Unknown error attempting to update gallery. The bot owner has been notified.";
	}
	$galleryPageName = $upload_report_writer->getGalleryPageName();
	$pageLink = wikilink($galleryPageName, "commons.wikimedia", true, false, "Return to the gallery");
	$historyLink = wikilink(
		$galleryPageName, 
		"commons.wikimedia", 
		true, 
		false, 
		"view history", 
		null, 
		false, 
		"history");
}


?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head></head>
<body><?php
if ($error) {
	echo $error;
} else {
	echo "Success!";
}

if (@$pageLink) {
	?> <?= $pageLink ?> or <?= $historyLink ?>.<?php
}
?></body>
</html>
