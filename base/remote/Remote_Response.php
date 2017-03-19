<?php

/**
 * 
 * @author magog
 *
 */
class Remote_Response {
	
	/**
	 * If there was an error, it is noted here. The field will be null if 
	 *   there is no error.
	 * @var mixed
	 */
	public $error;
	
	/**
	 * For a download, the handle to the file that was downloaded
	 * @var resource|null
	 */
	public $filename;

	/**
	 * The mime type of the response
	 * @var string
	 */
	public $mime;
	
	/**
	 * 
	 * @var int
	 */
	public $response_code;
	
	/**
	 * 
	 * @var string
	 */
	public $response_text;
	
	/**
	 * 
	 * @var int
	 */
	public $size;
	
	/**
	 * The amount of time that a response took
	 * @var int
	 */
	public $time;
}