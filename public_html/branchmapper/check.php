<?php
define("LOGGER_NAME", "branchmapper-check");
require_once __DIR__ . "/../../base/bootstrap.php";

try {
	$which = (new Web_Branch_Map_Factory())->create()->which();
	
	$message = "$which->count points mapped.\n";
	if ($which->warnings) {
		$message .= "Warnings:\n* " . join("\n* ", $which->warnings);
	} else {
		$message .= "No warnings.";
	}
	if ($which->messages) {
		$message .= "\n\n" . join("\n", $which->messages);
	}
	$json = json_encode(["message" => $message]);
} catch (Exception $e) {
	$json = json_encode(
		[
			"error" => "An error has occurred while retrieving your data. Please try again later or contact the bot operator if it persists."]);
}

$logger->debug("JSON: $json");

header('content-type: application/json; charset=utf-8');
echo $json;