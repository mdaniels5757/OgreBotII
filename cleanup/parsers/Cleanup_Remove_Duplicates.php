<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Duplicates implements Cleanup_Module {
	
	/**
	 * 
	 * @var XmlTemplate[] $all_xml_templates
	 */
	private $all_xml_templates;
	
	/**
	 *
	 * @var SplObjectStorage
	 */
	private $all_xml_templates_multi_regexes_map;
	
	
	/**
	 * 
	 * @var TemplateUtils
	 */
	private $template_utils;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_utils = $cleanup_package->get_template_utils();
		
		$this->all_xml_templates = array_filter(XmlTemplate::get_all_xml_templates(), 
			function (XmlTemplate $xml_template) {
				return $xml_template->get_cleanup_remove_duplicate();
			});
		$this->all_xml_templates_multi_regexes_map = new SplObjectStorage();
		foreach ($this->all_xml_templates as $xml_template) {
			$regex = $xml_template->get_aliases_and_name_regex();
			$this->all_xml_templates_multi_regexes_map->offsetSet($xml_template, 
				"/${regex}[\S\s]*${regex}/u");
		}
		
	}
	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		foreach ($this->all_xml_templates as $xml_template) {
			$regex = $this->all_xml_templates_multi_regexes_map->offsetGet($xml_template);
			
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
									
								
								$ci->set_text($text);
								$templates = $this->template_utils->get_all_templates($text,
									$xml_template);
								break 2;
							} catch (CombineTemplatesException $e) {
								$num_dupes_expected++;
							}
						}
					}
				}
			}
		}
	}
}