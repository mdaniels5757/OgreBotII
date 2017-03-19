<?php

/**
 * 
 * @author magog
 *
 */
class TemplatesOverlapException extends CombineTemplatesException {
	
	/**
	 * 
	 * @param string $firstTemplateName
	 * @param string $secondTemplateName
	 */
	public function __construct($firstTemplateName, $secondTemplateName) {
		global $logger, $validator;
		
		$validator->validate_arg($firstTemplateName, "string");
		$validator->validate_arg($secondTemplateName, "string");
		
		$errorMessage = "Cannot combine Template:$firstTemplateName " .
			 "and Template:$secondTemplateName because they overlap.";
		
		$logger->debug($errorMessage);
		parent::__construct($errorMessage);
	}
}