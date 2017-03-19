<?php

/**
 *
 * @author magog
 *
 */
class OAuthException extends BaseException {
	
	/**
	 * 
	 */
	public function __construct($msg = "") {
		parent::__construct($msg, null, false);
	}
}