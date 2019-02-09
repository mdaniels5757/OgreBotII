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
	 * @param string $filename
	 * @return string[]
	 */
	public static function get_all_gallery_names_by_file($filename) {
		$content = file_get_contents_ensure($filename);
		preg_match_all("/^(.+?)\|/m", $content, $matches);
		
		return array_unique($matches[1]);
	}
	
	/**
	 * 
	 * @param string[] $filenames
	 * @return string[]
	 */
	public static function get_all_gallery_names($filenames) {
		
		$tmp_file_name = TMP_DIRECTORY_SLASH . self::TEMP_FILE_ALL_GALLERIES_CACHE;
		$tmp_file_contents = file_get_contents($tmp_file_name);

		//read from temp file
		if ($tmp_file_contents) {
			$cached_content = unserialize($tmp_file_contents);
			$cached_filenames = $cached_content["filenames"];
			
			if ($filenames === $cached_filenames) {
				return $cached_content["galleries"];
			}
		}
		
		$content = array_unique(
			array_merge_all(
				array_map_filter(str_prepend($filenames, $log_directory), 
					[self::class, "get_all_gallery_names_by_file"])));
		
		$serialized_content = serialize(["filenames" => $filenames, "galleries" => $content]);
		
		file_put_contents_ensure($tmp_file_name, $serialized_content);
		
		return $content;
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