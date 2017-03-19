<?php
/**
 * 
 * @author magog
 *
 */
class Latitude_Longitude_Which_Result {
	
	/**
	 *
	 * @param Latitude_Longitude_Svg[] $latitude_longitudes        	
	 * @param string[] $warnings        	
	 * @param string[] $message        	
	 */
	public function __construct(array $latitude_longitudes = [], $warnings = [], $messages = [], 
		$count = null) {
		$this->latitude_longitudes_svg = $latitude_longitudes;
		$this->warnings = $warnings;
		$this->messages = $messages;
		$this->count = $count;
	}
	
	/**
	 *
	 * @var Latitude_Longitude_Svg[]
	 */
	public $latitude_longitudes_svg;
	
	/**
	 *
	 * @var string[]
	 */
	public $warnings;
	
	/**
	 *
	 * @var string[]
	 */
	public $messages;
	
	/**
	 *
	 * @var int
	 */
	public $count;
}