<?php

/**
 *
 * @author magog
 *
 */
class IllegalTypeException extends BaseException {
	
	/**
	 * 
	 * @param string $typeExpected
	 * @param mixed $val
	 */
	public function __construct($typeExpected, $val) {
		parent::__construct("Argument is not of type expected. Value: ".print_r($val, true));
	}
}