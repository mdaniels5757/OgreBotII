<?php
/**
 * 
 * @author magog
 *
 */
class Radio_Station_Map_Creator extends Latitude_Longitude_Svg_Creator {
	
	/**
	 * Keeping the variable as static so that it doesn't get serialized and plug
	 * up PHP's session memory
	 *
	 * @var string[]
	 */
	private static $all_lat_longs;
	
	/**
	 *
	 * @var string[]
	 */
	private $call_letters;
	
	/**
	 *
	 * @param string[] $call_letters        	
	 */
	public function __construct(array $call_letters) {
		$this->call_letters = array_map("strtoupper", $call_letters);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_latitude_longitudes()
	 */
	protected function get_latitude_longitudes() {
		global $logger;
		
		self::load_lat_longs();
		$result = new Latitude_Longitude_Svg_Internal_Get_Result();
		
		foreach ($this->call_letters as $call_letter) {
			$lat_long_string = @self::$all_lat_longs[$call_letter];
			
			if ($lat_long_string === null) {
				$result->warnings[] = "Call letters not recognized: $call_letter";
				continue;
			}
			
			preg_match("/^([\-\d\.]+)\s*\,\s*([\-\d\.]+)$/", $lat_long_string, $lat_long_match);
			if (!$lat_long_match) {
				$result->warnings[] = "An unknown error has occurred while geolocating $call_letter";
				$logger->error("Can't match string for call letter $call_letter: $lat_long_string");
				continue;
			}
			
			$result->messages[]= "$call_letter => $lat_long_string";
			$existing = @$result->latitude_longitudes[$lat_long_string];
			if ($existing === null) {
				$lat_long = new Latitude_Longitude();
				$lat_long->latitude = $lat_long_match[1];
				$lat_long->longitude = $lat_long_match[2];
				$lat_long->count = 1;
				$result->latitude_longitudes[$lat_long_string] = $lat_long;
			} else {
				$existing->count++;
			}
		}
		
		return $result;
	}
	
	/**
	 * @return void
	 */
	private static function load_lat_longs() {
		if (self::$all_lat_longs === null) {
			load_property_file_into_variable(self::$all_lat_longs, "broadcast-stations");
		}
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_arg_key()
	 */
	public static function get_arg_key() {
		return "radio";
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::load_from_string()
	 */
	public static function load_from_request_args(array $args) {
		$am = str_prepend(self::get_callsigns_from_block(array_key_or_exception($args, "am")), 
			"AM-");
		$fm = str_prepend(self::get_callsigns_from_block(array_key_or_exception($args, "fm")), 
			"FM-");
		
		return new Radio_Station_Map_Creator(array_merge($am, $fm));
	}
	
	/**
	 *
	 * @param string $block
	 * @return string[]
	 */
	private static function get_callsigns_from_block($block) {
		return array_map_filter(preg_split("/[\s\,]+/", $block),
			function ($arg) {
				$val = trim($arg);
				return $val ? $val : null;
			});
	}
}