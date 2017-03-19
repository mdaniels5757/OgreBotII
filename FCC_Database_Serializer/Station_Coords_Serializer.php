<?php

/**
 * 
 * @author magog
 *
 */
class Station_Coords_Serializer {
	
	/**
	 *
	 * @var int[]
	 */
	private $fields_and_lengths;
	
	/**
	 *
	 * @var int
	 */
	private $total_length;
	
	/**
	 */
	public function __construct() {
		$this->fields_and_lengths = ["band" => 2, "callsign" => 15, "latitude" => 50, 
			"longitude" => 50];
		$this->total_length = array_sum($this->fields_and_lengths);
	}
	/**
	 *
	 * @param Station_Coords $coords        	
	 * @return string
	 * @throws IllegalArgumentException a field is unserializable
	 */
	public function serialize(Station_Coords $coords) {
		$buffer = "";
		foreach ($this->fields_and_lengths as $field => $length) {
			$val = (string)$coords->$field;
			if (strlen($val) > $length) {
				throw IllegalArgumentException(
					"Can't serialize field $field of length " . strlen($val));
			}
			
			$buffer .= strpad($val, $length);
		}
		
		return $buffer;
	}
	
	/**
	 *
	 * @param Station_Coords[] $coords        	
	 * @return string
	 * @throws IllegalArgumentException
	 */
	public function serialize_all(array $coords) {
		return join("", array_map([$this, "serialize"], $coords));
	}
	
	/**
	 *
	 * @param string $string        	
	 * @return Station_Coords
	 * @throws IllegalArgumentException
	 */
	public function deserialize($string) {
		if (strlen($string) !== $this->total_length) {
			throw new IllegalArgumentException(
				"Wrong string length, can't serialize: " . strlen($string) .
					 " != $this->total_length");
		}
		
		$coords = new Station_Coords();
		$ptr = 0;
		foreach ($this->fields_and_lengths as $field => $length) {
			$coords->$field = substr($string, $ptr, $length);
			$ptr += $length;
		}
		
		return $coords;
	}
	
	/**
	 *
	 * @param string $string        	
	 * @return Station_Coords[]
	 * @throws IllegalArgumentException
	 */
	public function deserialize_all($string) {
		return array_map([$this, "deserialize"], str_split($string));
	}
}