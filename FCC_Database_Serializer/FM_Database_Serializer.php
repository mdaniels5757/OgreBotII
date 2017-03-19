<?php
/**
 * 
 * @author magog
 *
 */
class FM_Database_Serializer extends FCC_Database_Serializer {
	
	const BAND = "FM";
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see FCC_Database_Serializer::get_url()
	 */
	protected function get_url() {
		return "https://transition.fcc.gov/fcc-bin/fmq?type=0&list=4&NS=N&EW=W";
	}
	/**
	 * (non-PHPdoc)
	 * 
	 * @see FCC_Database_Serializer::get_band()
	 */
	protected function get_band() {
		return self::BAND;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see FCC_Database_Serializer::post_process()
	 */
	protected function post_process(Station_Coords $coords, $line) {
		if (strlen($coords->callsign) >= 5 && $coords->callsign[0] === "X" && 
				str_ends_with($coords->callsign, self::BAND)) {
			$coords->callsign = substr($coords->callsign, 0, - 1 * strlen(self::BAND));
		}
		parent::post_process($coords, $line);
	}
}