<?php

/**
 * 
 * @author magog
 *
 */
class Invalid_Regex_Exception extends Regex_Exception {
	
	public function __construct($regex) {
		parent::__construct("Invalid regex: $regex");
	}
}