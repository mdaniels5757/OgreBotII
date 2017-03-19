<?php

/**
 *
 * @author magog
 *
 */
class Namespace_Exception extends BaseException {
	
	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}