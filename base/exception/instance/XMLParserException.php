<?php

/**
 * 
 * @author magog
 *
 */
class XMLParserException extends Exception {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}