<?php
/**
 * 
 * @author magog
 *
 */
interface CommonsHelper_Dao {
	
	/**
	 * @return CommonsHelper_Interface
	 */
	public function load_interface();
	
	/**
	 * 
	 * @param string $names
	 * @return string
	 */
	public function get_templates_on_commons($names);
}