<?php

/**
 *
 * @author magog
 *
 */
class CantDelinkException extends BaseException {

	/**
	 *
	 * @var int
	 */
	private $status_code;

	/**
	 *
	 * @param int $status_code
	 * @return void
	 */
	public function __construct($status_code) {
		$this->status_code = $status_code;

		parent::__construct("Failed with status code of $status_code.",
			null, false);
	}
	/**
	 *
	 * @return int
	 */
	public function get_status_code() {
		return $this->status_code;
	}

}