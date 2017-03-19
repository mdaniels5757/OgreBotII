<?php

/**
 *
 * @author magog
 *
 */
class IllegalWikiNameException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}