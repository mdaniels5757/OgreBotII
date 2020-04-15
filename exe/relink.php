<?php
//--type=regex "--from=Kit right arm blacklowerthin.png" "--search=/_ra\d?\s*\=\s*_blacklowerthin\b/" --replacement="$0_2"
//--type=regex "--from=Kit right arm blacklowerthin.png" "--search=/\|\s*arm\s*\|\s*_blacklowerthin\b/" --replacement="$0_2"
require_once __DIR__ . "/../base/bootstrap.php";
global $env, $logger, $wiki_interface;

$argv = $env->load_command_line_args();
$type = find_command_line_arg($argv, "type", false, true, "auto");
$errlog = find_command_line_arg($argv, "errlog", false, true) !== null;
$test_conflicts = find_command_line_arg($argv, "ignore-conflicts", false, true) === null;
$logger->debug("Starting up with relink type $type");


$en = $wiki_interface->new_wiki("MDanielsBot");
$co = $wiki_interface->new_wiki("MDanielsBotCommons");

switch ($type) {
	case "auto":
		$relink = new Auto_Relink($en, $co);
		$relink->set_write_warnings($errlog);

		$logger->info("Error logs are " . ($errlog ? "ON" : "OFF") . ".");
		break;
	case "pages":
		$relink = new Page_Relink($en, $co);
		$relink->set_pages_from_unparsed_command_line($argv);
		break;
	case "regex":
		$relink = new Regex_Relink($en, $co, $argv);
		break;
	default:
		throw new IllegalArgumentException("Unreconized type: $type");
}
$relink->set_test_conflicts($test_conflicts);
$relink->run();
