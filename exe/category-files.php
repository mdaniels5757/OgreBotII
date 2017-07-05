<?php
require_once __DIR__ . "/../base/bootstrap.php";

/* parse command line variables */
$argv = $env->load_command_line_args();
//$argv[1] = 20160711220000;
$file_override = find_command_line_arg($argv, "FILE");

//$file_override_no_context = ARTIFACTS_DIRECTORY . "/filestuff.txt";
//$wiki_interface->set_live_edits(false);

(new Category_Files_Outputter(new Project_Data("commons.wikimedia"), 
	$file_override ? __DIR__ . '/' . $file_override : null))->run(@$argv[1], @$argv[2]);


?>
