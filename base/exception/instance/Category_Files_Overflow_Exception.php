<?php

/**
 *
 * @author magog
 *
 */
class Category_Files_Overflow_Exception extends BaseException {
	
	/**
	 *  	
	 * @return void
	 */
	public function __construct() {
		parent::__construct(Category_Files_Overflow_Exception::class, null, false);
	}

}