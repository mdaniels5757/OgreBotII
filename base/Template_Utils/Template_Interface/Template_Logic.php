<?php

/**
 * 
 * @author magog
 *
 */
interface Template_Logic {
	
	
	/**
	 * 
	 * @param Template $template
	 * @return boolean
	 */
	public function is_eligible(Template $template);
	
	/**
	 * Modify a field name before reading it. The values are used before
	 * 	writing a field, so they should not be duplicated. These values are, 
	 *   cached per template so they must not change for each subsequent call.
	 * @param string $name
	 * @param Template $template
	 * @return string[]
	 */
	public function get_field_name_map($name, Template $template);
	
	/**
	 * Modify a field value before reading it.
	 * @param string|null $value
	 * @param string $original_name
	 * @param Template $template
	 * @return string|null string if the value is modified, null if not.
	 */
	public function read_value($value, $original_name, Template $template);
	
	/**
	 * Modify a field value before writing it.
	 * @param string|null $value If set to null, removefield will be called
	 * @param string|null $existing_value
	 * @param string $original_name
	 * @param Template $template
	 */
	public function write_value($value, $original_name, Template $template);
}