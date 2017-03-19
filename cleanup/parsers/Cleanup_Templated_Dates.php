<?php
class Cleanup_Templated_Dates implements Cleanup_Submodule {
	
	/**
	 */
	public function __construct() {
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $tracker) {
		$tracker->preg_replace_callback(
			"/^(\s*)\{\{[_]*[Dd]ate[_]*\s*\|\s*(?<year>\d{1,4})\s*(?:\|\s*(?<month>\d{1,2})" .
				 "\s*(?:\|\s*(?<date>\d{1,2})\s*)?)?\}\}" .
				 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", [$this, "modify"]);
	}
	
	/**
	 *
	 * @param string[] $match        	
	 * @return string
	 */
	public function modify($match) {
		static $parameter_extractor = null;
		
		if ($parameter_extractor === null) {
			$parameter_extractor = new Array_Parameter_Extractor(
				["year" => true, "month" => false, "date" => false]);
		}
		
		list($year, $month, $date) = $parameter_extractor->extract($match);
		
		if ($month === "") {
			return "$match[1]$year$match[trailing]";
		}
		
		if (strlen($month) === 1) {
			$month = "0$month";
		}
		
		if ($date === "") {
			return "$match[1]$year-$month$match[trailing]";
		}
		
		if (strlen($date) === 1) {
			$date = "0$date";
		}
		return "$match[1]$year-$month-$date$match[trailing]";
	}
}