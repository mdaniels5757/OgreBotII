<?php

class Page_Parser {
	
	/**
	 * 
	 * @var Unparsed_Element_Type[]
	 */
	private static $elements_types;
	
	/**
	 *
	 * @var string
	 */
	private $text;
	
	
	/**
	 * 
	 * @var string[]
	 */
	private $elements;
	
	/**
	 *
	 * @param string $text        	
	 */
	public function __construct($text) {
		if (self::$elements_types === null) {
			self::$elements_types = Classloader::get_all_instances_of_type("Unparsed_Element_Type");
		}
		
		$start = 0;
		$elements = array();
		while (1) {
			$next = false;
			foreach (self::$elements_types as &$element_type_this) {
				$next_this = $element_type_this->find_next($text, $start);
				if ($next === false || ($next_this !== false && $next_this < $next)) {
					$next = $next_this;
					$element_type = $element_type_this;
				}
			}
			
			if ($next === false) {
				break;
			}
			
			$index = count($elements);
			
			$end = $element_type->find_close($text, $next);
			$replace_string = $element_type->get_replace_string();
			$no_parse_text = "%%%MTONOPARSE_$replace_string$index%%%";
			if ($end !== false) {
				$element_text = substr($text, $next, $end - $next);
				$text = substr($text, 0, $next) . $no_parse_text . substr($text, $end);
			} else {
				$element_text = substr($text, $next);
				$text = substr($text, 0, $next) . $no_parse_text;
			}
			$element_type->modify_text($element_text);
			
			$start = $next + strlen($no_parse_text);
			$elements[] = $element_text;
		}
		
		$this->text = $text;
		$this->elements = $elements;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}
	
	/**
	 * 
	 * @param string $text
	 * @return void
	 */
	public function set_text($text) {
		$this->text = $text;
	}
	
	/**
	 *
	 * @param string $var        	
	 * @return mixed
	 */
	public function __get($var) {
		return $this->$var;
	}
	
	/**
	 *
	 * @param string $var        	
	 * @param mixed $val        	
	 * @return void
	 */
	public function __set($var, $val) {
		$this->$var = $val;
	}
	
	/**
	 *
	 * @param string $callback        	
	 * @return void
	 */
	public function modify_links_for_project(Project_Data $project_data) {
		global $MB_WS_RE_OPT;
		
		preg_match_all(
			"/\[\[$MB_WS_RE_OPT:?$MB_WS_RE_OPT(.+?)$MB_WS_RE_OPT\]\]/u", 
			$this->text, 
			$matches, 
			PREG_OFFSET_CAPTURE);
		
		for($i = count($matches[0]) - 1; $i >= 0; $i--) {
			$linktext = $matches[1][$i][0];
			
			// relink
			if (preg_match("/^(.+?)$MB_WS_RE_OPT\|$MB_WS_RE_OPT(.+?)$/u", $linktext, $match)) {
				$linktext = $project_data->formatPageLink($match[1], $match[2]);
			} else {
				$linktext = $project_data->formatPageLinkAuto($linktext);
			}
			
			// save results to string
			$this->text = substr($this->text, 0, $matches[0][$i][1]) . $linktext . substr(
				$this->text, 
				$matches[0][$i][1] + strlen($matches[0][$i][0]));
		}
	}
	/**
	 *
	 * @return void
	 */
	public function unparse() {
		foreach ($this->elements as $i => $element) {
			$this->text = preg_replace(
				"/%%%MTONOPARSE_[A-Z]+$i%%%/", 
				escape_preg_replacement($element), 
				$this->text, 
				1);
		}
	}
}