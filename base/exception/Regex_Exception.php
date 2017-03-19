<?php

/**
 *
 * @author magog
 *
 */
class Regex_Exception extends BaseException {
		
	/**
	 * 
	 * @param string $message
	 */
	public function __construct($message) {
		parent::__construct($message, null, false);
	}
}