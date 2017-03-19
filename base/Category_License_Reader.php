<?php

/**
 * Currently only configured for Commons
 * @author magog
 *
 */
class Category_License_Reader {
	
	const GALLERY_PAGE = "User:OgreBot/License categories";
	const PROJECT_DATA = "commons.wikimedia";
	
	/**
	 * 
	 * @var Category_License_Reader
	 */
	private static $singleton;
	
	
	/**
	 * 
	 * @var string[]
	 */
	private $categories;
	
	/**
	 * 
	 * @var string[]
	 */
	private $regexes;
	
	/**
	 * 
	 */
	private function __construct() {
		global $wiki_interface;
		
		$text_response = $wiki_interface->get_text((new ProjectData(self::PROJECT_DATA))->getWiki(), 
			self::GALLERY_PAGE);
		$this->categories = map_array_function_keys(read_configuration_page_lines($text_response->text), 
			function ($line) {
				preg_match("/^\[\[\s*\:?\s*category\s*\:\s*(.+?)(?:\s*\|\s*(.+?))?\s*\]\]/i", $line, 
					$match);
				
				if (!$match) {
					ogrebotMail(self::class . ": Unrecognized line: $line");
					return;
				}
				
				return [$match[1], isset($match[2]) ? $match[2] : $match[1]];
			}, "IGNORE", "NOTEQUAL", true);
		
		load_property_file_into_variable($raw_licenses, "licenses");
		$this->regexes = map_array_function_keys($raw_licenses["regexes"], 
			function ($value) {
				$lastBar = mb_strrpos($value, "|");
				
				$category = mb_trim("/^" . substr($value, 0, $lastBar) . "$/u");
				$replacement = mb_trim(substr($value, $lastBar + 1));
				
				return [$category, $replacement];
			}, "FAIL");
	}
	
	/**
	 * 
	 * @param string[] $categories
	 * @return string[]
	 */
	public function get_license_categories(array $categories) {
		return array_map_filter($categories, function ($category) {
			if ($readable = @$this->categories[$category]) {
				return $readable;
			}
			
			foreach ($this->regexes as $regex => $replacement) {
				if (preg_match($regex, $category)) {
					return preg_replace($regex, $replacement, $category);
				}
			}
		});
	}
	
	/**
	 * 
	 * @return Category_License_Reader
	 */
	public static function get_singleton() {
		if (self::$singleton === null) {
			self::$singleton = new self();
		}
		return self::$singleton;
	}
	
	
}