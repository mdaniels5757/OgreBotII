<?php
/**
 * 
 * @author magog
 *
 */
interface Identity_Dao {
	
	/**
	 * 
	 * @param string|null $cookie
	 * @return string|null username
	 */
	public function get($cookie);
	
	/**
	 * 
	 * @param string $username
	 * @return string cookie
	 */
	public function set($username);
	
	/**
	 * 
	 * @param string $cookie
	 * @return void
	 */
	public function logout($cookie);
}