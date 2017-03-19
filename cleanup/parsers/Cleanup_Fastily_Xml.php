<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Fastily_Xml implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		if ($this->is_xml_compliant($ci->get_text())) {
			return;
		}

		Environment::get()->get_logger()->debug("Xml error; trying cleanup.");
		$this->try_cleanup_xml($ci);
	}
	
	/**
	 * 
	 * @param Cleanup_Instance $ci
	 * @return void
	 */
	private function try_cleanup_xml(Cleanup_Instance $ci) {
		$logger = Environment::get()->get_logger();
		
		preg_match(
			"/\=\=\s*\{\{Original upload log\}\}\s*\=\=\n" .
				 ".*?\{\{original description\|[a-z\-.]+\|.+?\}\}\n" .
				 "\{\|\s*class\=\"wikitable\"\s*\n" . "! \{\{int:filehist-datetime\}\} !! .+?\n/", 
				$ci->get_text(), $top_match, PREG_OFFSET_CAPTURE);
		
		if ($top_match) {
			$top = $top_match[0][1] + strlen($top_match[0][0]);
			preg_match("/(?<=\n)\|\}/", $ci->get_text(), $bottom_matches, PREG_OFFSET_CAPTURE, 
				$top);
			if ($bottom_matches) {
				$bottom = $bottom_matches[0][1];
				
				$text = substr($ci->get_text(), $top, $bottom - $top);
				$new_text = preg_replace(
					"/^\|\-\n\| [\d-]+ [\d:]+ \|\| [\d]+.[\d]+. \([\d]+ bytes\) " .
						 "\|\| .+? \|\| \'\'<nowiki>.*?\|Permission=$/um", "$0</nowiki>", $text);
				if ($new_text !== $text) {					
					$full_text = substr($ci->get_text(), 0, $top) . $new_text .
						 substr($ci->get_text(), $bottom);
					
					if ($this->is_xml_compliant($full_text)) {
						$ci->set_text($full_text);
						$logger->debug("Text successfully altered.");
						return;
					}
				}
			}
		}
		$logger->debug("Unable to alter text.");			
	}
	
	/**
	 * 
	 * @param string $text
	 * @return bool
	 */
	private function is_xml_compliant($text) {
		try {
			new Page_Parser($text);
			return true;
		} catch (XmlError $e) {
			return false;
		}
	}
}