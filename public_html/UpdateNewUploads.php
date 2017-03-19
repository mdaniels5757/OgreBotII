<?php
require_once __DIR__ . "/../base/bootstrap.php";

// this script takes quite a long time to run
ini_set("max_execution_time", 20 * SECONDS_PER_MINUTE);

define("MIN_INTERVAL_BETWEEN_REQUESTS", 70 * 60 /* 30 minutes */);

$POST = $env->get_request_args(true);
$project = @$POST['project'];
$start = @$POST['start'];
$minTimeInterval = 30 * 60; // 30 minutes

?>


<!DOCTYPE html>
<html>
<head></head>
<?php
if ($start === null) {
	echo "<BODY>No start date specified.</BODY>";
} else if ($project != "commons.wikimedia") {
	echo "<BODY>Invalid project</BODY>";
} else {
	try {
		$end = (new NewUploadsRangeCalculator())->get_end($start);
	} catch (IllegalArgumentException $e) {
		$errorMessage = "The bot can't understand the start time of your request. Please contact " .
			 "the bot owner if believe this is in error.";
	}
	if ($errorMessage !== null) {
		echo "<BODY>$errorMessage</BODY>";
	} else {
		$wrapper = new NewUploadsRunnerWrapper();
		$pageLink = wikilink($wrapper->getPageNameByTimestamp($start), $project, true, false, 
			"Return to the gallery") . " or " . wikilink($wrapper->getPageNameByTimestamp($start), 
			$project, true, false, "view history", null, false, "history");
		try {
			$wrapper->run($start, $end, false);
			$message = "Success!";
		} catch (EditConflictException $e) {
			$logger->warn("Edit conflict.");
			$logger->warn($e);
			$retryLink = "";
			$link = "$_SERVER[SCRIPT_NAME]?project=$project&start=$start";
			$message = "Unable to update due to too many edit conflicts. Please wait a few minutes and " .
				 "<a href=\"$link\" style=\"text-decoration:none;\">Click here</a> to try again.<br/><br/>";
			$message .= "Here is the text if you want to copy and paste it onto the page:<br/>\n";
			$message .= "<textarea cols=\"80\" rows=\"25\" dir=\"ltr\">";
			$message .= str_replace("<", "&lt;", str_replace("&", "&amp;", $e->getOriginalText()));
			$message .= "</textarea><br/>";
		} catch (Exception $e) {
			ogrebotMail($e);
			$message = "Unknown error. The bot owner has been notified. Please try again later.";
		}
		
		echo "<BODY>$message $pageLink.</BODY>";
	}
}
?>
</html>
