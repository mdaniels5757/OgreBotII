<?php

/**
 * 
 * @author magog
 *
 */
class TemplateFieldsMismatchException extends CombineTemplatesException {
	/**
	 * 
	 * @param string $firstTemplateName
	 * @param string $secondTemplateName
	 * @param string $fieldName
	 */
	public function __construct($firstTemplateName, $secondTemplateName, $fieldName) {
		global $logger, $validator;
		
		$validator->validate_arg($firstTemplateName, "string");
		$validator->validate_arg($secondTemplateName, "string");
		$validator->validate_arg($fieldName, "string");
		
		$errorMessage = "Cannot combine Template:$firstTemplateName " .
			 "and Template:$secondTemplateName because there is a type mismatch " .
			 "in field $fieldName.";
		
		$logger->warn($errorMessage);
		parent::__construct($errorMessage);
	}
}