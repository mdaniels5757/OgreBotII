<?php

/**
 *
 * @author magog
 *
 */
abstract class Now_Commons_Lookup_Exception extends BaseException {

	/**
	 *
	 * @var string
	 */
	private $output_message;

	/**
	 *
	 * @param string $message
	 */
	public function __construct($output_message) {
		parent::__construct("Now_Commons_Lookup_Exception : $output_message", null, false);
		$this->output_message = $output_message;
	}

	/**
	 *
	 * @return string
	 */
	public function get_output_message() {
		return $this->output_message;
	}
}
