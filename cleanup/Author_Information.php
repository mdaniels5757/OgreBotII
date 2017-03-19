<?php
/**
 * 
 * @author magog
 *
 */
class Author_Information {
	/**
	 *
	 * @var string
	 */
	private $username;
	/**
	 *
	 * @var string
	 */
	private $project;
	/**
	 *
	 * @var string
	 */
	private $language;
	/**
	 *
	 * @var bool
	 */
	private $self_replace;
	/**
	 *
	 * @var bool
	 */
	private $indicated_author;
	
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
	 * @return void
	 */
	public function set_username($username) {
		$this->username = $username;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_project() {
		return $this->project;
	}
	
	/**
	 *
	 * @param string $project
	 * @return void
	 */
	public function set_project($project) {
		$this->project = $project;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_language() {
		return $this->language;
	}
	
	/**
	 *
	 * @param string $language
	 * @return void
	 */
	public function set_language($language) {
		$this->language = $language;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_self_replace() {
		return $this->self_replace;
	}
	
	/**
	 *
	 * @param bool $self_replace
	 * @return void
	 */
	public function set_self_replace($self_replace) {
		$this->self_replace = $self_replace;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_indicated_author() {
		return $this->indicated_author;
	}
	
	/**
	 *
	 * @param bool $is_indicated_author
	 * @return void
	 */
	public function set_indicated_author($indicated_author) {
		$this->indicated_author = $indicated_author;
	}
}