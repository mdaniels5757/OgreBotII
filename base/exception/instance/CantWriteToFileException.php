<?php

/**
 *
 * @author magog
 *
 */
class CantWriteToFileException extends FileIOException {
	
	/**
	 * 
	 * @param string $filename
	 */
	public function __construct($filename) {
		global $validator;
		$validator->validate_arg($filename, "string");
		parent::__construct("Can't open file: $filename");
	}
}