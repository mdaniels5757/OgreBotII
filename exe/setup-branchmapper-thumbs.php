<?php
require_once __DIR__. "/../base/bootstrap.php";

$argv = $env->load_command_line_args(true);
$map = find_command_line_arg($argv, "map");
$svgs = Latitude_Longitude_Svg::load();
if ($map) {
	$maps = preg_split("/\s*\,\s*/", $map);
	$svgs = array_filter($svgs, function (Latitude_Longitude_Svg $svg) use($maps) {
		return in_array($svg->get_name(), $maps);
	});
}

$logger->info("Setting up " . count($svgs) . " maps");

$png_creator = (new Png_Creator_Factory())->get_png_creator();
array_walk($svgs, 
	function (Latitude_Longitude_Svg $svg) use($png_creator) {
		global $logger;
		
		$logger->info($svg->get_name());
		$png_blob = $png_creator->get_thumb($svg->get_text(), 250);
		
		file_put_contents_ensure(
			BASE_DIRECTORY . "/public_html/images/" . $svg->get_name() . "-thumb.png", $png_blob);
	});
