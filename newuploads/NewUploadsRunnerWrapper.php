<?php
class NewUploadsRunnerWrapper {
	
	/**
	 *
	 * @var Wiki
	 */
	private $wiki;
	
	/**
	 *
	 * @var string
	 */
	private $baseName;
	
	/**
	 *
	 * @var string
	 */
	private $editSummaryMainNew;
	
	/**
	 *
	 * @var string
	 */
	private $editSummaryMainUpdate;
	
	/**
	 *
	 * @var string
	 */
	private $editSummaryList;
	
	/**
	 *
	 * @var int[]
	 */
	private $intervals;
	
	/**
	 *
	 * @return Wiki
	 */
	public function getWiki() {
		return $this->wiki;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getBaseName() {
		return $this->baseName;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getEditSummaryMainNew() {
		return $this->editSummaryMainNew;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getEditSummaryMainUpdate() {
		return $this->editSummaryMainUpdate;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getEditSummaryList() {
		return $this->editSummaryList;
	}
	
	/**
	 *
	 * @return int[]
	 */
	public function getIntervals() {
		return $this->intervals;
	}
	
	/**
	 * parse date and gallery name into human readable text
	 * 
	 * @param string $start        	
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function getPageNameByTimestamp($start) {
		global $validator;
		$validator->validate_arg($start, "string");
		
		if (preg_match("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/", $start, $datematcher)) {
			$dateText = "$datematcher[1] " . date('F', mktime(0, 0, 0, $datematcher[2])) .
				 " $datematcher[3] $datematcher[4]:$datematcher[5]";
			$newPageName = "$this->baseName/$dateText";
			return $newPageName;
		} else {
			throw new InvalidArgumentException();
		}
	}
	
	/**
	 */
	public function __construct() {
		global $logger, $wiki_interface;
		
		$logger->debug("Entering NewUploadsRunnerWrapper::__construct()");
		
		load_property_file_into_variable($properties, "newuploads");
		$logger->trace($properties);
		
		$this->wiki = $wiki_interface->new_wiki($properties['wiki']);
		$this->baseName = $properties['page_name'];
		$this->editSummaryMainNew = $properties['edit_summary_main_gallery_new'];
		$this->editSummaryMainUpdate = $properties['edit_summary_main_gallery_update'];
		$this->editSummaryList = $properties['edit_summary_list_page'];
		
		$this->intervals = array_map("intval", $properties['intervals']);
		$logger->debug($this);
	}
	
	/**
	 *
	 * @param number $start        	
	 * @param number $end        	
	 * @param bool $new
	 *        	if true, the script will append the new gallery to the listing page and
	 *        	use the new page edit summary; if false, it will not append to the new list, and
	 *        	will use the update summary
	 * @param bool $live        	
	 * @throws EditConflictException
	 * @throws Exception
	 * @return void
	 */
	public function run($start, $end, $new) {
		global $logger, $curl_timeout, $validator, $wiki_interface;
		
		$logger->debug("Entering NewUploadsRunnerWrapper::run($start, $end, $new)");
		
		$validator->validate_arg($start, "numeric");
		$validator->validate_arg($end, "numeric");
		$validator->validate_arg($new, "bool");
		
		$editSummary = $new ? $this->editSummaryMainNew : $this->editSummaryMainUpdate;
		$newPageName = $this->getPageNameByTimestamp($start);
		
		if ($new) {
			$count = 0;
		} else {
			$pages = $wiki_interface->query_generic($this->wiki, 'titles', [$newPageName], 
				["prop" => "revisions", "rvprop" => "user", "rvlimit" => "max"]);
			$page = current($pages);
			if (isset($page['missing'])) {
				$count = 0;
			} else {
				$count = count(
					array_filter($page['revisions'],
						function ($revision) {
							return $revision['user'] === $this->wiki->get_username();
						}));
			}
		}
		
		// call the runner
		$runner = new NewUploadsRunner($this->wiki, $this->intervals);
		$text = $runner->runFromFileUploadDates($start, $end, $count + 1);
		
		$galleryPage = $wiki_interface->new_page($this->wiki, $newPageName);
		
		$wiki_interface->edit($galleryPage, $text, $editSummary, 0, 3, 300);
		
		if ($new) {
			$edit_summary_list = preg_replace("/^(.+)$/", $this->editSummaryList, $newPageName);
			$basePage = $wiki_interface->new_page($this->wiki, $this->baseName);
			
			preg_match("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/", $start, $datematcher);
			$dateText = "$datematcher[1] " . date('F', mktime(0, 0, 0, $datematcher[2])) .
				 " $datematcher[3] $datematcher[4]:$datematcher[5]";
			$wiki_interface->edit($basePage, "\r\n*[[/$dateText]]", $edit_summary_list, EDIT_APPEND);
		}
		
		$logger->info("Done.");
	}
}
?>
