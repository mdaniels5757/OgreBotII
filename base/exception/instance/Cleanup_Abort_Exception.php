<?php

/**
 * 
 * @author magog
 *
 */
class Cleanup_Abort_Exception extends BaseException {
	
	/**
	 * 
	 */
	public function __construct() {
		parent::__construct(get_class(), null, false);
	}
}