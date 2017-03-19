<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Remove_Duplicate_Templates implements Cleanup_Module {
	
	/**
	 *
	 * @var string[]
	 */
	private $replacements;
	
	
	public function __construct() {
		$all_xml_templates = XmlTemplate::get_all_xml_templates();
		$all_xml_templates_multi_regexes_map = new SplObjectStorage();
		
		$this->regexes = array_map(function(XmlTemplate $xml_template) {
			$regex = $xml_template->get_aliases_and_name_regex();
			return "/${regex}[\S\s]*${regex}/u";
		}, XmlTemplate::get_all_xml_templates());
	}

	/**
	 * TODO $this->template_utils will always be null...
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $tracker) {
		
		$updated = false;
		
		foreach ($this->regexes as $regex) {
			$regex = $all_xml_templates_multi_regexes_map->offsetGet($xml_template);
			
			if (preg_match($regex, $ci->get_text())) {
				$text = $ci->get_text();
				$templates = $this->template_utils->get_all_templates($text, $xml_template);
				
				$num_dupes_expected = 1;
				while (count($templates) > $num_dupes_expected) {
					for($i = 0; $i < count($templates); $i++) {
						for($j = $i + 1; $j < count($templates); $j++) {
							
							$first = array_key_or_exception($templates, $i);
							$second = array_key_or_exception($templates, $j);
							
							try {
								TemplateUtils::remove_duplicate_xml_templates($text, 
									$xml_template, $first, $second);
									
								// successful combination
								$templates = $this->template_utils->get_all_templates($text, 
									$xml_template);
								$ci->set_text($text);
								break 2;
							} catch (CombineTemplatesException $e) {
								$num_dupes_expected++;
							}
						}
					}
				}
			}
		}
		return $updated;
	}
}