<?php

/**
 *
 * @author magog
 *
 */
class TuscException extends BaseException {

	/**
	 * 
	 * @param string $tuscText
	 * @param int $httpCode
	 */
	public function __construct($tuscText, $httpCode) {
		parent::__construct("TUSC failure. HTTP code: $httpCode, text: $tuscText.");
	}
}