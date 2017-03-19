<?php
class UploadReportWriter {
	
	/**
	 *
	 * @var Wiki
	 */
	private $wiki;
	
	/**
	 *
	 * @var int
	 */
	private $start;
	
	/**
	 *
	 * @var int
	 */
	private $end;
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param number $start        	
	 * @param number $end        	
	 */
	public function __construct(Wiki $wiki, $start, $end) {
		global $validator;
		
		$validator->validate_arg($start, "numeric");
		$validator->validate_arg($end, "numeric");
		$validator->assert(
			preg_match("/^20\d{12}$/", $start), 
			"Illegal start date: $start. Format: YYYYMMDDHHMMSS");
		$validator->assert(
			preg_match("/^20\d{12}$/", $end), 
			"Illegal end date: $end. Format: YYYYMMDDHHMMSS");
		
		$this->wiki = $wiki;
		$this->start = (int)$start;
		$this->end = (int)$end;
	}
	
	/**
	 *
	 * @param string[][] $uploads        	
	 * @return UserData[]
	 */
	private function getUserData($uploads) {
		global $logger, $validator;
		
		$validator->validate_arg($uploads, "array");
		
		$logger->debug("getUserData($this->wiki, array[" . count($uploads) . "])");
		
		$userNames = array_unique(map_array($uploads, 'user'));
		$logger->debug(count($userNames) . " usernames found.");
		
		$userDataGlob = UserData::getUserData($this->wiki, $userNames, SECONDS_PER_HOUR /* 60 minute caching*/);
		
		return $userDataGlob;
	}
	
	/**
	 *
	 * @return string
	 */
	private function getUploadReport($count) {
		global $logger, $messages, $validator, $wiki_interface;
		
		// need LOTS of memory for this operation.
		$old_memory = ini_get("memory_limit");
		ini_set('memory_limit', '6G');
		$logger->debug("Memory limit set to 6G.");
		
		$logger->debug("getUploadReport()");
		
		$handlers = NewUploadHandler::getHandlers();
		
		$logger->debug("\$handlers is size " . count($handlers));
		
		$opt_outs = Gallery_Opt_Outs::get_opt_outs($this->wiki);
		$all_uploads = $wiki_interface->get_recent_uploads_with_image_data($this->wiki, 
			$this->start, $this->end, true);
		$allUserData = $this->getUserData($all_uploads);
		
		$date = substr($this->start, 0, 8);
		$text = "{{User:OgreBot/Notables uploads header|update=$date|last_update=~~~~~" .
			 "|count=$count}}\n\n";
		foreach ($handlers as $handler) {
			$thisText = "";
			
			$logger->debug("Running $handler");
			foreach ($all_uploads as $upload) {
				$userName = $upload['user'];
				$userData = array_key_or_exception($allUserData, $userName);
				
				if ($handler->isDisplayUpload($upload, $userData)) {
					if (in_array($userName, $opt_outs)) {
						$logger->info("Skipping user:$userName, who is opted out ($handler).");
					} else {
						$thisText .= "$upload[title]|" . $this->getTextUploadReport($upload, true) .
							 "\r\n";
					}
				}
			}
			
			if (strlen($thisText) > 0) {
				$title = array_key_or_exception($messages, $handler->getTitleKey());
				$text .= "== $title ==\r\n<gallery>\r\n$thisText</gallery>\r\n\r\n";
			}
		}
		
		return $text;
	}
	
	/**
	 *
	 * @param string[] $upload        	
	 * @param bool $showUserName        	
	 * @return string
	 */
	private function getTextUploadReport($upload, $showUserName) {
		global $logger, $validator;
		
		$validator->validate_arg($upload, "array");
		$validator->validate_arg($showUserName, "bool");
		
		$asData = new UploadData($upload);
		
		/**
		 * ***
		 * Basic info
		 * *****
		 */
		$text = "{{User:OgreBot/new upload|" . ($showUserName ? "user=" . $asData->getUploader() .
			 "|" : "") . "1=" . $asData->getTitle();
		
		$height = intval($asData->getHeight());
		$width = intval($asData->getWidth());
		$sizeText = readableByteSize($asData->getSize()) . " ${height}x$width";
		$text .= "|size=$sizeText";
		
		if (count($asData->getLicenses()) > 0) {
			$imploded = "|license=" . implode($asData->getLicenses(), ", ");
			$logger->trace($imploded);
			$text .= $imploded;
		}
		
		if ($asData->getSelf()) {
			$text .= "|self=1";
		}
		
		if ($asData->getReupload()) {
			$text .= "|reupload=1";
		}
		
		if ($asData->getNoLicense()) {
			$text .= "|nld=1";
		}
		
		if ($asData->getNoSource()) {
			$text .= "|nsd=1";
		}
		
		if ($asData->getNoPermission()) {
			$text .= "|npd=1";
		}
		
		if ($asData->getDr()) {
			$text .= "|dr=1";
		}
		
		if ($asData->getCopyvio()) {
			$text .= "|cv=1";
		}
		
		if ($asData->getOtrsPending()) {
			$text .= "|otrsPending=1";
		}
		
		if ($asData->getMobile()) {
			$text .= "|mobile=1";
		}
		
		/**
		 * ***
		 * Metadata
		 * *****
		 */
		$metadata = @$upload['metadata'];
		if ($metadata) {
			$make = null;
			$model = null;
			foreach ($metadata as $next) {
				if ($next['name'] === 'Make') {
					$make = mb_trim($next['value']);
				} else if ($next['name'] === 'Model') {
					$model = mb_trim($next['value']);
				}
			}
			
			if ($make) {
				$metadataText = $make;
				if ($model) {
					$metadataText .= " $model";
				}
				$text .= "|metadata=$metadataText";
			}
		}
		
		$text .= "}}";
		
		$validator->validate_arg($text, "string");
		return $text;
	}
	
	/**
	 *
	 * @param bool $update
	 * @throws APIError
	 * @throws CURLError
	 * @throws EditConflictException        	
	 */
	public function loadAndWrite($update) {
		global $constants, $logger, $messages, $wiki_interface;
		
		$indexPageName = $this->getIndexPageName();
		$dateText = $this->getDateText();
		$galleryPageName = $this->getGalleryPageName();
		if ($update) {
			$pages = $wiki_interface->query_generic($this->wiki, 'titles', [$galleryPageName], 
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
		} else {
			$count = 0;
		}
		
		$text = $this->getUploadReport($count + 1);
		
		$messageKey = $update ? "uploadhandler.editsummary.update" : "uploadhandler.editsummary.newpage";
		$editSummary = array_key_or_exception($messages, $messageKey);
		
		if (strlen($text) > 0) {
			$logger->debug("Making edit. Gallery size: " . mb_strlen($text));
			// add new page
			$galleryPage = $wiki_interface->new_page($this->wiki, $galleryPageName);
			$wiki_interface->edit($galleryPage, $text, $editSummary);
			
			// add page to index
			if (!$update) {
				$logger->debug("Editing index.");
				$editSummaryIndex = replace_named_variables(
					array_key_or_exception($messages, 'uploadhandler.editsummary.indexpage'), 
					['pgName' => $galleryPageName]);
				$indexPage = $wiki_interface->new_page($this->wiki, $indexPageName);
				$wiki_interface->edit($indexPage, "\r\n*[[/$dateText]]", 
					$editSummaryIndex, EDIT_APPEND);
			}
			$logger->debug("Success.");
		} else {
			$logger->info("No information for upload report today!");
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getGalleryPageName() {
		$indexPageName = $this->getIndexPageName();
		$dateText = $this->getDateText();
		return "$indexPageName/$dateText";
	}
	
	/**
	 * 
	 * @return string
	 */
	private function getDateText() {
		return date("Y F j", strtotime(substr($this->start, 0, 8)));
	}
	
	/**
	 * 
	 * @return string
	 */
	private function getIndexPageName() {
		global $constants;
		
		return array_key_or_exception($constants, 'uploadreport.pagename');
	}
}