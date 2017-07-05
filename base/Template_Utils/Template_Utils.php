<?php

/** 
 * WARNING: This class is expensive. Use with caution. If execution time 
 * lags, please rewrite TemplateIterator.
 * @author magog
 *
 */
class Template_Utils {
	
	/**
	 *
	 * @var string[][]
	 */
	private static $types;
	
	/**
	 *
	 * @var TemplateIterator
	 */
	private $cached;
	
	/**
	 *
	 * @var string
	 */
	private $text;
	
	
	/**
	 * @var Template_Factory
	 */
	private $template_factory;
	/**
	 *
	 * @return void
	 */
	private static function initStatic() {
		if (self::$types === null) {
			load_property_file_into_variable(self::$types, "license_templates");
		}
	}
	
	/**
	 * 
	 * @param Template_Factory|null $template_factory
	 */
	public function __construct(Template_Factory $template_factory = null) {
		self::initStatic();
		$this->template_factory = $template_factory;
	}
	
	/**
	 *
	 * @param string $text        	
	 */
	private function clean($text) {
		global $validator;
		
		$validator->validate_arg($text, "string");
		
		if ($this->text !== $text) {
			$this->cached = map_array_all(
				iterator_to_array(new TemplateIterator($text, $this->template_factory)), 
				function ($template) {
					return [self::normalize($template->getname()), $template];
				});
			$this->text = $text;
		}
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string $type        	
	 * @param int $max        	
	 * @throws IllegalArgumentException
	 * @return Abstract_Template[]
	 */
	public function getPredefinedClassTypesMulti($text, $type, $max = 0) {
		try {
			$allTypes = array_key_or_exception(self::$types, "all_$type");
		} catch (ArrayIndexNotFoundException $e) {
			throw new IllegalArgumentException("Unrecognized template mutli type: $type", $e);
		}
		return $this->getPredefinedClassTypesNoStatic($text, $allTypes, $max);
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string|string[] $types        	
	 * @param int $max        	
	 * @return Abstract_Template[]
	 */
	public function getPredefinedClassTypes($text, $types, $max = 0) {
		return $this->getPredefinedClassTypesNoStatic($text, $types, $max);
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string|string[] $types        	
	 * @param int $max
	 *        	DEPRECATED
	 * @throws IllegalArgumentException
	 * @return Abstract_Template[]
	 */
	private function getPredefinedClassTypesNoStatic($text, $types, $max = 0) {
		$this->clean($text);
		
		if (!is_array($types)) {
			$types = [$types];
		}
		
		try {
			$typeTemplates = array_merge_all(
				array_map(
					function ($type) {
						return array_key_or_exception(self::$types, $type);
					}, $types));
		} catch (ArrayIndexNotFoundException $e) {
			throw new IllegalArgumentException("Unrecognized template type: $type", $e);
		}
		
		$allTypes = [];
		foreach (array_merge_all($this->cached) as $pageTemplate) {
			$name = self::normalize($pageTemplate->__get("name"));
			if (array_search($name, $typeTemplates) !== false) {
				$allTypes[] = $pageTemplate;
				
				if ($max === count($allTypes)) {
					break;
				}
			}
		}
		return $allTypes;
	}
	
	/**
	 *
	 * @param string $name        	
	 * @return string
	 */
	public static function normalize($name) {
		global $MB_WS_RE;
		
		return preg_replace("/[${MB_WS_RE}_]+/u", " ", ucfirst_utf8(mb_trim($name)));
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param XmlTemplate|XmlTemplate[] $xmlTemplates        	
	 * @return Abstract_Template[]
	 */
	public function get_all_templates($text, $xmlTemplates) {
		global $validator;
		
		if (!is_array($xmlTemplates)) {
			$xmlTemplates = [$xmlTemplates];
		}
		
		$validator->validate_arg_array($xmlTemplates, "XmlTemplate");
		
		$this->clean($text);
		
		$all_type_names = array_merge_all(
			array_map(
				function ($xml_template) {
					return $xml_template->get_aliases_and_name();
				}, $xmlTemplates));
		
		return array_merge_all(prune_array_to_keys($this->cached, $all_type_names));
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string $type        	
	 * @return Abstract_Template[]
	 */
	public function get_all_templates_of_xml_type($text, $type) {
		global $validator;
		
		$validator->validate_arg($type, "string");
		
		$xmlTemplateType = array_key_or_exception(XmlTemplateType::get_all_types(), $type);
		
		return $this->get_all_templates($text, $xmlTemplateType->get_xmlTemplates());
	}
	
	/**
	 *
	 * @param string $template        	
	 * @param string $fieldname        	
	 * @param string[] $allFields        	
	 * @return string|null
	 */
	private static function get_template_field_with_whitespace_internal(&$template, $fieldname, 
		&$allFields) {
		$value = @$allFields[$fieldname];
		if ($value === null) {
			return null;
		}
		
		$quote = preg_quote($fieldname);
		preg_match("/^(\s*$quote\s*)\=/u", $value, $match);
		
		return $match[1];
	}
	
	/**
	 *
	 * @param Abstract_Template $template        	
	 * @param string $fieldname        	
	 * @param bool $ignore_case_first        	
	 * @return string
	 */
	public static function get_template_field_with_whitespace($template, $fieldname, 
		$ignore_case_first = true) {
		$allFields = $template->__get("fields");
		if ($ignore_case_first) {
			$name = self::get_template_field_with_whitespace_internal($template, 
				ucfirst_utf8($fieldname), $allFields);
			if ($name !== null) {
				return $name;
			}
			
			return self::get_template_field_with_whitespace_internal($template, 
				lcfirst_utf8($fieldname), $allFields);
		}
		
		return self::get_template_field_with_whitespace_internal($template, $fieldname, $allFields);
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param XmlTemplate $xmlTemplate        	
	 * @param Abstract_Template $first        	
	 * @param Abstract_Template $second        	
	 * @return void
	 * @throws TemplatesOverlapException
	 * @throws TemplateFieldsMismatchException
	 */
	public static function remove_duplicate_xml_templates(&$text, &$xmlTemplate, &$first, &$second) {
		global $logger, $validator;
		
		$validator->validate_arg($text, "string");
		$validator->validate_arg($xmlTemplate, "XmlTemplate");
		$validator->validate_arg($first, "Template");
		$validator->validate_arg($second, "Template");
		
		$firstString = ucfirst_utf8(mb_trim($first->getname()));
		$secondString = ucfirst_utf8(mb_trim($second->getname()));
		if ($logger->isDebugEnabled()) {
			$textLen = strlen($text);
			$xmlTemplateString = $xmlTemplate->get_name();
			$logger->debug(
				"remove_duplicate_xml_templates($textLen, $xmlTemplateString," .
					 "$firstString, $secondString)");
		}
		
		$firstAfter = $first->__get("after");
		$secondAfter = $second->__get("after");
		
		if (strlen($firstAfter) < strlen($secondAfter)) {
			throw new TemplatesOverlapException($firstString, $secondString);
		}
		
		$firstFields = $first->__get("fields");
		$secondFields = $second->__get("fields");
		
		$fieldsToAdd = array();
		foreach ($secondFields as $name => $ignored) {
			$nameAliases = array_key_or_value($xmlTemplate->get_field_aliases(), $name, [$name]);
			
			$empty = true;
			foreach ($nameAliases as $alias) {
				
				if (array_key_exists($alias, $firstFields)) {
					
					$fieldValue1 = mb_trim($first->fieldvalue($alias));
					$fieldValue2 = mb_trim($second->fieldvalue($name));
					if ($fieldValue1 != "" && $fieldValue2 != "" && $fieldValue1 !== $fieldValue2) {
						// mismatch; do not combine; end execution
						// quotation marks to stringify (for the validator)
						throw new TemplateFieldsMismatchException($firstString, $secondString, 
							"$name");
					} else {
						$empty = false;
					}
				}
			}
			
			if ($empty) {
				$fieldsToAdd[$name] = mb_trim($second->fieldvalue($name));
			}
		}
		$firstBefore = $first->__get("before");
		$secondBefore = $second->__get("before");
		$offset = strlen($secondBefore) - strlen($firstBefore) - strlen($first->__toString());
		if ($offset < 0) {
			$validator->assert(false, "Offset is negative. template: $name; text: $text");
		}
		
		if (preg_match("/\n\s*$/", $secondBefore)) {
			$secondAfter = preg_replace("/^[\t ]*\r?\n/", "", $secondAfter);
		}
		
		foreach ($fieldsToAdd as $name => $val) {
			$first->addfield($val, $name);
		}
		
		$first->rename($xmlTemplate->get_name());
		
		$text = $firstBefore . $first->__toString() . substr($firstAfter, 0, $offset) . $secondAfter;
	}
}
