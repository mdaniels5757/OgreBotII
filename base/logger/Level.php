<?php
class Level {
	const ALL = PHP_INT_MIN;
	const OFF = -1;
	const FATAL = 0;
	const ERROR = 1;
	const WARN = 2;
	const INFO = 3;
	const DEBUG = 4;
	const TRACE = 5;
	const INSANE = 6;
	private static $levels;
	
	/**
	 *
	 * @return void
	 */
	public static function init() {
		self::$levels = array_flip((new ReflectionClass(self::class))->getConstants());
	}
	
	/**
	 *
	 * @param int $int        	
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public static function int_to_level($int) {
		$val = self::$levels[$int];
		
		if ($val === null) {
			throw new InvalidArgumentException("Unrecognized level: $int");
		}
		
		return $val;
	}
	
	/**
	 *
	 * @param string $level        	
	 * @throws InvalidArgumentException
	 * @return number
	 */
	public static function level_to_int($level) {
		foreach (self::$levels as $int => $string) {
			if (strtoupper($level) == $string) {
				return $int;
			}
		}
		throw new InvalidArgumentException("Unrecognized level: $level");
	}
}
Level::init();