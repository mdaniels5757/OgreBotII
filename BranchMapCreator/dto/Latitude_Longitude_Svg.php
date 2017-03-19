<?php
class Latitude_Longitude_Svg {
	
	/**
	 *
	 * @var Latitude_Longitude_Svg[]
	 */
	private static $instances;
	
	/**
	 *
	 * @var string
	 */
	private static $map_wikitext;
	
	/**
	 *
	 * @var Latitude_Longitude_Svg
	 */
	private static $primary_instance;
	
	/**
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 *
	 * @var string
	 */
	private $type;
	
	/**
	 *
	 * @var float
	 */
	private $east;
	
	/**
	 *
	 * @var float
	 */
	private $west;
	
	/**
	 *
	 * @var float
	 */
	private $north;
	
	/**
	 *
	 * @var float
	 */
	private $south;
	
	/**
	 *
	 * @var string
	 */
	private $text;
	
	/**
	 *
	 * @var float
	 */
	private $viewbox_east;
	
	/**
	 *
	 * @var float
	 */
	private $viewbox_west;
	
	/**
	 *
	 * @var float
	 */
	private $viewbox_north;
	
	/**
	 *
	 * @var float
	 */
	private $viewbox_south;
	
	/**
	 *
	 * @var string
	 */
	private $author;
	
	/**
	 *
	 * @var string
	 */
	private $license;
	
	/**
	 *
	 * @var string
	 */
	private $human_readable;
	
	/**
	 *
	 * @var bool
	 */
	private $primary;
	
	/**
	 *
	 * @var string
	 */
	private $wikitext;
	
	/**
	 */
	private function __construct() {
	}
	
	/**
	 *
	 * @return Latitude_Longitude_Svg
	 * @throws IllegalStateException
	 */
	public static function get_primary_instance() {
		if (self::$primary_instance === null) {
			throw new IllegalStateException("get_primary_instance() called before load()");
		}
		return self::$primary_instance;
	}
	
	/**
	 *
	 * @return Latitude_Longitude_Svg[]
	 */
	public static function load() {
		if (self::$map_wikitext === null) {
			self::$map_wikitext = file_get_contents_ensure(
				BASE_DIRECTORY . "/properties/map.wikitext");
		}
		if (self::$instances === null) {
			self::$instances = map_array_function_keys(
				array_key_or_exception(XmlParser::xmlFileToStruct("maps.xml"), "MAPS", 0, 
					"elements", "MAP"), 
				function ($element) {
					global $logger;
					
					$instance = new Latitude_Longitude_Svg();
					$instance->name = array_key_or_exception($element, "attributes", "NAME");
					$instance->type = array_key_or_exception($element, "attributes", "TYPE");
					$instance->east = array_key_or_exception($element, "attributes", "EAST");
					$instance->west = array_key_or_exception($element, "attributes", "WEST");
					$instance->north = array_key_or_exception($element, "attributes", "NORTH");
					$instance->south = array_key_or_exception($element, "attributes", "SOUTH");
					$instance->primary = deep_array_key_exists($element, "attributes", "PRIMARY");
					$instance->human_readable = array_key_or_exception($element, "attributes", 
						"HUMAN-READABLE");
					$instance->author = array_key_or_exception($element, "elements", "AUTHOR", 0, 
						"value");
					$instance->license = array_key_or_exception($element, "elements", "LICENSE", 0, 
						"value");
					
					$logger->debug("Loading $instance->name.svg");
					
					$instance->text = file_get_contents_ensure(
						BASE_DIRECTORY . "/maps/$instance->name.svg");
					
					preg_match("/\bviewBox\s*\=\s*\"(\-?[\d\.]+) (\g1) (\g1) (\g1)\"/i", 
						$instance->text, $match);
					
					if ($match) {
						list($instance->viewbox_west, $instance->viewbox_south, $instance->viewbox_east, $instance->viewbox_north) = $match;
					} else {
						$instance->viewbox_west = 0;
						$instance->viewbox_south = 0;
						
						preg_match("/[^\-\w]height\s*\=\s*\"([\d\.]+)\"/i", $instance->text, 
							$height_match);
						preg_match("/[^\-\w]width\s*\=\s*\"([\d\.]+)\"/i", $instance->text, 
							$width_match);
						if (!$height_match || !$width_match) {
							throw new UnexpectedValueException(
								"Can't determine viewbox size " . "for $instance->name");
						}
						
						$instance->viewbox_east = $width_match[1];
						$instance->viewbox_north = $height_match[1];
					}
					
					return [$instance->name, $instance];
				}, "FAIL");
			
			$primaries = array_filter(self::$instances, 
				function (Latitude_Longitude_Svg $svg) {
					return $svg->primary;
				});
			
			if (count($primaries) !== 1) {
				throw new UnexpectedValueException(
					"Multiple primaries found in maps.xml. There should be exactly one.");
			}
			self::$primary_instance = array_pop($primaries);
		}
		return self::$instances;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_east() {
		return $this->east;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_west() {
		return $this->west;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_north() {
		return $this->north;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_south() {
		return $this->south;
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
	 * @return float
	 */
	public function get_viewbox_east() {
		return $this->viewbox_east;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_viewbox_west() {
		return $this->viewbox_west;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_viewbox_north() {
		return $this->viewbox_north;
	}
	
	/**
	 *
	 * @return float
	 */
	public function get_viewbox_south() {
		return $this->viewbox_south;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_author() {
		return $this->author;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_license() {
		return $this->license;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_human_readable() {
		return $this->human_readable;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_primary() {
		return $this->primary;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_wikitext() {
		if ($this->wikitext === null) {
			$this->wikitext = replace_named_variables(self::$map_wikitext, 
				["author" => $this->author, "date" => date("Y-m-d"), 
					"license" => $this->license, 
					"source" => "[[:File:" . str_replace("_", " ", $this->name) . ".svg|]]"]);
		}
		
		return $this->wikitext;
	}
	
	/**
	 *
	 * @return float;
	 */
	public function get_height() {
		return ($this->viewbox_north - $this->viewbox_south) / ($this->north - $this->south);
	}
	
	/**
	 *
	 * @return float;
	 */
	public function get_width() {
		return ($this->viewbox_east - $this->viewbox_west) / ($this->east - $this->west);
	}
	
	/**
	 * Get the recommended height in order to make the map fit perfectly with
	 * the primary map
	 *
	 * @return float
	 */
	public function get_recommended_height() {
		return ($this->north - $this->south) * self::$primary_instance->get_height();
	}
	
	/**
	 * Get the recommended height in order to make the map fit perfectly with
	 * the primary map
	 *
	 * @return float
	 */
	public function get_recommended_width() {
		return ($this->east - $this->west) * self::$primary_instance->get_width();
	}
}