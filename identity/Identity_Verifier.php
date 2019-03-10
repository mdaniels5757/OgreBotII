<?php

/**
 * 
 * @author magog
 *
 */
interface Identity_Verifier extends OAuth {
	
	/**
	 * 
	 * @return string|NULL
	 */
	public function get_cookie(): ?string ;
	
	/**
	 * 
	 * @return string|NULL
	 */
	public function get_username(): ?string;
	
	/**
	 * 
	 * @param string|NULL $cookie
	 * @return string|NULL
	 */
	public function get_username_by_cookie(?string $cookie): ?string;
	
	/**
	 * @return string[]
	 */
	public function get_auth_tool_keys() : array;
	
	/**
	 * 
	 */
	public function logout(): void;
}