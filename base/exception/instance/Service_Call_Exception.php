<?php

/**
 *
 * @author magog
 *
 */
class Service_Call_Exception extends BaseException {
	
	/**
	 * 
	 * @param string $key
	 */
	public function __construct($key) {
		parent::__construct("Service call without legal key provided! Value: $key", null, false);
		
		ogrebotMail($this);
	}
}