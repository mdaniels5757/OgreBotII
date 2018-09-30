<?php
require_once __DIR__ . "/../base/bootstrap.php";
global $env, $logger;

ini_set('xdebug.var_display_max_data', -1);

$args = $env->load_command_line_args(true);
$reload = find_command_line_arg($args, "reload");
$check_only = find_command_line_arg($args, "check") !== null;
$tests = [
		/* shared repository */
		["!-2014-wschowa-ul-daszynskiego-15-abri.jpg", "en", "wikipedia", false],
	
		/* empty */
		["( C ) Leonora Carrington - La artista viaje de Incognito (The artist traveling incognito) (1949) - Detail (6952303224).jpg", "de", "wikipedia", false],
			
		/* de */
		["072 Bip.jpg", "de", "wikipedia", true],
		
		/* de wikinews */
		["Digital Radio Logo.svg", "de", "wikinews", false],
		
		/* en */
		["MS DOT Seal.svg", "en", "wikipedia", false], 
		["Shore Capital Partners File-Shore Capital Partners.png", "en", "wikipedia", false],
		["PM Magnete.jpg", "en", "wikipedia", true],
		["PmodeOscillation.jpg", "en", "wikipedia", true],
		
		/* en redirect */
		["H03921eef61.jpg", "en", "wikipedia", true],
		
		/* el */
		["14 FESTIVAL LAIKOU XOROY SOULI PATRON.jpg", "el", "wikipedia", false],
		["14 FESTIVAL LAIKOU XOROY SOULI PATRON.jpg", "el", "wikipedia", true],
		
		/* en */
		["Charles M. Stokes.jpg", "en", "wikipedia", true],
		
		/* fi */
		["1165386579-00.jpg", "fi", "wikipedia", true],
		
		/* fr */
		["1080 Snowboarding Logo.png", "fr", "wikipedia", true],
		
		/* he */
		["HOT Anime.svg", "he", "wikipedia", true],
		
		/* hr */
		["SNJEG 6.jpg", "he", "wikipedia", true],
		
		/* zh */
		["HK2778-Champion-Real-Estate.jpg", "zh", "wikipedia", false]
		
];
if ("$reload" == "") {
	$logger->all(json_encode($tests, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
	return;
}

if ($reload !== "all") {
	$tests = array_intersect_key($tests, array_flip(preg_split("/\s*\,\s*/", $reload)));
}

foreach ($tests as $i => $test) {
	$logger->info("Test #$i");
	if (!$check_only) {
		$url = "http://localhost/local-working/commonshelper/index.php?language=$test[1]&project=$test[2]" .
			 "&ignorewarnings=1&doit=Get+text&image=" . urlencode($test[0]);
		if ($test[3]) {
			$url .= "&remove_categories=1";
		}
		$logger->debug("url: $url");
	}
	$logger->debug(
		CommonsHelper_Factory::get_service()->get_upload_text($test[0], $test[1], $test[2], 
			$test[3], false));
	if (!$check_only) {
		$logger->debug(file_get_contents($url));
		
		CommonsHelper_Compare::compare("old", "new");
	}
}

?>