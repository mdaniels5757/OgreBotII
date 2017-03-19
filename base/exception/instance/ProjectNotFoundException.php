<?php

/**
 *
 * @author magog
 *
 */
class ProjectNotFoundException extends BaseException {

	/**
	 *
	 * @param mixed $message
	 * @param Exception|null $chained
	 */
	public function __construct($message, $chained = null) {
		parent::__construct($message, null, $chained);
		ogrebotMail($this);
	}
}
