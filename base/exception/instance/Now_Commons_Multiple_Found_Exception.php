<?php

/**
 *
 * @author magog
 *
 */
class Now_Commons_Multiple_Found_Exception extends Now_Commons_Lookup_Exception {

	/**
	 *
	 * @var string
	 */
	private $first;

	/**
	 * 
	 * @param string $first
	 */
	public function __construct($first) {
		parent::__construct("Multiple Now Commons tags found on the page.");
		$this->first = $first;
	}

	/**
	 *
	 * @return string
	 */
	public function get_first() {
		return $this->first;
	}
}