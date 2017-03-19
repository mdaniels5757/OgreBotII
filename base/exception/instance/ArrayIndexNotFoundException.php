<?php

/**
 *
 * @author magog
 *
 */
class ArrayIndexNotFoundException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct("Array index not found. $message");
	}
}