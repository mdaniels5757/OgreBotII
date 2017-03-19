<?php

/**
 *
 * @author magog
 *
 */
class Array_Merge_Conflict_Exception extends BaseException {

	/**
	 * 
	 * @param string|int $key
	 * @param mixed $value1
	 * @param mixed $value2
	 */
	public function __construct($key, $value1, $value2) {
		$value1_string = print_r($value1, true);
		$value2_string = print_r($value2, true);
		parent::__construct("The key '$key' was duplicated. Values: $value1_string, $value2_string",
			null, $false);
	}
}