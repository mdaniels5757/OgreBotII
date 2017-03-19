<?php

/**
 *
 * @author magog
 *
 */
abstract class FileIOException extends BaseException {
	
	/**
	 * 
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}