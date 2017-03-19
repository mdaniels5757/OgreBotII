<?php

/**
 *
 * @author magog
 *
 */
class AssertionFailureException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
		$this->mail();
	}
}