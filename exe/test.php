<?php
require_once __DIR__ . "/../base/bootstrap.php";

$first = parseMediawikiTimestampRaw("20160501013000");
$last =  parseMediawikiTimestampRaw("20160702180000");


$wrapper = new NewUploadsRunnerWrapper();
$range_calculator = new NewUploadsRangeCalculator();
foreach (range($first, $last, 3600 * 1.5) as $timestamp) {
	$start = unixTimestampToMediawikiTimestamp($timestamp);
	if (!preg_match("/^2016\d{7}[03]00$/", $start)) {
		throw new Exception("Bad timestamp: $start");
	}
	
	try {
		$wrapper->run($start, $range_calculator->get_end($start), true);
	} catch (Exception $e) {
		ogrebotMail($e);
	}
	
}

?>
