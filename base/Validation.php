<?php
class Validation {
	/**
	 *
	 * @param bool $condition        	
	 * @param string $err_str        	
	 * @return void
	 * @throws AssertionFailureException
	 */
	public function assert($condition, $err_str = "See source code") {
		if (!$condition) {
			logger_or_stderr(LEVEL::ERROR, "Assertion failure: $err_str");
			throw new AssertionFailureException("Assertion failure: $err_str");
		}
		
		return true;
	}
	
	/**
	 *
	 * @param mixed $arg        	
	 * @param string $type        	
	 * @param bool $passed        	
	 * @return void
	 * @throws AssertionFailureException
	 */
	public function validate_args_condition($arg, $type, $passed) {
		$error = $this->_validate_args_condition($arg, $type, $passed);
		if ($error) {
			throw new AssertionFailureException($error);
		}
	}
	
	/**
	 *
	 * @param mixed $arg        	
	 * @param string $type        	
	 * @param bool $passed        	
	 * @return string|null
	 * @throws AssertionFailureException
	 */
	private function _validate_args_condition(&$arg, $type, $passed) {
		if ($passed) {
			return null;
		}
		return "Argument is not of type $type. Value: " . print_r($arg, true);
	}
	
	/**
	 *
	 * @param string $type        	
	 * @param mixed $_        	
	 * @return void
	 * @throws AssertionFailureException
	 */
	public function validate_args($type, $_) {
		$args = array_slice(func_get_args(), 1);
		foreach ($args as $arg) {
			$this->validate_arg($arg, $type, false);
		}
	}
	
	/**
	 *
	 * @param mixed $arg        	
	 * @param string $type        	
	 * @param boolean $allow_null        	
	 * @throws AssertionFailureException
	 */
	public function validate_arg($arg, $type, $allow_null = false) {
		$error = $this->_validate_arg($arg, $type, $allow_null);
		
		if ($error) {
			throw new AssertionFailureException($error);
		}
	}
	
	/**
	 *
	 * @param mixed $arg        	
	 * @param string $type        	
	 * @param bool $allow_null        	
	 * @return string|null
	 */
	private function _validate_arg(&$arg, $type, $allow_null = false) {
		static $valid_colors;
		if ($arg === null) {
			return $this->_validate_args_condition($arg, $type, $allow_null);
		}
		
		switch ($type) {
			case "float" :
				return $this->_validate_args_condition($arg, $type, is_float($arg) || is_int($arg));
			case "positive" :
				return $this->_validate_args_condition($arg, $type, is_numeric($arg) && $arg > 0);
			case "int" :
			case "integer" :
				return $this->_validate_args_condition($arg, $type, is_int($arg));
			case "number" :
			case "numeric" :
				return $this->_validate_args_condition($arg, $type, is_numeric($arg));
			case "bool" :
			case "boolean" :
				return $this->_validate_args_condition($arg, $type, is_bool($arg));
			case "array" :
				return $this->_validate_args_condition($arg, $type, is_array($arg));
			case "char" :
			case "character" :
				return $this->_validate_args_condition($arg, $type, 
					is_string($arg) && strlen($arg) == 1);
			case "function" :
			case "callable" :
				return $this->_validate_args_condition($arg, $type, 
					is_callable($arg) || $arg instanceof Closure);
			case "resource" :
				return $this->_validate_args_condition($arg, $type, is_resource($arg));
			case "string" :
			case "string-numeric" :
				return $this->_validate_args_condition($arg, $type, 
					is_string($arg) || is_numeric($arg));
			case "valid XML color":
				if ($valid_colors === null) {
					load_property_file_into_variable($svg_props, "svg");
					$valid_colors = array_key_or_exception($svg_props, "colors");
				}
				return $this->validate_args_condition($arg, $type, 
					preg_match("/^\#(?:[a-f\d]{3}|[a-f\d]{6})$/", $arg) ||
						 in_array($arg, $valid_colors));
		}
		return $this->_validate_args_condition($arg, $type, $arg instanceof $type);
	}
	
	/**
	 *
	 * @param mixed $arg        	
	 * @param string[] $types        	
	 * @param bool $allow_null        	
	 * @return void
	 * @throws AssertionFailureException
	 */
	public function validate_arg_multiple($arg, $types, $allow_null = false) {
		foreach ($types as $type) {
			if ($this->_validate_arg($arg, $type, $allow_null) === null) {
				return;
			}
		}
		throw new AssertionFailureException(
			"Argument is not of types [" . implode(",", $types) . "]. Value: " . print_r($arg, true));
	}
	
	/**
	 *
	 * @param array $array        	
	 * @param string $element_type        	
	 * @param bool $allow_null_array
	 *        	DEFAULT false
	 * @param bool $allow_null_elements
	 *        	DEFAULT false
	 * @param bool $allow_empty_array
	 *        	DEFAULT true
	 * @param int $level
	 *        	default 0
	 * @return void
	 * @throws AssertionFailureException
	 */
	public function validate_arg_array(&$array, $element_type, $allow_null_array = false, 
		$allow_null_elements = false, $allow_empty_array = true, $level = 0) {
		$this->validate_arg($array, "array", $allow_null_array);
		$this->validate_arg($element_type, "string", false);
		$this->validate_arg($allow_null_array, "boolean", false);
		$this->validate_arg($level, "int", false);
		
		if ($array !== null) {
			if (!$allow_empty_array && count($array) == 0) {
				throw new AssertionFailureException("Empty array");
			}
			
			$allow_null = is_array($allow_null_elements) ? array_shift($allow_null_elements) : $allow_null_elements;
			$this->validate_arg($allow_null, "boolean", false);
			if ($level === 0) {
				foreach ($array as $element) {
					$this->validate_arg($element, $element_type, $allow_null);
				}
			} else {
				foreach ($array as $element) {
					$this->validate_arg_array($element, $element_type, $allow_null, 
						$allow_null_elements, $level - 1);
				}
			}
		}
	}
}