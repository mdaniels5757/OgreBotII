<?php
class XmlTemplateType {

	/**
	 * 
	 * @var XmlTemplateType[]
	 */
	private static $all_types;
	
	/**
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * 
	 * @var XmlTemplate[]
	 */
	private $xmlTemplates;
	
	/**
	 */
	private function __construct() {
	}

	/**
	 * 
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * 
	 * @return XmlTemplate[]
	 */
	public function get_xmlTemplates() {
		return $this->xmlTemplates;
	}

	/**
	 * 
	 * @throws Exception
	 * @return XmlTemplateType[]
	 */
	public static function get_all_types() {
		global $logger, $validator;
		if (self::$all_types === null) {
			$logger->debug("Initializing all types.");

			$all_xml_templates = XmlTemplate::get_all_xml_templates();
			try {
				$template_data = XmlParser::xmlFileToStruct("templates.xml");

				$types = array_key_or_empty($template_data, "TEMPLATES", 0, "elements",
					"TEMPLATE-TYPE");

				if (!$types) {
					$logger->warn("No template types found in XML");
				}

				self::$all_types = [];
				foreach ($types as $type) {
					$xmlTemplateType = new XmlTemplateType();
						
					$name = array_key_or_exception($type, "elements", "NAME", 0, "value");
						
					$typesXml = array_key_or_exception($type, "elements", "TYPES", 0, "elements",
						"TYPE");
						
					$xmlTemplateType->name = $name;
					$xmlTemplateType->xmlTemplates = [];
						
					foreach ($typesXml as $typeXml) {
						$nextType = array_key_or_exception($typeXml, "value");

						$nextXmlTemplate = null;

						foreach ($all_xml_templates as $xml_template) {
							if ($xml_template->get_name() === $nextType) {
								$nextXmlTemplate = $xml_template;
								break;
							}
						}

						//not defined with aliases in XML; define a new one
						if (!$nextXmlTemplate) {
							$nextXmlTemplate =
							XmlTemplate::create_undefined_xml_template($nextType);
						}
						$xmlTemplateType->xmlTemplates[] = $nextXmlTemplate;
					}
						
					self::$all_types[$name] = $xmlTemplateType;
				}
			} catch (Exception $e) {
				ogrebotMail($e);
				throw $e;
			}

			$validator->validate_arg_array(self::$all_types, __CLASS__);

			$logger->debug("\$all_types successfully initialized with " .
				count(self::$all_types)." member(s)"
			);
		}

		return self::$all_types;
	}
}