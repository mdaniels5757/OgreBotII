<?php

class Cleanup_Search extends Abstract_Cleanup {
		
	/**
	 * 
	 * @var string
	 */
	private $search;
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_post_key()
	 */
	protected function get_post_key() {
		return "search";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::init()
	 */
	protected function init($post_vars) {
		$this->search = array_key_or_exception($post_vars, "src");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_log_string()
	 */
	protected function get_log_string() {
		return $this->search;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_edit_summary()
	 */
	protected function get_edit_summary() {
		global $logger, $messages;
		
		$logger->trace("get_edit_summary()");
		
		$userText = "[[User:$this->username]]";
		if (mb_strlen($this->search) <= 128) {
			$search = $this->search;
		} else {
			$search = "[too long to display]";
		}
		
		$raw_message = array_key_or_exception($messages, "cleanup_search.editsummary");
		$editSummary = replace_named_variables($raw_message, 
			["user" => $userText, "search" => $search]);		
		
		$logger->debug("get_edit_summary => $editSummary");
		
		return $editSummary;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Abstract_Cleanup::get_files()
	 */
	protected function get_files() {
		global $wiki_interface;
		
		return $wiki_interface->search($this->wiki, $this->search, 6, $this->get_limit());
	}
}
