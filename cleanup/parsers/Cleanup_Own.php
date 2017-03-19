<?php
/**
 * @author magog
 *
 */
class Cleanup_Own implements Cleanup_Module {
	
	/**
	 *
	 * @var string[]
	 */
	private $regex_keys;
	
	/**
	 *
	 * @var string[]
	 */
	private $regex_values;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		
		$regexes = [$constants["own_regex"] => "own", 
			$constants["self-photographed_regex"] => "self-photographed"];
		
		$this->regex_keys = array_keys($regexes);
		$this->regex_values = array_values(
			array_map(function ($regex) {
				return "{{" . "$regex}}";
			}, $regexes));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * if only "original text" is present in source field; remove it, as it's redundant; {{own}} work
		 */
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if (!$t) {
			return;
		}
		
		$source = $t->fieldvalue(Cleanup_Shared::SOURCE);		
		if (!$source) {
			return;
		}
		$source = mb_trim($source);
		
		// code that can't be placed into properties files
		$author_info = $ci->get_author_information();
		$changed = false;
		if ($author_info) {
			if ($ci->get_author_information()->get_project() == 'wikipedia' &&
				 $ci->get_author_information()->get_language() == 'en') {
				$username_regex = str_replace(" ", "[ _]+", 
					"(?i:" . $author_info->get_username()[0] . ")" .
					 substr($author_info->get_username(), 1));
				
				preg_replace_track(
					"/^\s*(I\,?(?: \((?:\[\[\:en\:User:$username_regex\|)?$username_regex" .
						 "(?:\]\])?(?: \(\[\[\:en\:User talk\:$username_regex\|talk\]\]\))?\))? created" .
						 " this work entirely by myself\.?)(\s*)$/ui", 
						"{{own}} ({{original text|1=$1|nobold=1}})$2", $source, $changed);
			}
		}
		if (preg_match("/\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log[ _]*\}\}\s*\=+\s*\n/u", 
			$ci->get_text())) {
			preg_replace_track(
				"/^\s*(((?:\'\'+)?)([\"\']?)(?:selbst\W*erstellt|selbst\W*gezeichnet|self\W*made|eigene?s?\W*(?:arbeit|aufnahme|(?:ph|f)oto(?:gra(?:ph|f)ie)?))\.?\\3\\2)(\s*)$/ui", 
				"{{own}} ({{original text|1=$1|nobold=1}})$4", $source, $changed);
		}
		
		if (!$changed) {
			$new_source = preg_replace($this->regex_keys, $this->regex_values, $source);
			$changed = $new_source !== $source;
			$source = $new_source;
		}
		
		if ($changed) {
			$t->updatefield(Cleanup_Shared::SOURCE, $source);
			$ci->set_text($t->wholePage(), false);
		}
	}
}