<?php
/**
 * 
 * @author magog
 *
 */
abstract class FCC_Database_Serializer {
	
	/**
	 *
	 * @var string
	 */
	const PROPERTIES_FILE_NAME = "broadcast-stations";
	
	
	/**
	 *
	 * @var string
	 */
	private $band;
	
	/**
	 * 
	 */
	public function __construct() {
		$this->band = $this->get_band();
	}
	
	/**
	 *
	 * @return void
	 * @throws CantOpenFileException
	 */
	public static function run($cache_time = null) {
		if ($cache_time === null) {
			$cache_time = SECONDS_PER_DAY * 6;
		}
		$http_reader = new Http_Cache_Reader($cache_time);
		
		$station_coords = array_merge_all(
			array_map(
				function (FCC_Database_Serializer $instance) use($http_reader) {
					global $logger;
					
					$logger->info("Processing " . get_class($instance) . " lines");
					$data = $http_reader->get_and_store_url($instance->get_url());
					$lines = preg_split("/\r?\n/", $data);
					
					$logger->info(count($lines) . " found.");
					
					/* @var $station_coords Station_Coords[] */
					$station_coords = array_map([$instance, "read_line"], $lines);
					
					$new_station_coords = [];
					foreach ($station_coords as $station_coord) {
						if ($station_coord === null) {
							continue;
						}
						$key = "$station_coord->band-$station_coord->callsign";
						if (!isset($new_station_coords[$key])) {
							$new_station_coords[$key] = [];
						}
						$new_station_coords[$key][] = $station_coord;
					}
					
					// find the most powerful strength of each $new_station_coords
					$instance->post_process_all($new_station_coords);
					$new_station_coords = array_map_filter($new_station_coords, 
						function (array $station_coords) {							
							// average together the stations by signal strength
							$total_strength = 0;
							$total_lat = 0;
							$total_long = 0;
							foreach ($station_coords as $station_coord) {
								$strength = $station_coord->signal_strength;
								$total_strength += $strength;
								$total_lat += $station_coord->coord->latitude * $strength;
								$total_long += $station_coord->coord->longitude * $strength;
							}
								
							// some repeaters have a listed strength of zero
							if ($total_strength) {
								return $total_lat / $total_strength . ", " .
									 $total_long / $total_strength;
							}
							
							foreach ($station_coords as $station_coord) {
								$total_lat += $station_coord->coord->latitude;
								$total_long += $station_coord->coord->longitude;
							}
							return $total_lat / count($station_coords) . ", " .
								 $total_long / count($station_coords);
						});
					
					$logger->info(
						get_class($instance) . ": pruned to " . count($new_station_coords) . " lines");
					
					return $new_station_coords;
				}, Classloader::get_all_instances_of_type(self::class)));
		ksort($station_coords);
		self::serialize_to_properties_file($station_coords);
	}
	
	/**
	 *
	 * @param string[] $station_coords        	
	 * @return void
	 * @throws CantOpenFileException
	 */
	private static function serialize_to_properties_file(array $station_coords) {
		$properties_file_path = BASE_DIRECTORY . "/properties/" . self::PROPERTIES_FILE_NAME .
			 ".properties";
		
		$string = join("\n", 
			array_map_pass_key($station_coords, 
				function ($key, $coords_string) {
					return "$key = $coords_string";
				}));
		
		file_put_contents_ensure($properties_file_path, $string);
	}
	
	/**
	 *
	 * @param string $line        	
	 * @return Station_Coords
	 */
	private function read_line($line) {
		global $logger;
		
		// check this is an actual emitter rather than a repeater, etc.
		if (!$line) {
			return;
		}
		
		$callsign = trim(substr($line, 1, 12));
		// unregistered??
		if ($callsign === "NEW" || $callsign === "-") {
			return;
		}
		
		if (str_ends_with($callsign, "-$this->band")) {
			$callsign = substr($callsign, 0, -1 * strlen("-$this->band"));
		}
	
		$coords = new Station_Coords();
		$coords->callsign = $callsign;
		$coords->band = $this->get_band();
		$coords->coord = new Geo_Coordinates();
		
		preg_match("/\|(N|S) \|([\d\.]+) *\|([\d\.]+) *\|([\d\.]+) *\|/", $line, $lat_match);
		preg_match("/\|(W|E) \|([\d\.]+) *\|([\d\.]+) *\|([\d\.]+) *\|/", $line, $long_match);
		preg_match("/\|([\d\.]+|\-) *kW *\|/", $line, $strength_match);
		
		if (!$lat_match || !$long_match) {
			$logger->warn("Can't parse line: $line");
			return;
		}
		
		if ($strength_match) {
			$coords->signal_strength = +$strength_match[1];
		} else {
			$logger->warn("Can't determine signal strength: $line");
			$coords->signal_strength = 0;
		}
		
		$coords->coord->latitude = ($lat_match[2] + $lat_match[3] / 60 + $lat_match[4] / 3600) *
			 ($lat_match[1] === "N" ? 1 : -1);
		$coords->coord->longitude = ($long_match[2] + $long_match[3] / 60 + $long_match[4] / 3600) *
			 ($long_match[1] === "E" ? 1 : -1);
		
		$this->post_process($coords, $line);
		return $coords;
	}
	
	/**
	 * Override as needed
	 *
	 * @param Station_Coords $coords        	
	 * @param string $line        	
	 * @return void
	 */
	protected function post_process(Station_Coords $coords, $line) {
	}
	
	/**
	 * Override as needed
	 *
	 * @param Station_Coords[] $coords        	
	 * @return void
	 */
	protected function post_process_all(array &$coords) {
	}
	
	/**
	 *
	 * @return string
	 */
	protected abstract function get_url();
	
	/**
	 *
	 * @return string
	 */
	protected abstract function get_band();
}