<?php

class User_at_project {
	
	/**
	 * 
	 * @var string
	 */
	private $langcode;
	
	/**
	 * 
	 * @var string
	 */
	private $project;
	
	/**
	 * 
	 * @var string
	 */
	private $username;
	
	/**
	 * 
	 * @var string
	 */
	private $template_string;
	
	/**
	 * 
	 * @var string
	 */
	private $direct_link;
	
	/**
	 * 
	 * @var string
	 */
	private $base_url;
	
	/**
	 * 
	 * @param string $template_string
	 * @param string $project
	 * @param string $username
	 * @param string $langcode
	 * @param string $direct_link
	 * @param string|null $base_url DEFAULT null
	 */
	public function __construct($template_string, $project, $username, $langcode, $direct_link,
		$base_url = null) {
		$this->project = $project;
		$this->username = $username;
		$this->template_string = $template_string;
		$this->langcode = $langcode;
		$this->direct_link = $direct_link;
		$this->base_url = $base_url;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_langcode() {
		return $this->langcode;
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
	 * @return string
	 */
	public function get_username() {
		return $this->username;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_template_string() {
		return $this->template_string;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_direct_link() {
		return $this->direct_link;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_base_url() {
		return $this->base_url;
	}
}