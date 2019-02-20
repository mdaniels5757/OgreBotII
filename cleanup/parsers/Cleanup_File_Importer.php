<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_File_Importer implements Cleanup_Module {

	/**
	 * 
	 * @var string
	 */
	private $dont_modify_links_regex;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		$this->dont_modify_links_regex = "/\s*:?\s*(user|file|image|[a-z]|$constants[langlinks_regex])\s*\:\s*\S/i";
	}

	/**
	 * 
	 * @param Cleanup_Instance $ci
	 * @return string|NULL
	 */
	private function get_project(Cleanup_Instance $ci): ?string {
		foreach ($ci->get_page_parser()->__get("elements") as $unparsed_element) {
			
			if (preg_match("/<\!\-\-\s*This file was moved here using FileImporter from " . 
					"\/\/([\w+\-]+)\.wikipedia\./u", $unparsed_element, $match)) {
				return $match[1];
			}
		}
		
		return null;
	}
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$lang = $this->get_project($ci);
		if ($lang) {
			$template = $ci->get_template(Cleanup_Shared::INFORMATION);
			if ($template) {				
				foreach(["description", "source", "date", "author", "other_versions", 
						"location", "other_fields", "permission"] as $field) {
					//update interwikis work
					$value = $template->fieldvalue($field);
					if ($value) {
						$last_offset = 0;
						preg_match_all("/\[\[([^\[\]]+)\]\]/", $value, $matches,
								PREG_OFFSET_CAPTURE | PREG_SET_ORDER, $last_offset);
						foreach (array_reverse($matches) as list(list($whole_link, $offset), list($link))) {
							list($target, $text, $etc) = array_pad(explode("|", $link, 3), 3, null);
							if (!$etc && !preg_match($this->dont_modify_links_regex, $target)) {
								$text = $text ?? $target;
								$value = substr($value, 0, $offset) . "[[:$lang:$target|$text]]" .
								substr($value, $offset + strlen($whole_link));
							}
						}
						$template->updatefield($field, $value);
					}
				}
				
				$additional = $template->fieldvalue("additional_information");
				if ($additional !== false && preg_match("/^\s*$/", $additional)) {
					$template->removefield("additional_information");
				}
				
				$description = $template->fieldvalue("description");
				if ($description) {					
					//add language wrapper to description
					if (strpos($description, "{{") === false) {
						$number_prefix = strpos($description, "=") ? "1=": "";
						$description = preg_replace("/^(\s*)([\s\S]+?)(\s*)$/", 
								"$1{{{$lang}|{$number_prefix}$2}}$3", $description);
					}
					$template->updatefield("description", $description);
					
					$ci->set_text($template->wholePage());
				}
			}
		}
	}
	
}