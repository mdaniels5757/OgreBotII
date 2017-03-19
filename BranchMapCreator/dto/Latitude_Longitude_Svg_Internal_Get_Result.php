<?php
/**
 * 
 * @author magog
 *
 */
class Latitude_Longitude_Svg_Internal_Get_Result {
	
	/**
	 *
	 * @param Latitude_Longitude[] $latitude_longitudes        	
	 * @param string[] $warnings        	
	 * @param string[] $messages        	
	 */
	public function __construct($latitude_longitudes = [], $warnings = [], $messages = []) {
		$this->latitude_longitudes = $latitude_longitudes;
		$this->warnings = $warnings;
		$this->messages = $messages;
	}
	
	/**
	 *
	 * @var Latitude_Longitude[]
	 */
	public $latitude_longitudes;
	
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
}