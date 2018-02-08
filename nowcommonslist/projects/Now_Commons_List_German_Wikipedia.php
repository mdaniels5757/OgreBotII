<?php

class Now_Commons_List_German_Wikipedia extends Now_Commons_List {

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
		$this->override_output_path = BASE_DIRECTORY . "/public_html/commons_images-de";
	}

	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_all_marked_images()
	 */
	protected function get_all_marked_images() {
		$wiki_interface = Environment::get()->get_wiki_interface();
		
		return array_merge(
			$wiki_interface->new_category_traverse($this->project_data->getWiki(),
				"Kategorie:Datei:NowCommons", false, 6, null),
			$wiki_interface->new_category_traverse($this->project_data->getWiki(),
				"Kategorie:Datei:NowCommons (gleicher Name)", false, 6, null));
	}

	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_project_data()
	 */
	protected function get_project_data() {
		if ($this->project_data === null) {
			$this->project_data = new Project_Data("de.wikipedia");
		}
		return $this->project_data;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::get_messages_key()
	 */
	protected function get_messages_key() {
		return "de.wikipedia";
	}
}