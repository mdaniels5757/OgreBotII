<?php

/**
 *
 * @author magog
 *
 */
class IllegalArgumentException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 * @param Exception|null $chained
	 */
	public function __construct($message, $chained = null) {
		parent::__construct($message, $chained);
	}
}