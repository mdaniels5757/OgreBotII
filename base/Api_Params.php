<?php
/**
 * 
 * @author magog
 *
 */
class Api_Params {
	
	/**
	 * 
	 * @var string[] associative array
	 */
	private $params;
	
	
	/**
	 * 
	 * @var string
	 */
	private $limit_param;
	
	/**
	 * 
	 * @var string
	 */
	private $index;
	
	/**
	 * 
	 * @var string the name of the title
	 */
	private $title;
	
	
	/**
	 * 
	 * @var string
	 */
	private $title_query;
	
	/**
	 * @var string[]
	 */
	private $top_index;
	
	/**
	 * 
	 * @var bool
	 */
	private $continue;
	
	/**
	 * 
	 * @var Wiki
	 */
	private $wiki;
	
	/**
	 * 
	 * @var int[]
	 */
	private static $limits = [];
	
	/**
	 * 
	 * @var Api_Params[][]
	 */
	private static $cache = [];
	
	/**
	 * 
	 * @var Api_Params[]
	 */
	private static $base_params;
	
	/**
	 * return Api_Params[]
	 */
	private static function parse_xml() {
		global $validator;
		
		$xml_data = XmlParser::xmlFileToStruct("api-params.xml");
		
		$defaults_xml = array_key_or_empty($xml_data, "API-PARAMS", 0, 'elements', 'DEFAULTS', 0, 
			'elements');
		
		$defaults = self::parse_single($defaults_xml);
		
		$queries = array_key_or_empty($xml_data, "API-PARAMS", 0, 'elements', 'QUERY');
		
		$all_api_params = [];
		foreach ($queries as $query) {
			$type = array_key_or_exception($query, "attributes", "TYPE");
			$query_xml = array_key_or_exception($query, "elements");
			
			$api_params = self::parse_single($query_xml);
			
			//merge with default
			if ($api_params->title === null) {
				$api_params->title = $defaults->title;
			}
			
			if ($api_params->limit_param === null) {
				$api_params->limit_param = $defaults->limit_param;
			}
			
			if ($api_params->index === null) {
				$api_params->index = $defaults->index;
			}
			
			if ($api_params->top_index === null) {
				$api_params->top_index = $defaults->top_index;
			}
			
			foreach ($defaults->params as $key => $default_param) {
				if (!isset($api_params->params[$key])) {
					$api_params->params[$key] = $default_param;
				}
			}
			
			$validator->validate_arg($api_params->index, "string");
			
			//commented out: not all functions are implemented with a documented max
			//$validator->validate_arg($api_params->limit_param, "string");
			$validator->validate_arg($api_params->title, "string");
			
			$all_api_params[$type] = $api_params;
		}
		
		return $all_api_params;
	}
	
	/**
	 * 
	 * @param array $xml_data
	 * @return Api_Params[]
	 */
	private static function parse_single($xml_data) {
		$api_params = new Api_Params();
		
		$api_params->params = map_array_function_keys(array_key_or_empty($xml_data, "PARAM"), 
			function ($param_xml) use($api_params) {
				global $validator;
				
				$name = array_key_or_exception($param_xml, "attributes", "NAME");
				$type = array_key_or_null($param_xml, "attributes", "TYPE");
				
				if ($type === "limit") {
					if ($api_params->limit_param !== null) {
						throw new IllegalArgumentException(
							"Multiple limit parameters found!" . print_r($xml_data));
					}
					$api_params->limit_param = $name;
					$value = "max";
				} else {
					$value = array_key_or_exception($param_xml, "attributes", "VALUE");
				}
				
				$validator->validate_arg($name, "string");
				$validator->validate_arg($value, "string");
				
				return [$name, $value];
			});
		
		$api_params->index = array_key_or_null($xml_data, "INDEX", 0, "attributes", "VALUE");
		$api_params->title = array_key_or_null($xml_data, "TITLE", 0, "attributes", "VALUE");
		$api_params->title_query = array_key_or_null($xml_data, "TITLE-QUERY", 0, "attributes", "VALUE");
		$api_params->continue = boolean_or_exception(_array_key_or_value($xml_data, "true", "CONTINUE", 
				0, "attributes", "VALUE"));
		
		$top_index = @$xml_data["TOP-INDEX"];
		if ($top_index !== null) {
			$top_index = array_key_or_null($top_index, 0, "attributes", "VALUE");
			if ($top_index === null) {
				$api_params->top_index = [];
			} else {
				$api_params->top_index = explode("|", $top_index);
			}
		}
		
		return $api_params;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @return int
	 */
	private static function get_limit(Wiki $wiki) {
		global $validator, $wiki_interface;
		
		$hash = $wiki->get_hash();
		if (self::$limits[$hash] === null) {
			$limit_query = $wiki_interface->api_query($wiki, 
				["action" => "paraminfo", "modules" => "parse"]);
			$limit_param = array_search_callback(
				array_key_or_exception($limit_query, 'paraminfo', 'modules', 0, 'parameters'), 
				function ($param) {
					return is_array($param) && $param['name'] === "prop";
				});
			
			$limit = array_key_or_exception($limit_param, "limit");

			self::set_limit($wiki, $limit);
		}
		
		return self::$limits[$hash];
	}

	/**
	 *
	 * @param Wiki $wiki
	 * @param int $size
	 * @throws IllegalStateException
	 * @return void
	 */
	private static function set_limit(Wiki $wiki, $size) {
		global $validator;
	
		$validator->validate_arg($size, "numeric");
		$size = (int)$size;
		
		if ($size <= 0) {
			throw new IllegalStateException("Tried cutting api limits; hit 0.");
		}
		self::$limits[$wiki->get_hash()] = (int)$size;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string $type
	 * @return Api_Params
	 */
	public static function get_api_params(Wiki $wiki, $type) {
		if (self::$base_params === null) {
			self::$base_params = self::parse_xml();
		}
		
		$wiki_hash = $wiki->get_hash();
		if (@self::$cache[$wiki_hash] === null) {
			self::$cache[$wiki_hash] = self::$base_params;
		}
		
		$api_params = array_key_or_exception(self::$cache[$wiki_hash], $type);
		$api_params->wiki = $wiki;
		return array_key_or_exception(self::$cache[$wiki_hash], $type);
	}
	
	/**
	 * @return void
	 */
	private function sync_local_max_query() {
		$size = self::get_limit($this->wiki);
		$this->params[$this->limit_param] = $size;
	}
	
	/**
	 * @return void
	 */
	public function lower_max_query() {
		$size = self::get_limit($this->wiki) * 7 / 8;
		self::set_limit($size);
		$this->sync_local_max_query();
		
	}
	
	/**
	 * 
	 * @return string|null
	 */
	public function get_title_query() {
		return $this->title_query;
	}
	
	/**
	 * 
	 * @return string[] associative array
	 */
	public function get_params() {
		return $this->params;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_index() {
		return $this->index;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function get_top_index() {
		return $this->top_index;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function get_continue() {
		return $this->continue;
	}
}