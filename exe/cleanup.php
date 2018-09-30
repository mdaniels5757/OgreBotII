<?php

require_once __DIR__ . "/../base/bootstrap.php";
global $argv, $logger, $wiki_interface;

$starttime = array_key_exists(1, $argv)?($argv[1]):
	date('Ymd235959', time()-SECONDS_PER_DAY*2-date("Z"));
$endtime = array_key_exists(2, $argv)?($argv[2]):
	date('Ymd000000', strtotime($starttime));

$logger->debug("Querying recent changes (Step 1) - $endtime to $starttime");
try {
	$co2 = $wiki_interface->new_wiki( "OgreBot_2Commons" );
	$auto_editor = new Cleanup_Auto_Editor($co2, "new upload");
	
	$files = $wiki_interface->new_files($co2, $starttime, $endtime);
	
	$logger->debug(count($files)." files found. Querying page content (Step 2)");
	$pagecontent = $wiki_interface->new_query_pages(
			$co2, 
			array_keys($files), 
			"revisions|categoriesnohidden",
			array("rvprop" => "content|timestamp")
	);
} catch (Exception $e) {
	ogrebotMail($e);
	throw $e;
}

$logger->debug("Analyzing text and performing edits (Step 3)");
$timelapse=0;
$i=0;

foreach($files as $title => $fileData) {
	if (!array_key_exists($title, $pagecontent)) {
		$logger->error("Array key doesn't exist. Was it moved/deleted during the query?");
		$logger->error($title);
		continue;
	}
	
	$thistime = time();
	$i++;
	if ($thistime-$timelapse>4) {
		$logger->debug((((int)($i*1000/count($pagecontent)))/10)."% complete "
			."($i processed)");
		$timelapse=time();
	}

	//mediawiki bug or page deleted
	$text = _array_key_or_value($pagecontent, null, [$title, 'revisions', 0, '*']);
	$date = _array_key_or_value($pagecontent, null, [$title, 'revisions', 0, "timestamp"]);
	$categories = _array_key_or_value($pagecontent, null, [$title, 'categories']);
	if($text !== null && $date !==null) {
		$time_millis = strtotime($date);
		do {
			$retry = false;
			try {
				$auto_editor->process($title, $text, $categories, $time_millis);
			} catch (EditConflictException $e) {
				$logger->warn($e);
				$retry = true;
			} catch (Exception $e) {
				ogrebotMail($e);
			}
		} while ($retry);
	}
}

$logger->debug("Cleanup complete.");

