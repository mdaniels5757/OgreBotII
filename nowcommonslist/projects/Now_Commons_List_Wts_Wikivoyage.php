<?php
class Now_Commons_List_Wts_Wikivoyage extends Now_Commons_List {
	
	/**
	 *
	 * @var ProjectData
	 */
	private $project_data;
	
	/**
	 */
	public function __construct() {
		global $logger;
		
		$logger->debug("In " . get_class() . "::__construct()");
		$this->override_output_path = BASE_DIRECTORY . "/public_html/commons_images-wts.wikivoyage";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Now_Commons_List::is_get_local_links()
	 */
	protected function is_get_local_links() {
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Now_Commons_List::get_all_marked_images()
	 */
	protected function get_all_marked_images() {
		global $wiki_interface;
		
		$same = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:Files with the same name on Wikimedia Commons", true, 6, null);
		$different = $wiki_interface->new_category_traverse($this->project_data->getWiki(), 
			"Category:Files with a different name on Wikimedia Commons", true, 6, null);
		
		return array_merge($same, $different);
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Now_Commons_List::get_project_data()
	 */
	protected function get_project_data() {
		global $wiki_interface;
		if ($this->project_data === null) {
			$this->project_data = new ProjectData("wts.wikivoyage");
			
			//the site is prone to taking naps; set initial to 5 minutes
			$wiki_interface->set_curl_timeout(300);
		}
		return $this->project_data;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Now_Commons_List::get_messages_key()
	 */
	protected function get_messages_key() {
		return "wts.wikivoyage";
	}
}