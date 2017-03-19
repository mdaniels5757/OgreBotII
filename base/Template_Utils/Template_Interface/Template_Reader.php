<?php
interface Template_Reader {
	
	/**
	 * 
	 * @param Template $template
	 * @param string $name
	 * @return string
	 */
	public function read_value(Template $template, $name);
	
	/**
	 * 
	 * @param Template $template
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function write_value(Template $template, $name, $value);
}