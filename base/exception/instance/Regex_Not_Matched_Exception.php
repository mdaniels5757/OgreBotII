<?php

/**
 * 
 * @author magog
 *
 */
class Regex_Not_Matched_Exception extends Regex_Exception {
	
	/**
	 * 
	 * @param string $regex
	 * @param string $string
	 */
	public function __construct($regex, $string) {
		parent::__construct("Regex doesn't match: regex = $regex, string = $string");
	}
}