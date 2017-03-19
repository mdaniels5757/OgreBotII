<?php
/**
 * 
 * @author magog
 *
 */
class Direct_Latitude_Longitude_Svg_Creator extends Latitude_Longitude_Svg_Creator {
	const DEGREES_SIGN = "(?:\xB0|\xC2\xB0)";
	const MINUTES_SIGN = "(?:'|′|\xE2\x80)";
	const SECONDS_SIGN = "(?:\"|''|″|\xE2\x80\xB3)";
	
	/**
	 *
	 * @var Latitude_Longitude_Svg_Internal_Get_Result
	 */
	private $get_result;
	
	/**
	 *
	 * @param Latitude_Longitude[] $latlongs        	
	 * @param string[] $warnings        	
	 */
	public function __construct(array $latlongs, array $warnings = []) {
		$this->get_result = new Latitude_Longitude_Svg_Internal_Get_Result($latlongs, $warnings);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_latitude_longitudes()
	 */
	protected final function get_latitude_longitudes() {
		return $this->get_result;
	}
	
	/**
	 *
	 * @param string $string        	
	 * @return self
	 */
	public static function parse_from_string($string) {
		$strings = preg_split("/(?:;|\r?\n)/", $string);
		
		$lat_longs = [];
		$warnings = [];
		foreach ($strings as $string) {
			$trim = trim($string);
			
			if ($trim === "") {
				continue;
			}
			
			$split = preg_split("/[\,\s]+/", $trim);
			
			if (count($split) != 2) {
				$warnings[] = "Can't parse line: $string";
				continue;
			}
			
			$lat = null;
			$long = null;
			
			foreach ($split as $index => $token) {
				$token = mb_trim($token);
				if (preg_match(
					"/^([\d\.]+)" . self::DEGREES_SIGN .
						 "?(N(?:orth)?|E(?:ast)?|S(?:outh)?|W(?:est)?)$/i", $token, $match)) {
					switch (strtoupper($match[2][0])) {
						case "N" :
							$lat = $match[1];
							break;
						case "E" :
							$long = $match[1];
							break;
						case "S" :
							$lat = -$match[1];
							break;
						default :
							$long = -$match[1];
					}
					continue;
				}
				if (preg_match(
					"/^([\d\.]+)" . self::DEGREES_SIGN . "([\d\.]+)" . self::MINUTES_SIGN .
						 "([\d\.]+)" . self::SECONDS_SIGN .
						 "(N(?:orth)?|E(?:ast)?|S(?:outh)?|W(?:est)?)$/i", $token, $match)) {
					
					$parsed = $match[1] + $match[2] / 60 + $match[3] / 3600;
					switch (strtoupper($match[4][0])) {
						case "N" :
							$lat = $parsed;
							break;
						case "E" :
							$long = $parsed;
							break;
						case "S" :
							$lat = -$parsed;
							break;
						default :
							$long = -$parsed;
					}
					continue;
				}
				
				if (preg_match("/^(\-[\d\.]+)°?$/i", $token, $match)) {
					if ($index === 0) {
						$lat = $match[1];
					} else {
						$long = $match[1];
					}
					continue;
				}
				$warnings[] = "Can't parse token: $token";
				continue 2;
			}
			
			if ($lat === null || $long === null) {
				$warnings[] = "Can't parse line: $string";
				continue;
			}
			
			$lat_long = new Latitude_Longitude();
			$lat_long->count = 1;
			$lat_long->latitude = (float)$lat;
			$lat_long->longitude = (float)$long;
			$lat_longs[] = $lat_long;
		}
		
		return new self($lat_longs, $warnings);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_arg_key()
	 */
	public static function get_arg_key() {
		return "latlong";
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::load_from_request_args()
	 */
	public static function load_from_request_args(array $args) {
		return self::parse_from_string($args['latlong']);
	}
}