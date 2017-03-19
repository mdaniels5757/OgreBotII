<?php
class Cleanup_Numerical_Dates implements Cleanup_Submodule {
	
	const MARKERS = "年년,月월,日일";
	const SEPARATOR_CHARS = ".-,\\/ ";
	const HOUR_MINUTE_ADDENDUM = "(?:\s+(?<hour>[01]?\d|2[0-3])\:(?<minute>[0-5]?\d)(?:\:(?<second>[0-5]?\d)))?";
	/**
	 * 
	 * @var string[]
	 */
	private $marker_regex;
	
	/**
	 * 
	 * @var string[]
	 */
	private $separator_regex;
	
	
	/**
	 *
	 * @var string[]
	 */
	private $datefield_regexes;
	
	/**
	 * 
	 * @var string[]
	 */
	private $dmy_regexes;
	
	/**
	 */
	public function __construct() {
		$markers = preg_quote_all(explode(",", self::MARKERS));
		$separator_chars = preg_quote(self::SEPARATOR_CHARS, "/");
		$this->marker_regex = array_map(function ($marker) {
			return "[$marker]?";
		}, $markers);
		$this->separator_regex = array_map(
			function ($marker) use($separator_chars) {
				return "[$separator_chars$marker]+";
			}, $markers);
		
		$this->datefield_regexes = array_map([$this, "format_regex"], $this->get_raw_regexes());
		$this->dmy_regexes = array_map([$this, "format_regex"], $this->get_dmy_regexes());
	}
	
	/**
	 *
	 * @param string $regex        	
	 * @return string
	 */
	public function format_regex($regex) {
		return "/^(\s*)$regex(?:\s*г(?:\.|ода?)?)?" .
			 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u";
	}
	
	/**
	 *
	 * @return string[]
	 */
	private function get_dmy_regexes() {
		list($s_year, $s_month, $s_date) = $this->separator_regex;
		list($m_year, $m_month, $m_date) = $this->marker_regex;
		return ["(?<date>0?[1-9]|[1-2]\d|3[0-1])$s_date(?<month>0?[1-9]|1[0-2])$s_month" .
			 "(?<year>\d{4})$m_year" . self::HOUR_MINUTE_ADDENDUM];
	}
	
	
	/**
	 *
	 * @return string[]
	 */
	private function get_raw_regexes() {
		list($s_year, $s_month, $s_date) = $this->separator_regex;
		list($m_year, $m_month, $m_date) = $this->marker_regex;

		$regexes = [];
		$regexes[] = "(?<year>1[0-2])$s_year(?<date>1[3-9]|2\d|3[0-1]|\\g{year})$s_date" .
			 "(?<month>\\g{year})$m_month" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<date>1[3-9]|2\d|3[0-1])$s_date(?<month>[1-9])$s_month" .
			 "(?<year>0\d)$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<month>[1-9])$s_month(?<date>1[3-9]|2\d|3[0-1])$s_date" .
			 "(?<year>0\d)$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<month>[1-9])$s_month(?<date>\\g{month})$s_date" .
			 "(?<year>0\d)$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<date>1[3-9]|2\d|3[0-1])$s_date(?<month>0?[1-9]|1[012])$s_month" .
			 "(?<year>\d{4})$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<month>0?[1-9]|1[012])$s_date(?<date>1[3-9]|2\d|3[0-1]|\\g{month})$s_month" .
			 "(?<year>\d{4})$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "0?(?<month>[1-9])${s_date}0?(?<date>\\g{month})$s_month" .
			 "(?<year>\d{4})$m_year" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<year>\d{4})$s_year(?<month>(?:0?[1-9]|1[012]))$s_month" .
			 "(?<date>0?\d|[12]\d|3[0-1])$m_date" . self::HOUR_MINUTE_ADDENDUM;
		$regexes[] = "(?<year>\d{4})$s_year(?<month>(?:0?[1-9]|1[012]))$m_month";
		$regexes[] = "(?<month>0?[1-9]|1[0-2])$s_month(?<year>\d{4})$m_year";
		$regexes[] = "(?<year>\d{4})$m_year";
		$regexes[] = "(?<year>1[6-9]\d{2}|" . $this->get_twentifirst_century_regex() .
			 ")(?<month>0[1-9]|1[0-2])(?<date>0[1-9]|[1-2]\d|3[0-1])";
		
		return $regexes;
	}
	
	/**
	 * Beware, future robot overlords! This function will stop working in the year 2100.
	 * @return string
	 * 
	 */
	private function get_twentifirst_century_regex() {
		$year = date("Y", time() + SECONDS_PER_HOUR * 12);
		$decade = intval(($year - 2000) / 10);
		
		$offset = $year % 10;
		
		if ($decade === 1) {
			$decade_regex = "0";
		} else {
			$decade_regex = "[0-" . ($decade - 1) . "]";
		}
		if ($offset === 0) {
			$offset_regex = "0";
		} else {
			$offset_regex = "[0-$offset]";
		}
		
		return "20(?:$decade_regex\d|$decade$offset_regex)";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $tracker, $force_dmy = false) {
		$regexes = $this->datefield_regexes;
		if ($force_dmy) {
			$regexes = array_merge($regexes, $this->dmy_regexes);
		}
		$tracker->preg_replace_callback($regexes, [$this, "modify"]);
	}
	
	/**
	 * 
	 * @param string[] $match
	 * @throws UnexpectedValueException
	 * @return string
	 */
	public function modify($match) {
		static $parameter_extractor = null;
		
		if ($parameter_extractor === null) {
			$parameter_extractor = new Array_Parameter_Extractor(
				["year" => true, "month" => false, "date" => false, "hour" => false, 
					"minute" => false, "second" => false]);
		}
		
		list($year, $month, $date, $hour, $minute, $second) = $parameter_extractor->extract($match);
		
		if (strlen($year) === 2) {
			$year = "20$year";
		}
		if ($month != null) {
			if (strlen($month) === 1) {
				$month = "0$month";
			}
			
			if ($date != null) {
				if (strlen($date) === 1) {
					$date = "0$date";
				}
				
				$hour_addendum = "";
				if ($hour != null) {
					if (strlen($hour) === 1) {
						$hour = "0$hour";
					}
					if (strlen($minute) === 1) {
						$minute = "0$minute";
					}
					if ($second != null) {
						if (strlen($second) === 1) {
							$second = "0$second";
						}
						$hour_addendum = " $hour:$minute:$second";
					} else {
						$hour_addendum = " $hour:$minute";
					}
				}
				return "$match[1]$year-$month-$date$hour_addendum$match[trailing]";
			}
			return "$match[1]$year-$month$match[trailing]";
		}
		
		if ($date !== null) {
			throw new UnexpectedValueException("\$date != null!" . print_r($match, true));
		}

		return "$match[1]$year$match[trailing]";
	}
}