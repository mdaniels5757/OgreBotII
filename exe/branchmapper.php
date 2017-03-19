<?php
require_once __DIR__ . "/../base/bootstrap.php";

$argv = $env->load_command_line_args(true);

$fdic_numbers = find_command_line_arg($argv, "fdic");
$color = find_command_line_arg($argv, "color");
$size = find_command_line_arg($argv, "size");
$directory = find_command_line_arg($argv, "directory");

if ($fdic_numbers !== null) {
	$fdic_numbers = preg_split("/\s*,\s*/", $fdic_numbers);
	$logger->info("Loading FDIC_Map_Creator");
	$branch_mapper = new FDIC_Map_Creator($fdic_numbers, "0");
} 

if (@$branch_mapper === null) {
	die("Arguments not provided...");
}

$logger->info("loading...");
if ($color) {
	$branch_mapper->set_color($color);
}
if ($size === null) {
	$size = 1;
}
if ($directory === null) {
	$directory = TMP_DIRECTORY;
}

$map_names = str_replace("-0", "", $branch_mapper->run());

$png_svg_service = new Png_Svg_Service("0");
$png_svg_service->zip($map_names, $size);