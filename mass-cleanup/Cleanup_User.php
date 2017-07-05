<?php

/**
 * 
 * @author magog
 *
 */
class Cleanup_User extends Abstract_Cleanup {
		
	/**
	 * 
	 * @var string
	 */
	private $uploader;
	
	/**
	 * Wikimedia timestamp
	 * @var number
	 */
	private $start;
	
	/**
	 * Wikimedia timestamp
	 * @var number
	 */
	private $end;
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_post_key()
	 */
	protected function get_post_key() {
		return "uploader";
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Abstract_Cleanup::init()
	 */
	protected function init($post_vars) {
		list($this->uploader, $this->end, $this->start) = (new Array_Parameter_Extractor(
			["src" => true, "start" => false, "end" => false]))->extract($post_vars);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_log_string()
	 */
	protected function get_log_string() {
		return "$this->uploader $this->start $this->end";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_edit_summary()
	 */
	protected function get_edit_summary() {
		global $logger;
		
		$logger->trace("get_edit_summary()");
		
		$userText = "[[User:$this->username]]";
		if (mb_strlen($this->uploader) <= 125) {
			$uploader = "[[User:$this->uploader]]";
		} else {
			$uploader = "[too long to display]";
		}
		
		$raw_message = Environment::prop("messages", "cleanup_uploader.editsummary");
		$editSummary = replace_named_variables($raw_message, 
			["user" => $userText, "uploader" => $uploader]);		
		
		$logger->debug("get_edit_summary => $editSummary");
		
		return $editSummary;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Abstract_Cleanup::get_files()
	 */
	protected function get_files() {		
		return Environment::get()->get_wiki_interface()->uploads_by_user($this->wiki, 
			$this->uploader, $this->start, $this->end, $this->get_limit());
	}
}
