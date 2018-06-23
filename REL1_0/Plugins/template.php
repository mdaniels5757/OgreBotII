<?php

/**
 * @author Sam Korn <smoddy@gmail.com>
 * @author Soxred93 <soxred93@gmail.com>
 * @copyright Copyright (c) 2009, Sam Korn
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Modify and use templates
 *
 * Facilitate the isolation and editing of templates.
 *
 * @todo Convert this to Peachy standards
 */

class Template implements Abstract_Template {

	/**
	 * Text preceeding the template
	 * @var string
	 */
	private $before;
	/**
	 * Text of the template on creation
	 * @var string
	 */
	private $templatestring;
	/**
	 * Text that follows the template
	 * @var string
	 */
	private $after;
	/**
	 * Double curly-bracket and any white-space before the template's name
	 * @var string
	 */
	private $open;
	/**
	 * Name of template
	 * @var string
	 */
	private $name;
	/**
	 * Array of fields of the template
	 *
	 * The keys of the array are the names of the fields as they appear to MediaWiki.
	 *
	 * @var array
	 */
	private $fields;
	/**
	 * The double curly-brakced that closes the template
	 * @var string
	 */
	private $end;

	
	/**
	 * 
	 * @var string[]
	 */
	private static $template_search_regex_cache = [];
	
	/**
	 * Extract a template from a string
	 *
	 * Find the template $name in the string $text and build template from this.  Only
	 * the first occurence will be found -- others must be found using {@link Template::$after}.
	 *
	 * @param string $text Text to find template in
	 * @param string $name Name of the temlate to find
	 * @param int $offset Where to start parsing the document in looking for the template Magog 2012-12-24
	 */
	public function __construct($text,$name,$offset=0, $ignore_fieldname = false) {
		global $logger, $validator;
		
		$text = str_replace(["{{", "}}", "{", "}", "%%MTOBEGINBRACKETS%%", 
			"%%MTOENDBRACKETS%%"], 
			["%%MTOBEGINBRACKETS%%", "%%MTOENDBRACKETS%%", "%%MTOBEGINBRACKET%%", 
				"%%MTOENDBRACKET%%", "{{", "}}"], $text);
		
		$name_regex = @self::$template_search_regex_cache[$name];
		if ($name_regex === null) {
			//periodically clean the cache
			if (count(self::$template_search_regex_cache) > 1024) {
				self::$template_search_regex_cache = [];
			}
			$name_quote = preg_replace("/[ _]+/", "[ _]+", preg_quote($name, '/'));
			if ($name_quote[0] !== '\\') {
				$name_quote = "(?i:$name_quote[0])" . substr($name_quote,1);
			}
			$name_regex = "/\{\{\s*$name_quote\s*(?:(?:\|.*)|(?:\}.*))/s";
			self::$template_search_regex_cache[$name] = $name_regex;			
		}
		
		preg_match($name_regex,$text,$match, 0, $offset);
		
		if (isset($match[0])) {
			$from = strpos($text, $match[0], $offset);
		} else {
			return;
		}

		$i = 2;
		$counter = 2;

		$open = -1;
		$close = strpos($match[0], "}", $i);
		do {
			if ($close === false) {
				//template will never close properly
				break;
			}
			
			if ($open === false) {
				$close = strpos($match[0], "}", $i);
				if ($close === false) {
					//template will never close properly
					return;
				}
				$i = $close;
			} else if ($open < $close) {
				$open = strpos($match[0], "{", $i);
				if ($open === false) {
					$i = $close;
				} else {
					$i = $open < $close ? $open : $close;
				}
			} else {
				$close = strpos($match[0], "}", $i);
				if ($close === false) {
					//template will never close properly
					return;
				} 
				
				$i = $open < $close ? $open : $close;
			}
			
			if ($i === $open) {
				$counter++;
			} else {
				$counter--;
			}

			$i += 2;
		} while ($counter !== 1);
		
		$end = $i;

		$after = substr($match[0], $end);
		$this->templatestring = substr($text, $from, $end);
		if (!preg_match('/(\{\{\s*)([^|}]*)(.*)/s', $this->templatestring, $match)) {
			$logger->warn("templatestring doesn't match. Value: " . $this->templatestring);
			return;
		}

		list($this->before, $this->after, $this->open, $this->name, $this->templatestring) = str_replace(
			["%%MTOBEGINBRACKET%%", "%%MTOENDBRACKET%%"], ["{", "}"], 
			[substr($text, 0, $from), $after, $match[1], $match[2], $this->templatestring]);

		if (false === strpos($this->templatestring,'|')) {
			$this->fields = array();
			$this->end = '}}';
			return;
		}
		
		$subtemplate = 0;
		$current = '';
		$link = false;
		$ignored_letter = -1;
		$text = str_replace(["%%MTOENDBRACKET%%", "%%MTOBEGINBRACKET%%"], ["}", "{"], $text);
		$i = -1;
		$fields = [];
		$strlen = strlen($match[3]);
		while (($next_i = strcspn($match[3], "|{}=[]<", ++$i) + $i) < $strlen) {
			
			$next = $match[3][$next_i];
			$current .= substr($match[3], $i, $next_i - $i);
			$i = $next_i;
			
			switch ($next) {
				case '=':
					if ($subtemplate === 0) {
						/* ignore headers */
						if (@$match[3][$i - 1] === "\n" && @$match[3][$i + 1] === '=') {
							$strpos = strpos($match[3], "\n", $i);
							$test_str = substr($match[3], $i, $strpos ? $strpos - $i + 1 : 0);
							if (preg_match("/^\=\=.*?\=\=\s*\n$/", $test_str)) {
								$current .= $test_str;
								$i = $strpos;
								continue 2;
							}
						}
							
						//a "good" equals sign is one that we won't ignore during parsing; if the sign is located inside certain tags (e.g., math, ref, gallery),
						// then it is a "bad" one that is ignored by the software
						$current .= "%%%MTOGOODEQUALS%%%";
						continue 2;
					}
					break;
				case '<':
					$tag_search_substr = ltrim(substr($match[3], $i + 1));
					foreach (["gallery", "ref", "math", "poem"] as $needle) {
						if (stripos($tag_search_substr, $needle) === 0) {
							$following_char = $tag_search_substr[strlen($needle)];
							if ($following_char === '>' || ctype_space($following_char)) {
								try {
									$xml_type = Xml_Reader::simple_parse_element($match[3], $i);
								} catch (XmlError $e) {
									$logger->warn($e);
									throw new TemplateParseException("Can't parse XML");
								}
								if ($xml_type !== null && strtolower($xml_type->open_tag) === $needle) {
									$i = $xml_type->end_position;
									$current .= substr($match[3], $xml_type->start_position,
										$i - $xml_type->start_position);
									$i--;
									continue 3;
								}
							}
						}
					}
					break;
				case '{':
					$after = @$match[3][$next_i + 1];
					if ($after === '{') {
						$subtemplate++;
						$current .= "{";
						$i++;
					}
					break;
				case '}':
					$after = @$match[3][$next_i + 1];
					if ($after === '}') {
						$i++;
						if ($current && !$subtemplate && !$link) {
							$fields[] = $current;
							$current = '';
							continue 2;
						}
						$current .= "}";
						$subtemplate--;
					}
					break;
				case '|' :
					if (!$subtemplate && !$link) {
						if ($i) {
							$fields[] = $current;
							$current = '';
						}
						continue 2;
					}
					break;
				case ']' :
					$after = @$match[3][$next_i + 1];
					if ($after === ']' && $link) {
						$link = false;
						$current .= "]";
						$i++;
					}
					break;
				case '[' :
					$after = @$match[3][$next_i + 1];
					if ($after === '[' && !$link) {
						$link = true;
						$current .= "[";
						$i++;
					}
					break;
			}
			
			$current .= $next;
		}
		
		if ($link) {
			// unterminated link causing problems
			$fields[] = $current;
		}
		
		$i = 0;
		$this->fields = [];

		// this is actually impossible to set, so it will always indicate an empty field
		if ($fields) {
			$last = end($fields);
			if ($last === "%%MTOENDBRACKET%%" || $last === "}") {
				$fields[key($fields)] = '';
			}
		}
		$fields = str_replace(
			["%%MTOBEGINBRACKET%%", "%%MTOENDBRACKET%%", "=", "%%%MTOGOODEQUALS%%%"], 
			["{", "}", "%%%MTOBADEQUALS%%%", "="], $fields);
		
		foreach ($fields as $field) {
			if ($ignore_fieldname) {
				$array_key = ++$i;
			} else {
				$array_key = strstr($field, '=', true);
				if ($array_key !== false) {
					$array_key = trim($array_key);
				} else {
					$array_key = ++$i;
				}
			}
			
			// next 6 lines: in case the array key already exists, combine the two fields
			$field = str_replace('%%%MTOBADEQUALS%%%', '=', $field);
			if (isset($this->fields[$array_key])) {
				if (!$ignore_fieldname) {
					$quote = preg_quote($array_key, "/");
					$field = preg_replace("/\s*$quote\s*\=/", "", $field);
				}
				$field = $this->fields[$array_key] . $field;
			}
			
			$this->fields[$array_key] = $field;
		}
		$this->end = '}}';
	}

	public function __get($var) {
		return $this->$var;
	}
	public function __set($var,$val) {
		return $this->$var=$val;
	}

	public function __toString() {
		$return = $this->open;
		$return .= $this->name;
		if (is_array($this->fields)) {
			foreach ($this->fields as $field) {
				$return .= "|$field";
			}
		}
		$return .= $this->end;
		return $return;
	}

	/**
	 * Return the string used in {@link __construct()} with the new template.
	 *
	 * @return string
	 */
	public function wholePage() {
		return $this->before . ((string) $this) . $this->after;
	}

	/**
	 * Get the value of a field
	 *
	 * @param string $fieldname Name of the field to find
	 * @param bool $trim_fieldname Trim "fieldname=" from the beginning.
	 * @return string|boolean Value of template if it exists, otherwise boolean false
	 */
	public function fieldvalue($fieldname, $trim_fieldname=true) {
		if (isset($this->fields[$fieldname])) {
			$key = is_numeric($fieldname)?"(?:$fieldname\s*\=)?":(preg_quote($fieldname, "/")."\s*\=");
			$result = $this->fields[$fieldname];
			if ($trim_fieldname) {

				$result = preg_replace("/^\s*$key\s*?([\s\S]*?)$/", "$1", $result);
			}
			return $result;
		} else {
			return false;
		}
	}
	
	/**
	 *
	 * @param string $fieldname
	 *        	Make sure to always pass the value to the function as lcfirst
	 *        	and without underscores
	 * @return string|null
	 */
	public function information_style_fieldvalue($fieldname) {
		$value = $this->fieldvalue($fieldname);
		if ($value !== false) {
			return $value;
		}
		
		$ucfirst = ucfirst($fieldname);
		$value = $this->fieldvalue($ucfirst);
		if ($value !== false) {
			return $value;
		}
		
		$lcfirst_underscore = str_replace(" ", "", $fieldname);
		$value = $this->fieldvalue($lcfirst_underscore);
		if ($value !== false) {
			return $value;
		}
		
		$ucfirst_underscore = ucfirst($lcfirst_underscore);
		$value = $this->fieldvalue($ucfirst_underscore);
		if ($value !== false) {
			return $value;
		}
		
		return null;
	}

	/**
	 * Change the name of a field
	 *
	 * @param string $oldname Name of the field to migrate
	 * @param string $newname New name of the field
	 */
	public function renamefield($oldname,$newname) {
		foreach ($this->fields as $name => $field) {
			if ($name != $oldname) {
				$newfields[$name] = $field;
				continue;
			}

			$newfields[$newname] = preg_replace('/^(\s*)' . preg_quote($oldname,'/') . '(\s*=)/is',"$1" . $newname . "$2",$field);
		}
		$this->fields = $newfields;
	}

	/**
	 * Delete a field
	 *
	 * @param string $fieldname Name of field to delete
	 */
	public function removefield($fieldname) {
		unset($this->fields[$fieldname]);
	}

	/**
	 * Rename template
	 *
	 * @param string $newname New name of template
	 */
	public function rename($newname) {
		$this->name = $newname;
	}

	/**
	 * Get the name of the template
	 */
	public function getname() {
		return $this->name;
	}
	
	/**
	 * Add a field to the template
	 *
	 * If the fieldname is not given, the parameter will be added effectively as
	 * a numbered parameter.
	 *
	 * @param string $value Value of parameter
	 * @param string fieldname Name of parameter
	 */
	public function addfield($value,$fieldname = '') {
		if (!$fieldname) {
			$fieldname = (max(array_keys($this->fields)) + 1);
		} else {
			$this->fields[$fieldname] = $fieldname . ' = ';
		}

		return $this->fields[$fieldname] .= $value;
	}

	/**
	 * Does the field exist?
	 *
	 * If a field with the name specified by $fieldname exists, return true. Else, return false/
	 *
	 * @param string $fieldname Name of field to search for
	 * @return boolean
	 */
	public function fieldisset($fieldname) {
		if (isset($this->fields[$fieldname])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update the value of field $fieldname to $value.
	 *
	 * If the field does not exist, add it.
	 *
	 * @param string $fieldname Name of field to update
	 * @param string $value Value to update to
	 */
	public function updatefield($fieldname,$value) {
		if (!$this->fieldisset($fieldname)) {
			return $this->addfield($value,$fieldname);
		}

		$fieldval = $this->fieldvalue($fieldname, false);

		/* numerical, unmarked field value */
		if ($fieldname==1 && !preg_match("/^\s*1\s*\=/", $fieldval)) {
			$result = $value;
			if (stripos($result, "=")!==false) {
				$result="1=$result";
			}
		} else if (preg_match("/^\s*(\d+)\s*$/", $fieldname, $match) &&
				!preg_match("/^\s*$match[1]\s*\=/", $fieldval))  {
			/* numerical, unmarked field value past 1 */
			throw new TemplateParseException("Feature not supported");
		} else {
			$oldvalue = preg_replace("/^\s*".preg_quote($fieldname)."\s*\=/", "", $fieldval);

			//quote replace values
			$value = strtr($value, ["\\" => "\\\\", "$" => "\\$"]);

			// Magog 2012-05-25 fixed preg_quote delimiter and returning value now, "$1$value$2" => what you see below to avoid errors when $value starts with a number (e.g., 2 -> "$12").
			if(!preg_match('/^(.+?=\s*)' . preg_quote($oldvalue,'/') . '(\s*)/is', $fieldval)) {
				throw new TemplateParseException("Can't find fieldname \"$fieldname\" in field value \"$fieldval\" in template \"$this\"");
			}

	 		$result = preg_replace('/^(.+?=\s*)' . preg_quote($oldvalue,'/') . '(\s*)/is',"\${1}$value\${2}",$fieldval);
		}
		$this->fields[$fieldname] = $result;
		return $this->fields[$fieldname];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Abstract_Template::get_original_template()
	 */
	public function get_original_template() {
		return $this;
	}

	/**
	 * 
	 * @param string $text
	 * @param string $name
	 * @param int $offset
	 * @param bool $ignore_fieldname
	 * @return false|Template
	 */
	public static function extract ($text, $name, $offset=0, $ignore_fieldname=false /* for parser functions */) {
		$template = new Template ($text,$name,$offset, $ignore_fieldname);

		if (!$template->name) {
			unset($template);
			return false;
		} else {
			return $template;
		}
	}
}
