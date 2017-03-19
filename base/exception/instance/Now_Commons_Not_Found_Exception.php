<?php

/**
 *
 * @author magog
 *
 */
class Now_Commons_Not_Found_Exception extends Now_Commons_Lookup_Exception {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct("Now Commons tag not found on the page.");
	}
}