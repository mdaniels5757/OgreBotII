<?php

/**
 * 
 * @author magog
 *
 */
interface Lazy_Load_Storage extends ArrayAccess {
		
	/**
	 * 
	 * @return array
	 */
	public function get_storage();
}