<?php

/**
 * 
 * @author magog
 *
 */
class Cleanup_Bad_Template_Close implements Cleanup_Module {
	
	/**
	 *
	 * @var string
	 */
	private $bad_langlink_regex;
	
	
	/**
	 * 
	 * @var TemplateUtils $template_utils
	 */
	private $template_utils;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		$this->bad_langlink_regex = "/(?<!{)\{[\s_]*(?:$constants[langlinks_regex])[\s_]*\|/";
		$this->template_utils = $cleanup_package->get_template_utils();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		global $MB_WS_RE, $MB_WS_RE_OPT;


		$text = $ci->get_text();
		
		// check for bad open langlinks (commons.wikimedia.org/w/index.php?diff=176304964)
		if (preg_match($this->bad_langlink_regex, $text, $match, PREG_OFFSET_CAPTURE)) {
			$ci->add_warning(Cleanup_Shared::BAD_LANGLINK);
			return;
		}
		
		$all_infobox_templates = $this->template_utils->get_all_templates_of_xml_type($text, 
			"Infobox");
		foreach ($all_infobox_templates as $infobox) {
			$after = $infobox->__get("after");
			if (preg_match("/^$MB_WS_RE_OPT\|$MB_WS_RE_OPT([\w$MB_WS_RE]+?)\s*\=/u", $after, 
				$match_arr)) {
				
				$xmlTemplate = XmlTemplate::get_by_name($infobox->getname());
				$field = TemplateUtils::normalize($match_arr[1]);
				
				$suspicious_field = array_key_exists($field, $xmlTemplate->get_fields_and_aliases());
				
				if ($suspicious_field) {
					// there should be an unclosed template somewhere if it's truly unclosed... look for by
					// removing all the templates from the page and looking for }}
					$template_indices = array();
					foreach (new TemplateIterator($text) as $template) {
						$startIndex = strlen($template->__get("before"));
						$endIndex = strlen($template->__get("templatestring")) + $startIndex;
						$template_indices[] = array($startIndex, $endIndex);
					}
					
					preg_match_all("/(?<!\})\}\}(?!\})/u", $text, $matches, PREG_OFFSET_CAPTURE);
					$ok_indices = array();
					
					$i = 0;
					foreach ($matches[0] as $match) {
						$this_index = $match[1];
						while ($i < count($template_indices)) {
							
							$startIndex = $template_indices[$i][0];
							$endIndex = $template_indices[$i][1];
							
							if ($this_index <= $startIndex) {
								break;
							}
							
							$i++;
							
							// inside a template... nevermind
							if ($this_index < $endIndex) {
								continue 2;
							}
						}
						
						// found!
						$before = $infobox->__get("before");
						$templatestring = $infobox->__get("templatestring");
						$after = $infobox->__get("after");
						
						$templatestringlen = strlen($templatestring);
						if ($templatestringlen < 2 ||
							 substr($templatestring, $templatestringlen - 2) != "}}") {
							$errorMessage = "Unexpected template string. Value: $templatestring. Page text: $text";
							ogrebotMail($errorMessage);
							continue;
						}
						
						if ($ci->set_text(
							$infobox->__get("before") .
								 substr($templatestring, 0, $templatestringlen - 2) . $after)) {
							// iterate...
							$this->cleanup($ci);
						}
						
						return;
					}
				}
			}
			return;
		}
	}
}