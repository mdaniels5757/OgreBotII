<?php

/**
 * 
 * @author magog
 *
 */
class Hook_Register {
	

	/**
	 * 
	 * @var Hook[]
	 */
	private static $hooks = [];
	
	
	/**
	 *
	 * @param string $name
	 * @return void
	 */
	public static function register($name) {
		if (!isset(self::$hooks[$name])) {
			self::$hooks[$name] = new Hook();
		}
	}

	/**
	 * 
	 * @param string $name
	 * @param callable $callable
	 * @return void
	 * @throws IllegalStateException
	 */
	public static function add($name, callable $callable) {
		if (!isset(self::$hooks[$name])) {
			throw new IllegalStateException("Hook not registered: $name");
		}
		self::$hooks[$name]->add($callable);
	}
	
	
	/**
	 * 
	 * @param string $name
	 * @param callable $callable
	 * @return void
	 * @throws IllegalStateException
	 */
	public static function trigger($name) {
		if (!isset(self::$hooks[$name])) {
			throw new IllegalStateException("Hook not registered: $name");
		}
		self::$hooks[$name]->trigger();
	} 
}