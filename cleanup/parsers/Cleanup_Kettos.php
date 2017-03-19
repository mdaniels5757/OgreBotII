<?php
/**
 * @author magog
 *
 */
class Cleanup_Kettos implements Cleanup_Module {
	
	/**
	 *
	 * @var string
	 */
	private $kettos_local_templates_regex;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->kettos_local_templates_regex = $cleanup_package->get_constants()["kettos_local_templates_regex"];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		if (!$ci->get_author_information()) {
			return;
		}
		
		foreach ($ci->get_page_parser()->__get("elements") as $unparsed_element) {
			if (preg_match(
				"/<\!\-\-\s*Templates[^>]+\"Template:(?:$this->kettos_local_templates_regex" .
					 ")\"[^>]+do not appear to exist on commons\./u", $unparsed_element)) {
				$this->kettos_replace($ci);
			}
		}
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @return void
	 */
	private function kettos_replace(Cleanup_Instance $ci) {
		$username = $ci->get_author_information()->get_username();
		$language = $ci->get_author_information()->get_language();
		$project = $ci->get_author_information()->get_project();
		
		$ci->preg_replace(
			"/\{\{([Cc]{2}[\-\.\,\w]+?)\}\}\s*\{\{([Cc]{2}[\-\.\,\w]+?)\}\}\s*" .
				 "\{\{(GFDL(?:\-(?:(?:no|with)\-disclaimers|en|sr|it))?)(?:\|" .
				 preg_quote($username) . ")?((?i)\|migration\=[a-z\-]+)?\}\}/u", 
				"{{self|author={{user at project|1=" . escape_preg_replacement($username) .
				 "|2=$project|3=$language}}|$1|$2|$3$4}}");
		
		$ci->preg_replace(
			"/\{\{([Cc]{2}[\-\.\,\w]+?)\}\}\s*\{\{(GFDL(?:\-(?:(?:no|with)\-disclaimers|en|sr|it))?)" .
				 "(?:\|" . preg_quote($username) . ")?((?i)\|migration\=[a-z\-]+)?\}\}/u", 
				"{{self|author={{user at project|1=" . escape_preg_replacement($username) .
				 "|2=$project|3=$language}}|$1|$2$3}}");
		$ci->preg_replace("/\{\{([Cc]{2}.+?)\}\}/u", 
			"{{self|author={{user at project|1=" . escape_preg_replacement($username) .
				 "|2=$project|3=$language}}|$1}}");
		
		/* not major change */
		$ci->preg_replace(
			"/\{\{([Uu]ser[ _]+at[ _]+project)\|1\=([^\]\|\=]+?)\|2=([^\]\|]+?)\|3=([^\]\|]+?)\}\}/u", 
			"{{" . "$1|$2|$3|$4}}", false);
	}
}