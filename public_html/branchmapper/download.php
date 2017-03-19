<?php
define("LOGGER_NAME", "branchmapper-download");
require_once __DIR__ . "/../../base/bootstrap.php";

$branchmapper = null;
$data_blob = null;
try {
	$request_key = get_request_key();
	$POST = $env->get_request_args();
	$branchmapper = (new Web_Branch_Map_Factory())->get_stored();
	if ($branchmapper) {
		$map = array_key_or_exception($POST, "map");
		$color = strtolower(trim(array_key_or_exception($POST, "color")));
		$radius = array_key_or_exception($POST, "radius");
		
		$svg = Latitude_Longitude_Svg::load()[$map];
		
		$validator->validate_arg($color, "valid XML color");
		$branchmapper->set_color($color);
		$branchmapper->set_fill_radius($radius);
		
		$data_blob = $branchmapper->run($svg);
	}
} catch (Exception $e) {
	ogrebotMail($e);
}

if ($data_blob) {
	header("Content-Disposition: attachment; filename=$map.svg");
	header("Set-Cookie: branchmapper-$request_key-$map=true; path=" . $environment["cookie.path"]);
	echo $data_blob;
} else {
	?>
<!DOCTYPE HTML>
<html>
<head>
<title>Error</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />


<body>
	<p>Error: unable to download.</p>
</body>
</html><?php
}