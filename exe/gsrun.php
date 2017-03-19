<?php
require_once __DIR__ . "/../base/bootstrap.php";

$argv = $env->load_command_line_args();

$setters = !in_array($argv, "--no-setters");
$camel_case = in_array($argv, "--camel-case");

$text = file_get_contents_ensure(ARTIFACTS_DIRECTORY . "/filestuff.txt");

echo Generate_Getters_Setters::generate_from_text($text, $setters, $camel_case, true);