<?php
require_once "constants.php";
require_once "array_utils.php";
require_once "Classloader.php";

if (defined('HHVM_VERSION')) {
	require_once "hhvm.php";
}

require_once "String_Utils.php";
require_once "wiki_string_utils.php";
require_once "Local_Io.php";
require_once "remote_io.php";
require_once __DIR__ . "/../REL1_0/Init.php";

$env = Environment::init();
/* for autosuggest not picking up globals in PDT */
if (false) {
   $logger = $env->get_logger();
   $validator = $env->get_validator();
   $wiki_interface = $env->get_wiki_interface();
}