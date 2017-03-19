<?php
class UserData implements Serializable {
	const MARSHALL_FILE_NAME = "userrights2.dat";
	
	/**
	 *
	 * @var UserData[]
	 */
	private static $userData = null;
	
	/**
	 * API base URL for the given wiki
	 * 
	 * @var string
	 */
	private $wiki;
	
	/**
	 * username without the prefix
	 * 
	 * @var string
	 */
	private $username;
	
	/**
	 *
	 * @var int
	 */
	private $editCount;
	
	/**
	 *
	 * @var string[]
	 */
	private $rights;
	
	/**
	 *
	 * @var number
	 */
	private $registration;
	
	/**
	 *
	 * @var int[]
	 */
	private $talkpageWarnings;
	
	/**
	 * when did we last refresh this data?
	 * 
	 * @var number
	 */
	private $timestamp;
	public function __construct() {
	}
	
	/**
	 *
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}
	
	/**
	 *
	 * @return number
	 */
	public function getEditCount() {
		return $this->editCount;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getRights() {
		return $this->rights;
	}
	
	/**
	 *
	 * @return int[]
	 */
	public function getTalkpageWarnings() {
		return $this->talkpageWarnings;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isAdmin() {
		return in_array("sysop", $this->rights);
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isAutoPatrolled() {
		return in_array("autopatrolled", $this->rights) || $this->isAdmin();
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isBot() {
		return in_array("bot", $this->rights);
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isConfirmed() {
		return in_array("autoconfirmed", $this->rights) || in_array("confirmed", $this->rights);
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isFilemover() {
		return in_array("filemover", $this->rights) || $this->isAdmin();
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isImageReviewer() {
		return in_array("Image-reviewer", $this->rights) || $this->isAdmin();
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string[] $usernames        	
	 * @param int $timeout
	 *        	timeout in seconds
	 * @return UserData[]
	 */
	public static function getUserData(Wiki $wiki, array $usernames, $timeout) {
		global $logger, $validator, $wiki_interface;
		
		$validator->validate_arg($wiki, "Wiki");
		$validator->validate_arg($timeout, "int");
		$validator->validate_arg_array($usernames, "string-numeric");
		
		$userData = &self::$userData;
		
		if ($userData === null) {
			self::unmarshallUserData($timeout);
			$userData = &self::$userData;
		}
		
		// initialize array; store by username
		$newDataQuery = array();
		$time = time();
		$updates = false;
		foreach ($usernames as $name) {
			if (!isset($userData[$name]) || $userData[$name]->timestamp + $timeout < $time) {
				$obj = new UserData();
				$obj->username = $name;
				$obj->wiki = $wiki->get_base_url();
				$userData[$name] = $obj;
				$newDataQuery[] = $name;
				$updates = true;
			}
		}
		
		if ($updates) {
			/**
			 * Get userpage data *
			 */
			$usernames_with_prefix = preg_replace("/^/", "User:", $newDataQuery);
			
			/**
			 * Get user rights, registration date, and set query timestamp *
			 */
			$rights = $wiki_interface->query_generic($wiki, 'ususers', $newDataQuery, 
				['usprop' => 'blockinfo|groups|registration|editcount']);
			
			foreach ($rights as $right) {
				$user = $right['name'];
				$userData[$user]->rights = $right['groups'];
				$userData[$user]->registration = $right['registration'];
				$userData[$user]->timestamp = $time;
				$userData[$user]->editCount = intval($right['editcount']);
			}
			
			/**
			 * Get talk page templates for warning data *
			 */
			$usernames_with_talk_prefix = preg_replace("/^/", "User talk:", $newDataQuery);
			
			$talkpages = $wiki_interface->new_query_pages($wiki, $usernames_with_talk_prefix, 
				'templates|revisions', [], $redirects);
			
			$talkpageWarnings = UserTalkPageWarnings::getWarningTemplates();
			foreach ($usernames_with_talk_prefix as $pageName) {
				
				$userName = substr($pageName, 10);
				
				// follow redirects
				$redirect = @$redirects[$pageName];
				if ($redirect !== null) {
					$logger->trace("Redirect: $pageName -> $redirect.");
					
					if (isset($redirects[$redirect])) {
						$logger->warn("$pageName is a double redirect.");
						continue;
					}
					$pageName = $redirect;
				}
				
				try {
					$content = _array_key_or_exception($talkpages, [$pageName]);
				} catch (ArrayIndexNotFoundException $e) {
					$logger->warn("Array key not found: $pageName.");
					continue;
				}
				
				// deleted/non-existent user talk page
				if (isset($content['missing'])) {
					$logger->warn("$pageName unexpectedly not found.");
					continue;
				}
				
				$logger->insane($userName);
				if (isset($userData[$userName])) {
					$logger->insane($content);
					$userTalkWarnings = 0;
					$templates = @$content['templates'];
					
					if ($templates) {
						foreach ($templates as $template) {
							$userTalkWarnings |= @$talkpageWarnings[$template["title"]];
						}
					}
					$userData[$userName]->talkpageWarnings = $userTalkWarnings;
					$validator->validate_arg($userData[$userName]->talkpageWarnings, "int");
				} else {
					$logger->error("$userName not in userData???");
				}
			}
			self::marshallUserData();
		}
		
		return array_intersect_key($userData, array_fill_keys($usernames, true));
	}
	
	/**
	 *
	 * @param int $timeout        	
	 * @return void
	 */
	private static function unmarshallUserData($timeout) {
		global $logger;
		$filename = TMP_DIRECTORY_SLASH . self::MARSHALL_FILE_NAME;
		
		$logger->debug("unmarshallUserData");
		
		$userData = &self::$userData;
		
		$file_contents = file_exists($filename) ? file_get_contents($filename) : false;
		if ($file_contents !== false) {
			self::$userData = unserialize($file_contents);
		} else {
			self::$userData = false;
		}
		$logger->insane("unmarshalled user data:");
		$logger->insane(self::$userData);
		
		if (self::$userData === false) {
			self::$userData = array();
			$logger->warn("User data not found; starting from scratch");
		} else {
			$countStart = count(self::$userData);
			
			// prune old records
			$time = time();
			foreach ($userData as $index => $data) {
				$unset = !($data instanceof UserData) /* corrupt data */
					|| $data->timestamp + $timeout < $time; /* stale data */
				
				if ($unset) {
					unset(self::$userData[$index]);
				}
			}
			$countEnd = count(self::$userData);
			
			$logger->debug(
				"$countStart users found. " . ($countStart - $countEnd) .
					 " users pruned; $countEnd users remaining.");
		}
	}
	
	/**
	 *
	 * @throws Exception
	 * @return void
	 */
	private static function marshallUserData() {
		global $logger;
		$filename = TMP_DIRECTORY_SLASH . self::MARSHALL_FILE_NAME;
		
		$userData = &self::$userData;
		
		$logger->debug("marshalling " . count($userData) . " users");
		
		if (file_put_contents($filename, serialize($userData), LOCK_EX) === false) {
			throw new Exception("Can't marshall user data");
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Serializable::serialize()
	 */
	public function serialize() {
		return implode(
			array($this->wiki, $this->username, "", serialize($this->rights), $this->registration, 
				$this->timestamp, $this->editCount, $this->talkpageWarnings), '|');
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Serializable::unserialize()
	 */
	public function unserialize($data) {
		global $logger;
		$tokens = explode('|', $data);
		$this->wiki = $tokens[0];
		$this->username = $tokens[1];
		// no longer in use $tokens[2]
		$this->rights = unserialize($tokens[3]);
		$this->registration = $tokens[4];
		$this->timestamp = $tokens[5];
		$this->editCount = intval($tokens[6]);
		
		if (count($tokens) > 7) {
			$this->talkpageWarnings = intval($tokens[7]);
		}
		if ($logger->isInsaneEnabled()) {
			$logger->insane("Unmarshalled user data: \"$data\" => " . print_r($this));
		}
	}
}
