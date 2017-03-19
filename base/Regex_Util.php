<?php

/**
 * 
 * @author magog
 *
 */
class Regex_Util {
	
	/**
	 * 
	 * @param string $pattern
	 * @param string $subject
	 * @param int $flags
	 * @param int $offset
	 * @return number
	 * @throws Regex_Exception
	 */
	public static function match($pattern, $subject, $flags = null, $offset = null) {
		$result = preg_match($pattern, $subject, $matches, $flags, $offset);
		if ($result === false) {
			throw new Invalid_Regex_Exception($pattern);
		}
		if ($result === 0) {
			throw new Regex_Not_Matched_Exception($pattern, $string);
		}
		return $matches;
	}
	

	/**
	 *
	 * @param string $pattern
	 * @param string $subject
	 * @param int $flags
	 * @param int $offset
	 * @return string
	 * @throws Regex_Exception
	 */
	public static function match_all($pattern, $subject, $flags = PREG_SET_ORDER, $offset = null) {
		$result = preg_match_all($pattern, $subject, $matches, $flags, $offset);
		if ($result === false) {
			throw new Invalid_Regex_Exception($pattern);
		}
		if ($result === 0) {
			throw new Regex_Not_Matched_Exception($pattern, $string);
		}
		return $matches;
	}
}