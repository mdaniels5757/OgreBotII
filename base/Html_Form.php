<?php
class Html_Form {
	
	/**
	 *
	 * @var string
	 */
	private $action;
	
	/**
	 * get or post
	 * 
	 * @var string
	 */
	private $method;
	
	/**
	 * hidden input fields and their values
	 * 
	 * @var string[]
	 */
	private $input_fields = [];
	public function __construct($action = null, $method = 'get', $inputs = []) {
		$this->action = $action;
		$this->set_method($method);
		$this->add_input_fields($inputs);
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_action() {
		return $this->action;
	}
	
	/**
	 *
	 * @param string $action        	
	 * @return void
	 */
	public function set_action($action) {
		$this->action = $action;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}
	
	/**
	 *
	 * @param string $method        	
	 * @return void
	 */
	public function set_method($method) {
		global $validator;
		$validator->validate_args_condition($method, "'post' or 'get'", 
			$method === 'post' || $method === 'get');
		$this->method = $method;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function get_input_fields() {
		return $this->input_fields;
	}
	
	/**
	 * @param bool $type default = "hidden"
	 * @return string
	 */
	public function get_input_fields_as_string($type = "hidden") {
		return implode("", 
			array_map_pass_key($this->input_fields, 
				function ($name, $val) use ($type) {
					$string = "<input type=\"$type\" name=\"" . sanitize($name) . "\"";
					if ($val !== null) {
						$string .= " value=\"" . sanitize($val) . "\"";
					}
					$string .= " />";
					
					return $string;
				}));
	}
	
	/**
	 *
	 * @param string|null $value        	
	 * @param string $key        	
	 * @return void
	 */
	public function add_input_field($value, $key) {
		global $validator;
		
		$validator->validate_arg($key, "string");
		$validator->validate_arg($value, "string", true);
		
		$this->input_fields[$key] = $value;
	}
	
	/**
	 *
	 * @param string[] $fields        	
	 * @return void
	 */
	public function add_input_fields($fields) {
		global $validator;
		
		$validator->validate_arg($fields, "array");
		
		array_walk($fields, [$this, 'add_input_field']);
	}
	
	/**
	 *
	 * @param string $url        	
	 * @param string $method        	
	 * @param bool $raw        	
	 * @throws IllegalArgumentException
	 * @return Html_Form
	 */
	public static function parse_from_url($url, $method = 'get', $raw = false) {
		global $validator;
		
		$validator->validate_arg($url, "string");
		$validator->validate_arg($method, "string");
		$validator->validate_arg($raw, "bool");
		
		$action = strstr($url, '?', true);
		if ($action !== false) {
			$end_params_string = substr(strstr($url, '?'), 1);
			if ($end_params_string) {
				$fields = map_array_function_keys(preg_split("/\&/", $end_params_string), 
					function ($string) use($raw) {
						$split = preg_split("/\=/", $string);
						if (!$split) {
							return;
						}
						$count = count($split);
						if ($count > 2) {
							throw new IllegalArgumentException(
								"Can't split with multiple =: $string");
						}
						
						$split =  array_map($raw ? "rawurldecode" : "urldecode", $split);
						if ($count === 1) {
							$split[1] = null;
						}
						
						return $split;
					}, "FAIL");
			} else {
				$fields = [];
			}
		} else {
			$action = $url;
		}
		
		return new Html_Form($action, $method, $fields);
	}
	
	/**
	 *
	 * @return string
	 */
	public function __toString() {
		return "<form action=\"$this->action\" method=\"$this->method\">" .
			 $this->get_input_fields_as_string() . "</form>";
	}
}