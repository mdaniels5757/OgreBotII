<?php
class XmlTemplate {
	
	/**
	 *
	 * @var XmlTemplate[]
	 */
	private static $all_templates;
	
	/**
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 *
	 * @var string[]
	 */
	private $aliases;
	
	/**
	 *
	 * @var string[]
	 */
	private $aliases_and_name;

	/**
	 * A rough regular expression used internally to ease processor
	 *   load when searching for the template on a page.
	 * @var string
	 */
	private $aliases_and_name_regex;
	
	/**
	 * A rough regular expression used internally to ease processor
	 *   load when searching for the template on a page.
	 * @var string[]
	 */
	private $aliases_and_name_regexes;
	
	/**
	 *
	 * @var bool
	 */
	private $cleanup_remove_duplicate;
	
	/**
	 *
	 * @var string[]
	 */
	private $field_aliases;
	
	/**
	 *
	 * @var array
	 */
	private $fields_and_aliases;
	
	/**
	 * @var string|null
	 */
	private $infobox_location;
	
	/**
	 * 
	 * @var bool
	 */
	private $major_infobox_change;
	
	/**
	 */
	private function __construct() {
		$this->aliases = [];
		$this->aliases_and_name = [];
		$this->field_aliases = [];
	}

	/**
	 * 
	 * @return string
	 */
	public function get_name(){
		return $this->name;
	}

	/**
	 * 
	 * @return string[]
	 */
	public function get_aliases(){
		return $this->aliases;
	}

	/**
	 * 
	 * @return string[]
	 */
	public function get_aliases_and_name(){
		return $this->aliases_and_name;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_aliases_and_name_regex() {
		if ($this->aliases_and_name_regex === null) {
			if ($this->aliases_and_name) {
				$this->aliases_and_name_regex = "(?:" .
					 implode("|", $this->get_aliases_and_name_regexes()) . ")";
			} else {
				$this->aliases_and_name_regex = ALWAYS_FAIL_REGEX;
			}
		}
		return $this->aliases_and_name_regex;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function get_aliases_and_name_regexes() {
		if ($this->aliases_and_name_regexes === null) {
			$this->aliases_and_name_regexes = map_array_function_keys($this->aliases_and_name, 
				function ($val) {
					$regex = regexify_template($val);
					return [$val, "\{\{[\s_]*(?:$regex)[\s_]*(?:\||\}\})"];
				});
		}
		return $this->aliases_and_name_regexes;
	}

	/**
	 * 
	 * @return boolean
	 */
	public function get_cleanup_remove_duplicate(){
		return $this->cleanup_remove_duplicate;
	}

	/**
	 * 
	 * @return string[]
	 */
	public function get_field_aliases() {
		return $this->field_aliases;
	}

	/**
	 * 
	 * @return array
	 */
	public function get_fields_and_aliases() {
		return $this->fields_and_aliases;
	}

	/**
	 *
	 * @return string|null
	 */
	public function get_infobox_location() {
		return $this->infobox_location;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_major_infobox_change() {
		return $this->major_infobox_change;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param string[] $fields
	 * @return XmlTemplate
	 */
	public static function create_undefined_xml_template($name, $fields) {
		global $validator;

		$validator->validate_arg($name, "string");
		$validator->validate_arg_array($fields, "string", false, true, true, 2);

		$xmlTemplate = new XmlTemplate();
		$xmlTemplate->name = $name;
		$xmlTemplate->aliases_and_name[] = $name;
		$xmlTemplate->fields = $fields;
		$xmlTemplate->cleanup_remove_duplicate = false;
		$xmlTemplate->major_infobox_change = false;

		return $xmlTemplate;
	}

	/**
	 * 
	 * @return XmlTemplate[]
	 */
	public static function get_all_xml_templates() {
		global $logger, $validator;
		if (self::$all_templates === null) {
			$logger->debug("Initializing templates from XML.");

			try {
				$template_data = XmlParser::xmlFileToStruct("templates.xml");

				$templates = array_key_or_exception($template_data,
					array("TEMPLATES", 0, "elements", "TEMPLATE"));

				self::$all_templates = [];
				foreach ($templates as $template) {
					$elements = array_key_or_exception($template, "elements");

					$xmlTemplate = new XmlTemplate();

					/*
					 * name
					*/
					$name = array_key_or_exception(
						$elements,
						array("NAME", 0, "value")
					);
					$validator->validate_arg($name, "string");

					$xmlTemplate->name = $name;
					$xmlTemplate->aliases_and_name[] = $name;

					/*
					 * aliases
					 */
					$aliasesXml = array_key_or_empty($elements, "ALIASES", 0, "elements", "ALIAS");
						
					foreach ($aliasesXml as $aliasXml) {
						$alias = array_key_or_exception($aliasXml, "value");
						$validator->validate_arg($alias, "string");
						$xmlTemplate->aliases[] = $alias;
						$xmlTemplate->aliases_and_name[] = $alias;
					}
						
					/*
					 * fieldaliases
					 */
					$fieldAliasesXml = array_key_or_empty($elements, "FIELDALIASES", 0, "elements",
						"FIELDALIAS");
					foreach ($fieldAliasesXml as $fieldAliasXml) {
						$aliasName = array_key_or_exception($fieldAliasXml, 
							array("elements", "NAME", 0, "value"));
						$validator->validate_arg($aliasName, "string");

						$fieldAliasAliasesXml = array_key_or_empty($fieldAliasXml, "elements",
							"ALIAS");

						$fieldAliases = [];
						$fieldAliases[] = $aliasName;
						foreach ($fieldAliasAliasesXml as $fieldAliasAliasXml) {
							$fieldAliasText = array_key_or_exception($fieldAliasAliasXml, "value");
							$validator->validate_arg($fieldAliasText, "string");
							$fieldAliases[] = $fieldAliasText;
						}

						foreach ($fieldAliases as $fieldAlias) {
							$xmlTemplate->field_aliases[$fieldAlias] = &$fieldAliases;
						}

					}
						
					/*
					 * fieldaliases (alt form)
					 */
					$fieldsXml = array_key_or_empty($elements, "FIELDS", 0, "elements", "FIELD");
					foreach ($fieldsXml as $fieldXml) {
						$name = array_key_or_exception($fieldXml, "attributes", "VALUE");
						$name = TemplateUtils::normalize($name);

						$aliasesXml = array_key_or_empty($fieldXml, "elements", "ALIAS");
						$aliases = [];
						foreach ($aliasesXml as $aliasXml) {
							$alias = array_key_or_exception($aliasXml, "attributes", "VALUE");
							$alias = TemplateUtils::normalize($alias);
							$aliases[] = $alias;
						}
						$xmlTemplate->field_aliases[$name] = $aliases;
					}
						
					$xmlTemplate->fields_and_aliases = [];
					foreach ($xmlTemplate->field_aliases as $name => $aliases) {
						$xmlTemplate->fields_and_aliases[$name] = null;
						foreach ($aliases as $alias) {
							$xmlTemplate->fields_and_aliases[$alias] = null;
						}
					}
						
						/*
					 * cleanupRemoveDuplicates
					 */
					$cleanupRemoveDuplicates = array_key_exists("CLEANUP-REMOVE-DUPLICATES", 
						$elements);

					$xmlTemplate->cleanup_remove_duplicate = $cleanupRemoveDuplicates;

					$infobox_location = array_key_or_null($elements, "INFOBOX-LOCATION", 0, "value");
					$xmlTemplate->infobox_location = $infobox_location !== null ? strtolower(
						$infobox_location) : null;
					
					$xmlTemplate->major_infobox_change = array_key_exists("MAJOR-INFOBOX-CHANGE", 
						$elements);
						
					sort($xmlTemplate->aliases, SORT_STRING);
					sort($xmlTemplate->aliases_and_name, SORT_STRING);
						
					self::$all_templates[] = $xmlTemplate;
				}
			} catch (Exception $e) {
				ogrebotMail($e);
				throw $e;
			}

			$validator->validate_arg_array(self::$all_templates, __CLASS__, false, false, false);

			$logger->debug("\$all_templates successfully initialized with " .
				count(self::$all_templates)." member(s)"
			);
		}

		return self::$all_templates;
	}

	/**
	 * 
	 * @param string $name
	 * @throws ArrayIndexNotFoundException
	 * @return XmlTemplate
	 */
	public static function get_by_name($name) {
		global $validator;

		$validator->validate_arg($name, "string");

		$normalized = TemplateUtils::normalize($name);

		$all = self::get_all_xml_templates();
		foreach ($all as $template) {
			if ($template->name === $normalized) {
				return $template;
			}
		}

		throw new ArrayIndexNotFoundException(
			"Template not found: $name (normalized: $normalized)");
	}
}