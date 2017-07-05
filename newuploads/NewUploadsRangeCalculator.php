<?php
class NewUploadsRangeCalculator {
	
	/**
	 * 
	 * @var number[]
	 */
	private $ranges;
	
	public function __construct() {
		$new_uploads_xml = XmlParser::xmlFileToStruct("newuploads.xml");
		$ranges_xml = array_key_or_exception($new_uploads_xml, 'NEWUPLOADS', 0, 'elements', 
			'RANGES', 0, 'elements', 'RANGE');
		
		$this->ranges = map_array_function_keys($ranges_xml, 
			function ($range_xml) {
				$start = array_key_or_exception($range_xml, "attributes", "START");
				$count = array_key_or_exception($range_xml, "attributes", "COUNT");
				
				return [$start, $count];
			});
		ksort($this->ranges, SORT_NUMERIC);
	}

	/**
	 * 
	 * @param number $start
	 * @throws IllegalArgumentException
	 * @return number
	 */
	public function get_end($start) {
		$count_per_day = $this->get_count_for_date($start);
		$hour = substr($start, 8, 2) + substr($start, 10, 2) / 60 + substr($start, 12, 2) / 3600;
		
		
		if ($hour % (24 / $count_per_day) !== 0) {
			throw new IllegalArgumentException("Can't get end date for start $start");
		}
		
		return unixTimestampToMediawikiTimestamp(
			strtotime($start) + 24 / $count_per_day * SECONDS_PER_HOUR - 1);
	}
	
	/**
	 * 
	 * @param number $date
	 * @return number
	 */
	private function get_count_for_date($date) {
		global $validator;

		$validator->validate_args_condition($date, "date on the half hour", 
			preg_match("/^20\d{12}$/", $date));
		
		foreach ($this->ranges as $key => $next) {
			if ($date < $key) {
				return $previous;
			}
			$previous = $next;
		}
		return $previous;
	}
}