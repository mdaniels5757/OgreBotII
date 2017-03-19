<?php
class Relink_Failure {

	/**
	 *
	 * @var string
	 */
	public $file_name;
	
	/**
	 * 
	 * @var string
	 */
	public $dest_name;

	/**
	 * Page names which could not be relinked
	 * @var string[]
	 */
	public $linked_page;

	/**
	 *
	 * @var string
	 */
	public $status_code;

}
