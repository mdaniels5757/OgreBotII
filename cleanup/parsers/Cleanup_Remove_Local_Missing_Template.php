<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Local_Missing_Template implements Cleanup_Module {
	
	/**
	 * 
	 * @var array
	 */
	private $constants;
	
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->constants = $cleanup_package->get_constants();
	}

	/**
	 *
	 * @param string $page_text
	 * @param string $re
	 *        	regular expression
	 * @return boolean
	 */
	private function remove_local_missing_templates(Cleanup_Instance $ci, $re) {
		$ci->iter_replace(
			"/(<\!\-\- Templates[^>]+)\"Template:(?:$re)\"\,? ([^>]+do not appear to exist on commons. " .
			"\-\->)/u", "$1$2", false, $flag);
	
		
		$ci->preg_replace(
			["/<\!\-\- Templates were used in the original description page as well ?\, but " .
			"do not appear to exist on commons[\.#] \-\->\s*?\n/u",
					"/<\!\-\- Templates were used in the original description page as well ?\, but " .
			"do not appear to exist on commons[\.#] \-\->/u"], "", false, $flag);
	
		return $flag;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		global $logger;
		
		$this->remove_local_missing_templates($ci, 
			$this->constants["unnecessary_local_templates_regex"]);
		
		$this->remove_local_missing_templates($ci, 
			$this->constants["kettos_local_templates_regex"]);
		
		// check deletion or possibly non-free issues
		$check_dels = $this->remove_local_missing_templates($ci, 
			$this->constants["warning_local_templates_regex"]);
		
		if ($check_dels) {
			$ci->add_warning(Cleanup_Shared::LOCAL_DELETION);
		}
		
		// check ref tags to see if a reference was left untransferred on local project
		$check_refs = $this->remove_local_missing_templates($ci, 
			$this->constants["refs_local_templates_regex"]);
		if ($check_refs) {
			$ci->add_warning(Cleanup_Shared::CITE_NO_REPLACEMENT);
		}
		
		// check for omitted information template
		$check_inf = $this->remove_local_missing_templates($ci, 
			$this->constants["information_local_templates_regex"]);
		
		if ($check_inf) {
			$ci->add_warning(Cleanup_Shared::INFORMATION_OMITTED);
		}
		
		$check_flickr = $this->remove_local_missing_templates($ci, 
			$this->constants["flickr_local_templates_regex"]);
			
			// add flickrreview if necessary
		if ($check_flickr) {
			if (!$ci->get_template("Flickrreview") && !$ci->get_template("FlickrReview") &&
				 !$ci->get_template("Flickreview")) {
				if (preg_match("/" . Cleanup_Shared::LICENSE_HEADER . "/u", $ci->get_text(), $match, 
					PREG_OFFSET_CAPTURE)) {
					$index = strlen($match[0][0]) + $match[0][1];
				} else {
					$index = 0;
				}
				$ci->set_text(
					substr($ci->get_text(), 0, $index) . "{{Flickrreview}}\r\n" .
						 substr($ci->get_text(), $index));
			}
		}
		
		// check for more templates; add them to the error log.
		if (preg_match(
			"/<\!\-\- Templates[^>]+ were used in the original description page as well ?\, " .
				 "but do not appear to exist on commons[\.\#] \-\->/u", $ci->get_text(), $match)) {
			$logger->info("Unrecognized local templates: $match[0]; full text: " . $ci->get_text());
		}
		
	}
}