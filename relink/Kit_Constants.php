<?php

/**
 * 
 * @author magog
 *
 */
class Kit_Constants {
	
	/**
	 *
	 * @var string[]
	 */
	private static $kit_types = ["body" => "b", "left_arm" => "la", "right_arm" => "ra", 
			"shorts" => "sh", "socks" => "so"];
	
	/**
	 * @return string[]
	 */
	public static function get_kit_types() {
		return self::$kit_types;
	}
	
}