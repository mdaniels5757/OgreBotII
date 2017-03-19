<?php
require_once __DIR__ . "/../base/bootstrap.php";

$argv = $env->load_command_line_args(true);
$lcache = find_command_line_arg($argv, "lcache");

FCC_Database_Serializer::run($lcache);
