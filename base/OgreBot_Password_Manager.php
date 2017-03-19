<?php
/**
 * 
 * @author magog
 *
 */
class OgreBot_Password_Manager implements Password_Manager {
	
	/**
	 * 
	 * @var array
	 */
	private $secrets;
	
	public function __construct() {
		load_property_file_into_variable($this->secrets, "secrets");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Password_Manager::get_password()
	 * @throws ArrayIndexNotFoundException if the password can't be located
	 */
	public function get_password($configuration) {
		$username = str_replace(" ", "_", $configuration['username']);
		return array_key_or_exception($this->secrets, "password_$username");
	}
}