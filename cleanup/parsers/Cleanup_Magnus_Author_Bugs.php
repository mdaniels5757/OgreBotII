<?php
/**
 * @author magog
 *
 */
class Cleanup_Magnus_Author_Bugs implements Cleanup_Module {



	/**
	 *
	 * @var string
	 */
	private $wikilinks_re;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {

		$wikilinks_quote = implode("|", preg_quote_all(array_keys(ProjectData::getInterwikis())));
		
		$this->wikilinks_re = "/Original uploader was \{\{user at project\|([^\[\]\|]+)\|(" .
			$wikilinks_quote . ")\|(" . $cleanup_package->get_constants()["langlinks_regex"] . ")\}\}/u";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace_callback($this->wikilinks_re, 
			function ($match) {
				$project_data = new ProjectData($match[2], $match[3]);
				$page_link = $project_data->formatPageLink("User:$match[1]", $match[1], 
					"commons.wikimedia");
				$project = "$match[3].$match[2]";
				return "Original uploader was $page_link at [http://$project.org $project]";
			}, true, $reverse_user);
		
	}
	

}