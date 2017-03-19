<?php

/**
 *
 * @author magog
 *
 */
class BaseException extends Exception {
	
	/**
	 *
	 * @var boolean
	 */
	private $sentMail = false;
	
	/**
	 *
	 * @var boolean
	 */
	private $logged = false;
	
	/**
	 *
	 * @param mixed $message        	
	 * @param Exception|null $chained        	
	 * @param
	 *        	boolean logged
	 */
	public function __construct($message, $chained = null, $logged = false) {
		parent::__construct($message, null, $chained);
		
		if ($logged) {
			logger_or_stderr(Level::ERROR, $this);
			$this->logged = true;
		}
	}
	
	/**
	 *
	 * @return void
	 */
	protected function mail() {
		ogrebotMail($this);
	}
	
	/**
	 * override in order to log this exception twice
	 * 
	 * @return void
	 */
	public function overrideLogged() {
		$this->logged = false;
	}
	
	/**
	 *
	 * @return void
	 */
	public function setLogged() {
		$this->logged = true;
	}
	
	/**
	 *
	 * @return void
	 */
	public function setMailSent() {
		$this->sentMail = true;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isLogged() {
		return $this->logged;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isSentMail() {
		return $this->sentMail;
	}
}