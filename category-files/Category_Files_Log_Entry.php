<?php
class Category_Files_Log_Entry {
	
	const TEMP_FILE_ALL_GALLERIES_CACHE = "all_galleries_cache.tmp";
	
	/**
	 *
	 * @var string
	 */
	public $gallery;
	
	/**
	 *
	 * @var string
	 */
	public $category;
	
	/**
	 *
	 * @var string[]
	 */
	public $tree;
	
	/**
	 *
	 * @var string|null
	 */
	private static $prune_to_gallery;
	
	
	/**
	 *
	 * @param string $line        	
	 * @return Category_Files_Log_Entry
	 */
	public static function parse_line($line) {
		global $logger;
		
		preg_match("/^(.+?)\|(.+?)\|(.+)$/", $line, $entries_match);
		if ($entries_match) {
			if (!self::$prune_to_gallery || self::$prune_to_gallery === $entries_match[1]) {
				$entry = new Category_Files_Log_Entry();
				$entry->gallery = $entries_match[1];
				$entry->category = $entries_match[2];
				$entry->tree = explode("|", $entries_match[3]);
				return $entry;
			}
		} else {
			$logger->warn("Can't read entry: $line");
		}
	}
	
	/**
	 * 
	 * @return string[][]
	 */
	public static function get_all_gallery_names(): array {
		$output = ogrebotExecWithOutput("node " . BASE_DIRECTORY . "/js/exe/get-gallery-names.js");
		return json_decode($output, true);
	}
	
	/**
	 *
	 * @param string $filename
	 * @return Category_Files_Log_Entry[]
	 * @throws CantOpenFileException
	 */
	public static function parse_file($filename) {
		$content = file_get_contents_ensure($filename);
		$lines = preg_split("/\r?\n/", preg_replace("/\r?\n$/", "", $content));
		
		if (self::$prune_to_gallery) {
			$lines = preg_grep("/" . preg_quote(self::$prune_to_gallery, '/') . "/", $lines);
		}
		$all_parsed = [];
		foreach ($lines as $line) {
			$parsed = self::parse_line($line);
			if ($parsed) {
				$all_parsed[] = $parsed;
			}
		}
		return $all_parsed;
	}
	
	/**
	 *
	 * @return string|null
	 */
	public static function get_prune_to_gallery() {
		return self::$prune_to_gallery;
	}
	
	/**
	 *
	 * @param string|null $prune_to_gallery
	 * @return void
	 */
	public static function set_prune_to_gallery($prune_to_gallery) {
		self::$prune_to_gallery = $prune_to_gallery;
	}
	
}