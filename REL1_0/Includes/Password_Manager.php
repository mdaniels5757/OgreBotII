<?php

/**
 * A class to load the passwords without storing them in the .cfg file.
 *   This allows the source to be public without the password. 
 * @author magog
 *
 */
interface Password_Manager {
	
	/**
	 * 
	 * @param array $configuration
	 * @return string|null The password
	 */
	public function get_password($configuration);
}