<?php
interface Abstract_Template {
	
	/**
	 * 
	 * @param string $var
	 * @return mixed
	 */
	public function __get($var);
	
	/**
	 * 
	 * @param string $var
	 * @param mixed $val
	 * @return mixed
	 */
	public function __set($var,$val);
	
	/**
	 * @return string
	 */
	public function __toString();
	
	/**
	 * Return the string used in {@link __construct()} with the new template.
	 *
	 * @return string
	 */
	public function wholePage();
	
	/**
	 * Get the value of a field
	 *
	 * @param string $fieldname
	 *        	Name of the field to find
	 * @param bool $trim_fieldname
	 *        	Trim "fieldname=" from the beginning.
	 * @return string|boolean Value of template if it exists, otherwise boolean false
	 */
	public function fieldvalue($fieldname, $trim_fieldname = true);
	
	
	/**
	 * Change the name of a field
	 *
	 * @param string $oldname Name of the field to migrate
	 * @param string $newname New name of the field
	 * @return void
	 */
	public function renamefield($oldname, $newname);
	
	/**
	 * Delete a field
	 *
	 * @param string $fieldname Name of field to delete
	 * @return void
	 */
	public function removefield($fieldname);
	
	/**
	 * Rename template
	 *
	 * @param string $newname New name of template
	 * @return void
	 */
	public function rename($newname);
	
	/**
	 * Get the name of the template
	 * @return string
	 */
	public function getname();
	
	/**
	 * Add a field to the template
	 *
	 * If the fieldname is not given, the parameter will be added effectively as
	 * a numbered parameter.
	 *
	 * @param string $value Value of parameter
	 * @param string fieldname Name of parameter
	 * @return mixed
	 */
	public function addfield($value,$fieldname = '');
	
	/**
	 * Does the field exist?
	 *
	 * If a field with the name specified by $fieldname exists, return true. Else, return false/
	 *
	 * @param string $fieldname Name of field to search for
	 * @return boolean
	 */
	public function fieldisset($fieldname);
	
	/**
	 * Update the value of field $fieldname to $value.
	 *
	 * If the field does not exist, add it.
	 *
	 * @param string $fieldname Name of field to update
	 * @param string $value Value to update to
	 * @return mixed
	 */
	public function updatefield($fieldname, $value);
	
	/**
	 * @return Template
	 */
	public function get_original_template();
}