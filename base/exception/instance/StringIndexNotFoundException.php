<?php

/**
 *
 * @author magog
 *
 */
class StringIndexNotFoundException extends BaseException {
	
	/**
	 * 
	 * @param string $message
	 */
	public function __construct($message) {
		parent::__construct("String index not found. $message");
	}
}
