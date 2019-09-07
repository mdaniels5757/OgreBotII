<?php
class Wiki_Interface {
	
	const EN_WIKIPEDIA_SYSOP_CACHE = "userrights.dat";
	
	/**
	 *
	 * @var int
	 */
	private $curl_timeout = 30;
	
	/**
	 *
	 * @var int[]
	 */
	private $backup_timeouts = [];
	
	/**
	 *
	 * @var int[]
	 */
	private $max_query = [];
	
	/**
	 *
	 * @var bool
	 */
	private $live_edits;
	
	/**
	 * 
	 * @var callable[][]
	 */
	private $hooks = [];
	
	/**
	 * 
	 * @var Wiki[]
	 */
	private $wiki_cache = [];
	
	/**
	 * 
	 * @var int
	 */
	private $max_curl_attempts;
	
	public function __construct() {
		global $constants;
		
		$this->max_curl_attempts = (int)array_key_or_exception($constants, 'maxqueryattempts');
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param array $result
	 * @return null|string[][][]
	 */
	private function get_query_continue(Wiki $wiki, array &$result) {
		if ($wiki->supports_version("1.21")) {
			return @$result['continue'];
		} else {
			$continue = @$result['query-continue'];
			if (!$continue) {
				return null;
			}
			
			return array_merge_all($continue);
		}
	}
	
	/**
	 * As Mediawiki has a nasty propensity to simply timeout, I've written a wrapper
	 * function to capture timeouts and rerun the query a few times.
	 * It will run the
	 * query 4 times before dying.
	 * 
	 * @param callable $callback        	
	 * @param Wiki $wiki        	
	 * @throws Exception
	 * @return mixed
	 */
	public function run_mediawiki_query($callback, Wiki $wiki = null) {
		global $logger, $validator;
		
		$validator->validate_arg($callback, "function");
		$validator->validate_arg($wiki, "Wiki", true);
		
		try {
			for($i = 1; true; $i++) {
				try {
					if ($wiki) {
						$new_timeout = $this->curl_timeout * $i;
						$wiki->get_http()->setTimeout($new_timeout);
						if ($i > 1) {
							$logger->debug("Timeout temporarily reset to $new_timeout seconds.");
						}
					}
					$result = $callback();
					
					// success
					return $result;
				} catch (Exception $e) {
					if ($e instanceof APIError || $e instanceof CURLError) {
						$logger->error($e);
					} else {
						throw $e;
					}
					
					if ($i < $this->max_curl_attempts) {
						$logger->info("cURL error (retry #$i of " . ($this->max_curl_attempts - 1) . ")");
					} else {
						$logger->error("Exceeded maximum number of curl attempts");
						$logger->error($e);
						
						throw $e;
					}
				}
			}
		} finally {
			if ($wiki) {
				$wiki->get_http()->setTimeout($this->curl_timeout);
			}
		}
	}
	
	/**
	 *
	 * @param int $timeout        	
	 * @return void
	 */
	public function set_curl_timeout($timeout) {
		global $logger, $validator;
		
		$validator->validate_arg($timeout, "int");
		
		$this->backup_timeouts[] = $this->curl_timeout;
		$this->curl_timeout = $timeout;
		$logger->debug("Timeout set to $timeout seconds.");
	}
	
	/**
	 *
	 * @return void
	 */
	public function reset_curl_timeout() {
		global $logger;
		
		if (!empty($this->backup_timeouts)) {
			$this->curl_timeout = array_pop($this->backup_timeouts);
			$logger->debug("Timeout restored to $this->curl_timeout seconds.");
		} else {
			$logger->warn("reset_curl_timeout() called with no corresponding set.");
		}
	}
	
	/**
	 * @deprecated use get_text
	 * @param Page $page        	
	 * @param bool $force        	
	 * @return string
	 */
	public function get_page_text(Page $page, $force = false) {
		return $this->get_text($page->get_wiki(), $page->get_title(), true)->text;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string|string[] $page_name
	 * @param bool $follow_redirect OPTIONAL default true
	 * @return Page_Text_Response|Page_Text_Response[]
	 */
	public function get_text(Wiki $wiki, $page_names, $follow_redirect = true) {
		
		$array = is_array($page_names);
		if (!$array) {
			$page_names = [$page_names];
		}
		
		$props = ["prop" => "revisions", "rvprop" => "content"];
		if ($follow_redirect) {
			$props["redirects"] = "";
		}
		$results = $this->query_generic($wiki, "titles", $page_names, $props, $redirects, $missing);
		
		$page_text_responses = array_merge(array_map(function($result) {
			$page_text_response = new Page_Text_Response();
			$page_text_response->exists = !isset($result['missing']);
			if ($page_text_response->exists) {
				$page_text_response->text = array_key_or_exception($result, "revisions", 0, "*");
			}
			return $page_text_response;
		}, $results), array_fill_keys($missing, new Page_Text_Response()));
		
		array_walk($redirects, function ($to, $from) use ($page_text_responses) {
			$page_text_response = array_key_or_exception($page_text_responses, $to);
			$page_text_response->redirect_from = $from;
			$page_text_response->redirect_to = $to;
		});
		
		if (!$array) {
			return array_shift($page_text_responses);
		}
		
		return $page_text_responses;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param array $query_array        	
	 * @param bool $post        	
	 * @return mixed
	 */
	public function api_query(Wiki $wiki, array $query_array, $post = false) {
		global $validator;
		
		$validator->validate_arg($query_array, "array");
		return $this->run_mediawiki_query(
			function () use($wiki, $query_array, $post) {
				return $wiki->apiQuery($query_array, $post, false);
			}, $wiki);
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string $name        	
	 * @return Image
	 */
	public function new_image(Wiki $wiki, $name) {
		global $validator;
		
		$validator->validate_arg($name, "string");
		return $this->run_mediawiki_query(
			function () use($wiki, $name) {
				return new Image($wiki, $name);
			}, 
			$wiki);
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string $type        	
	 * @param string[]|null $extraParams
	 *        	- can be null
	 * @param string[]|null $redirects
	 * @param callable $stop_loading_callback
	 * @param string[]|null $missing
	 * @throws T86611_Exception if thrown
	 * @return array containing the query
	 */
	protected function simple_query(Wiki &$wiki, $type, $extraParams, array &$redirects = null,
		callable $stop_loading_callback = null, array &$missing = null) {
		
		global $logger, $validator;
		
		$validator->validate_arg($type, "string");
		$validator->validate_arg_array($extraParams, "string-numeric", true);
		
		if ($extraParams === null) {
			$extraParams = [];
		}
		
		if (!is_array($redirects)) {
			$redirects = [];
		}
		
		if (!is_array($missing)) {
			$missing = [];
		}
		
		if ($logger->isTraceEnabled()) {
			$logger->trace("simple_query($wiki, $type, " . query_to_string($extraParams) . ")");
		}
		// query type data
		$api_params = Api_Params::get_api_params($wiki, $type);
		$baseParams = $api_params->get_params();
		$subIndex = $api_params->get_index();
		$keyType = $api_params->get_title();
		
		$g = 0;
		$query_continue = [];
		if ($api_params->get_continue()) {
			$baseParams['continue'] = '';
		}
		$all_values = [];
		$sub_index_search = $api_params->get_top_index();
		$sub_index_search[] = $subIndex;
		$redirects_search = $api_params->get_top_index();
		$redirects_search[] = "redirects";
		do {
			$g++;
			$logger->trace(".");
			
			$query = array_merge($baseParams, $extraParams, $query_continue);
			
			$result = $this->api_query($wiki, $query, true);
			
			try {
				$this->run_hooks("simple_query_get_api_data", $result);
				
				$old_query_continue = $query_continue;
				$query_continue = $this->get_query_continue($wiki, $result);
				
				if ($query_continue && $query_continue === $old_query_continue) {
					$logger->warn(
						"Mediawiki hiccup? Same query_continue returned! " . print_r($query, true));
					throw new T86611_Exception($query_continue["aicontinue"]);
				}
				$logger->trace(" {$g}");
				
				// translate array keys into a new array so that array_merge_recursive will
				// properly combine arrays
				$sub_query = array_key_or_exception($result, $sub_index_search);

				$translated = map_array_function_keys($sub_query, 
					function ($value) use($keyType, &$missing) {
						if (isset($value['missing'])) {
							$missing[] = $value[$keyType];
						} else {
							return [$value[$keyType], $value];
						}
					});
				
				$redirects_query = _array_key_or_value($result, [], $redirects_search);
				$next_redirects = map_array_function_keys($redirects_query, function ($redirect) {
					$from = array_key_or_exception($redirect, 'from');
					$to = array_key_or_exception($redirect, 'to');
					return [$from, $to];
				});
				
				$redirects = array_merge($redirects, $next_redirects);
				
			} catch (ArrayIndexNotFoundException $e) {
				ogrebotMail($e);
				throw new WikiDataException("Unexpected query return.", $e);
			}
			$all_values = array_merge_recursive_new_index_if_numeric($all_values, $translated);
			

			if ($stop_loading_callback && !$stop_loading_callback($translated, $all_values)) {
				$query_continue = false;
			}
		} while ($query_continue);
		
		return $all_values;
	}
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string $name        	
	 * @param mixed $pageid        	
	 * @param bool $followRedir        	
	 * @param bool $normalize        	
	 * @return Page
	 */
	public function new_page(Wiki $wiki, $name, $pageid = null, $followRedir = true, $normalize = true) {
		global $validator;
		
		$validator->validate_arg($name, "string");
		return $this->run_mediawiki_query(
			function () use($wiki, $name, $pageid, $followRedir, $normalize) {
				return new Page($wiki, $name, $pageid, $followRedir, $normalize);
			}, 
			$wiki);
	}
	
	/**
	 *
	 * @param string $config        	
	 * @return Wiki
	 * @throw BadEntryError
	 */
	public function new_wiki($config) {
		global $logger, $validator;
		
		if (isset($this->wiki_cache[$config])) {
			return $this->wiki_cache[$config];
		}
		
		$validator->validate_arg($config, "string");
		$logger->trace("Loading $config");
		$wiki = $this->run_mediawiki_query(
			function () use($config) {
				return Peachy::newWiki($config);
			});
		$wiki->addHook('APIQueryCheckError', [self::class, "api_data_warning_check"]);
		
		$this->wiki_cache[$config] = $wiki;
		
		return $wiki;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string $search_text
	 * @param string|null $namespaces
	 * @param int $limit
	 * @return string[]
	 */
	function search(Wiki $wiki, $search_text, $namespaces = null, $limit = 10000) {
		global $logger, $validator;
		
		$logger->debug("search($wiki, $search_text, $namespaces, $limit)");
		
		$validator->validate_arg($search_text, "string");
		$validator->validate_arg($namespaces, "string", true);
		$validator->validate_arg($limit, "int");
		
		$query_results = $this->simple_query($wiki, "search", 
			["srsearch" => $search_text, "srnamespace" => $namespaces], $redirects, 
			function (&$next_query, $all_data) use ($limit) {
				return count($all_data) < $limit;
			});
		
		$titles = array_slice(array_keys($query_results), 0, $limit);
		
		return $titles;
	}
	
	/**
	 * @Deprecated
	 * @param Wiki $wiki        	
	 * @param string $category        	
	 * @param bool $subcats        	
	 * @param int[]|int|null $namespaces        	
	 * @param int $limit        	
	 * @param string[] $ignored
	 *        	Subcategories not to search
	 * @return string[]
	 */
	function new_category_traverse(Wiki $wiki, $category, $subcats = false, $namespaces = null, $limit = 5000, 
		$ignored = []) {
		
		global $validator;
		
		$validator->validate_arg($category, "string");
		$validator->validate_arg($subcats, "bool");
		$validator->validate_arg_multiple($namespaces, ["array", "numeric"], true);
		$validator->validate_arg($limit, "int", true);
		$validator->validate_arg_array($ignored, "string");
		
		if (is_null($namespaces)) {
			$namespaces = [];
		} else if (is_int($namespaces)) {
			$namespaces = [$namespaces];
		} else {
			$validator->validate_arg_array($namespaces, "numeric");
		}
		
		if ($subcats && !in_array(CATEGORY_NAMESPACE, $namespaces)) {
			$include_cats = false;
			$namespaces[] = CATEGORY_NAMESPACE;
		} else {
			$include_cats = true;
		}
		
		$ignored = array_fill_keys($ignored, true);
		
		$extra_params = [];
		
		if (count($namespaces) > 0) {
			$extra_params["cmnamespace"] = implode("|", $namespaces);
		}
		
		$members = [];
		$categories = [$category];
		
		while ((count($members) < $limit || $limit === null) && count($categories) > 0) {
			
			$next_category = array_shift($categories);
			$ignored[$next_category] = true;
			$extra_params['cmtitle'] = $next_category;
			$this->simple_query($wiki, "categorymembers", $extra_params, $redirects, 
				function ($new_members) use(&$members, &$categories, $limit, &$include_cats, $ignored) {
					try {
						array_walk($new_members, 
							function ($query_data) use(&$members, &$categories, $limit, &$include_cats, 
							$ignored) {
								$is_category = array_key_or_exception($query_data, "ns") ==
									 CATEGORY_NAMESPACE;
								
								$title = array_key_or_exception($query_data, "title");
								if ($is_category) {
									if (!@$ignored[$title]) {
										$categories[] = $title;
									}
									if ($include_cats) {
										$members[] = $title;
									}
								} else {
									$members[] = $title;
								}
								
								if ($limit !== null && count($members) >= $limit) {
									throw new ArrayWalkBreakException();
								}
							});
						
						return true;
					} catch (ArrayWalkBreakException $e) {
						return false;
					}
				});
		}
		
		return $members;
	}
	
	/**
	 *
	 * @param Page $page        	
	 * @param string $text        	
	 * @param string $summary        	
	 * @param int $settings        	
	 * @param int $edit_conflicts  
	 * @param int|null $timeout      	
	 * @return void
	 * @throws CURLError
	 * @throws EditConflictException
	 * @throws APIError
	 */
	public function edit(Page $page, $text, $summary, $settings = 0, $edit_conflicts = 0, 
		$timeout = null) {
		
		global $logger, $validator;
	
		$validator->validate_arg($settings, "int");
		$validator->validate_arg($edit_conflicts, "int");
		$validator->validate_arg($timeout, "int", true);
		
		if ($logger->isDebugEnabled()) {
			$logger->debug(
				"edit(" . $page->get_title() . ", " . strlen($text) .
					 ", $summary, $settings, $edit_conflicts, $timeout)");
			$logger->debug($settings);
		}
		
		$minor = $settings & EDIT_MINOR ? true : false;
		$bot   = $settings & EDIT_NO_BOT ? false : true;
		$force = $settings & EDIT_NO_FORCE ? false : true;
		$pend = $settings & EDIT_APPEND ? "ap" : ($settings & EDIT_PREPEND ? "pre" : null);
		$create = $settings & EDIT_CREATE_ONLY ? "only" : ($settings & EDIT_NO_CREATE ? "never" : null);
		
		if ($timeout !== null) {
			$this->set_curl_timeout($timeout);
		}
		try {
			$this->edit_throw_exceptions($page, $text, $summary, $minor, $bot, $force, $pend, 
				$create);
		} catch (EditConflictException $e) {
			if ($edit_conflicts <= 0) {
				throw $e;
			} else {
				$this->edit($page, $text, $summary, $settings, $edit_conflicts - 1);
			}
		} finally {
			if ($timeout !== null) {
				$this->reset_curl_timeout();
			}
		}
	}
	
	/**
	 * @deprecated
	 * @param Page $page        	
	 * @param string $text        	
	 * @param string $summary        	
	 * @param bool $minor        	
	 * @param bool $bot        	
	 * @param bool $force        	
	 * @param bool|string $pend        	
	 * @throws CURLError
	 * @throws EditConflictException
	 * @throws APIError
	 * @return void
	 */
	public function edit_throw_exceptions(Page $page, $text, $summary, $minor = false, $bot = true, 
		$force = false, $pend = false, $create = false) {
		global $logger, $validator;
		
		$validator->validate_arg($text, "string");
		$validator->validate_arg($summary, "string");
		$title = $page->get_title();
		$wiki = $page->get_wiki();
		
		$timeout = intval(strlen($text) / 400);
		if ($timeout > $this->curl_timeout) {
			$logger->debug("Setting curl timeout to $timeout.");
			$this->set_curl_timeout($timeout);
			$reset = true;
		} else {
			$reset = false;
		}
		
		if (!$this->live_edits) {
			$logger->all("**** Feigning wiki edit");
			$logger->all("Wiki: " . $wiki->get_base_url());
			$logger->all("Page: $title");
			$logger->all("Summary: $summary");
			$logger->all("Minor: " . ($minor ? "true" : "false"));
			$logger->all("Pend: " . ($pend ? $pend : "false"));
			// $logger->all("Create: ".($create?"true":"false"));
			$logger->all("Text:");
			$logger->all($text);
			return;
		} else if ($logger->isTraceEnabled()) {
			$logger->trace("**** Making wiki edit");
			$logger->trace("Wiki: " . $wiki->get_base_url());
			$logger->trace("Page: $title");
			$logger->trace("Summary: $summary");
			$logger->trace("Minor: " . ($minor ? "true" : "false"));
			$logger->trace("Pend: " . ($pend ? $pend : "false"));
			// $logger->trace("Create: ".($create?"true":"false"));
			$logger->trace("Text:");
			$logger->insane($text);
		} else {
			$logger->debug("Performing edit to $title");
		}
		
		try {
			$return_result = $this->run_mediawiki_query(
				function () use($page, $text, $summary, $minor, $bot, $force, $pend, $create) {
					return $page->edit($text, $summary, $minor, $bot, $force, $pend, $create);
				}, 
				$page->get_wiki());
			
			// PHP, please add finally. kthxbye.
			if ($reset) {
				$this->reset_curl_timeout();
			}
		} catch (Exception $e) {
			$logger->error($e);
			
			if ($reset) {
				$this->reset_curl_timeout();
			}
			
			if ($e instanceof EditConflictException || $e instanceof PermissionsError ||
				 $e instanceof APIError || $e instanceof CURLError) {
				throw $e;
			}
			
			$logger->warn("Unknown exception type.");
			throw $e;
		}
		
		/*
		 * known mediawiki and class bug; it just craps out sometimes on timeouts and
		 * $page loses all its data
		 */
		if ($return_result === false) {
			
			if (strlen($page->get_title()) === 0 && !$pend) {
				$logger->debug("... API error... checking status of edit ");
				$page = $this->new_page($wiki, $title);
				$newtext = $this->get_page_text($page, true);
				
				if ($newtext === $text) {
					/*
					 * edit was successful; MW error occurred after server commit,
					 * so no worries
					 */
					$logger->debug("... API is lying, edit was successful ");
					return;
				}
				$logger->debug("... edit was unsuccessful ");
			}
			
			$logger->error("**** Unable to write to page");
			$logger->error("Wiki: " . $page->get_wiki()->get_base_url());
			$logger->error("Page: " . $page->get_title());
			$logger->error("Summary: $summary");
			$logger->error("Minor: " . ($minor ? "true" : "false"));
			$logger->error("Pend: " . ($pend ? $pend : "false"));
			$logger->error("Create: " . ($create ? "true" : "false"));
			$logger->error("Text:\n***BEGIN TEXT***\n\n$text\n\n***END TEXT***");
			
			throw new APIError("Unknown API error.");
		}
	}
	public function edit_suppress_exceptions(Page $page, $text, $summary, $minor = false, $bot = true, 
		$force = false, $pend = false, $create = false) {
		global $logger;
		
		try {
			$this->edit_throw_exceptions($page, $text, $summary, $minor, $bot, $force, $pend, 
				$create);
		} catch (Exception $e) {
			$logger->warn($e);
			if (!$e instanceof PermissionsError && !$e instanceof EditConflictException) {
				ogrebotMail($e);
			}
		}
	}
	
	/**
	 *
	 * @param Image $img        	
	 * @param string $upload_summary        	
	 * @param string $filename
	 * @param string $mime
	 * @param int $timeout        	
	 * @param int $reset_timeout        	
	 * @return void
	 * @throws UploadException
	 */
	public function upload_mw(Image $img, $upload_summary, $filename, $mime, $timeout = NULL, $reset_timeout = 30) {
		global $logger, $validator;
		
		$validator->validate_args("string", $upload_summary, $filename, $mime);
		
		
		$validator->assert(
			$img->get_exists(), 
			$img->get_page()->get_title() . " does not exist; upload to new file not yet available.");
		
		$page = $img->get_page();
		$http_instance = $page->get_wiki()->get_http();
		
		if ($this->live_edits) {
			/* we cannot afford to run timeouts with several megabyte files */
			$http_instance->setTimeout($timeout);
			$return_val = null;
			$curl_try_failures = 0;
			do {
				$time_start = time();
				try {
					$return_val = $img->upload($mime, $filename, "", $upload_summary);
					break;
				} catch (Exception $e) {
					if ($e instanceof APIError || $e instanceof CURLError) {
						$logger->error($e);
					} else {
						throw UploadException($e);
					}
					
					/*
					 * this will loop ONLY if a timeout occurs which is unrelated to upload file size. The script will only catch the problem if
					 * the $timeout variable is set upon the function call. Otherwise, the script will think the file is too large, and error out.
					 * It IS possible this will result in duplicate uploads; this is unfortunate, but not our problem: the MW API doesn't handle curl
					 * errors exceptionally well.
					 */
					if (++$curl_try_failures <= $this->max_curl_attempts && (time() - $time_start) < $timeout) {
						$logger->info(
							"... cURL error (retry #$curl_try_failures of " . $this->max_curl_attempts . ")");
					} else {
						throw new UploadException($e);
					}
				}
			} while ($curl_try_failures != 0);
			
			// TODO finally...
			$http_instance->setTimeout($reset_timeout);
			
			if (!$return_val) {
				throw new UploadException();
			}
		} else {
			$logger->all("Simulated (i.e., non-live) upload: press any key to continue.");
		}
	}
	
	/**
	 * FIXME: mediawiki bug wherein it returns shared image reversions if there is a local
	 * description page for a shared image
	 * 
	 * @param Image $image        	
	 * @throws IllegalStateException
	 * @return array[]
	 */
	public function get_upload_history(Image $image) {
		global $validator;
		
		if (!$image->get_exists()) {
			return [];
		}
		
		$hist_tmp = $this->run_mediawiki_query(
			function () use($image) {
				return $image->imageinfo(500, -1, -1, null, null, 
					['timestamp', 'user', 'comment', 'url', 'size', 'sha1', 'mime', 
						'archivename']);
			}, 
			$image->get_page()->get_wiki());
		
		$validator->assert(is_array($hist_tmp) && count($hist_tmp) == 1);
		/* there will be only one object, an array with an arbitrary index */
		foreach ($hist_tmp as $imginfo) {
			/* redirect; just return empty array */
			if (!array_key_exists('imageinfo', $imginfo)) {
				return [];
			}
			
			$return_var = $imginfo['imageinfo'];
			if (count($return_var) > 500) {
				throw new IllegalStateException(
					"Too many files in history; " .
						 "get_upload_history() is not currently configured to handle >500");
			}
			return $return_var;
		}
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @return void
	 */
	private function init_max_query(Wiki &$wiki) {
		global $logger;
		
		$logger->debug("init_max_query($wiki)");
		$limit = $this->api_query($wiki, ["action" => "paraminfo", "modules" => "parse"]);
		
		$params = array_key_or_null($limit, 'paraminfo', 'modules', 0, 'parameters');
		foreach ($params as $param) {
			$name = array_key_or_null($param, 'name');
			$limit = array_key_or_null($param, 'limit');
			if ($name === 'prop' && is_numeric($limit)) {
				$max_query = intval($limit);
				$logger->debug("Max	query is $max_query");
				break;
			}
		}
		
		if ($max_query === null) {
			$logger->error(
				"Unable to parse rate limit: query: " . print_r($limit, true) .
					 "; limit assumed as 50.");
			$max_query = 50;
		}
		$hash = $wiki->get_hash();
		$this->max_query[$hash] = $max_query;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @return int
	 */
	protected function get_max_query(Wiki $wiki) {
		$wikiHash = $wiki->get_hash();
		if (@$this->max_query[$wikiHash] === null) {
			$this->init_max_query($wiki);
		}
		
		return $this->max_query[$wikiHash];
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @return int
	 */
	protected function reduce_max_query(Wiki $wiki) {
		global $logger;
		
		$wikiHash = $wiki->get_hash();
		$max_query = &$this->max_query[$wikiHash];
		$next_max_query = intval($max_query * 7 / 8);
		
		$logger->warn("\$max_query reduced ($max_query -> $next_max_query");
		$max_query = $next_max_query;
		if ($max_query_local === 0) {
			throw new WikiDataException("Query size reduced to zero.");
		}
		return $max_query;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string $type        	
	 * @param string[] $elements        	
	 * @param string[] $props        
	 * @param string[]|null $redirects	
	 * @param string[]|null $missing
	 * @return mixed
	 */
	public function query_generic(Wiki &$wiki, $type, $elements, $props, array &$redirects = null, 
			array &$missing = null) {
		global $logger, $validator;
		
		$validator->validate_arg($type, "string");
		$validator->validate_arg_array($elements, "string");
		$validator->validate_arg_array($props, "string");
		$validator->validate_arg($props, "array");
		
		$countElements = count($elements);
		
		if ($logger->isDebugEnabled()) {
			$logger->debug(
				"query_generic($wiki, $type, array[$countElements], " . query_to_string($props) . ")");
		}
		

		$api_params = Api_Params::get_api_params($wiki, $type);
		$title_query = $api_params->get_title_query();
		if ($title_query === null) {
			$title_query = $type;
		}
		$max_query = $this->get_max_query($wiki);
		
		$results = [];
		
		$all_values = [];
		for($h = 0; $h < $countElements; $h += $max_query) {
			$array_slice = array_slice($elements, $h, min($max_query, $countElements - $h));
			$title_str = implode("|", $array_slice);
			
			$props[$title_query] = $title_str;
			$logger->debug("*$h");
			
			try {
				$new_values = $this->simple_query($wiki, $type, $props, $redirects, null, $missing);
			} catch (WikiDataException $e) {
				$h -= $max_query;
				$this->reduce_max_query($wiki);
				continue;
			}
			$all_values = array_merge_recursive($all_values, $new_values);
		}
		
		return $all_values;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string $title
	 * @param int $options
	 * @param string|null $namespace
	 * @return string[]
	 */
	public function get_usage(Wiki $wiki, $title, $options = 0, $namespace = null) {
		global $validator;
		
		$validator->validate_arg($title, "string");
		$validator->validate_arg($options, "int");
		$validator->validate_arg_multiple($namespace, ["string", "int"], true);
		
		$iufilterredir = $options & USAGE_REDIRECTS_ONLY ? "redirects" : ($options &
			 USAGE_NON_REDIRECTS_ONLY ? "nonredirects" : "all");
		
		$extra_params = ["iufilterredir" => $iufilterredir];
		
		if ($options & USAGE_PAGES_THROUGH_REDIRECTS) {
			$extra_params["iuredirect"] = "1";
		}
		
		if ($namespace !== null) {
			$extra_params["iunamespace"] = $namespace;
		}
		
		$results = $this->query_generic($wiki, "iutitle", [$title], $extra_params);
		
		return array_keys($results);
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string $sha1
	 * @param bool $only_first
	 * @return string[]|string|null
	 */
	function get_files_by_hash(Wiki $wiki, $sha1, $only_first = false) {
		global $logger, $validator;
		
		if (!$sha1) {
			$logger->warn("Attempted to get files for empty hash");
			return null;
		}
		$validator->validate_arg($sha1, "string");
		
		$results = array_keys($this->simple_query($wiki, "allimages", ['aisha1' => $sha1]));
		
		if ($only_first) {
			return $results ? array_shift($results) : null;
		}
		
		return $results;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string[] $search_titles
	 *        	titles for which we'll gather information
	 * @param string[] $query_prop
	 *        	e.g., "imageinfo|revisions|templates"
	 * @return array
	 */
	function query_pages(Wiki &$wiki, $search_titles, $query_prop) {
		global $logger, $validator;
		
		$count = count($search_titles);
		
		$logger->debug("query_pages($wiki, $count, $query_prop)");
		
		$max_query = $this->get_max_query($wiki);
		
		$query_prop_array = explode("|", $query_prop);
		$templates = in_array("templates", $query_prop_array);
		$imageinfo = in_array("imageinfo", $query_prop_array);
		$revisions = in_array("revisions", $query_prop_array);
		$categorieshidden = in_array("categorieshidden", $query_prop_array);
		$categoriesnohidden = in_array("categoriesnohidden", $query_prop_array);
		$categoriesall = in_array("categoriesall", $query_prop_array);
		$categories = false;
		$static_props = ["prop" => $query_prop, "redirects" => ""];
		
		if ($templates) {
			$static_props['tllimit'] = 'max';
		}
		
		if ($imageinfo) {
			$static_props['iilimit'] = 'max';
			$static_props['iiprop'] = "sha1|mime|user|comment|url|size|timestamp|bitdepth|" .
				 "metadata";
		}
		
		if ($revisions) {
			$static_props['rvprop'] = 'content';
		}
		if ($categorieshidden) {
			$categories = true;
			array_splice($query_prop_array, array_val_to_key($query_prop_array, "categorieshidden"), 
				1, "categories");
			$static_props['clshow'] = 'hidden';
		}
		if ($categoriesnohidden) {
			$categories = true;
			array_splice($query_prop_array, 
				array_val_to_key($query_prop_array, "categoriesnohidden"), 1, "categories");
			$static_props['clshow'] = '!hidden';
		}
		if ($categoriesall) {
			$categories = true;
			array_splice($query_prop_array, array_val_to_key($query_prop_array, "categoriesall"), 1, 
				"categories");
		}
		if ($categories) {
			$static_props['cllimit'] = 'max';
		}
		
		$static_props['prop'] = implode("|", $query_prop_array);
		$results = [];
		
		for($h = 0; $h < $count; $h += $max_query) {
			$array_slice = array_slice($search_titles, $h, min($max_query, $count - $h));
			$title_str = implode("|", $array_slice);
			$query_vars_no_props = ['action' => 'query', 'titles' => $title_str];
			
			$g = 0;
			$query = [];
			$logger->debug("...$h");
			do {
				
				if ($logger->isDebugEnabled()) {
					$logger->debug("Memory usage: " . memory_get_usage());
				}
				$g++;
				$query_continue = [];
				if ($query !== null && array_key_exists('query-continue', $query)) {
					$new_props = [];
					foreach ($query['query-continue'] as $prop_name => $next) {
						$new_props[] = $prop_name;
						foreach ($next as $continue_key => $continue_val) {
							$query_continue[$continue_key] = $continue_val;
						}
					}
					unset($query['query-continue']);
					$static_props['prop'] = implode("|", $new_props);
					
					if ($logger->isDebugEnabled()) {
						$logger->debug("......" . implode("|", $query_continue));
					}
				}
				$query_vars = array_merge($query_vars_no_props, $static_props, $query_continue);
				
				$new_query = $this->api_query($wiki, $query_vars, true);
				
				$validator->validate_arg($new_query, "array");
				
				$query = array_merge_recursive($query, $new_query);
				
				// seems to occur when Wikimedia doesn't want so many queries at once
				if (!array_key_exists("query", $query)) {
					$h -= $max_query_local;
					$this->reduce_max_query($wiki);
					continue;
				}
				
				$logger->trace("{" . $h . ($g !== 1 ? "-$g" : "") . "}");
				if (array_key_exists("normalized", $query['query'])) {
					foreach ($query['query']['normalized'] as $normalized_title) {
						$results[$normalized_title['from']]['normalized'] = $normalized_title['to'];
					}
				}
				if (array_key_exists("redirects", $query['query'])) {
					foreach ($query['query']['redirects'] as $redirect) {
						$results[$redirect['from']]['redirect'] = $redirect['to'];
					}
				}
			} while (array_key_exists('query-continue', $query));
			
			$pages = $query["query"]["pages"];
			foreach ($pages as $index => $subpage) {
				if (!$subpage) {
					$logger->debug("Empty page?");
					continue;
				}
				if (!array_key_exists("title", $subpage)) {
					
					// just a simple redirect
					if (is_array($subpage) && isset($subpage['imagerepository']) &&
						 !$subpage['imagerepository']) {
						continue;
					}
					
					// See https://bugzilla.wikimedia.org/show_bug.cgi?id=61815.
					$logger->trace("No title? \$index = $index; \$subpage = ");
					$logger->trace($subpage);
					continue;
				}
				$title = $subpage["title"];
				
				if (!array_key_exists($title, $results)) {
					$results[$title] = [];
				}
				if (array_key_exists("missing", $subpage)) {
					$results[$title]['missing'] = true;
					continue;
				}
				
				if ($revisions && isset($subpage['revisions'])) {
					$results[$title]['text'] = $subpage['revisions'][0]['*'];
					if (strlen($results[$title]['text']) > 100000) {
						$logger->debug("Very large page: $title");
					}
				}
				if ($imageinfo) {
					$results[$title]['repository'] = $subpage['imagerepository'];
					if ($results[$title]['repository'] == 'local') {
						$results[$title]['history'] = $subpage['imageinfo'];
					}
				}
				
				if ($templates) {
					if (!array_key_exists("templates", $subpage)) {
						$subpage['templates'] = [];
					}
					$templates_this = $subpage['templates'];
					
					if (!array_key_exists('templates', $results[$title])) {
						$results[$title]['templates'] = [];
					}
					
					foreach ($templates_this as $template) {
						$results[$title]['templates'][] = $template['title'];
					}
				}
				
				if ($categories && array_key_exists("categories", $subpage)) {
					
					$categories = $subpage['categories'];
					
					if (!array_key_exists('categories', $results[$title])) {
						$results[$title]['categories'] = [];
					}
					
					foreach ($categories as $category) {
						$results[$title]['categories'][] = $category['title'];
					}
				}
			}
		}
		return $results;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string[] $search_titles
	 *        	titles for which we'll gather information
	 * @param string $query_prop
	 *        	e.g., "imageinfo|revisions|templates"
	 * @param string[] $additional_prop
	 * @param string[]|null $redirects 	
	 * @return array
	 */
	function new_query_pages(Wiki $wiki, $search_titles, $query_prop, $additional_prop = [],
		array &$redirects = null) {
		global $logger, $validator;
		
		$validator->validate_arg_array($search_titles, "string");
		$validator->validate_arg($query_prop, "string");
		
		$logger->debug(
			"new_query_pages($wiki, array[" . count($search_titles) . "], $query_prop, " .
				 query_to_string($additional_prop) . ")");
		
		$query_prop_array = explode("|", $query_prop);
		$static_props = ["redirects" => ""];
		
		if (in_array("templates", $query_prop_array)) {
			$static_props['tllimit'] = 'max';
		}
		
		if (in_array("imageinfo", $query_prop_array)) {
			$static_props['iilimit'] = 'max';
			$static_props['iiprop'] = "sha1|mime|user|comment|url|size|timestamp|bitdepth|" .
				 "metadata";
		}
		
		if (in_array("revisions", $query_prop_array)) {
			$static_props['rvprop'] = 'content';
		}
		
		if (in_array("categorieshidden", $query_prop_array)) {
			$static_props['cllimit'] = 'max';
			array_splice($query_prop_array, array_val_to_key($query_prop_array, "categorieshidden"), 
				1, "categories");
			$static_props['clshow'] = 'hidden';
		}
		
		if (in_array("categoriesnohidden", $query_prop_array)) {
			$static_props['cllimit'] = 'max';
			array_splice($query_prop_array, 
				array_val_to_key($query_prop_array, "categoriesnohidden"), 1, "categories");
			$static_props['clshow'] = '!hidden';
		}
		
		if (in_array("categoriesall", $query_prop_array)) {
			$static_props['cllimit'] = 'max';
			array_splice($query_prop_array, array_val_to_key($query_prop_array, "categoriesall"), 1, 
				"categories");
		}
		$static_props['prop'] = implode("|", $query_prop_array);
		
		$static_props = array_merge($static_props, $additional_prop);
		
		return $this->query_generic($wiki, 'titles', $search_titles, $static_props, $redirects);
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
 	 * @param number $start        	
	 * @param number $end        	
	 * @param bool $getCategories
	 * @throws ArrayIndexNotFoundException
	 * @return string[][]
	 */
	function get_recent_uploads_with_image_data(Wiki $wiki, $start, $end) {
		global $logger;
		
		$logger->debug("get_recent_uploads_with_image_data($wiki, $start, $end)");

		// A necessary hack to avoid out of memory errors when files
		//   carry absurdly large amounts of metadata (>=12MB)
		$hook = function (&$api_data) {
			foreach ($api_data['query']['allimages'] as &$file) {
				if (isset($file[METADATA])) {
					foreach ($file[METADATA] as $key => &$metadatum) {
						if (!in_array(@$metadatum["name"], ["Make", "Model", SPECIAL_INSTRUCTIONS])) {
							unset($file[METADATA][$key]);
						}
					}
				}
			}
		};
		$this->add_hook("simple_query_get_api_data", $hook);
		$uploads = $this->get_recent_uploads_single_array($wiki, $start, $end, 
			["user", "sha1", "size", "dimensions", "mime", "timestamp", "comment", "metadata"]);
		$this->remove_hook("simple_query_get_api_data", $hook);
		
		$imagenames = array_keys($uploads);
		
		$data = $this->new_query_pages($wiki, $imagenames, 'categorieshidden');
	
		foreach ($data as $title => &$this_data) {
			if (!isset($uploads[$title])) {
				$logger->warn(
					"$title not found in array; was the original page just " .
						 "altered while performing the query?");
				continue;
			}
			
			$api_categories = array_key_or_empty($this_data, "categories");
			$uploads[$title]['categories'] = map_array($api_categories, "title");
		}
		return $uploads;
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param number $start        	
	 * @param number $end        	
	 * @param string[] $aiprops OPTIONAL extra aiprops
	 * @throws ArrayIndexNotFoundException
	 * @return string[][]
	 */
	function get_recent_uploads_single_array(Wiki $wiki, $start, $end, $aiprops = []) {
		global $logger, $validator;
		
		$validator->validate_arg($start, "numeric");
		$validator->validate_arg($end, "numeric");
		$validator->validate_arg_array($aiprops, "string");
		
		$aiprops_string = implode("|", $aiprops);
		$logger->debug("get_recent_uploads_single_array($wiki, $start, $end, $aiprops_string)");
		
		try {
			$params = ['aistart' => $start, 'aiend' => $end, 'aisort' => 'timestamp', 
				'aidir' => 'newer', 'aiprop' => $aiprops_string];
			
			$values = $this->simple_query($wiki, "allimages", $params);
		} catch (T86611_Exception $e) {
			//this solution is clumsy but I lack time and resources to care.
			$bad_time = intval($e->get_continue());
			if ($bad_time <= 0) {
				//didn't parse
				throw $e;
			}

			$logger->info("T86611_Exception caught with bad time of $bad_time");
			
			$values_begin = $start === $bad_time ? [] : 
				$this->get_recent_uploads_single_array($wiki, $start, $bad_time - 1, $aiprops);
			$values_end = $end === $bad_time ? [] :
				$this->get_recent_uploads_single_array($wiki, $bad_time + 1, $end, $aiprops);
			$values = array_merge($values_begin, $values_end);
		}
		$logger->debug("end get_recent_uploads_single_array");
		
		return $values;
	}
	
	/**
	 * Different from get_recent_uploads in that this uses a different API function
	 * and doesn't return reuploads.
	 * 
	 * @param Wiki $wiki        	
	 * @param String $starttime        	
	 * @param String $endtime        	
	 * @throws WikiDataException - if thrown by simple_query
	 * @return array
	 */
	function new_files(Wiki &$wiki, $starttime, $endtime) {
		global $logger, $validator;
		
		$validator->validate_arg($starttime, "numeric");
		$validator->validate_arg($endtime, "numeric");
		
		$logger->debug("newfiles($wiki, $starttime, $endtime)");
		$results = $this->simple_query($wiki, "allimages", 
			["aiprop" => "user|timestamp|url", "aisort" => "timestamp", "aidir" => "older", 
				"aistart" => $starttime, "aiend" => $endtime]);
		
		$logger->debug("\$results = array[" . count($results) . "]");
		
		return array_reverse($results, true);
	}
	
	/**
	 * Different from get_recent_uploads in that this uses a different API function
	 * and doesn't return reuploads.
	 *
	 * @param Wiki $wiki    
	 * @param String $uploader    	
	 * @param number $starttime        	
	 * @param number $endtime        	
	 * @param number|null $limit        	
	 * @throws WikiDataException - if thrown by simple_query
	 * @return array
	 */
	public function uploads_by_user(Wiki &$wiki, $uploader, $starttime = null, $endtime = null, 
		$limit = null) {
		global $logger, $validator;

		$validator->validate_arg($uploader, "string");		
		$validator->validate_arg($starttime, "numeric", true);
		$validator->validate_arg($endtime, "numeric", true);
		$validator->validate_arg($limit, "numeric", true);
		
		$logger->debug("newfiles($wiki, $uploader, $starttime, $endtime, $limit)");
		
		$params = ["aiprop" => "user|timestamp|url", "aisort" => "timestamp", "aidir" => "older",
				"aiuser" => $uploader];
		
		if ($starttime !== null) {
			$params["aistart"] = $starttime;
		}
		
		if ($endtime !== null) {
			$params["aiend"] = $endtime;
		}
		
		$limit_callback = $limit !== null ? function (&$next_query, $all_data) use ($limit) {
			return count($all_data) < $limit;
		} : null;
		
		$results = $this->simple_query($wiki, "allimages", $params, $redirects, $limit_callback);
		
		if ($limit !== null) {
			$results = array_slice($results, 0, $limit);
		}
		
		$logger->debug("\$results = array[" . count($results) . "]");
		
		return array_reverse(array_keys($results));
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param number $starttime        	
	 * @param number $endtime        	
	 * @param string[] $files        	
	 * @param array $dates        	
	 */
	function get_recent_uploads(Wiki &$wiki, $starttime, $endtime, &$files, &$dates) {
		global $logger, $validator;
		
		$files = [];
		$dates = [];
		
		$rccontinue = null;
		do {
			$logger->debug("...$rccontinue");
			$params = ['action' => 'query', 'list' => 'recentchanges', 
				'rcprop' => 'title|loginfo|timestamp', 'rcnamespace' => '6', 'rctype' => 'log', 
				'rclimit' => 'max', 'rcstart' => $starttime, 'rcend' => $endtime];
			
			if ($rccontinue != null) {
				$params['rccontinue'] = $rccontinue;
			}
			
			$query = $this->api_query($wiki, $params);
			
			/* check for errors */
			if ($query['error'] != NULL) {
				$files = ['error', $query['error']];
				break;
			}
			if (!array_key_exists('query', $query) ||
				 !array_key_exists('recentchanges', $query['query'])) {
				$files = ['error', "unknown query error"];
				break;
			}
			
			/* append files */
			$new_files = $query['query']['recentchanges'];
			foreach ($new_files as $filename) {
				if ($filename['logaction'] == 'upload') {
					$title = $filename['title'];
					if (@$dates[$title] === null) {
						/*
						 * duplicate; in case of upload, deletion, reupload:
						 * ignore the earliest one
						 */
						$files[] = $title;
						$dates[$title] = $filename['timestamp'];
					}
				}
			}
			
			/* continue */
			if (array_key_exists('query-continue', $query)) {
				$continue = $query['query-continue']['recentchanges'];
				if (@$continue['rcstart'] !== null) {
					$rccontinue = preg_replace(
						"/^(\d{4})\-(\d{2})\-(\d{2})T(\d{2})\:(\d{2})" . "\:(\d{2})Z$/", 
						"$1$2$3$4$5$6", 
						$continue['rcstart']);
				} else {
					$rccontinue = preg_replace(
						"/^(\d{4})\-(\d{2})\-(\d{2})T(\d{2})\:(\d{2})" . "\:(\d{2})Z\|\d+$/", 
						"$1$2$3$4$5$6", 
						$continue['rccontinue']);
				}
			} else {
				$rccontinue = NULL;
			}
		} while ($rccontinue);
		$validator->assert(count($files) === count($dates));
		
		/* reverse array (oldest first) */
		$dates = array_reverse($dates);
	}
	
	/**
	 *
	 * @param Wiki $wiki        	
	 * @param string[] $page_titles
	 * @param string|null $restrict_to_namespaces DEFAULT null
	 * @return string[]
	 */
	function get_all_redirects(Wiki $wiki, $page_titles, $restrict_to_namespace = null) {
		global $logger;
		
		//this wasn't added until at least version 1.20
		if (!$wiki->supports_version("1.20")) {
			$logger->warn("Can't load redirects version not supported.");
			return [];
		}
		
		$params = array("prop" => "redirects", "rdlimit" => "max");
		if ($restrict_to_namespace !== null) {
			$params['rdnamespace'] = $restrict_to_namespace;
		}
		
		$all_redirects = $this->query_generic($wiki, "titles", $page_titles, $params);
		
		return array_map_filter($all_redirects, function($to_element) {
			$redirects = @$to_element['redirects'];
			return $redirects ? map_array($redirects, "title") : null;
		});
	}
	
	/**
	 *
	 * @param Image $img        	
	 * @param bool $omit_dupes   
	 * @param bool $header     	
	 * @return string
	 */
	public function file_history_to_text(Image $img, $omit_dupes, $header) {
		$instances = Upload_History_Instance::read_from_wiki_image_info(
			$this->get_upload_history($img));
		$base_url = $img->get_page()->get_wiki()->get_base_url();
		preg_match("/^https?:\/\/([a-z\-]+\.wik[a-z\-]+)\.org\/.+$/", $base_url, $proj_array);
		if (!$proj_array) {
			throw new UnexpectedValueException("Illegal wiki name: $base_url");
		}
		return (new Legacy_CommonsHelper_History_Text_Writer())->write($proj_array[1], 
			$img->get_title(false), $instances, $omit_dupes, $header);
	}
	
	/**
	 * Gets user rights; avoid unnecessary calls by storing in cache file; updates if >
	 * one week since last check
	 * 
	 * @param Wiki $wiki        	
	 * @param string $usernames     	
	 * @return bool[]
	 */
	public function is_sysop(Wiki $wiki, $usernames) {
		global $logger;
		
		$filename = BASE_DIRECTORY . DIRECTORY_SEPARATOR . self::EN_WIKIPEDIA_SYSOP_CACHE;
		$file_contents = file_get_contents($filename);
		if ($file_contents === null) {
			$logger->info("Recreating userrights.");
			$file_contents = "";
		}
		preg_match_all("/^(.*) (\d) (\d+)$/mu", $file_contents, $matches, PREG_SET_ORDER);
		
		$now = time();
		$cached_rights = map_array_function_keys($matches, function ($match) use ($now) {
			$boolflag = $match[2] ? true : false;
			$timestamp = intval($match[3]);
			if ($now - $timestamp < SECONDS_PER_DAY * 7) {
				return [$match[1], [$boolflag, $timestamp]];
			}
		});
		$usernames_key_array = array_fill_keys($usernames, null);
		$usernames_array = array_intersect_key($usernames_key_array, $cached_rights);
		
		$uncached_usernames = array_keys(array_diff($usernames_key_array, $usernames_array));
		
		/* not in cache */
		$logger->debug(" Querying the following users: ");
		$logger->debug($uncached_usernames);
		$rights = $this->query_generic ( $wiki, 'ususers', $uncached_usernames, [ 
				'usprop' => 'groups']);
		$usernames_array = array_merge($cached_rights, array_map(function ($right) use ($now) {
				return [in_array("sysop", $right['groups']), $now];
			}, $rights));
		
		$new_file_content = implode("", array_map_pass_key($usernames_array, function($user, $data) {
			return "$user ". ($data[0] ? 1 : 0) . " $data[1]\n"; 
		}));
		file_put_contents($filename, $new_file_content, FILE_APPEND);
		
		return map_array($usernames_array, 0);
	}
	
	/**
	 * FIXME: Can't handle video formats properly
	 * 
	 * @param array $image_ver_instance        	
	 * @param int $width        	
	 * @return NULL|string
	 */
	function get_thumb($image_ver_instance, $width) {
		global $validator;
		
		if (preg_match(
			"/^https?:\/\/(?:wts|www|en|fr|nl|ru|sv)\.wikivoyage(?:\-old)\.org?\//", 
			$image_ver_instance['url'])) {
			// wikivoyage doesn't support thumbnail sizes on demand
			$width = 120;
		}
		
		if (!array_key_exists('url', $image_ver_instance)) {
			$validator->validate_args_condition(
				$image_ver_instance, 
				"array with argument filehidden", 
				array_key_exists('filehidden', $image_ver_instance));
			return NULL;
		}
		
		if (preg_match("/\.(midi?|ogg)$/i", $image_ver_instance['url'])) {
			$thumb_url = "images/fileicon-mid.png";
		} else {
			if ($image_ver_instance["height"] == 0 || $image_ver_instance["width"] == 0) {
				return NULL;
			}
			
			$aspect_ratio = $image_ver_instance["height"] / $image_ver_instance["width"];
			$width = ($aspect_ratio < 1) ? $width : intval(round($width / $aspect_ratio));
			if (preg_match(
				"/^(https?:\/\/(?:upload\.wikimedia|(?:wts|en|fr|nl|ru|sv).wikivoyage\-old|www\.wikivoyage" .
					 "\-old)\.org\/[a-z]+(?:\/shared)?\/[a-z\-]+\/)archive\/([\da-f]+\/[\da-f]+\/)(?:(\d+%21)(.+))?$/s", 
					$image_ver_instance['url'], $match)) {
				if (!isset($match[3]) && !isset($match[4])) {
					return null;
				}
				$URL_parsed = [$match[1], "archive/", $match[2], $match[3], $match[4]];
			} else if (preg_match(
				"/^(https?:\/\/(?:upload\.wikimedia|(?:wts|en|fr|nl|ru|sv).wikivoyage\-old|www\.wikivoyage" .
					 "\-old)\.org\/[a-z]+(?:\/shared)?\/[a-z\-]+\/)([\da-f]+\/[\da-f]+\/)(.+)$/s", 
					$image_ver_instance['url'], $match)) {
				$URL_parsed = [$match[1], "", $match[2], "", $match[3]];
			} else {
				$validator->assert(false, 
					"Unexpected characters: unable to parse URL: " . $image_ver_instance['url']);
			}
			
			if (preg_match("/\.pdf$/i", $image_ver_instance['url'])) {
				/* PDFs will have previews regardless of original resolution */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/page1-$width" .
					 "px-$URL_parsed[4].jpg";
			} else if (preg_match("/\.webm$/i", $image_ver_instance['url'])) {
				/* WEBMs will have previews regardless of original resolution */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/$width" .
					 "px--$URL_parsed[4].jpg";
			} else if (preg_match("/\.(webp|svg|xcf)$/i", $image_ver_instance['url'])) {
				/* SVGs will have previews regardless of original resolution */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/$width" .
					 "px-$URL_parsed[4].png";
			} else if (preg_match("/\.tiff?$/i", $image_ver_instance['url'])) {
				/* TIFFs will have previews regardless of original resolution */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/lossy-page1-$width" .
					 "px-$URL_parsed[4].jpg";
			} else if (preg_match("/\.ogv$/i", $image_ver_instance['url'])) {
				/* OGVs do not have previews at pixelage */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/${width}px-$URL_parsed[4].jpg";
			} else if (preg_match("/\.djvu$/i", $image_ver_instance['url'])) {
				/* DJVUs will have previews regardless of original resolution */
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/page1-$width" .
					 "px-$URL_parsed[4].jpg";
			} else if ($width < $image_ver_instance["width"]) {
				$thumb_url = "$URL_parsed[0]thumb/$URL_parsed[1]$URL_parsed[2]$URL_parsed[3]$URL_parsed[4]/$width" .
					 "px-$URL_parsed[4]";
			} else {
				/* non-SVG preview too big: just use original image */
				$thumb_url = $image_ver_instance['url'];
			}
		}
		return $thumb_url;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param array $original_query_props
	 * @param array $api_data
	 * @return void
	 */
	public static function api_data_warning_check(Wiki $wiki, array $original_query_props, 
		array $api_data) {
		global $logger;
		
		// keep a list of warnings already outputted, so as not to display them multiple times
		static $logged_warnings = [];
		$warnings = @$api_data["warnings"];
		if ($warnings) {
			$original_query = $wiki->get_base_url() . "?" . query_to_string($original_query_props);
			$stacktrace_log_args = $logger->isTraceEnabled();
			if (is_string($warnings)) {
				$warnings = ["" => $warnings];
			}
			foreach ($warnings as $type => $warning) {
				$backtrace = get_backtrace($stacktrace_log_args, 4);
				$warning_and_stacktrace = self::api_warning_to_string($type, $warning) .
					 "\n" . indent(get_backtrace_string($backtrace), 4);
				if (!isset($logged_warnings[$warning_and_stacktrace])) {
					$logged_warnings[$warning_and_stacktrace] = true;
					$logger->warn(
						"API query $original_query returned the" .
							 " following warning:\n$warning_and_stacktrace");
				}
			}
		}
	}
	

	/**
	 *
	 * @param string|string[]|string[][] $issues
	 * @return string[] the error strings
	 */
	private static function api_warning_to_string($type, $issue) {
		global $logger;
		
		if (is_array($issue) && isset($issue["*"])) {
			$issue_string = $issue["*"];
		} else {
			$issue_string = print_r($issue, true);
			$logger->error("Unexpected \$issue value: $issue_string");				
		}
		
		return "$type: $issue_string";
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string[] $titles
	 * @return string[] The key is the name of the page shadowing, and the value is true
	 */
	public function get_shadows(Wiki $wiki, $titles) {
		$shadows_query = $this->new_query_pages($wiki, $titles, 'imageinfo', 
			array("iilocalonly" => ""));
		$shadows = array_filter($shadows_query, 
			function ($query) {
				if (@$query['imagerepository'] === "local") {
					return true;
				}
			});
		
		return $shadows;
	}
	
	public function get_namespace_data(Wiki $wiki) {
		return $this->simple_query($wiki, "namespaces", ["siprop"=>"namespaces"]);
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_live_edits() {
		return $this->live_edits;
	}
	
	/**
	 *
	 * @param bool $live_edits        	
	 * @return void
	 */
	public function set_live_edits($live_edits) {
		$this->live_edits = $live_edits;
	}
	

	/**
	 * 
	 * @param string $type
	 * @param mixed $data
	 * @return void
	 */
	private function run_hooks($type, &$data) {
		if (isset($this->hooks[$type])) {
			foreach ($this->hooks[$type] as $hook) {
				call_user_func_array($hook, [&$data]);
			}
		}
	}
	
	/**
	 * 
	 * @param string $type
	 * @param callable $hook
	 * @return void
	 */
	public function add_hook($type, $hook) {
		if (!isset($this->hooks[$type])) {
			$this->hooks[$type] = [];
		}
		$this->hooks[$type][] = $hook;
	}
	
	/**
	 * 
	 * @param string $type
	 * @param callable $hook
	 * @return boolean
	 */
	public function remove_hook($type, $hook) {
		if (!isset($this->hooks[$type])) {
			return false;
		}
		
		$index = array_search($hook, $this->hooks[$type]);
		if ($index !== false) {
			unset($this->hooks[$type][$index]);
		}
		return false;
	}
	
	
	/**
	 * 
	 * @param string[] $titles
	 * @param string[] $params
	 * @return Get_Wikidata_Response
	 */
	public function get_wikidata_data($titles, $params) {
		
		static $wikidata_wiki = null;
		if ($wikidata_wiki === null) {
			$wikidata_wiki = $this->new_wiki("OgreBot.wikidata");
		}
		$response = new Get_Wikidata_Response();
		
		$response->data = $this->query_generic($wikidata_wiki, "wbgetentities", $titles, $params, 
				$redirects, $response->missing);
		
		return $response;
	}
	
	/**
	 * 
	 * @param string $interwiki
	 * @param string $longname
	 * @param string[] $titles
	 * @return Geo_Coordinates_Response
	 */
	public function get_wikidata_coords($interwiki, $longname, $titles) {
		
		$response = new Geo_Coordinates_Response();
		
		$wikidata = $this->get_wikidata_data($titles, ["sites" => $longname]);
		$response->pages_not_found = $wikidata->missing;
		$response->coords_not_found = [];
		$response->geo_coordinates = map_array_function_keys($wikidata->data, 
			function (array $datum) use($interwiki, $response) {
			
				$title = array_key_or_exception($datum, "labels", $interwiki, "value");
				$coord = array_key_or_null($datum, "claims", "P625", 0, "mainsnak", 
						"datavalue", "value");
				
				if ($coord !== null) {
					$geo_coords = new Geo_Coordinates();
					$geo_coords->latitude = $coord["latitude"];
					$geo_coords->longitude = $coord["longitude"];
					
					return [$title, $geo_coords];
				}

				$response->coords_not_found[] = $title;
			}, "FAIL");
		
		return $response;
	}
}