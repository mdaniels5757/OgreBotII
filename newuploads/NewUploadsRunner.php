<?php
class NewUploadsRunner {

	/**
	 *
	 * @var Wiki
	 */
	private $wiki;

	/**
	 *
	 * @var int[]
	 */
	private $intervals;

	/**
	 *
	 * @return int[]
	 */
	public function getIntervals() {
		return $this->intervals;
	}

	/**
	 *
	 * @param Wiki $wiki
	 * @param int[] the intervals at which the bot will place a new header. An interval is
	 *    assumed at 1, which is treated uniquely. For example, if $intervals {10, 50}, the
	 *    bot will create three headers: Uploaders with 1 edit, uploaders with 10 or fewer
	 *    uploads, and uploaders with 50 uploads or fewer uploads.
	 */
	public function __construct(Wiki &$wiki, $intervals) {
		global $validator;
		$this->wiki = $wiki;

		$validator->validate_arg_array($intervals, "int");
		$first_interval = reset($intervals);
		$validator->validate_args_condition($first_interval, "integer greater than 1",
			$first_interval > 1);
		$previous = 0;
		foreach ($intervals as $interval) {
			$validator->validate_args_condition($interval, "greater than the previous interval",
				$interval > $previous);
			$previous = $interval;
		}
		$this->intervals = $intervals;
	}

	/**
	 *
	 * @param number $starttime
	 * @param number $endtime
	 * @param number $count
	 * @return string
	 */
	public function runFromFileUploadDates($starttime, $endtime, $count) {
		global $logger, $validator, $wiki_interface;

		$logger->info("runFromFileUploadDates $starttime $endtime");
		$validator->validate_arg($starttime, "numeric");
		$validator->validate_arg($endtime, "numeric");

		// need LOTS of memory for this operation.
		$old_memory = ini_get("memory_limit");
		ini_set('memory_limit', '4G');
		$logger->debug("Memory limit set to 4G.");

		$opt_outs = Gallery_Opt_Outs::get_opt_outs($this->wiki);

		// get the list of new uploads
		$all_uploads = $wiki_interface->get_recent_uploads_with_image_data($this->wiki, $starttime,
			$endtime);

		if (count($all_uploads) === 0) {
			$logger->warn("Empty \$all_uploads array");
		}
		$logger->debug(count($all_uploads) . " uploads found.");

		// array of all uploaders; organize all uploads by users
		$userNames = [];
		$uploadsByUserName = [];

		foreach ($all_uploads as $index => $upload) {
			@$uploadsByUserName[$upload['user']][] = $upload;
		}

		foreach ($opt_outs as $opt_out) {
			unset($uploadsByUserName[$opt_out]);
		}

		$userNames = array_keys($uploadsByUserName);
		$logger->debug(count($userNames) . " usernames found.");

		/* 60 minute caching */
		$userDataGlob = UserData::getUserData($this->wiki, $userNames, SECONDS_PER_HOUR);

		// organize user data by number of edits
		$usersByEditCount = [];
		$maxEditCountToStore = end($this->intervals);
		foreach ($userNames as $userName) {
			$editCount = $userDataGlob[$userName]->getEditCount();

			if ($editCount <= $maxEditCountToStore) {
				// $logger->trace("User:$userName included: $editCount edits.");
				$usersByEditCount[$editCount][] = $userName;
			} else {
				// $logger->trace("User:$userName removed: $editCount edits.");
				unset($uploadsByUserName[$userName]);
			}
		}
		$logger->debug(count($uploadsByUserName) . " usernames are below the threshold.");

		// filter deleted files; follow redirects
		$eligible_files_title_only = array();
		foreach ($uploadsByUserName as $user) {
			foreach ($user as $upload) {
				$eligible_files_title_only[] = $upload['title'];
			}
		}

		// output the data
		$diff = parseMediawikiTimestampRaw($endtime) - parseMediawikiTimestampRaw($starttime);
		$previous = unixTimestampToMediawikiTimestamp(
			parseMediawikiTimestampRaw($starttime) - $diff - 1);
		$next = unixTimestampToMediawikiTimestamp(parseMediawikiTimestampRaw($endtime) + 1);
		$data = "{{User:MDanielsBot/new upload header|previous=$previous|" .
			 "this=$starttime|next=$next|last_update=~~~~~|count=$count}}\n\n";

		// 1 edit (or 0 edits bc Mediawiki is bizarre about how it counts reuploads):
		// unique case
		$usersWithZeroOrOneEdits = (@$usersByEditCount[0] ? $usersByEditCount[0] : []) +
			 (@$usersByEditCount[1] ? $usersByEditCount[1] : []);
		if ($usersWithZeroOrOneEdits) {
			$data .= "== Users with 1 edit ==\r\n";
			$data .= "<gallery>\r\n";
			foreach ($usersWithZeroOrOneEdits as $user) {
				/*
				 * need to do a foreach, in case the user has multiple uploads; this will
				 * happen if the user has overwritten files, which Mediawiki doesn't count
				 * as an edit.
				 */
				foreach ($uploadsByUserName[$user] as $upload) {
					$filename = $upload['title'];
					$data .= "$filename|" . $this->getText($upload, true) . "\r\n";
				}
			}
			$data .= "</gallery>\r\n\r\n";
		}

		// >1 edit
		$i = 1;
		$string_utils = Environment::get()->get_string_utils();
		foreach ($this->intervals as $interval) {
			$new = true;
			while ($i++ < $interval) {
				if (@$usersByEditCount[$i]) {
					if ($new) {
						$data .= "== Users with $interval or fewer edits ==\r\n";
						$new = false;
					}
					foreach ($usersByEditCount[$i] as $user) {
						$user_encoded = encodePageTitle($user);
						if (count($uploadsByUserName[$user]) > 0) {
							$direction = $string_utils->get_string_direction($user);
							$user_bdi = ($direction & String_Utils::STRING_DIRECTION_RTL)  ?
								"<bdi>$user</bdi>" : $user;
							$data .= "==== $user_bdi ($i edits) ====\r\n";
							$data .= "{{User:MDanielsBot/new uploader|1=$user";

							// talk page warnings
							$data .= $this->talkWarningsToTemplateParams($userDataGlob, $user);

							$data .= "}}\r\n<gallery>\r\n";
							foreach ($uploadsByUserName[$user] as $upload) {
								$filename = $upload['title'];
								$data .= "$filename|" . $this->getText($upload, false) . "\r\n";
							}
							$data .= "</gallery>\r\n\r\n";
						} else {
							$logger->warn("Count for user uploads == 0: $user");
						}
					}
				}
			}
		}

		$validator->validate_arg($data, "string");
		return $data;
	}

	/**
	 *
	 * @param array $upload
	 * @param bool $showUserName
	 * @return string
	 */
	private function getText(&$upload, $showUserName) {
		global $validator;

		$validator->validate_arg($upload, "array");
		$validator->validate_arg($showUserName, "bool");

		$asData = new UploadData($upload);

		//Basic info
		$text = "{{User:MDanielsBot/new upload|" . ($showUserName ? "user=" . $asData->getUploader() .
			 "|" : "") . "1=" . $asData->getTitle();

		$height = intval($asData->getHeight());
		$width = intval($asData->getWidth());
		$sizeText = readableByteSize($asData->getSize()) . " ${height}x$width";
		$text .= "|size=$sizeText";

		if (count($asData->getLicenses()) > 0) {
			$imploded = "|license=" . implode($asData->getLicenses(), ", ");
			// $logger->trace($imploded);
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
	 * @param array $allUserData
	 * @param string $userName
	 * @return string
	 */
	private function talkWarningsToTemplateParams(&$allUserData, $userName) {
		global $logger, $validator;

		$str = "";
		$warnings = null;
		if (@$allUserData[$userName]) {
			$warnings = $allUserData[$userName]->getTalkpageWarnings();
			$validator->validate_arg($warnings, "int", true);

			if ($warnings !== null) {
				if ($warnings & UserTalkPageWarnings::nld) {
					$str .= "|nld=1";
				}
				if ($warnings & UserTalkPageWarnings::nsd) {
					$str .= "|nsd=1";
				}
				if ($warnings & UserTalkPageWarnings::npd) {
					$str .= "|npd=1";
				}
				if ($warnings & UserTalkPageWarnings::copyvio) {
					$str .= "|copyvio=1";
				}
				if ($warnings & UserTalkPageWarnings::idw) {
					$str .= "|idw=1";
				}
				if ($warnings & UserTalkPageWarnings::scope) {
					$str .= "|scope=1";
				}
				if ($warnings & UserTalkPageWarnings::endOfCVs) {
					$str .= "|endOfCVs=1";
				}
				if ($warnings & UserTalkPageWarnings::blocked) {
					$str .= "|blocked=1";
				}
			}
		} else {
			$logger->warn("\$allUserData[$userName] is not set. Its value is: ");
			$logger->warn($allUserData);
		}

		$logger->trace("talkWarningsToTemplateParams($userName) => $warnings => $str");
		return $str;
	}
}
?>
