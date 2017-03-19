<?php

class Generate_Getters_Setters {

	/**
	 * 
	 * @var string
	 */
	const unknown_type = "unknown_type";
	
	/**
	 * 
	 * @param string $text
	 * @return string[][]
	 */
	private static function get_variables ($text) {
		static $variable_regex = "[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\[\])*";
		
		
		preg_match_all("/^(?:\s*\/\**\*+\r?\n(?:\s*\*\s*\r?\n)*\s*\*\s*@var\s+($variable_regex(?:\|$variable_regex)*)\s*\r?\n".
				"(?:\s*\*\s*\r?\n)*\s*\*\/)?\s*(?:private|protected|public)\s+\\$($variable_regex(?:\|$variable_regex)*)\s*".
				"(?:\=\s*[^;]+\s*)?;/m",
				$text, $matches, PREG_SET_ORDER);
		
		$variables = array();
		foreach ($matches as $match) {
			$variable_type = $match[1];

			if (!$variable_type) {
				$variable_type = self::unknown_type;
			}
			$variable_name = $match[2];
			
			$variables[] = array($variable_type, $variable_name);
		}
		return $variables;
	}
	
	/**
	 *
	 * @param string $text
	 * @param boolean $include_setters
	 * @param boolean $use_camel_case
	 * @param boolean constructor
	 * @return string
	 */
	public static function generate_from_text($text, $include_setters, $use_camel_case, $constructor) {
		$variables = self::get_variables($text);
		
		$string = "";

		if ($constructor) {
			$names = array();
			$string.=<<<EOF
	/**
	 *	 
EOF;
			foreach ($variables as $variable_array) {
				$type = $variable_array[0];
				$name = $variable_array[1];
				$names[] = "\$$name";
				$string.=<<<EOF
				
	 * @param $type \$$name	  
EOF;
			}
			$all_names = implode(", ", $names);
			$string.=<<<EOF

	 * @return void
	 */
	public function __construct($all_names) {
EOF;
			foreach ($variables as $variable_array) {
				$type = $variable_array[0];
				$name = $variable_array[1];
				$string.=<<<EOF

		\$this->$name = \$$name;
EOF;
			}
			$string.= <<<EOF

	}
EOF;
		}

		foreach ($variables as $variable) {
			$variable_type = $variable[0];
			$variable_name = $variable[1];

			if ($use_camel_case) {
				$transformed = ucfirst_utf8($variable_name);
			} else {
				$transformed = "_$variable_name";
			}

			//getter
			$string.=<<<EOF

	/**
	 *
	 * @return $variable_type
	 */
	public function get$transformed() {
		return \$this->$variable_name;
	}

EOF;
			if ($include_setters) {
				$string.=<<<EOF

	/**
	 *
	 * @param $variable_type \$$variable_name
	 * @return void
	 */
	public function set$transformed(\$$variable_name) {
		global \$validator;
		\$validator->validate_arg(\$$variable_name, "$variable_type");
		
		\$this->$variable_name = \$$variable_name;
	}

EOF;
			}
		}

		return $string;
	}
}