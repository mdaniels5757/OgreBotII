<?php
/**
 * @author magog
 *
 */
class Cleanup_License_Templates_To_Information_Field implements Cleanup_Module {
	
	/**
	 *
	 * @var XmlTemplate[]
	 */
	private $child_to_master;
	
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
		
		$templates_from_xml = array_filter(XmlTemplate::get_all_xml_templates(), 
			function ($xml_template) {
				return $xml_template->get_infobox_location() !== null;
			});
		
		$this->child_to_master = array_merge_all(
			array_map(
				function ($xml_template) {
					return array_fill_keys($xml_template->get_aliases_and_name(), $xml_template);
				}, $templates_from_xml));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		
		$re = "/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			 ")/u";
		if (preg_match($re, $ci->get_text(), $match, PREG_OFFSET_CAPTURE)) {
			$start = $match[0][1];
			$end = $start + strlen($match[0][0]);
			
			$before = substr($ci->get_text(), 0, $start);
			$middle = substr($ci->get_text(), $start, $end - $start);
			$after = substr($ci->get_text(), $end);
			
			$utils = new TemplateUtils($this->template_factory);
			$information_types = $utils->get_all_templates_of_xml_type($before, "Infobox");
			
			if (count($information_types) === 0) {
				return $ci->get_text();
			} else if (count($information_types) === 1) {
				$information = array_shift($information_types);
			} else {
				$information = array_shift($information_types);
				$last_information = array_pop($information_types);
				
				// only continue ir $last_information is actually a subtemplate of $information
				if (strlen($last_information->__get("after")) < strlen($information->__get("after"))) {
					return $text;
				}
			}
			$templates_by_name = map_array_function_keys(
				iterator_to_array(new TemplateIterator($middle)), 
				function (Abstract_Template $template) {
					return [TemplateUtils::normalize($template->getname()), $template];
				}, true);
			
			$xml_template = null;
			foreach ($templates_by_name as $name => $template) {
				if (isset($this->child_to_master[$name])) {
					$xml_template = $this->child_to_master[$name];
					break;
				}
			}
			
			if ($xml_template !== null) {
				if ($xml_template->get_major_infobox_change()) {
					$ci->set_sigificant_changes();
				}
				$location = $xml_template->get_infobox_location();
				if ($location === "after") {
					$template->rename($xml_template->get_name());
					$before_info = $information->__get("before") . $information->__toString();
					$after_info = $information->__get("after");
					if (preg_match(
						"/^(?:\s*?\\n)?\{\{\s*(?:[Oo]bject[ _]+location[ _]+dec|[Gg]lobe[ _]+location|[Ll]ocation[ _]+dec|[Ll]ocation|[Ll]ocation\-Panorama|GeoPolygon|[Ll]ocation[ _]+withheld|Lunar[ _]+location[ _]+dec|[Rr]are[ _]+species[ _]+location)\s*\|[^\}\{]+\}\}/u", 
						$after_info, $match)) {
						$before_info .= $match[0];
						$after_info = substr($after_info, strlen($match[0]));
					}
					$before_info .= "\n";
					// $after_info = preg_replace("/^(?:\s*?\\n)?/u", "", $after_info);
					$newtext = $this->template_information_field_remove_duplicate($before_info, 
						$after_info, $template, "\n");
				} else {
					if ($location === "delete") {
						$ci->set_sigificant_changes(true);
					} else {
						$new_field_value = $this->template_information_field_remove_duplicate("", 
							$information->fieldvalue($location), $template, "<br/>");
						$information->updatefield($location, $new_field_value);
					}
					$newtext = $information->wholePage();
				}
				
				$ci->set_text(
					$newtext . preg_replace("/\r?\n$/mu", "", $template->__get("before")) .
						 $template->__get("after") . $after, false);
				$this->cleanup($ci);
				return;
			}
		}
	}
	
	/**
	 *
	 * @param string $before_template        	
	 * @param string $after_template        	
	 * @param Abstract_Template $template        	
	 * @param string $after_separator        	
	 * @return string
	 */
	private function template_information_field_remove_duplicate($before_template, $after_template, 
		Abstract_Template $template, $after_separator) {
		$license = $template->__get("name");
		$next = 0;
		while ($dupe_template = Template::extract(substr($after_template, $next), $license)) {
			$dupefields = $dupe_template->__get("fields");
			$fields = $template->__get("fields");
			$all_fields_are_duplicate_on_first = true;
			$all_fields_are_duplicate_on_second = true;
			foreach ($dupefields as $fieldname => $fieldvalue) {
				if ((array_key_exists($fieldname, $fields) &&
					 trim($fields[$fieldname]) !== $fieldvalue) ||
					 (!array_key_exists($fieldname, $fields) && trim($fieldvalue) !== "")) {
					$all_fields_are_duplicate_on_first = false;
					break;
				}
			}
			foreach ($fields as $fieldname => $fieldvalue) {
				if ((array_key_exists($fieldname, $dupefields) &&
					 trim($dupefields[$fieldname]) !== $fieldvalue) || (!array_key_exists(
						$fieldname, $dupefields) && trim($fieldvalue) !== "")) {
					$all_fields_are_duplicate_on_second = false;
					break;
				}
			}
			// yes can remove this template
			if ($all_fields_are_duplicate_on_first) {
				$before = $dupe_template->__get("before");
				$after = preg_replace("/^" . preg_quote($after_separator, "/") . "/u", "", 
					$dupe_template->__get("after"));
				$after_template = $before . $after;
			} else if ($all_fields_are_duplicate_on_second) {
				$template = null;
				break;
			} else {
				$next = strlen($dupe_template->__get("before")) .
					 strlen($dupe_template->__toString());
			}
		}
		if (!$template || trim($after_template) === "") {
			$after_separator = "";
		}
		return $before_template . ($template ? $template->__toString() : "") . $after_separator .
			 $after_template;
	}
}