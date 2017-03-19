<?php

abstract class Abstract_Cleanup {
	
	const LOG_FILENAME = "../cleanup-multi-log";
	
	/**
	 * 
	 * @var string[]
	 */
	private static $key_map;
	
	/**
	 * Max number of pages to read at once.
	 * @var int
	 */
	private $limit;
	
	/**
	 * Max age of the thread in seconds. After that, process will abort.
	 * @var int
	 */
	private $max_age;
	
	/**
	 * 
	 * @var Cleanup_Progress_Indicator
	 */
	private $progress_indicator;
	
	/**
	 * 
	 * @var string
	 */
	protected $username;
	
	/**
	 * 
	 * @var Wiki
	 */
	protected $wiki;
	
	
	/**
	 * @return string
	 */
	protected abstract function get_post_key();
	
	/**
	 * 
	 * @param string[] $post_vars
	 * @return void
	 */
	protected abstract function init($post_vars);
	
	/**
	 * 
	 * @return string
	 */
	protected abstract function get_log_string();
	
	/**
	 * @return string[] an array of file names (including namespace) to process
	 */
	protected abstract function get_files();
	
	/**
	 * @return string the edit summary
	 */
	protected abstract function get_edit_summary();
	
	/**
	 * 
	 * @return string[]
	 */
	public static function get_all_post_keys() {
		if (self::$key_map === null) {
			/* @var $instances Abstract_Cleanup[] */
			/* @var $instance Abstract_Cleanup */
			$instances = Classloader::get_all_instances_of_type(self::class);
			self::$key_map = map_array_function_keys($instances, function ($instance) {
				return [$instance->get_post_key(), get_class($instance)];
			});
		}
		
		return array_keys(self::$key_map);
	}
	
	/**
	 * 
	 * @param string[] $post_vars
	 * @return Abstract_Cleanup
	 * @throws ArrayIndexNotFoundException $post_key is not valid
	 * 		   CantOpenFileException
	 */
	public static function get_by_post_key(Wiki $wiki, $post_vars) {
		self::get_all_post_keys();
		
		list($request_key, $user, $limit, $type) = extract_array_params($post_vars, "request_key", "user", 
			"limit", "type");
		$class_name = array_key_or_exception(self::$key_map, $type);
		
		$filename = replace_named_variables(DO_CLEANUP_FILE, ["request_key" => $request_key]);
		$handle = fopen($filename, "a");
		if (!$handle) {
			throw new CantOpenFileException("Can't open file for write access: $filename");
		}
		
		fwrite($handle, "startup");
		fflush($handle);

		
		/* @var $cleanup Abstract_Cleanup */
		$cleanup = new $class_name();
		$cleanup->wiki = $wiki;
		$cleanup->username = array_key_or_exception($post_vars, "user");
		$cleanup->progress_indicator = new Cleanup_Progress_Indicator($handle);
		$cleanup->limit = (int)$limit;
		$cleanup->init($post_vars);
		$cleanup->log();
		
		return $cleanup;
	}
	
	/**
	 * 
	 * @param string[] $post_vars
	 * @return void
	 */
	private function init_local($post_vars) {
		$user = array_key_or_exception($post_vars, "user");
		$this->init($post_vars);
	}
	
	private function log() {
		$type = $this->get_post_key();
		$log_string = $this->get_log_string();
		write_to_log(__DIR__ . "/" .self::LOG_FILENAME, "|*" . date('Y-m-d H:i:s') .
			 "|$type|$this->username|$this->limit|$log_string\n");
	}
	
	/**
	 *
	 * @return bool
	 */
	public function run() {
		global $logger, $wiki_interface;		
		
		$logger->debug("run()");
		$file_list = $this->get_files();
		sort($file_list, SORT_STRING);
		
		$start = time();
		
		$pagecontent = $wiki_interface->new_query_pages($this->wiki, $file_list, "revisions");
		
		if ($this->progress_indicator) {
			$this->progress_indicator->setup(count($pagecontent));
		}
		
		$auto_editor = new Cleanup_Auto_Editor($this->get_wiki(), $this->get_edit_summary());
		foreach ($pagecontent as $title => $data) {
			if ($this->max_age !== null && time() - $start > $this->max_age) {
				if ($this->progress_indicator) {
					$this->progress_indicator->error("Thread exceeded max age: $this->max_age");
				}
				return false;
			}
			
			$text = array_key_or_null($data, 'revisions', '0', '*');
			
			if ($text === null) {				
				//mediawiki bug or page deleted
				$logger->error("No revisions found for $title (has it been deleted or what?");
				continue;
			}
			$made_change = $auto_editor->process($title, $text, null, $start);
			
			if ($this->progress_indicator) {
				$this->progress_indicator->step([$title, $made_change]);
			}
		}

		if ($this->progress_indicator) {
			$this->progress_indicator->complete();
		}
		
		$logger->info("Done.");
		
		return true;
	}
	
	public function __destruct() {
		if ($this->progress_indicator) {
 			$this->progress_indicator->close();
		}
	}
	
	/**
	 * 
	 * @return int
	 */
	protected function get_limit() {
		return $this->limit;
	}

	/**
	 * 
	 * @return int
	 */
	public function get_max_age() {
		return $this->max_age;
	}
	
	/**
	 * 
	 * @param int $max_age
	 */
	public function set_max_age($max_age) {
		$this->max_age = $max_age;
	}
	
	/**
	 * 
	 * @return Progress_Indicator|null
	 */
	public function get_progress_indicator() {
		return $this->progress_indicator;
	}
	
	/**
	 * 
	 * @param Progress_Indicator $progress_indicator
	 */
	public function set_progress_indicator($progress_indicator) {
		$this->progress_indicator = $progress_indicator;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_username() {
		return $this->username;
	}
	
	/**
	 * 
	 * @param string $username
	 */
	public function set_username($username) {
		$this->username = $username;
	}
	
	/**
	 * 
	 * @return Wiki
	 */
	public function get_wiki() {
		return $this->wiki;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @return void
	 */
	public function set_wiki(Wiki $wiki) {
		$this->wiki = $wiki;
	}
}