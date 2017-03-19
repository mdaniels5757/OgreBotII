<?php

/**
 *
 * @author magog
 *
 */
class ParseException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}