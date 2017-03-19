<?php

/**
 * Named for phabricator:T86611
 * @author magog
 *
 */
class T86611_Exception extends BaseException {

	/**
	 * 
	 * @var string
	 */
	private $continue;
	
	/**
	 * 
	 * @param string $continue
	 */
	public function __construct($continue) {
		$this->continue = str_replace("_", " ", $continue);
		parent::__construct("T86611_Exception($this->continue)", null, false);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_continue() {
		return $this->continue;
	}
}