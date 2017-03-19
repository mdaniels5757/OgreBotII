<?php

class Http_Cache_Reader {

	
	const CACHE_FILE_NAME = "files.properties";
	
	/**
	 * 
	 * @var int
	 */
	private static $default_cache_time = DEFAULT_URL_CACHE_TIME;
	
	/**
	 * 
	 * @var int
	 */
	private $cache_time;
	
	/**
	 * 
	 * @param int $default_cache_time
	 * @return void
	 */
	public static function set_default_cache_time($default_cache_time) {
		self::validate_cache_time($default_cache_time);
		self::$default_cache_time = $default_cache_time;
	}
	
	/**
	 * 
	 * @param int $cache_time
	 * @return void
	 * @throws IllegalArgumentException
	 */
	public static function validate_cache_time($cache_time) {
		global $validator;
		$validator->validate_arg($cache_time, "int");
		if ($cache_time < 0 || $cache_time > DEFAULT_URL_CACHE_TIME) {
			throw new IllegalArgumentException("Cache time cannot be less than 0 or ".
					" greater than ". DEFAULT_URL_CACHE_TIME);
		}
	}
	
	/**
	 * 
	 * @param int $http_cache_time
	 */
	public function __construct($cache_time = null) {
		
		if($cache_time === null) {
			$cache_time = self::$default_cache_time;			
		}

		$this->set_cache_time($cache_time);		
	}
	
	/**
	 * 
	 * @return int
	 */
	public function get_cache_time() {
		return $this->cache_time;
	}
	
	/**
	 * 
	 * @param int $cache_time
	 * @return void
	 */
	public function set_cache_time($cache_time) {
		self::validate_cache_time($cache_time);
		$this->cache_time = $cache_time;
	}

	/**
	 *
	 * @param int $cache_time
	 * @return void
	 */
	public function set_max_cache_time($cache_time) {
		self::validate_cache_time($cache_time);
		if ($this->cache_time > $cache_time) {
			$this->cache_time = $cache_time;
		}
	}
	
	/**
	 * Currently only supports URLs with <=255 characters (excluding http://)
	 * 
	 * @param string $url        	
	 * @return string the http content
	 * @throws IllegalArgumentException not an http:// url, or the url >=256 characters
	 * @throws CantOpenFileException can't open the cached file
	 * @throws AssertionFailureException $url is not a string
	 * @throws CURLError can't download the file
	 */
	public function get_and_store_url($url) {
		global $logger, $validator;
		
		static $purged_cache = false;
		
		/* @var $cache_table string[][] */
		
		$validator->validate_arg($url, "string");
		
		$logger->debug("get_and_store_url($url)");
		
		
		$cache_update = false;
		$cache_table_text = file_get_contents(CACHE_DIRECTORY . self::CACHE_FILE_NAME);
		$cache_table = array ();
		if ($cache_table_text !== false) {
			$table = json_decode($cache_table_text, true);
			if ($table !== null) {
				$cache_table = &$table;
			}
		}
		
		// prune old cache entries
		$now = time();
		if (!$purged_cache) {
			foreach ($cache_table as $next_url => $cache_datum) {
				if ($cache_datum['time'] < $now - DEFAULT_URL_CACHE_TIME) {
					$logger->debug("Deleting old cache entry : $next_url");
					unlink(CACHE_DIRECTORY . $cache_table[$next_url]['filename]']);
					unset($cache_table[$next_url]);
					$cache_update = true;
				}
			}
			$purged_cache = true;
		}
		
		$cache_file_data = @$cache_table[$url];
		
		if ($cache_file_data != null) {
			$filename = $cache_file_data['filename'];
			$time = $cache_file_data['time'];
		}
		
		if (@$filename !== null && file_exists(CACHE_DIRECTORY . $filename) &&
			 $time >= $now - $this->cache_time) {
			$logger->debug("... found in cache");
		} else {
			$cache_update = true;
			
			if (@$filename && file_exists(CACHE_DIRECTORY . $filename)) {
				unlink(CACHE_DIRECTORY . $filename);
			}
			
			do {
				$filename = (string)rand();
			} while (file_exists(CACHE_DIRECTORY . $filename));
			
			$logger->info("Downloading $url");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, "Magog the Ogre's Wikimedia bot");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
			$handle = curl_download($ch, $url, CACHE_DIRECTORY . $filename);
			curl_close($ch);
			
			$cache_table[$url] = array (
					'filename' => $filename,
					'time' => $now 
			);
		}
		
		if ($cache_update) {
			$logger->debug("Saving cache: " . count($cache_file_data) . " entries.");
			
			$text = json_encode($cache_table, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			file_put_contents_ensure(CACHE_DIRECTORY . self::CACHE_FILE_NAME, $text);
		}
		
		return file_get_contents_ensure(CACHE_DIRECTORY . $filename);
	}
}
