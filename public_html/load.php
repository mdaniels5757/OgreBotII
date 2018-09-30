<?php
$start = microtime();
require_once __DIR__ . "/../base/bootstrap.php";
global $logger;

$ws = new Web_Script(preg_split("/\\|/", @$_REQUEST[Web_Script::WEB_ARGUMENT]));
$about_one_year = gmdate('D, d M Y H:i:s \G\M\T', time() + SECONDS_PER_DAY * 365);


switch ($ws->get_type()) {
	case "css":
		header("content-type:text/css; charset=utf-8");
		break;
	case "js":
		header("content-type:text/javascript; charset=utf-8");		
}
header("Cache-Control: max-age=2592000");
header("Expires: $about_one_year");


echo $ws->get_text();

$logger->debug("Load time in microseconds: " . (microtime() - $start));