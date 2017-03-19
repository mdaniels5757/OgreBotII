<?php

/**
 * 
 * @author magog
 *
 */
class UploadException extends BaseException {
	/**
	 * 
	 * @param Exception|null $chained
	 */
	public function __construct($chained = null) {
		parent::__construct(get_class(), $chained, false);
	}
}