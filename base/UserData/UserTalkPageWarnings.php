<?php

class UserTalkPageWarnings {
	
	const nld 	   = 0x1;
	const nsd 	   = 0x2;
	const npd	   = 0x4;
	const copyvio  = 0x8;
	const idw	   = 0x10;
	const scope    = 0x20;
	const endOfCVs = 0x40;
	const blocked  = 0x80;

	
	/**
	 * 
	 * @var string 
	 */
	private static $WARNING_TEMPLATES_FILE_NAME = "warning_templates";
	
	/**
	 * 
	 * @var int[]
	 */
	private static $warningTemplates;

	/**
	 * 
	 * @return int[]
	 */
	public static function getWarningTemplates() {
		return self::$warningTemplates;
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public static function loadWarningTemplates() {
		global $logger, $validator;
		
		$logger->debug("loadWarningTemplates()");
		
		try {
			if (self::$warningTemplates === null) {
				load_property_file_into_variable($warningTemplatesWithName, 
					self::$WARNING_TEMPLATES_FILE_NAME);
				
				$validator->validate_arg_array($warningTemplatesWithName, "string", false, false, 
					false, 1);
				
				$class = new ReflectionClass("UserTalkPageWarnings");
				$warningTemplatesMap = $class->getConstants();
				$validator->validate_arg_array($warningTemplatesMap, "int", false, false, false, 0);
				
				self::$warningTemplates = [];
				foreach ($warningTemplatesWithName as $type => $templates) {
					$code = array_key_or_exception($warningTemplatesMap, $type);
					foreach ($templates as $template) {
						self::$warningTemplates[$template] = $code;
					}
				}
				
				log_property_data(self::$warningTemplates);
			}
		} catch (Exception $e) {
			ogrebotMail($e);
			throw $e;
		}
	}
}

//initialize
UserTalkPageWarnings::loadWarningTemplates();

?>
