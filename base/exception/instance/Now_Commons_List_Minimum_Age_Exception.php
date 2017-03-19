<?php

/**
 *
 * @author magog
 *
 */
class Now_Commons_List_Minimum_Age_Exception extends BaseException {

	/**
	 * 
	 * @param int $min_age
	 * @param int $actual_age
	 */
	public function __construct($min_age, $actual_age) {
		$seconds_remaining_human_readable = seconds_to_human_readable_time($min_age - $actual_age);
		
		parent::__construct("Gallery has been updated too recently. Please wait "
			."$seconds_remaining_human_readable until reloading.");
	}
}