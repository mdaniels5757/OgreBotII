<?php
/**
 * 
 * @author magog
 *
 */
class AM_Database_Serializer extends FCC_Database_Serializer {
	
	const NIGHT_FLAG_APPEND = "|*NIGHT*";
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see FCC_Database_Serializer::process_line()
	 */
	protected function post_process(Station_Coords $coords, $line) {
		if (preg_match("/\|Nighttime\s*\|/", $line)) {
			$coords->callsign .= self::NIGHT_FLAG_APPEND;
		}
		parent::post_process($coords, $line);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see FCC_Database_Serializer::post_process_all()
	 */
	protected function post_process_all(array &$callsign_and_coords) {
		global $logger;
		
		$negative_strlen = -1 * strlen(self::NIGHT_FLAG_APPEND);
		
		foreach ($callsign_and_coords as $callsign => $coords) {
			if (substr($callsign, $negative_strlen) === self::NIGHT_FLAG_APPEND) {
				$actual_callsign = substr($callsign, 0, $negative_strlen);
				if (!isset($callsign_and_coords[$actual_callsign])) {
					$logger->info("Nighttime station without a daytime equivalent: $actual_callsign");
					$callsign_and_coords[$actual_callsign] = $coords;
				}
				unset($callsign_and_coords[$callsign]);
			}
		}
		
		parent::post_process_all($callsign_and_coords);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see FCC_Database_Serializer::get_url()
	 */
	protected function get_url() {
		return "https://transition.fcc.gov/fcc-bin/amq?type=0&list=4&NS=N&EW=W";
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see FCC_Database_Serializer::get_band()
	 */
	protected function get_band() {
		return "AM";
	}
}