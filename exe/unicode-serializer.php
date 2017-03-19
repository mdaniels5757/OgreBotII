<?php
require_once __DIR__ . "/../base/bootstrap.php";

$http_cache_reader = new Http_Cache_Reader();
$http_cache_reader->set_max_cache_time(SECONDS_PER_DAY);
$points = array_map(function($line) {
	$data = explode(";", $line);
	
	switch ($data[4]) {
		case "L":
			$type = "ltr";
			break;
		case "R":
		case "AL":
		case "AN":
			$type = "rtl";
			break;
		case "LRE":
		case "RLE":
		case "PDF":
		case "LRO":
		case "RLO":
			$type = "dir";
			break;
		default:
			$type = "neutral";
	}
	
	$is_range_start = preg_match("/^\<.+\, First\>$/", $data[1]);
	
	return [hexdec($data[0]), $is_range_start, $type];
	 
}, explode("\n", trim($http_cache_reader->get_and_store_url(Environment::prop("constants", "unicode.url")))));

$range = null;
$points = array_values($points);//$points[1548]
$out = [];
foreach ($points as $i => $point) {
	if ($range) {
		$out[count($out) - 1][1] = $point[0];
		$range = null;
	} else {
		if ($i !== 0 && $points[$i - 1][0] !== $points[$i][0] - 1) {
			$out[] = [$points[$i - 1][0] + 1, $points[$i][0] - 1, "illegal"];
		}
		$range = $point[1] ? $point : null;
		$out[] = $point;
	}
}

$out = array_values($out);
$properties = [];
$iterator = new ArrayIterator($out);
for($anchor = $iterator->current(); $iterator->valid(); $iterator->next()) {
	$next = $iterator->current();
	if ($anchor[2] !== $next[2]) {
		$properties[]= "points []= \"$anchor[0];$anchor[2]\"";
		$anchor = $next;
	}
}
$properties[]= "points []= \"$anchor[0];$anchor[2]\"";
file_put_contents_ensure(BASE_DIRECTORY . "/properties/unicode.properties" , implode("\n", $properties));