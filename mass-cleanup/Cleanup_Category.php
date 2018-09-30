<?php

class Cleanup_Category extends Abstract_Cleanup {
	
	/**
	 * 
	 * @var string
	 */
	private $category;
	
	/**
	 * 
	 * @var boolean
	 */
	private $subcats;

	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_post_key()
	 */
	protected function get_post_key() {
		return "category";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::init()
	 */
	protected function init($post) {
		global $logger, $validator;
		
		$this->category = array_key_or_exception($post, "src");
		$this->subcats = $subcats = array_key_exists('subcategories', $post);
		
		$validator->validate_arg($this->category, "string");
		$validator->validate_args_condition($this->category, "string starting with the text \"Category:\"",
			str_starts_with($this->category, "Category:", 0, true));
		$validator->validate_arg($this->subcats, "boolean");
		
		$logger->info("init($this->category, $this->subcats)");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_edit_summary()
	 */
	protected function get_edit_summary() {
		global $logger;
		global $messages;
		
		$logger->trace("get_edit_summary()");
		
		$userText = "[[User:$this->username]]";
		if (mb_strlen($this->category) <= 128) {
			$categoryDisplayName = "[[$this->category]]";
		} else {
			$categoryDisplayName = substr($this->category, 0, 129)."...";
		}
		
		$string = array_key_or_exception($messages, "cleanup_category.editsummary");
		$message = replace_named_variables($string, 
			array("user" => $userText, "category" => $categoryDisplayName));		
		
		$logger->debug("get_message() => $message");
		return $message;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_log_string()
	 */
	protected function get_log_string() {
		return "$this->category|$this->subcats";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_files()
	 */
	protected function get_files() {
		global $wiki_interface;
		
		$files = $wiki_interface->new_category_traverse($this->wiki, $this->category, $this->subcats, 6, 
			$this->get_limit());
		
		return $files;
	}
}
