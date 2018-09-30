<?php
require_once "constants.php";
require_once "Array_Utils.php";
require_once "Classloader.php";

if (defined('HHVM_VERSION')) {
	require_once "hhvm.php";
}

require_once "String_Utils.php";
require_once "Wiki_String_Utils.php";
require_once "Local_Io.php";
require_once "Remote_Io.php";
require_once __DIR__ . "/../REL1_0/Init.php";

$env = Environment::init();
/* for autosuggest not picking up globals in PDT */
if (false) {
	$argv = [];
	$constants = [];
	$logger = $env->get_logger ();
	$string_utils = new String_Utils ();
	$validator = $env->get_validator ();
	$wiki_interface = $env->get_wiki_interface ();
}