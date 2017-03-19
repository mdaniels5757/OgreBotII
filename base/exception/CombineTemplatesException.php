<?php

/**
 * 
 * @author magog
 *
 */
abstract class CombineTemplatesException extends BaseException {
	
	/**
	 * 
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message, null, false);
	}
}