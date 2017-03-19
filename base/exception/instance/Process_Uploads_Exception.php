<?php
/**
 * A class for expected errors that the uploads may cause.
 * @author Magog
 *
 */
class Process_Uploads_Exception extends BaseException {

	/**
	 * 
	 * @var string
	 */
	private $message_code;

	/**
	 * 
	 * @param string $message_code
	 */
	public function __construct($message_code) {
		parent::__construct("Uploads failed with code $message_code", null, false);
		$this->message_code = $message_code;
	}

	/**
	 * 
	 * @return string
	 */
	public function getMessage_code() {
		return $this->message_code;
	}
}