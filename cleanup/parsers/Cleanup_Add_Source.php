<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Add_Source implements Cleanup_Module {
	
	
	/**
	 *
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
	// add source field in {{information}} template if it's not there
		// per http://commons.wikimedia.org/w/index.php?oldid=131405179#cleanup.js
		$iterator = new TemplateIterator($text, $this->template_factory);
		foreach ($iterator as $template) {
			if (ucfirst(mb_trim($template->__get("name"))) === Cleanup_Shared::INFORMATION &&
				 !$template->fieldisset(Cleanup_Shared::SOURCE)) {
				// keep consistent spacing: steal it from another field
				$field = $template->fieldvalue(Cleanup_Shared::DESCRIPTION);
				if ($field === null) {
					$field = $template->fieldvalue(Cleanup_Shared::DATE);
					if ($field === null) {
						$field = $template->fieldvalue(Cleanup_Shared::AUTHOR);
						if ($field === null) {
							$logger->warn("Template with basically no information found: $text");
							continue;
						}
						$name = Template_Utils::get_template_field_with_whitespace($template, 
							Cleanup_Shared::AUTHOR);
					} else {
						$name = Template_Utils::get_template_field_with_whitespace($template, 
							Cleanup_Shared::DATE);
					}
				} else {
					$name = Template_Utils::get_template_field_with_whitespace($template, 
						Cleanup_Shared::DESCRIPTION);
				}
				
				if (!preg_match("/(\s*)$/u", $field, $spacing_value_match)) {
					$validator->assert(false, 
						"How did this happen? \$field = " . print_r($field, true));
				}
				// preserve caps
				$source = preg_match("/[A-Z]/u", $name[0]) ? "Source" : Cleanup_Shared::SOURCE;
				$fieldname = str_pad($source, strlen($name));
				
				// add field after description, if present, and before the other fields, if not
				$fields = $template->__get("fields");
				$position = array_search(trim($name), array_keys($fields));
				if (trim(strtolower($name)) === Cleanup_Shared::DESCRIPTION) {
					$position++;
				}
				// array splice, but keep keys
				$new_fields = array_merge(array_slice($fields, 0, $position, true), 
					array(trim($fieldname) => "$fieldname=$spacing_value_match[0]"), 
					array_slice($fields, $position, count($fields) - $position, true));
				$template->__set("fields", $new_fields);
				
				$ci->set_text($template->wholePage());
			}
		}
	}
}