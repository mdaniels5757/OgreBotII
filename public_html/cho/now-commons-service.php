<?php
require_once __DIR__ . "/../../base/bootstrap.php";

global $env;

/**
 * 
 * @author magog
 *
 */
class Now_Commons_Service {
	
	const NOW_COMMONS_PREFIXES = ["nowcommons.tag", "nowcommons.deletetext", 
		"nowcommons.editsummary"];

	const NO_ESCAPE_PREFIX = "noEscape/";

	/**
	 * @return string[][]
	 */
	public function get_sorted_project_names() {
		$sorted_projects = [];
		foreach (get_all_project_names() as $name) {
			list($sub, $project) = explode(".", $name, 2);
			if (!isset($sorted_projects[$project])) {
				$sorted_projects[$project] = [];
			}
			$sorted_projects[$project][] = $sub;
		}
		return $sorted_projects;
	}
	
	/**
	 * @return string[][]
	 */
	public function get_codes() {
		$bucket = [];		
		foreach (load_property_file("oldver_messages") as $key => $val) {
			foreach (self::NOW_COMMONS_PREFIXES as $prefix) {
				$prefix_dot = "$prefix.";
				if (str_starts_with($key, $prefix_dot)) {
					$key = substr($key, strlen($prefix_dot));
					
					if (str_starts_with($val, self::NO_ESCAPE_PREFIX)) {
						$val = substr($val, strlen(self::NO_ESCAPE_PREFIX));
					}
					if (!@$bucket[$key]) {
						$bucket[$key] = [];
					}
					$bucket[$key][$prefix] = $val;
				}
			}
		}
		
		return $bucket;
	}
	
	/**
	 *
	 * @return string|null A string with the error message, if any, or null if successful
	 */
	public function upload() {
		new Identity_Verifier();
	}
}
$ncs = new Now_Commons_Service();

$args = array_replace(["checkbad" => true, "removecats" => false], $env->get_request_args());
switch (@$args["type"]) {
	case "interface":
		$data = [
			"ifs" => array_map(
				function (CommonsHelper_Interface $if) {
					return array_filter((array)$if);
				}, CommonsHelper_Factory::get_dao()->load_interface()), 
			"projects" => $ncs->get_sorted_project_names(),
			"codes" => $ncs->get_codes()];

		$cache_length = SECONDS_PER_DAY * 7;
		$cache_length_formatted = gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_length);
		
		header("Cache-Control: max-age=$cache_length");
		header("Expires: $cache_length_formatted");
		
		break;
	case "text":
		$data = CommonsHelper_Factory::get_service()->get_upload_text(@$args["file"],
			@$args["subproject"], @$args["project"], $args["removecats"], $args["checkbad"]);
		break;
	default:
		$data = ["error" => "Type not recognized"];
}
header('content-type: application/json; charset=utf-8');

echo json_encode($data, Environment::prop("environment", "jsonencode.options"));