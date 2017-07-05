<?php
class FDIC_Map_Creator extends Zip_Code_Branch_Map_Creator {
	
	/**
	 *
	 * @var int
	 */
	const STEP = 100;
	
	/**
	 *
	 * @var number[]
	 */
	private $certNumbers;
	
	/**
	 *
	 * @param string|string[] $certNumbers        	
	 * @param string $request_key        	
	 */
	public function __construct($certNumbers) {
		global $validator;
		
		if (!is_array($certNumbers)) {
			$certNumbers = [$certNumbers];
		}
		
		$validator->validate_arg_array($certNumbers, "numeric");
		$validator->validate_arg_array($certNumbers, "positive");
		$this->certNumbers = $certNumbers;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Zip_Code_Branch_Map_Creator::get_zips()
	 */
	public function get_zips() {
		global $constants, $logger;
		
		$http_cache_reader = new Http_Cache_Reader();
		$http_cache_reader->set_cache_time(SECONDS_PER_DAY * 7);
		
		$zips = [];
		foreach ($this->certNumbers as $certNumber) {
			
			$url = replace_named_variables($constants['fdic_map_creator.json_url'], 
				["certNumber" => $certNumber, "step" => self::STEP], false);
			
			$i = 0;
			do {
				// seems to be dying on http...
				$url = preg_replace("/^http:\/\/([a-z\-\.]+)(?::80)?\//", "https://$1/", $url);
				
				$logger->info("Count " . (++$i * self::STEP));
				$string = $http_cache_reader->get_and_store_url($url);
				$json_data = json_decode($string, true);
				$json_results = array_key_or_exception($json_data, "d", "results");
				
				$next_zips = array_map_filter($json_results, 
					function ($json_result) {
						global $validator;
						
						$servTypeCd = array_key_or_exception($json_result, 'servTypeCd');
						$zip = array_key_or_exception($json_result, 'zip');
						
						$validator->validate_arg($zip, "numeric");
						$validator->validate_arg($servTypeCd, "numeric");
						
						if (in_array($servTypeCd, [11, 12, 16, 22, 23], false)) {
							return (int)$zip;
						}
					});
				
				$zips = array_merge($zips, $next_zips);
				
				$url = array_key_or_exception($json_data, "d", "__next");
			} while (array_key_or_exception($json_data, "d", "__count") != 0);
		}
		
		return $zips;
	}
	
	/**
	 *
	 * @param string $string
	 * @return self
	 */
	public static function parse_from_string($string) {
		$certNumbers = preg_split("/[\,\s]+/", mb_trim($string));
		return new self($certNumbers);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_arg_key()
	 */
	public static function get_arg_key() {
		return "fdic";
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::load_from_request_args()
	 */
	public static function load_from_request_args(array $args) {
		return self::parse_from_string($args['fdic']);
	}
}