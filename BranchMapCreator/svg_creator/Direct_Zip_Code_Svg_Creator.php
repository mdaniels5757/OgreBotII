<?php
class Direct_Zip_Code_Svg_Creator extends Zip_Code_Branch_Map_Creator {
	
	/**
	 *
	 * @var int[]
	 */
	private $zips;
	
	/**
	 *
	 * @var string[]
	 */
	private $warnings;
	
	/**
	 *
	 * @param Latitude_Longitude[] $zips        	
	 * @param string[] $warnings        	
	 */
	public function __construct(array $zips, array $warnings = []) {
		$this->zips = $zips;
		$this->warnings = $warnings;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Zip_Code_Branch_Map_Creator::get_zips()
	 */
	protected function get_zips() {
		return $this->zips;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Zip_Code_Branch_Map_Creator::get_warnings()
	 */
	protected function get_warnings() {
		return $this->warnings;
	}
	
	/**
	 *
	 * @param string $string        	
	 * @return self
	 */
	public static function parse_from_string($string) {
		$strings = preg_split("/[\,\s\;]+/", $string);
		
		$zips = [];
		$warnings = [];
		foreach ($strings as $string) {
			
			preg_match("/^(\d{1,5})$/", $string, $match);
			
			if (!$match) {
				$warnings[] = "Can't parse line: $string";
				continue;
			}
			
			$zips[] = (int)$match[1];
		}
		
		return new self($zips, $warnings);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::get_arg_key()
	 */
	public static function get_arg_key() {
		return "zip";
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Latitude_Longitude_Svg_Creator::load_from_request_args()
	 */
	public static function load_from_request_args(array $args) {
		return self::parse_from_string($args['zip']);
	}
}