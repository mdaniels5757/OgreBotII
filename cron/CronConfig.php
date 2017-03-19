<?php
class CronConfig {
	
	/**
	 * 
	 * @var string|null
	 */
	private $directory;

	/**
	 * 
	 * @var string
	 */
	private $command;
	
	/**
	 * 
	 * @var string
	 */
	private $args;
	
	/**
	 * 
	 * @var string|null
	 */
	private $post;
	
	/**
	 * 
	 * @var int
	 */
	private $timeout;
	
	/**
	 * 
	 * @var bool
	 */
	private $hhvm;
	
	/**
	 * 
	 * @return string|null
	 */
	public function getDirectory(){
		return $this->directory;
	}
	
	/**
	 * 
	 * @param string|null $directory
	 * @return void
	 */
	public function setDirectory($directory){
		$this->directory = $directory;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getCommand(){
		return $this->command;
	}
	
	/**
	 * 
	 * @param string $command
	 */
	public function setCommand($command){
		$this->command = $command;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getArgs(){
		return $this->args;
	}
	
	/**
	 * 
	 * @param string $args
	 * @return void
	 */
	public function setArgs($args){
		$this->args = $args;
	}
	
	/**
	 * 
	 * @return string|null
	 */
	public function getPost() {
		return $this->post;
	}
	
	/**
	 * 
	 * @param string|null $post
	 * @return void
	 */
	public function setPost($post) {
		$this->post = $post;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getTimeout(){
		return $this->timeout;
	}
	
	/**
	 * 
	 * @param int $timeout
	 */
	public function setTimeout($timeout){
		$this->timeout = $timeout;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function getHhvm() {
		return $this->hhvm;
	}
	
	/**
	 * 
	 * @param boolean $hhvm
	 * @return void
	 */
	public function setHhvm($hhvm) {
		$this->hhvm = $hhvm;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString() {
		return preg_replace("/[\r\n\t]+/", " ", print_r($this, true));
	}
}