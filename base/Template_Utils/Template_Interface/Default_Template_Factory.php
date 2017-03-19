<?php
class Default_Template_Factory implements Template_Factory {
	
	private function __construct() {
		
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Factory::extract()
	 */
	public function extract($text, $name, $offset=0) {
		return Template::extract($text, $name, $offset);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Factory::get()
	 */
	public function get(Abstract_Template $template) {
		return $template;
	}
	
	/**
	 * @return self
	 */
	public static function get_singleton() {
		return new Default_Template_Factory();
	}
}