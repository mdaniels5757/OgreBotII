<?php
class Project_Data {

	const NAMESPACE_CACHE_FILE_NAME = "namespaces.dat";

	/**
	 *
	 * @var Array_Parameter_Extractor
	 */
	private static $namespace_array_param_extractor;

	/**
	 *
	 * @var Lazy_Load_Storage
	 */
	private static $all_timed_namespace_data;

	/**
	 *
	 * @var string[]
	 */
	private static $interwikis;

	/**
	 *
	 * @var string[]
	 */
	private static $interwikisFull;

	/**
	 *
	 * @var string[]
	 */
	private static $interwikisFullUrl;

	/**
	 *
	 * @var Project_Data[]
	 */
	private static $projectDatas = [];

	/**
	 *
	 * @var string The project - "wikipedia", "wikimedia", etc.
	 */
	private $project;

	/**
	 *
	 * @var string The subproject - "en", "commons", etc.
	 */
	private $subproject;

	/**
	 *
	 * @var Wiki A Peachy Wiki instance.
	 */
	private $wiki;

	/**
	 *
	 * @var string the interwiki link on Commons
	 */
	private $interwiki_commons;

	/**
	 *
	 * @var string
	 */
	private $defaultHostWiki;

	/**
	 *
	 * @var string
	 */
	private $baseUrl;

	/**
	 *
	 * @var string
	 */
	private $actionPrefix;

	/**
	 *
	 * @var string
	 */
	private $standardPrefix;

	/**
	 *
	 * @var boolean
	 */
	private $allowSecure;

	/**
	 *
	 * @var string
	 */
	private $secure_url_prefix;

	/**
	 *
	 * @var Wiki_Namespace[]
	 */
	private $namespaces;

	/**
	 *
	 * @var Project_Data[]
	 */
	private static $by_base_url = [];

	/**
	 * @return void
	 */
	private static function _autoload() {
		$xmlData = XmlParser::xmlFileToStruct("wikilinks.xml");

		$wikilinksFullXml = array_key_or_exception($xmlData, "WIKILINKS", 0, "elements",
			"WIKILINK-FULL");

		foreach ($wikilinksFullXml as $linkFull) {
			$name = array_key_or_exception($linkFull, "attributes", "NAME");
			$value = array_key_or_null($linkFull, "attributes", "VALUE");
			if ($value != null) {
				@self::$interwikisFull[$name] = $value;
			} else {
				$fullUrl = array_key_or_exception($linkFull, "attributes", "FULL-URL");
				@self::$interwikisFullUrl[$name] = $fullUrl;
			}
		}

		$wikilinksXml = array_key_or_exception($xmlData, "WIKILINKS", 0, "elements", "WIKILINK");

		foreach ($wikilinksXml as $link) {
			$name = array_key_or_exception($link, "attributes", "NAME");
			$value = array_key_or_exception($link, "attributes", "VALUE");

			@self::$interwikis[$name] = $value;
		}
		self::$namespace_array_param_extractor = new Array_Parameter_Extractor(
			["id" => true, "*" => true, "canonical" => false]);
		self::$all_timed_namespace_data = new Cached_Lazy_Load_Storage(
			Classloader::get_closure(self::class, "load_namespace_static"),
			TMP_DIRECTORY_SLASH . self::NAMESPACE_CACHE_FILE_NAME, SECONDS_PER_DAY);
	}

	private static function load_namespace_static($base_url) {
		return self::$by_base_url[$base_url]->load_namespace();
	}
	/**
	 *
	 * @param Project_Data $project_data
	 */
	private function load_namespace() {
		global $wiki_interface;

		$query = $wiki_interface->api_query($this->getWiki(),
			["action" => "query", "meta" => "siteinfo",
					"siprop" => "namespaces|namespacealiases"], true);

		$all_aliases = map_array_all(array_key_or_empty($query, "query", "namespacealiases"),
			function ($namespace_data) {
				return [array_key_or_exception($namespace_data, "id"),
					array_key_or_exception($namespace_data, "*")];
			});
		$namespaces_array = array_reverse(array_key_or_exception($query, "query", "namespaces"),
			true);

		$namespaces_array = map_array_function_keys($namespaces_array, function ($namespace) {
			list($id, $name, $canonical) = self::$namespace_array_param_extractor->extract($namespace);
			$aliases = array_key_or_empty($all_aliases, $id);
			$aliases[] = $name;
			if ($canonical && $canonical !== $name) {
				$aliases[] = $canonical;
			}

			$talk = false;
			$talk_space = null;
			if ($id > 0 && $id % 2) {
				$talk = true;
			}

			$wiki_namespace = new Wiki_Namespace();
			$wiki_namespace->set_id($id);
			$wiki_namespace->set_aliases($aliases);
			$wiki_namespace->set_name($name);
			$wiki_namespace->set_talk($talk);
			return [$id, $wiki_namespace];
		});

		array_walk($namespaces_array,
			function (Wiki_Namespace $namespace) use ($namespaces_array) {
				$id = $namespace->get_id();
				if ($id >= 0 && $id % 2 === 0 && isset($namespaces_array[$id + 1])) {
					$namespace->set_talk_namespace($namespaces_array[$id + 1]);
				}
			});

		return $namespaces_array;
	}

	/**
	 *
	 * @param string $arg0
	 * @param string $arg1
	 * @param mixed $deprecated_data
	 *        	Ignore this parameter, please
	 * @throws ProjectNotFoundException
	 */
	public function __construct($arg0, $arg1 = null, $deprecated_data = true) {
		global $validator;

		$validator->validate_arg($arg0, "string");
		$validator->validate_arg($arg1, "string", true);

		if ($arg1 === null) {
			$match = self::parseProjectString($arg0);
			$this->construct($match[0], $match[1]);
		} else {
			$this->construct(strtolower($arg0), strtolower($arg1));
		}
	}

	/**
	 *
	 * @param string $project
	 * @param string $subproject
	 * @throws ProjectNotFoundException
	 */
	private function construct($project, $subproject) {
		global $validator, $environment;

		$validator->validate_arg($project, "string");
		$validator->validate_arg($subproject, "string", true);

		$this->project = $project;
		$this->subproject = $subproject;

		if (!preg_match("/^wik[a-z\-]+$/", $this->project)) {
			throw new ProjectNotFoundException("Illegal project name: $this->project");
		}

		if (!preg_match("/^[a-z\-]+/", $this->subproject)) {
			throw new ProjectNotFoundException("Illegal subproject name: $this->subproject");
		}

		if ($this->subproject === "wts" && $this->project === "wikivoyage") {
			$this->project = "wikivoyage-old";
		}

		$this->secure_url_prefix = array_key_or_exception($environment, "url.prefix.secure");

		if ($this->project === "wikivoyage-old-shared") {
			if ($this->subproject !== "www") {
				throw new ProjectNotFoundException("$this->subproject.$this->project");
			}
			$this->baseUrl = "www.wikivoyage-old.org";
			$this->actionPrefix = "w/shared/index.php";
			$this->standardPrefix = "shared/";
			$this->allowSecure = false;
		} else {
			$this->baseUrl = "$this->subproject.$this->project.org";
			$this->actionPrefix = "w/index.php";
			$this->standardPrefix = "wiki/";
			$this->allowSecure = ($this->project !== "wikivoyage-old");
		}
		self::$by_base_url[$this->baseUrl] = $this;
	}

	/**
	 *
	 * @param string $string
	 * @throws ProjectNotFoundException
	 * @return string[]
	 */
	private static function parseProjectString($string) {
		$string = strtolower($string);
		if (!preg_match("/^([a-z\-]+)\.([a-z\-]+)/", $string, $match)) {
			throw new ProjectNotFoundException("Illegal full project name: $string");
		}
		return [$match[2], $match[1]];
	}

	/**
	 *
	 * @deprecated No longer needed, wiki loading is done automatically upon retrieval
	 * @return void
	 * @throws ProjectNotFoundException
	 */
	public function loadWiki() {
		$this->_loadWiki();
	}

	/**
	 *
	 * @return void
	 * @throws ProjectNotFoundException
	 */
	private function _loadWiki() {
		global $logger, $wiki_interface;

		if ($this->wiki === null) {
			$logger->debug("Load wiki $this->subproject.$this->project");

			if ($this->project === "wikipedia" && $this->subproject === "en") {
				$link = "MDanielsBot";
			} else if ($this->project === "wikimedia" && $this->subproject === "commons") {
				$link = "MDanielsBotCommons";
			} else {
				$link = "$this->subproject";
				if ($this->project !== "wikipedia") {
					$link .= ".$this->project";
				}
			}
			try {
				$this->wiki = $wiki_interface->new_wiki($link);
			} catch (BadEntryError $e) {
				throw new ProjectNotFoundException("Unknown error: unable to locate project: $link");
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getProject() {
		return $this->project;
	}

	/**
	 *
	 * @return string
	 */
	public function getSubproject() {
		return $this->subproject;
	}

	/**
	 *
	 * @return Wiki
	 * @throws ProjectNotFoundException
	 */
	public function getWiki() {
		$this->loadWiki();
		return $this->wiki;
	}

	/**
	 *
	 * @return string
	 */
	public function getDefaultHostWiki() {
		return $this->defaultHostWiki;
	}

	/**
	 *
	 * @param string $defaultHostWiki
	 */
	public function setDefaultHostWiki($defaultHostWiki) {
		$this->defaultHostWiki = $defaultHostWiki;
	}

	/**
	 *
	 * @param string $page
	 * @param string $display_name
	 * @return string
	 */
	private function linkFullProject($page, $display_name) {
		$val = @self::$interwikisFullUrl["$this->subproject.$this->project"];
		if ($val !== null) {
			$url = replace_named_variables($val, ["page" => urlencode($page)]);
			return "[$url $display_name]";
		}
		// else: return null
	}

	/**
	 *
	 * Get the interwiki link for this project on $host_wiki.
	 *
	 * @param string $host_wiki
	 * @throws ProjectNotFoundException
	 * @return string
	 */
	private function getProjectLink($host_wiki) {
		global $logger, $validator;

		$validator->validate_arg($host_wiki, "string");

		$host_project_data = new self($host_wiki, null, false);

		$hostProj = $host_project_data->getProject();
		$hostSubproj = $host_project_data->getSubProject();

		$projEquals = $hostProj === $this->project;
		$subProjEquals = $hostSubproj === $this->subproject;

		if ($projEquals && $subProjEquals) {
			return "";
		}

		$thiswiki = "$this->subproject.$this->project";

		foreach (self::$interwikisFull as $key => $val) {
			if ($thiswiki === $key) {
				return $val;
			}
		}

		if ($projEquals || ($this->project === "wikipedia" && $hostProj === "wikimedia")) {
			return $this->subproject;
		}

		$link = null;

		foreach (self::$interwikis as $key => $val) {
			if ($this->project === $key) {
				$link = $val;
				break;
			}
		}

		if ($link === null) {
			throw new ProjectNotFoundException("Project not found: $host_wiki");
		}

		if (!$subProjEquals && ($this->subproject !== "en" || $hostProj !== "wikimedia")) {
			$link = "$this->subproject:$link";
		}

		$logger->debug(
			"getProjectLink($host_wiki): this = $this->subproject.$this->project => $link");

		return $link;
	}

	/**
	 *
	 * @param string $page
	 * @param string $host_wiki
	 * @param bool $remove_namespace
	 * @throws ProjectNotFoundException
	 * @return string
	 */
	public function formatPageLinkAuto($page, $host_wiki = null, $remove_namespace = false) {
		global $validator;

		$validator->validate_arg($page, "string");
		$validator->validate_arg($host_wiki, "string", true);
		$validator->validate_arg($remove_namespace, "bool");

		if ($remove_namespace) {
			$display_name = preg_replace("/^.+\:/", "", $page);
		} else {
			$display_name = $page;
		}

		return $this->formatPageLink($page, $display_name, $host_wiki);
	}

	/**
	 *
	 * @param string $page
	 * @param string $display_name
	 * @param string $host_wiki
	 * @throws ProjectNotFoundException
	 * @return string
	 */
	public function formatPageLink($page, $display_name, $host_wiki = null) {
		global $validator;

		$validator->validate_arg($page, "string");
		$validator->validate_arg($display_name, "string");
		$validator->validate_arg($host_wiki, "string", true);

		if ($host_wiki === null) {
			$host_wiki = $this->defaultHostWiki;
		}

		$link = $this->linkFullProject($page, $display_name);

		if ($link !== null) {
			return $link;
		}

		$link = "[[:";
		$interwiki = $this->getProjectLink($host_wiki);
		if ($interwiki) {
			$link .= "$interwiki:";
		}
		$link .= "$page";

		if ($interwiki || $remove_namespace) {
			$link .= "|$display_name";
		}
		$link .= "]]";

		return $link;
	}

	/**
	 * @return Wiki_Namespace[]
	 */
	public function get_namespaces() {
		return self::$all_timed_namespace_data->offsetGet($this->baseUrl);
	}

	/**
	 *
	 * @param string $page_name
	 * @return Wiki_Namespace
	 */
	public function get_namespace($page_name) {
		$namespaces = $this->get_namespaces();

		$namespace_string = strstr($page_name, ":", true);
		if ($namespace_string === false) {
			$namespace_string = "";
		}
		$namespace_string = Template_Utils::normalize(mb_strtolower(mb_trim($namespace_string)));

		$namespace = array_search_callback($namespaces,
			function ($namespace) use($namespace_string) {
				return in_array($namespace_string, $namespace->get_aliases());
			}, false);

		if ($namespace === null) {
			$namespace = array_key_or_exception($namespaces, 0);
		}
		return $namespace;
	}

	/**
	 *
	 * @param string $page_name
	 * @throws Namespace_Exception
	 * @return string
	 */
	public function get_talk_page_name($page_name) {
		$namespace = $this->get_namespace($page_name);
		$after_colon = Template_Utils::normalize(
			$namespace->get_id() !== 0 ? strstr($page_name, ":") : ":$page_name");

		$talk_namespace = $namespace->get_talk_namespace();

		if ($talk_namespace === null) {
			if ($namespace->get_talk()) {
				return  Template_Utils::normalize($page_name);
			}

			throw new Namespace_Exception("Talk namespace not found for namespace of $page_name");
		}

		$talk_namespace_string = $talk_namespace->get_name();
		return "$talk_namespace_string$after_colon";
	}
	/**
	 *
	 * @param string $page_name
	 * @return string
	 */
	public static function get_base_page_name($page_name) {
		$before_slash = mb_strstr($page_name, "/", true);
		if ($before_slash !== false) {
			$page_name = $before_slash;
		}
		return Template_Utils::normalize($before_slash);
	}

	/**
	 *
	 * @param string $pageName
	 * @param string $action
	 * @return string
	 */
	public function getRawLink($pageName, $action = null) {
		$string = $this->allowSecure ? $this->secure_url_prefix : "http:";
		$string .= "//$this->baseUrl/";
		$string .= $action !== null ? "$this->actionPrefix?title=" : $this->standardPrefix;
		$string .= encodePageTitle($pageName);
		if ($action !== null) {
			$string .= "&action=$action";
		}

		return $string;
	}

	public function __toString() {
		return $this->baseUrl;
	}

	/**
	 *
	 * @return string[]
	 */
	public static function getInterwikis() {
		return self::$interwikis;
	}

	/**
	 * Returns a singleton
	 * @param string $type
	 * @return Project_Data
	 * @throws ProjectNotFoundException
	 */
	public static function load($type) {
		if (!isset(self::$projectDatas[$type])) {
			self::$projectDatas[$type] = new Project_Data($type);
		}
		return self::$projectDatas[$type];
	}
}
