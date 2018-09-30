<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Remove_Empty_Templates implements Cleanup_Module {
	
	/**
	 * 
	 * @var string
	 */
	private $license_template_regex;
	
	/**
	 * 
	 * @var string
	 */
	private $template_regex;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		
		$this->template_regex = $this->get_regexified_templates(
			$constants["empty_templates_remove"], "\$template");
		
		// add langlink icons to regular license templates to remove
		$this->license_template_regex = $this->get_regexified_templates(
			array_merge(array_key_or_exception($constants, "empty_license_templates_remove"), 
				str_append($constants["langlinks"], " icon")), 
			escape_preg_replacement(
				Cleanup_Shared::LICENSE_HEADER . Cleanup_Shared::INTERMEDIATE_TEMPLATES) .
				 "\$template");
	}
	
	/**
	 * 
	 * @param string[] $templates
	 * @param string $full_regex_string
	 * @return string
	 */
	private function get_regexified_templates(array $templates, $full_regex_string) {
	
		$regex_string = implode("|", array_map(function($template) {
			return regexify_template($template);
		}, $templates));
		
		$full_regex_string_replaced = replace_named_variables($full_regex_string,
			["template" => "\{\{\s*(?:$regex_string)\s*\}\}(?:\s*?\\n)?"], false);
		
		return "/$full_regex_string_replaced/u";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * Remove the following templates if they take no parameters
		 */
		$ci->preg_replace($this->template_regex, "");
		
		$ci->preg_replace("/\{\{\s*(?:[Ll]egend)\s*\}\}(?:\s*?\\n)?/u", "", true, $legend);
		if ($legend) {
			$ci->add_warning(Cleanup_Shared::LEGEND_OMITTED);
		}
		
		/**
		 * Remove the following templates if they are located in the license field and if
		 * they take no parameters
		 */
		$ci->iter_replace($this->license_template_regex, "$1");
	}
}