<?php
/**
 * 
 * @author magog
 *
 */
class Array_Utils {
	
	/**
	 * 
	 * @param array $array
	 * @return mixed
	 */
	public static function first(array $array) {
		return current($array);
	}
	/**
	 *
	 * @param array $array
	 * @return mixed
	 */
	public static function last(array $array) {
		return end($array);
	}
	
	/**
	 * A pass-by-val splice alternative
	 * @param array $input
	 * @param int $offset
	 * @param int $length [optional] 
	 * @param array $replacement [optional] 
	 * @return array
	 */
	public static function splice(array $input, $offset, $length = null, array $replacement = null) {
		array_splice($input, $offset, $length, $replacement);
		return $input;
	}
	/**
	 * A pass-by-val map_array alternative
	 *
	 * @param array $array
	 * @param string[]|string $_,...
	 * @return array
	 */
	public static function map_array(array $array, $_) {
		if (!is_array($_)) {
			$_ = array_slice(func_get_args(), 1);
		}
		return map_array($array, $_);
	}
}
/**
 * 
 * @param array $haystack
 * @param mixed $needle
 * @param bool $strict
 * @return string
 */
function array_val_to_key($haystack, $needle, $strict = false) {
	if ($strict) {
		foreach ($haystack as $key => $val) {
			if ($val === $needle) {
				return $key;
			}
		}
	} else {
		foreach ($haystack as $key => $val) {
			if ($val == $needle) {
				return $key;
			}
		}
	}
	return null;
}

/**
 *
 * @param mixed $needle        	
 * @param array $haystack        	
 * @param bool $strict        	
 * @return bool
 */
function in_array_recurse($needle, array $haystack, bool $strict = NULL) {
	foreach ($haystack as $instance) {
		if (is_array($instance)) {
			if (in_array_recurse($needle, $instance, $strict)) {
				return true;
			}
		} else if (($strict && $instance === $needle) || (!$strict && $instance == $needle)) {
			return true;
		}
	}
	return false;
}

/**
 *
 * @param array $arg,...        	
 * @param string $_,...        	
 * @return mixed
 */
function array_key_or_null(&$arg, $_) {
	$func_args = func_get_args();
	array_shift($func_args);
	return _array_key_or_value($arg, null, $func_args);
}

/**
 *
 * @param array $arg,...        	
 * @param string $arg,...        	
 * @return mixed
 */
function array_key_or_blank(&$arg, $_) {
	$func_args = func_get_args();
	array_shift($func_args);
	return _array_key_or_value($arg, "", $func_args);
}

/**
 *
 * @param array $arg,...        	
 * @param string $arg,...        	
 * @return mixed
 */
function array_key_or_empty(&$arg, $_) {
	$func_args = func_get_args();
	array_shift($func_args);
	return _array_key_or_value($arg, [], $func_args);
}

/**
 *
 * @deprecated
 *
 * @see _array_key_or_value()
 * @param mixed[] $array        	
 * @param string[]|string $args        	
 * @param mixed $default        	
 * @return mixed
 */
function array_key_or_value($array, $args, $default = null) {
	return _array_key_or_value($array, $default, $args);
}

/**
 * Same as array_key_or_value, but with the arguments reshuffled to
 * take advantage of varargs
 * 
 * @see array_key_or_value()
 * @param mixed[] $array        	
 * @param mixed $default        	
 * @param mixed $arg,...        	
 * @return mixed
 */
function _array_key_or_value($array, $default, $args) {
	if (!is_array($args)) {
		$args = array_slice(func_get_args(), 2);
	}
	foreach ($args as $arg) {
		if (!is_array($array) || !isset($array[$arg])) {
			return $default;
		}
		$array = $array[$arg];
	}
	return $array;
}

/**
 * Same as array_key_or_exception, except the array is passed by ref
 * for performance reasons.
 * 
 * @param array $element        	
 * @param array $args        	
 * @throws ArrayIndexNotFoundException
 * @return mixed
 */
function _array_key_or_exception($element, array $args) {
	if (count($args) === 0) {
		return $element;
	}
	
	$arg = array_shift($args);
	if (!is_array($element) || !isset($element[$arg])) {
		throw new ArrayIndexNotFoundException(print_r($arg, true));
	}
	return _array_key_or_exception($element[$arg], $args);
}

/**
 *
 * @param array $array        	
 * @param mixed $arg,...        	
 * @return mixed
 * @throws ArrayIndexNotFoundException
 */
function array_key_or_exception($array, $args) {
	if (!is_array($args)) {
		$args = array_slice(func_get_args(), 1);
	}
	return _array_key_or_exception($array, $args);
}

/**
 * Get the element type of the first element of an array, or
 * null if it does not exist
 * 
 * @param array $array        	
 */
function array_element_type(array &$array) {
	if (count($array) === 0) {
		return "NULL";
	}
	
	reset($array);
	$type = gettype(current($array));
	reset($array);
	
	return $type;
}

/**
 *
 * @param array $array        	
 * @param mixed $_,...        	
 * @return boolean
 */
function array_keys_exist($array, $_) {
	try {
		array_keys_exist_exception($array, $_ = array_slice(func_get_args(), 1));
		return true;
	} catch (ArrayIndexNotFoundException $e) {
		return false;
	}
}

/**
 *
 * @param array $array        	
 * @param mixed $_,...        	
 * @return void
 * @throws ArrayIndexNotFoundException
 */
function array_keys_exist_exception($array, $_) {
	if (!is_array($_)) {
		$_ = array_slice(func_get_args(), 1);
	}
	
	foreach ($_ as $arg) {
		if (!array_key_exists($arg, $array)) {
			throw new ArrayIndexNotFoundException(print_r($arg, true));
		}
	}
}

/**
 *
 * @param array $array        	
 * @param mixed $args        	
 * @return bool
 */
function deep_array_key_exists($array, $args) {
	if (!is_array($args)) {
		$args = array_slice(func_get_args(), 1);
	}
	foreach ($args as $arg) {
		if (!is_array($array) || !isset($array[$arg])) {
			return false;
		}
		$array = $array[$arg];
	}
	return true;
}

/**
 *
 * @param array $array        	
 * @param int $index        	
 * @return number
 */
function findNextIndexNumeric(&$array, $index) {
	$index += 1;
	
	if (!isset($array[$index])) {
		return $index;
	}
	return findNextIndexNumeric($array, $index);
}

/**
 * a function written before I knew that array_merge_recursive exists in the PHP library
 * 
 * @param array $arr1        	
 * @param array $arr2        	
 * @param int $action        	
 * @return array
 */
function array_merge_recursive_new_index_if_numeric($arr1, $arr2) {
	global $logger, $validator;
	
	if ($arr1 === null) {
		$arr1 = [];
	}
	if ($arr2 === null) {
		$arr2 = [];
	}
	
	// validate types
	$validator->validate_arg($arr1, "array", true);
	$validator->validate_arg($arr2, "array", true);
	
	// debug output
	if ($logger->isInsaneEnabled()) {
		$arr1String = "array[" . count($arr1) . "]";
		$arr2String = "array[" . count($arr2) . "]";
		$logger->insane("array_merge_recursive_new_index_if_numeric($arr1String, $arr2String)");
	}
	
	foreach ($arr2 as $index => $value2) {
		
		// case 1: index doesn't exist in 1
		if (!isset($arr1[$index])) {
			$arr1[$index] = $value2;
		} else {
			$value1 = $arr1[$index];
			
			if (is_array($value1)) {
				// case 2: it's an array in one, and in two
				if (is_array($value2)) {
					if ($value1 !== $value2 && is_numeric($index)) {
						$newIndex = findNextIndexNumeric($arr1, $index);
						$arr1[$newIndex] = $value2;
					} else {
						$arr1[$index] = array_merge_recursive_new_index_if_numeric($value1, $value2);
					}
				} else {
					// case 3: one is an array, two isn't; append two
					$arr1[$index][] = $value2;
				}
			} else {
				if (is_array($value2)) {
					// case 4: one is not an array, two is => merge
					$arr1[$index] = $value2;
					array_unshift($arr1[$index], $value1);
				} else if ($value1 !== $value2 && is_numeric($index)) {
					// case 5: neither is an array
					$newIndex = findNextIndexNumeric($arr1, $index);
					$arr1[$newIndex] = $value2;
				} // else: do nothing; value is already present
			}
		}
	}
	return $arr1;
}

/**
 * Find a command line argument.
 * If found, the function will return the
 * argument's value after = (or an empty string if none exists). If not
 * found, null is returned. The argument is then removed from the array.
 *
 * @param string[] $array        	
 * @param string $param        	
 * @param bool $required
 *        	DEFAULT false
 * @param bool $case_sensitive
 *        	DEFAULT true
 * @param mixed $default
 *        	only relevant if $required is false
 * @return string|null
 */
function find_command_line_arg(array &$array, $param, $required = false, $case_sensitive = true, 
	$default = null) {
	global $validator;
	
	$validator->validate_arg($param, "string");
	$validator->validate_arg($case_sensitive, "bool");
	$validator->validate_arg($required, "bool");
	
	$param_quote = preg_quote($param, '/');
	$regex = "/^\-\-$param_quote(?:\=(.*?))?$/";
	
	if (!$case_sensitive) {
		$regex .= "i";
	}
	
	foreach ($array as $i => $arg) {
		if (preg_match($regex, $arg, $preg_match)) {
			array_splice($array, $i, 1);
			
			$match = @$preg_match[1] !== null ? $preg_match[1] : "";
			return $match;
		}
	}
	
	if ($required) {
		throw new IllegalStateException("Can't find command line argument: $param");
	}
	
	return $default;
}

/**
 * Prune an array so each elements is only its subindex
 * 
 * @param array $array        	
 * @param string[]|string $_
 *        	,...
 * @return array
 */
function map_array(array &$array, $_) {
	$indices = is_array($_) ? $_ : array_slice(func_get_args(), 1);
	return array_map(
		function (&$val) use(&$indices) {
			return array_key_or_exception($val, $indices);
		}, $array);
}

/**
 *
 * @param array $array        	
 * @param callable $callback        	
 * @param bool|string $collision        	
 * @param string $warn DEFAULT "none"; only relevant if $collision != "FAIL"
 * @param bool $email_warn DEFAULT false
 * @return array
 * @throws IllegalStateException
 */
function map_array_function_keys(array $array, $callback, $collision = "OVERWRITE", $warn = "NONE", 
	$email_warn = false) {
	global $logger;
	
	$new_array = [];
	
	//deprecated boolean feature
	if ($collision === true) {
		$collision = "IGNORE";
	} else if ($collision === false) {
		$collision = "OVERWRITE";
	}
	
	foreach ($array as $key => $val) {
		$callback_val = $callback($val, $key);
		
		if ($callback_val !== null) {
			list($key, $val) = $callback_val;
			if ($key !== null) {
				if (!isset($new_array[$key])) {
					$new_array[$key] = $val;
				} else {
					if ($collision === "FAIL") {
						throw new IllegalStateException("Not unique to array: $key");
					}
									
					$warning = null;
					if ($warn === "ALL") {
						$warning = "Array collision on key $key, val $val";
					} else if ($warn === "NOTEQUAL") {
						$prev = $new_array[$key];
						if ($prev !== $val) {
							$warning = "Array collision on key $key, prev-val $prev, val $val";
						}
					}
					
					if ($warning) {
						if ($email_warn) {
							ogrebotMail($warning);
						} else {
							$logger->warn($warning);
						}
					}
					
					if ($collision === "OVERWRITE") {
						$new_array[$key] = $val;
					}
				}
			}
		}
	}
	
	return $new_array;
}

/**
 *
 * @param array $array        	
 * @param callable $callback        	
 * @return array[]
 */
function map_array_all(array $array, $callback) {
	$new_array = [];
	foreach ($array as $key => $val) {
		$callback_val = $callback($val, $key);
		
		if ($callback_val !== null) {
			list($key, $val) = $callback_val;
			if ($key !== null) {
				if (!isset($new_array[$key])) {
					$new_array[$key] = [];
				}
				$new_array[$key][] = $val;
			}
		}
	}
	
	return $new_array;
}

/**
 * Because PHP inexplicably doesn't pass the key in array_map...
 * 
 * @param array $array        	
 * @param callable $callback        	
 * @return array
 */
function array_map_pass_key(array $array, $callback) {
	$new_array = [];
	foreach ($array as $key => $val) {
		$callback_val = $callback($key, $val);
		if ($callback_val !== null) {
			$new_array[$key] = $callback_val;
		}
	}
	
	return $new_array;
}

/**
 *
 * @param array $array        	
 * @param string $callback        	
 * @param bool $key_only        	
 * @return array
 */
function array_filter_use_keys(array $array, $callback = null, $key_only = false) {
	if (_PHP_MINOR_VERSION >= 5.6) {
		return array_filter($array, $callback, 
			$key_only ? ARRAY_FILTER_USE_KEY : ARRAY_FILTER_USE_BOTH);
	}
	
	if ($key_only) {
		foreach ($array as $key => $val) {
			if (!$callback($key)) {
				unset($array[$key]);
			}
		}
	} else {
		foreach ($array as $key => $val) {
			if (!$callback($val, $key)) {
				unset($array[$key]);
			}
		}
	}
	
	return $array;
}

/**
 *
 * @param array $array        	
 * @param callable $map_callback        	
 * @param callable|null $filter_callback
 *        	DEFAULT null
 * @return array
 */
function array_map_filter(array $array, $map_callback, $filter_callback = null) {
	if ($filter_callback === null) {
		
		/*
		 * PHP documentation says we should be able to pass a null $filter_callback
		 * to array_filter, and yet PHP still emits a warning...
		 */
		$filter_callback = function ($val) {
			return $val !== null && $val !== false;
		};
	}
	return array_filter(array_map($map_callback, $array), $filter_callback);
}

/**
 *
 * @param array $array        	
 * @param string[] $keys        	
 * @throws AssertionFailureException if the keys do not perfectly map to the keys of $array
 * @return array
 */
function &array_sort_custom(array $array, array $keys) {	
	$new_array = [];
	foreach ($keys as $key) {
		if (!isset($array[$key])) {
			throw new AssertionFailureException("Key not found in array: $key");
		}
		$new_array[$key] = $array[$key];
	}
	return $new_array;
}

/**
 *
 * @param array[] $array        	
 * @return array
 */
function array_merge_all(array $array) {
	if (count($array) === 0) {
		return [];
	}
	return call_user_func_array("array_merge", $array);
}

/**
 *
 * @param array[] $array        	
 * @return array
 * @throws Array_Merge_Conflict_Exception
 */
function array_merge_no_conflicts(array $array) {
	
	$total_count = array_sum(
		array_map(function ($sub_array) {
			return count($sub_array);
		}, $array));
	
	$merged = array_merge_all($array);
	
	// conflict found... locate where the problem is
	if ($total_count !== count($merged)) {
		$new_merged = [];
		foreach ($array as $sub_array) {
			$intersect = array_intersect_key($new_merged, $sub_array);
			foreach ($intersect as $key => $value) {
				if ($new_merged[$key] !== $sub_array[$key]) {
					throw new Array_Merge_Conflict_Exception($key, $new_merged[$key], 
						$sub_array[$key]);
				}
			}
			$new_merged = array_merge($new_merged, $sub_array);
		}
	}
	
	return $merged;
}

/**
 * Extract the parameters of an associative array into a numbered array
 * 
 * @param array $array        	
 * @param string[]|string $_ ,...
 * @return array
 * @throws ArrayIndexNotFoundException
 */
function extract_array_params($array, $_) {
	if (is_array($_)) {
		$keys = array_values($_);
	} else {
		$keys = array_slice(func_get_args(), 1);
	}
	
	return (new Array_Parameter_Extractor(array_fill_keys($keys, true)))->extract($array);
}

/**
 * Search an array for an item using a callback to determine if the item was found.
 * 
 * @param array $array        	
 * @param callable $callback
 *        	the callable should return true if found, false if not
 * @param bool $exception
 *        	throw an exception if the index wasn't found, rather than returning null
 * @param bool $return_key
 *        	return the key rather than the value
 * @throws ArrayIndexNotFoundException
 * @return string|null the index of the item found, or null if not found
 */
function array_search_callback(array $array, callable $callback, $exception = true, $return_key = false, 
	$user_data = null) {
	foreach ($array as $key => $value) {
		if ($callback($value, $key, $user_data)) {
			return $return_key ? $key : $value;
		}
	}
	
	if ($exception) {
		throw new ArrayIndexNotFoundException("[search by callable]");
	}
}

/**
 *
 * @param int $byte        	
 * @return int[]
 */
function get_bit_values($byte) {
	global $logger, $validator;
	
	$validator->validate_arg($byte, "integer");
	$validator->validate_args_condition($byte, $byte >= 0, "\$byte >= 0");
	
	$logger->debug("get_bit_values($byte)");
	
	$vals = [];
	for($pos = 0; $byte !== 0; $pos++) {
		$val = 1 << $pos;
		
		if ($byte & $val) {
			$vals[] = $val;
			$byte &= ~$val;
		}
	}
	
	$logger->debug("Returning " . implode($vals));
	
	return $vals;
}

/**
 *
 * @param array $array        	
 * @param array $keys        	
 * @return array
 */
function prune_array_to_keys(array $array, array $keys) {
	return array_intersect_key($array, array_flip($keys));
}

/**
 * Emulate a PHP backtrace (reasonably close approximation) (as of PHP 5.5)
 * 
 * @param array|null $backtrace_array
 *        	If null, the function will fill in
 *        	the backtrace with option DEBUG_BACKTRACE_IGNORE_ARGS. DEFAULT null.
 * @return string
 */
function get_backtrace_string(array $backtrace_array = null) {
	if ($backtrace_array === null) {
		$backtrace_array = get_backtrace(false, 2);
	}
	$formatted_backtrace_array = array_map_pass_key($backtrace_array, 
		function ($index, $element) {
			if (isset($element['args'])) {
				$args = array_map(
					function (&$arg) {
						if (is_null($arg)) {
							return "NULL";
						}
						
						if (is_array($arg)) {
							return "Array(" . count($arg) . ")";
						}
						
						if (is_bool($arg)) {
							return $arg ? "true" : "false";
						}
						
						if (is_object($arg)) {
							return "Object(" . get_class($arg) . ")";
						}
						
						if (is_string($arg)) {
							if (strlen($arg) > 18) {
								$string_arg = substr($arg, 0, 14) . " ...";
							} else {
								$string_arg = $arg;
							}
							return "'" . strtr($string_arg, 
								["'" => "\\'", "\\" => "\\\\", "\r" => "\\r", "\n" => "\\n"]) .
								 "'";
						}
						
						// Array, reference, number, etc.
						return "$arg";
					}, $element['args']);
				$args_string = implode(", ", $args);
			} else {
				$args_string = "";
			}
			$file = array_key_or_value($element, "file", "<unknown>");
			$file = preg_replace("/^.+\//", "", $file);
			$line = @$element["line"];
			$class = @$element['class'];
			$type = @$element['type'];
			return "#$index $file($line): $class$type$element[function]($args_string)";
		});
	
	$formatted_backtrace_array[] = "#" . count($formatted_backtrace_array) . " {main}";
	
	return implode("\n", $formatted_backtrace_array);
}

define("PREG_MATCH_ARRAY_ALL", 1);
/**
 *
 * @param string[] $regexes        	
 * @param string $subject        	
 * @param array $match
 * @param int $flags
 * @return mixed the index of the array on which the callback succeeded, or false if
 *         not found
 */
function preg_match_array($regexes, $subject, array &$match = null, $flags = null) {
	$callback = $flags & PREG_MATCH_ARRAY_ALL ? "preg_match_all" : "preg_match";
	$flags = $flags & ~PREG_MATCH_ARRAY_ALL;
	foreach ($regexes as $index => $regex) {
		if ($callback($regex, $subject, $match, $flags)) {
			return $index;
		}
	}
	return false;
}

/**
 *
 * @param array $array        	
 * @param int|string $item        	
 * @return int|string|false The key of the removed item, or false if null is found.
 */
function array_remove(&$array, $item) {
	foreach ($array as $key => $val) {
		if ($item === $val) {
			unset($array[$key]);
			return $key;
		}
	}
	return false;
}

/**
 *
 * @param bool $use_args
 *        	DEFAULT true
 * @param int $start
 *        	DEFAULT 1
 * @param int|null $limit
 *        	DEFAULT null
 */
function get_backtrace($use_args = true, $start = 1, $limit = null) {
	if ($limit !== null) {
		$limit += $start - 1;
	}
	
	// don't need the object
	$options = $use_args ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS;
	
	return array_slice(debug_backtrace($options, $limit), $start);
}