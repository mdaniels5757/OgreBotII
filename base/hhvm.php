<?php
if (!defined("CAL_GREGORIAN")) {
	define("CAL_GREGORIAN", 0);
}
if (!function_exists("cal_days_in_month")) {
	/**
	 *
	 * @param int $calendar
	 *        	only CAL_GREGORIAN supported for now
	 * @param int $month        	
	 * @param int $year        	
	 * @return int|false
	 * @throws IllegalArgumentException if $calendar isn't CAL_GREGORIAN
	 */
	function cal_days_in_month($calendar, $month, $year) {
		global $logger;
		
		logger_or_stderr(Level::TRACE, "cal_days_in_month($calendar, $month, $year)");
		if ($calendar !== CAL_GREGORIAN) {
			throw new IllegalArgumentException(
				"Only CAL_GREGORIAN supported. Calendar value: $calendar");
		}
		$int_month = (int)$month;
		$int_year = (int)$year;
		
		if ($int_month < 1 || $int_month > 12) {
			trigger_error("Illegal month value: $month", E_USER_WARNING);
			return false;
		}
		
		if ($int_year < 1) {
			trigger_error("Illegal or unsupported year value: $year", E_USER_WARNING);
			return false;
		}
		
		switch ($int_month) {
			case 1 :
			case 3 :
			case 5 :
			case 7 :
			case 8 :
			case 10 :
			case 12 :
				return 31;
			case 4 :
			case 6 :
			case 9 :
			case 11 :
				return 30;
		}
		
		// February
		return $int_year % 4 === 0 && ($int_year % 100 !== 0 || $int_year % 400 === 0) ? 29 : 28;
	}
}