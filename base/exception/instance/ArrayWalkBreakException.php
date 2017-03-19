<?php

/**
 *
 * @author magog
 *
 */
class ArrayWalkBreakException extends BaseException {
	
	/**
	 * 
	 */
	public function __construct() {
		parent::__construct("Array walk broken", null, false);
	}
}