<?php

/**
 * 
 * @author magog
 *
 */
class Relink_Text {
	
	/**
	 *
	 * @var int
	 */
	const SUCCESS = 0;
	
	/**
	 *
	 * @var int
	 */
	const NO_PRE_TRANSCLUSION = 1;
	
	/**
	 *
	 * @var int
	 */
	const NEW_ALREADY_PRESENT = 2;
	
	/**
	 *
	 * @var int
	 */
	const NO_POST_TRANSCLUSION = 4;
	
	/**
	 *
	 * @var int
	 */
	const OTHER_IMAGE_CHANGED = 8;
	
	/**
	 *
	 * @var int
	 */
	const OLD_NOT_REMOVED = 16;
	
	/**
	 *
	 * @var string
	 */
	private $file_pre;
	
	/**
	 *
	 * @var string
	 */
	private $file_post;
	
	/**
	 *
	 * @var string
	 */
	private $page_name;
	
	/**
	 *
	 * @var string
	 */
	private $text_pre;
	
	/**
	 *
	 * @var string[]
	 */
	private $transclusions;
	
	/**
	 * 
	 * @var Wiki
	 */
	private $wiki;
	
	/**
	 * 
	 * @var bool 
	 */
	private $test_conflict;
	
	/**
	 *
	 * @param string $file_pre        	
	 * @param string $file_post        	
	 * @param string $text_pre        	
	 * @param string $page_name
	 * @param bool $test_conflict        	
	 */
	public function __construct(Wiki $wiki, $file_pre, $file_post, $text_pre, $page_name,
			$test_conflict) {
		global $validator;
		$validator->validate_args("string", $file_pre, $file_post, $text_pre, $page_name);
		$validator->validate_arg($test_conflict, "bool");
		
		$this->wiki = $wiki;
		$this->file_pre = str_replace(" ", "_", $file_pre);
		$this->file_post = str_replace(" ", "_", $file_post);
		$this->page_name = $page_name;
		$this->text_pre = $text_pre;
		$this->test_conflict = $test_conflict;
	}
	
	/**
	 *
	 * @return int
	 */
	public function test_pre() {
		$old_transclusions = $this->get_inclusions($this->text_pre);
		
		if ($this->test_conflict && in_array($this->file_post, $old_transclusions)) {
			return self::NEW_ALREADY_PRESENT;
		}
		
		if (!in_array($this->file_pre, $old_transclusions)) {
			return self::NO_PRE_TRANSCLUSION;
		}
		
		$this->transclusions = $old_transclusions;
		
		return self::SUCCESS;
	}
	
	/**
	 *
	 * @param string $text_post
	 * @throws IllegalStateException
	 * @return int
	 */
	public function test_post($text_post) {
		global $validator;
		
		if ($this->transclusions === null) {
			throw new IllegalStateException(
				"test_post() called before test_pre() or after test_pre() failure");
		}
		
		$validator->validate_arg($text_post, "string");
		
		$new_transclusions = $this->get_inclusions($text_post);
		
		$old_only = array_diff($this->transclusions, $new_transclusions);
		$new_only = $this->test_conflict ? array_diff($new_transclusions, $this->transclusions) 
			: [$this->file_post];
		if (!in_array($this->file_pre, $old_only)) {
			return self::OLD_NOT_REMOVED;
		}
		if (!in_array($this->file_post, $new_only)) {
			return self::NO_POST_TRANSCLUSION;
		}
		
		if ((count($old_only) !== 1 && (count($old_only) !== 2 || $this->test_conflict)) || count($new_only) !== 1) {
			return self::OTHER_IMAGE_CHANGED;
		}
		
		return self::SUCCESS;
	}
	
	/**
	 *
	 * @param string $text        	
	 * @return string[]
	 */
	private function get_inclusions($text) {
		global $wiki_interface;
		
		$data = $wiki_interface->api_query($this->wiki, 
			['action' => 'parse', 'title' => $this->page_name, 'text' => $text, 
				'prop' => 'images'], true);
		
		return array_key_or_exception($data, 'parse', 'images');
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_file_pre() {
		return $this->file_pre;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_file_post() {
		return $this->file_post;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_page_name() {
		return $this->page_name;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_text_pre() {
		return $this->text_pre;
	}
}