<?php
abstract class Zip_Code_Branch_Map_Creator extends Latitude_Longitude_Svg_Creator {
	
	/**
	 *
	 * @var string[]
	 */
	private static $zips_to_latlongs;
	
	/**
	 * return int[]
	 */
	protected abstract function get_zips();
	
	/**
	 * Called after get_zips; add warnings to the warning set.
	 * Override as needed.
	 * 
	 * @return string[]
	 */
	protected function get_warnings() {
		return [];
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_latitude_longitudes()
	 */
	protected final function get_latitude_longitudes() {
		global $logger;
		
		if (self::$zips_to_latlongs === null) {
			load_property_file_into_variable(self::$zips_to_latlongs, "zips_to_latlongs");
		}
		
		$zips = $this->get_zips();
		$logger->info("Total zip codes returned: " . count($zips));
		
		$warnings = [];
		$lat_longs = [];
		foreach ($zips as $zip) {
			if (!isset($lat_longs[$zip])) {
				$as_lat_long_string = @self::$zips_to_latlongs[$zip];
				
				if ($as_lat_long_string === null) {
					if (!@$warnings[$zip]) {
						$warnings[$zip] = "Unrecognized zip: $zip";
						$logger->warn($warnings[$zip]);
					}
				} else {
					$lat_longs[$zip] = new Latitude_Longitude();
					$lat_longs[$zip]->count = 1;
					list($lat_longs[$zip]->latitude, $lat_longs[$zip]->longitude) = preg_split(
						"/\,/", $as_lat_long_string, 2);
				}
			} else {
				$lat_longs[$zip]->count++;
			}
		}
		
		$warnings = array_merge(array_values($warnings), array_values($this->get_warnings()));
		return new Latitude_Longitude_Svg_Internal_Get_Result(array_values($lat_longs), $warnings);
	}
}