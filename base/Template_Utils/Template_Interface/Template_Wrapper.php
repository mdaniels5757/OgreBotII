<?php
/**
 * 
 * @author magog
 *
 */
class Template_Wrapper implements Abstract_Template {
	
	/**
	 * The original template
	 * 
	 * @var Template
	 */
	private $template;
	
	/**
	 *
	 * @var Template_Logic[]
	 */
	private $template_logics;
	
	
	/**
	 *
	 * @param Template $template        	
	 * @param Template_Logic[] $template_logics        	
	 */
	public function __construct(Template $template, array $template_logics) {
		$this->template = $template;
		$this->template_logics = $template_logics;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::__get()
	 */
	public function __get($var) {
		return $this->template->__get($var);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::__set()
	 */
	public function __set($var, $val) {
		return $this->template->__set($var, $val);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::__toString()
	 */
	public function __toString() {
		return $this->template->__toString();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::wholePage()
	 */
	public function wholePage() {
		return $this->template->wholePage();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::fieldvalue()
	 */
	public function fieldvalue($field_name, $trim_fieldname = true) {
		$field_name = $this->locate_field($field_name);
		if ($field_name !== null) {
			return $this->template->fieldvalue($field_name);
		}
		return false;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see Abstract_Template::first_field_value()
	 */
	public function first_field_value(array $fieldnames): ?string {
		foreach ($fieldnames as $field_name) {
			$field_name = $this->locate_field($field_name);
			if ($field_name !== null) {
				return $this->template->fieldvalue($field_name);
			}			
		}
		return null;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::renamefield()
	 */
	public function renamefield($old_name, $new_name) {
		$old_name = $this->locate_field($old_name);
		if ($old_name !== null) {
			$this->template->renamefield($old_name, $new_name);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::removefield()
	 */
	public function removefield($field_name) {
		$field_name = $this->locate_field($field_name);
		if ($field_name !== null) {
			$this->template->removefield($field_name);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::rename()
	 */
	public function rename($newname) {
		$this->template->rename($newname);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::getname()
	 */
	public function getname() {
		return $this->template->getname();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::addfield()
	 */
	public function addfield($value, $field_name = '') {
		$new_field_name = $this->locate_field($field_name);
		if ($new_field_name !== null) {
			$field_name = $new_field_name;
		}
		foreach ($this->template_logics as $template_logic) {
			$value = $template_logic->write_value($value, $field_name, $this->template);
		}
		return $this->template->updatefield($field_name, $value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::fieldisset()
	 */
	public function fieldisset($field_name) {
		return $this->locate_field($field_name) !== null;		
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::updatefield()
	 */
	public function updatefield($field_name, $value) {
		$new_field_name = $this->locate_field($field_name);
		if ($new_field_name !== null) {
			$field_name = $new_field_name;
		}
		foreach ($this->template_logics as $template_logic) {
			$value = $template_logic->write_value($value, $field_name, $this->template);
		}
		return $this->template->updatefield($field_name, $value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::get_original_template()
	 */
	public function get_original_template() {
		return $this->template;
	}
	
	/**
	 * 
	 * @param string $field
	 * @return string
	 */
	private function locate_field($field) {
		if ($this->template->fieldisset($field)) {
			return $field;
		}
		
		foreach ($this->template_logics as $template_logic) {
			$eligible_names = $template_logic->get_field_name_map($field, $this->template);
			foreach ($eligible_names as $eligible_name) {
				if ($this->template->fieldisset($eligible_name)) {
					return $eligible_name;
				}
			}
		}
	}
}

?>