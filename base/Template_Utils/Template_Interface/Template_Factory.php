<?php 

/**
 *
 * @author magog
 *
 */
interface Template_Factory {
	
	/**
	 *
	 * @param string $text
	 * @param string $name
	 * @param int $offset
	 * @return false|Abstract_Template
	 */
	public function extract($text, $name, $offset=0);
	
	/**
	 * 
	 * @param Abstract_Template $template
	 * @return Abstract_Template
	 */
	public function get(Abstract_Template $template);
}
?>