<?php 

/**
 * 
 * @author magog
 *
 */
//FIXME: PHP 5.6 finally learns how to do better const's...
define("CLEANUP_SHARED_BR", "<(?i:\/br\s*|\s*br\s*\/?\s*)>");
define("CLEANUP_SHARED_OPT_BR", "(?:" . CLEANUP_SHARED_BR . ")?");
define("CLEANUP_SHARED_ORIGINAL_UPLOAD_DATE_IGNORABLE", 
	"\.?\s*?" . CLEANUP_SHARED_OPT_BR . "(?:\s*\*?\s*\(?(?:\{\{[Oo]riginal upload date\|[\d\-]+\}\}|\{\{" .
		 "Date[\d\|]+\}\} \(first version\)\; \{\{Date[\d\|]+}} \(last version\)" .
		 ")\)?)?\.?(?<trailing>\s*)$");
define("CLEANUP_SHARED_INTERMEDIATE_TEMPLATES", 
	"(?:\s*?(?:\{\{.+?\}\}|%%%MTONOPARSE_COMMENT\d%%%)\s*?\\n)*");
define("CLEANUP_SHARED_LICENSE_HEADER", 
	"(\\n(\=+) *\{\{\s*[Ii][Nn][Tt]\s*\:[Ll]icen[cs]e\-header\s*\}\} *\:? *\\2\s*?\\n" .
		 CLEANUP_SHARED_INTERMEDIATE_TEMPLATES . ")");
class Cleanup_Shared {
	
	// /////////////////////////
	// ///// Text regexes //////
	// /////////////////////////
	const BR = CLEANUP_SHARED_BR;
	const OPT_BR = CLEANUP_SHARED_OPT_BR;
	const ORIGINAL_UPLOAD_DATE_IGNORABLE = CLEANUP_SHARED_ORIGINAL_UPLOAD_DATE_IGNORABLE;
	const INTERMEDIATE_TEMPLATES = CLEANUP_SHARED_INTERMEDIATE_TEMPLATES;
	const LICENSE_HEADER = CLEANUP_SHARED_LICENSE_HEADER;
	
	///////////////////////////
	//////// Templates ////////
	///////////////////////////
	const INFORMATION = "Information";
	
	///////////////////////////
	///// Template fields /////
	///////////////////////////
	const AUTHOR = "author";
	const DATE = "date";
	const DESCRIPTION = "description";
	const LOCATION = "location";
	const MIGRATION = "migration";
	const OTHER_FIELDS = "other_fields";
	const OTHER_VERSIONS = "other_versions";
	const PERMISSION = "permission";
	const SOURCE = "source";
	
	///////////////////////////
	//////// Warnings /////////
	///////////////////////////
	const BAD_LANGLINK = "bad_langlink";
	const CITE_NO_REPLACEMENT = "cite_no_replacement";
	const DUPLICATE_AUTHOR = "duplicate_author";	
	const ENCODING_MANGLED = "encoding_mangled";
	const INFORMATION_OMITTED = "information_omitted";
	const LEGEND_OMITTED = "legend_omitted";
	const LOCAL_DELETION = "local_deletion";
	const MANGLED_TRANSFER = "mangled_transfer";
	const OTRS_REMOVED = "otrs_removed";
	const SUSPICIOUS_INTERWIKIS = "suspicious_interwikis";
	const TEMPLATE_UNCLOSED = "template_unclosed";
	const UNCLOSED_XML = "unclosed_xml";
	
	/**
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 * 
	 */
	public function __construct() {
		global $logger;
		
		$logger->trace("Cleanup_Shared::__construct()");
		
		load_property_file_into_variable($this->constants, "cleanup");
		
		$licenses_array = array_key_or_exception($this->constants, "non_uploader_licenses");
		$license_regex = implode("|",
			array_map(function ($license) {
				return regexify_template($license, false);
			}, $licenses_array));
		
		$licenses_array_regex = array_key_or_exception($this->constants, "non_uploader_regexes");
		$license_regex .= "|" . implode("|", $licenses_array_regex);
		$this->constants["non_uploader_licenses_regex"] = "(?i:(?:(?:pd\-art(?:\-two)?|pd\-scan)\s*\|)?\s*(?:$license_regex)\s*)";
		
		$this->regexify_string_in_template_properties("own");
		$this->regexify_string_in_template_properties("self-photographed");
		
		// unnecessary local templates
		$this->regexify_raw_in_cleanup_properties("unnecessary_local_templates");
		$this->regexify_raw_in_cleanup_properties("kettos_local_templates");
		$this->regexify_raw_in_cleanup_properties("warning_local_templates");
		$this->regexify_raw_in_cleanup_properties("refs_local_templates");
		$this->regexify_raw_in_cleanup_properties("information_local_templates");
		$this->regexify_raw_in_cleanup_properties("flickr_local_templates");
		$this->regexify_raw_in_cleanup_properties("langlinks", true);
		$this->regexify_raw_in_cleanup_properties("no_modify_interwiki_prefixes", true);
		$this->regexify_raw_in_cleanup_properties("otrs", true);
		
		$months = range(1, 12);
		array_walk($months,
			function ($month) {
				$this->regexify_raw_in_cleanup_properties("month_$month", false);
			});
		
		$this->regexify_raw_in_cleanup_properties("category");
		
		$this->regexify_xml_template("free_screenshot", "Free screenshot");
		$this->regexify_xml_template("pd_art", "PD-Art");
		
		// unknown
		$this->regexify_multilingual_author("unknown");
		$this->regexify_multilingual_author("unknown-photographer");
		$this->regexify_multilingual_author("anonymous");
	}
	
	/**
	 *
	 * @param string $constant_prefix
	 * @param string $template_name
	 * @return void
	 */
	private function regexify_xml_template($constant_prefix, $template_name) {
		$xml_template = XmlTemplate::get_by_name($template_name);
		$aliases_and_name = $xml_template->get_aliases_and_name();
		$this->constants["${constant_prefix}_regex"] = "(?:" . implode("|",
			array_map("regexify_template", $aliases_and_name)) . ")";
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $whitespace_any
	 */
	private function regexify_string_in_template_properties($key, $whitespace_any = true) {
		global $logger, $validator;
	
		$validator->validate_arg($key, "string");
		$validator->validate_arg($whitespace_any, "bool");
	
		$logger->trace("regexify_string_in_template_properties($key, $whitespace_any)");
	
		$strings = array_key_or_exception($this->constants, $key);
		$validator->validate_arg($strings, "array");
		$validator->assert(count($strings) > 0);
	
		$templates = array();
		foreach ($strings as $string) {
			$string = mb_trim($string);
			$string = preg_quote($string, "/");
			if ($whitespace_any) {
				$string = preg_replace("/\s+/u", "[^a-z]*", $string);
			}
			$string = preg_replace("/([ăá])/ui", "[a$1]", $string);
			$string = preg_replace("/([čç])/ui", "[c$1]", $string);
			$string = preg_replace("/(é)/ui", "[e$1]", $string);
			$string = preg_replace("/([ıí])/ui", "[i$1]", $string);
			$string = preg_replace("/(ł)/ui", "[l$1]", $string);
			$string = preg_replace("/([óőöø])/ui", "[o$1]", $string);
			$string = preg_replace("/([şŝ])/ui", "[s$1]", $string);
			$string = preg_replace("/([üű])/ui", "[u$1]", $string);
			$templates[] = $string;
		}
		$this->constants["${key}_regex"] = "/^(\s*)(?:" . implode("|", $templates) . ")(\s*)$/ui";
		$logger->trace("\$Cleanup_Shared[${key}_regex] = " . $this->constants["${key}_regex"]);
	}
	/**
	 *
	 * @param string $key
	 * @param bool $is_wiki_pagename
	 *        	DEFAULT false
	 * @return void
	 */
	private function regexify_raw_in_cleanup_properties($key, $is_wiki_pagename = false) {
		global $logger, $validator;
	
		$validator->validate_arg($key, "string");
		$logger->trace("regexify_raw_in_cleanup_properties($key)");
	
		$constants = array_unique(array_key_or_empty($this->constants, $key));
		if ($is_wiki_pagename) {
			$strings = array_map("regexify_template", $constants);
		} else {
			// note: PHP defect, can't reference preg_quote as a string
			// and pass argument
			$strings = array_map(function ($string) {
				return preg_quote($string, "/");
			}, $constants);
		}
	
		$strings_r = array_key_or_empty($this->constants, "${key}_r");
	
		$regex = implode("|", array_merge($strings, $strings_r));
	
		$this->constants["${key}_regex"] = $regex;
		$logger->trace("\$Cleanup_Shared[${key}_regex] = " . $this->constants["${key}_regex"]);
	}
	
	/**
	 * 
	 * @param string $key
	 * @return void
	 */
	private function regexify_multilingual_author($key) {
		global $logger;
	
		$this->regexify_raw_in_cleanup_properties($key);
		$this->constants["${key}_author_regex"] = "/^\{{0,2}\s*(?i:" .
			$this->constants["${key}_regex"] . ")\s*?\}{0,2}\s*?\;?\.?\s*?" . Cleanup_Shared::OPT_BR .
			"\s*?(?:\*?\s*\(?\{\{[Oo]riginal uploader\|[^\|\}]+?\|(?:\s*\d\s*\=\s*)?\w+\|(?:\s*\d\s*\=" .
			"\s*)?[\w\-]+\}\}\)?\.?(?:\s*Later version\(s\) were uploaded by .+?)?)?(\s*)$/u";
			$logger->trace(
				"\$Cleanup_Shared[${key}_author_regex] = " . $this->constants["${key}_author_regex"]);
	}
	
	/**
	 * @return array
	 */
	public function get_constants() {
		return $this->constants;
	}
	
	/**
	 * 
	 * @param Abstract_Template $template
	 * @return string
	 */
	public static function remove_template_and_trailing_newline(Abstract_Template $template) {
		return $template->__get("before") . preg_replace("/^\s*\r?\n/m", "", $template->__get("after"));
	}
	
}