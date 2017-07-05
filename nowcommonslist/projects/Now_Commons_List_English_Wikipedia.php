<?php

class Now_Commons_List_English_Wikipedia extends Now_Commons_List {

	/**
	 *
	 * @var Project_Data
	 */
	private $project_data;

	/**
	 *
	 */
	public function __construct() {
		global $logger;

		$logger->debug("In ". get_class() . "::__construct()");
		$this->override_output_path = BASE_DIRECTORY . "/public_html/commons_images";
	}

	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_all_marked_images()
	 */
	protected function get_all_marked_images() {
		global $wiki_interface;

		$same = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:Wikipedia files with the same name on Wikimedia Commons", true, 6, null, 
			["Category:All Wikipedia files with the same name on Wikimedia Commons"], null);
		$extra_same = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:All Wikipedia files with the same name on Wikimedia Commons", true, 6, null, 
			[], null);
		$different = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:Wikipedia files with a different name on Wikimedia Commons", true, 6, null, 
			["Category: All Wikipedia files with a different name on Wikimedia Commons"], null);
		$extra_different = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:All Wikipedia files with a different name on Wikimedia Commons", true, 6, null, 
			[], null);

		return array_merge($same, $extra_same, $different, $extra_different);
	}

	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_project_data()
	 */
	protected function get_project_data() {
		if ($this->project_data === null) {
			$this->project_data = new Project_Data("en.wikipedia");
		}
		return $this->project_data;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_messages_key()
	 */
	protected function get_messages_key() {
		return "en.wikipedia";
	}
}