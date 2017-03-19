<?php

/**
 *
 * @author magog
 *
 */
class IllegalStateException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}