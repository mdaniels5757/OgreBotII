<?php

abstract class Now_Commons_List {
	
	const COMMONS_PROJECT_KEY = "commons.wikimedia";
	const GALLERY = "/html/gallery.php";
	const INDEX = "/html/index.php";
	const MESSAGES_DIRECTORY = "nowcommonslist-messages";
	const PROJECTS_DIRECTORY = "/projects";
	const TEMPLATE_DIRECTORY = "/html/template/";
	const TEMPLATE_PREFIX = "Template:";
	const GALLERY_SIZE = 500;
	const THUMB_SIZE = 200;
	const PREVIEW_SIZE = 190;
	
	private static $license_types = ["attribution", "keeplocal", "other", "public_domain", 
		"sharealike", "problematic"];
	
	/**
	 * 
	 * @var Now_Commons_List[]
	 */
	private static $instances;
	
	/**
	 * 
	 * @var string
	 */
	protected $override_output_path = null;

	/**
	 * 
	 * @var string[][]
	 */
	private $local_license_highlights;
	
	/**
	 *
	 * @var string[][]
	 */
	private $local_license_highlight_regexes;
	
	/**
	 * 
	 * @var string
	 */
	private $local_project_key;
	
	/**
	 * 
	 * @var string[][]
	 */
	private $commons_license_highlights;
	
	/**
	 *
	 * @var string[][]
	 */
	private $commons_license_highlight_regexes;
	
	/**
	 * 
	 * @var ProjectData
	 */
	private $local_project_data;

	/**
	 *
	 * @var ProjectData
	 */
	private $commons_project_data;
	
	/**
	 * 
	 * @var int
	 */
	private $start_time;
	
	/**
	 * 
	 * @var int
	 */
	private $license_cache_time = MINUTES_PER_DAY;
	
	/**
	 * 
	 * @var int|null
	 */
	private $limit;
	
	/**
	 * 
	 * @var int|null
	 */
	private $min_time_between_runs;
	
	/**
	 * 
	 * @var string[]
	 */
	private $prefixes_to_ignore;
	
	/**
	 *
	 * @return void
	 * @throws Now_Commons_List_Minimum_Age_Exception
	 */
	public final function generate() {
		global $constants, $logger;
		
		$diff = time() - filemtime($this->get_output_index_page_path());
		if ($diff < $this->min_time_between_runs) {
			throw new Now_Commons_List_Minimum_Age_Exception($this->min_time_between_runs, $diff);
		}
		
		$this->prefixes_to_ignore = array_key_or_empty($constants, 
			"image.replacement.global.delink.optoutprefix");
		
		
		$this->local_project_data = $this->get_project_data();
		//TODO remove $this->local_project_data->loadWiki();
		$this->commons_project_data = ProjectData::load(self::COMMONS_PROJECT_KEY);
		$this->local_project_key = $this->get_messages_key();
		$this->start_time = time();
		$logger->info("The time is " . date('Y-m-d H:i', $this->start_time));
		
		$now_commons_images_unchunked = $this->get_objects();
		$count = count($now_commons_images_unchunked);
		
		$galleries = array();
		
		for($i = 0; $i < $count; $i += self::GALLERY_SIZE) {
			$index = count($galleries) + 1;
			$range = ($i + 1) . "-" . min([$i + self::GALLERY_SIZE, $count]);
			
			$gallery_page_name = $this->get_output_gallery_page_path($index);
			$logger->debug("Gallery #$index: $gallery_page_name");
			$galleries[$range] = preg_replace("/^.+\//", "", $gallery_page_name);

			$now_commons_gallery = new Now_Commons_Gallery();
			$now_commons_gallery->local_project_data = $this->local_project_data;
			$now_commons_gallery->commons_project_data = $this->commons_project_data;
			$now_commons_gallery->start_time = $this->start_time;
			$now_commons_gallery->now_commons_images = array_slice($now_commons_images_unchunked, $i, 
				min([$count, $i + self::GALLERY_SIZE]) - $i);
			
			ob_start(function ($output) use ($gallery_page_name) {
				global $logger;
				
				$logger->debug("Writing to gallery to $gallery_page_name");
				$output = (new Http_Io(false))->quick_html_minify($output);
				file_put_contents_ensure($gallery_page_name, $output);
			});
			
			//wrap in functor to keep local variables untainted
			call_user_func(function() use ($now_commons_gallery) {
				require __DIR__ . self::GALLERY;
			});
			ob_end_flush();
		}
		
		//index page
		$project_key = $this->local_project_key;
		ob_start(function ($output) {
			global $logger;
		
			$index_page_name = $this->get_output_index_page_path();
			
			$logger->debug("Writing to gallery to $index_page_name");
			file_put_contents_ensure($index_page_name, $output);
		});
		//wrap in functor to keep local variables untainted
		$time = $this->start_time;
		call_user_func(function() use ($galleries, $project_key, $time) {
			require __DIR__ . self::INDEX;
		});
		ob_end_flush();
		
	}
	
	/**
	 * 
	 * @return Now_Commons_Image[]
	 */
	private function get_objects() {
		global $logger, $validator, $wiki_interface;
		
		$logger->debug("Now_Commons_List::get_objects()");
		
		$properties = load_property_file("nowcommonslist");
		
		$step = 0;
		
		$logger->debug("Step " . ++$step . ": startup.");
		
		$localized_messages = load_property_file(
			self::MESSAGES_DIRECTORY . "/$this->local_project_key");
		/**
		 * Local variables for the sake of brevity
		 */
		$l_project_data = $this->local_project_data;
		$c_project_data = $this->commons_project_data;
		$l_wiki = $l_project_data->getWiki();
		$c_wiki = $c_project_data->getWiki();
		
		
		$logger->debug("Step " . ++$step . ": Loading license cache ... ");
		$licenses_cache = $this->load_licenses();
		
		
		$logger->debug("Step " . ++$step . ": Querying marked images ... ");
		$image_names = array_unique($this->get_all_marked_images());
		$logger->info("Found " . count($image_names) . " files total.");
		
		
		if ($this->limit !== null && count($image_names) > $this->limit) {
			$logger->info("Pruning to $this->limit images.");
			$image_names = array_slice($image_names, 0, $this->limit);
		}
		
		$logger->debug("Step " . ++$step . ": Gathering local image information...");
		$local_query_data_unsort = $wiki_interface->new_query_pages($l_wiki, $image_names, 
			'imageinfo|revisions|templates');
		
		//resort by original order
		$local_query_data = array_sort_custom($local_query_data_unsort, $image_names);
		
		$images = [];
		$all_commons_listed_names = [];
		foreach ($local_query_data as $title => $data) {
			$count = count($images);
			if ($count % 100 === 0) {
				$logger->debug("Organizing: $count completed of " . count($local_query_data));
			}
			$image = new Now_Commons_Image();
			$image->local_title = $title;
			$image->local_url = $l_project_data->getRawLink($title);
			$image->local_edit_link = $l_project_data->getRawLink($title, "edit");
			$image->local_text = array_key_or_blank($data, "revisions", 0, "*");
			$image->local_formatted_text = $this->get_formatted_text(
				$image->local_text, $licenses_cache[$this->local_project_key], array());
			$image->local_exists = !isset($data['missing']);
			$image->local_fileinfo_link = replace_named_variables($properties['fileinfo.link'], 
				array("project" => $this->local_project_key, "file" => rawurlencode($title)));
			$image->local_now_commons_link = $l_project_data->getRawLink($title, 
				"edit&functionName=NowCommonsReviewOK");
				
			$image_info = array_key_or_empty($data, 'imageinfo');
			$image->local_uploaders = array_unique(map_array($image_info, 'user'));
			$image->local_upload_history = Upload_History_Instance::read_from_wiki_image_info(
				$image_info);
			
			$image_info_most_recent = @$image_info[0];
			if ($image_info_most_recent) {
				$image->local_size = $image_info_most_recent['size'];
				$image->local_width = $image_info_most_recent['width'];
				$image->local_height = $image_info_most_recent['height'];
				$image->local_hash = $image_info_most_recent['sha1'];
				$image->local_mime = $image_info_most_recent['mime'];
		
				if ($image->local_width && $image->local_height) {
					if ($image->local_height < $image->local_width) {
						$image->local_preview_width = self::PREVIEW_SIZE;
						$image->local_preview_height = max([intval($image->local_height /
								$image->local_width * self::PREVIEW_SIZE), 1]);
						$thumb_width = self::THUMB_SIZE;
					} else {
						$image->local_preview_height = self::PREVIEW_SIZE;
						$image->local_preview_width = max([intval($image->local_width
								/ $image->local_height * self::PREVIEW_SIZE), 1]);		
						$thumb_width = max([intval($image->local_width
								/ $image->local_height * self::THUMB_SIZE), 1]);
					}
				} else {
					$image->local_preview_width = self::PREVIEW_SIZE;
					$image->local_preview_height = self::PREVIEW_SIZE;		
					$thumb_width = self::THUMB_SIZE;
				}
				$image->local_thumb = $wiki_interface->get_thumb($image_info_most_recent, 
						$thumb_width);
			} else {
				$image->local_exists = false;
			}
		
			//commons listed name
			try {
				$listed_name = $this->get_listed_commons_image($title, $image->local_text);
			} catch (Now_Commons_Multiple_Found_Exception $e) {
				$image->errors[] = $e->get_output_message();
				$listed_name = $e->get_first();
			} catch (Now_Commons_Lookup_Exception $e) {
				$image->errors[] = $e->get_output_message();
				$listed_name = $image->local_title;
			}

			$all_commons_listed_names[] = $listed_name;
			$image->commons_listed_name = $listed_name;
			
			$images[$title] = $image;
		}

		$logger->debug("Step " . ++$step . ": Gathering local redirects ...");
		$local_redirects = $wiki_interface->get_all_redirects($l_wiki, $image_names, 6);
		foreach ($local_redirects as $title => $redirects) {
			$count = count($redirects);
			$images[$title]->warnings[] = "Local page has $count redirects";
		}
		
		
		$logger->debug("Step " . ++$step . ": Gathering local talk information...");
		$talk_names = preg_replace("/^[\s\S]+\:/", "File talk:", $image_names);
		$talk_query = $wiki_interface->new_query_pages($l_wiki, $talk_names, '');
		foreach ($talk_query as $title => $data) {
			/* @var $image Now_Commons_Image */
			$image = @$images["File:" . substr($title, 10)];
			if ($image !== null) {
				$image->local_talk_link = !isset($data['missing']) ? $title : null;
			} else {
				$logger->warn("Talk index not found: $title");
			}
		}
		
		$logger->debug("Step " . ++$step . ": Gathering Commons information for listed Commons images...");
		
		$commons_query_data = $wiki_interface->new_query_pages($c_wiki, $all_commons_listed_names, 
			'imageinfo|revisions', array(), $redirects);
		
		$non_identicals = array();
		foreach ($images as $image) {
			$redirect = @$redirects[$image->commons_listed_name];
			if ($redirect) {
				$image->commons_listed_name = $redirect;
				$image->warnings[] = "NowCommons points to a redirect on Commons.";
			}
			
			$commons_listed_query_result = @$commons_query_data[$image->commons_listed_name];
			
			if ($commons_listed_query_result !== null) {
				$sha = array_key_or_null($commons_listed_query_result, "imageinfo", 0, "sha1");
				if ($sha === $image->local_hash) {
					$image->commons_title = $image->commons_listed_name;
				} else {
					$non_identicals[] = $image;
				}
			} else {
				$logger->warn("Can't find commons listed image: $image->commons_listed_name");
			}
		}
		
		/* check that Commons version is an exact match; if it's not, then check for an exact match */
		$logger->info(
			"Step " . ++$step .
			": Searching for Commons equivalents for local images whose {{NowCommons}}" .
			" link doesn't show an exact duplicate... ");
		$extra_dupes = array();
		foreach ($non_identicals as $image) {
			$dupe_title = $wiki_interface->get_files_by_hash($c_wiki, $image->local_hash, true);
			if ($dupe_title !== null) {
				$image->commons_title = $dupe_title;
				$extra_dupes[] = $dupe_title;
			} else {
				$image->commons_title = $image->commons_listed_name;
			}
		}
		$logger->info("Step " . ++$step . ": querying said equivalents... ");
		
		$new_commons_query_data = $wiki_interface->new_query_pages($c_wiki, $extra_dupes, 
			'imageinfo|revisions');
		
		//flatten results back into original
		$commons_query_data = array_merge_recursive($commons_query_data, $new_commons_query_data);
		
		$delete_reason_same = array_key_or_exception($localized_messages, 'delete.same');
		$delete_reason_different = array_key_or_exception($localized_messages, 
			'delete.different');
		
		$move_talk_reason = urlencode(
			array_key_or_exception($localized_messages, "talk.move.reason"));
		$move_talk_params = array_key_or_exception($properties, "talk.move.url_params");
		$i = 0;
		foreach ($images as $title => $image) {
			if (++$i % 100 === 0) {
				$logger->debug("Organizing: $i completed of " . count($images));
			}
			if ($image->commons_title !== null) {				
				$commons_query_datum = _array_key_or_exception($commons_query_data, 
					[$image->commons_title]);
			} else {				
				$commons_query_datum = [];
				$image->commons_title = $image->commons_listed_name;
			}
				
			$same_name = $image->local_title === $image->commons_title;
			
			$image->commons_url = $c_project_data->getRawLink($image->commons_title);
			$image->commons_edit_link = $c_project_data->getRawLink($image->commons_title, "edit");
			$image->commons_text = array_key_or_blank($commons_query_datum, "revisions", 0, "*");
			$image->commons_formatted_text = $this->get_formatted_text($image->commons_text, 
				$licenses_cache[self::COMMONS_PROJECT_KEY], $image->local_uploaders);
			$image->commons_cleanup_link = replace_named_variables($properties['cleanup.link'], 
				array("image" => urlencode($image->commons_title)));
			
			if ($image->local_talk_link) {
				if ($same_name) {
					$image->local_view_talk_link = $l_project_data->getRawLink(
						$image->local_talk_link); 
				} else {
					$image->local_move_talk_link = $l_project_data->getRawLink("Special:MovePage", 
						replace_named_variables($move_talk_params, 
							array("oldTitle" => urlencode($image->local_talk_link), 
								"newTitle" => urlEncode($this->get_talk_link($image->commons_title)), 
								"reason" => $move_talk_reason)));
				}
			}

			
			$delete_reason = replace_named_variables(
				$same_name ? $delete_reason_same : $delete_reason_different, 
				array("new" => "[[$image->commons_title]]"));
			$delete_reason_full = urlencode($delete_reason);
			$image->local_delete_link = $l_project_data->getRawLink($image->local_title, 
				"delete&wpReason=$delete_reason_full&fromNowCommons=1");
			$image->local_delete_reason = $delete_reason;
		
			$image_info = array_key_or_empty($commons_query_datum, 'imageinfo');
			$image->commons_upload_history = Upload_History_Instance::read_from_wiki_image_info(
				$image_info);
				
			$image_info_most_recent = @$image_info[0];
			if ($image_info_most_recent) {
				$image->commons_exists = true;
				$image->commons_size = $image_info_most_recent['size'];
				$image->commons_width = $image_info_most_recent['width'];
				$image->commons_height = $image_info_most_recent['height'];
				$image->commons_hash = $image_info_most_recent['sha1'];
				$image->commons_mime = $image_info_most_recent['mime'];
				
				if ($image->commons_mime && $image->commons_mime !== $image->local_mime) {
					$image->errors[] = "Different MIMEs";
				}
			
				if ($image->commons_width && $image->commons_height) {
					if ($image->commons_height < $image->commons_width) {
						$image->commons_preview_width = self::PREVIEW_SIZE;
						$image->commons_preview_height = max([intval($image->commons_height /
								$image->commons_width * self::PREVIEW_SIZE), 1]);
						$thumb_width = self::THUMB_SIZE;
					} else {
						$image->commons_preview_height = self::PREVIEW_SIZE;
						$image->commons_preview_width = max([intval($image->commons_width
								/ $image->commons_height * self::PREVIEW_SIZE), 1]);
						$thumb_width = max([intval($image->commons_width
								/ $image->commons_height * self::THUMB_SIZE), 1]);
					}
				} else {
					$image->commons_preview_width = self::PREVIEW_SIZE;
					$image->commons_preview_height = self::PREVIEW_SIZE;
					$thumb_width = self::THUMB_SIZE;
				}
				$image->commons_thumb = $wiki_interface->get_thumb($image_info_most_recent,
						$thumb_width);
			} else {
				$image->commons_exists = false;
				$image->errors[] = "File does not exist on Commons";
			}
			
			foreach ($image->local_upload_history as $local_upload_history) {
				foreach ($image->commons_upload_history as $commons_upload_history) {
					if ($commons_upload_history->hash === $local_upload_history->hash) {
						continue 2;
					}
				}
				// hash not found, needs transfer
				$image->old_versions_link = replace_named_variables($properties['oldversion.link'], 
					["project" => $this->local_project_key, 
						"src" => rawurlencode($image->local_title), 
						"trg" => rawurlencode($image->commons_title)]);
				break;
			}
				
		}
		
		$different_name_images = array_filter($images, 
			function ($image) {
				return $image->local_title !== $image->commons_title;
			});
		$logger->info(
			"Step " . ++$step . ": Querying links for pages with a different name on Commons...  ");
		$i = 0;
		if ($this->is_get_local_links()) {
			$logger->info("Step " . ++$step . ": Querying local links");
			foreach ($different_name_images as $title => $image) {
				$logger->info(" (" . ++$i . " of " . count ($different_name_images) . " total: $title)" );
				$image->local_links = array_values(array_filter(
					$wiki_interface->get_usage($l_wiki, $title, USAGE_PAGES_THROUGH_REDIRECTS), 
					function ($link) {
						foreach ($this->prefixes_to_ignore as $prefix_to_ignore) {
							if (str_starts_with($link, $prefix_to_ignore)) {
								return false;
							}
						}
						return true;
					}));
			}
		} else {
			$logger->info("Step " . ++$step . ": Querying local links SKIPPED");
			foreach ($different_name_images as $title => $image) {
				$image->local_links = [];
			}
		}

		if ($i === 0) {
			/* all image equivalents were the same name */
			$logger->debug("SKIPPED (unnecessary)...");
		}
		
		$different_name_images_title = array_map(
			function ($image) {
				return $image->commons_title;
			}, $different_name_images);
		
		if ($this->is_get_local_links()) {
			$logger->info("Step " . ++$step . ": Querying shadows ...  ");
			$shadows = $wiki_interface->get_shadows($l_wiki, $different_name_images_title);
		} else {
			$logger->info("Step " . ++$step . ": Querying shadows SKIPPED ");
			$shadows = [];
		}
		
		$logger->info(count($shadows) . " shadows found.");
		if ($shadows) {
			foreach ($images as $image) {
				if ($image->local_title !== $image->commons_title &&
					 isset($shadows[$image->commons_title])) {
					$image->errors[] = "A local image shadows Commons.";
				}
			}
		}
		
		
		return $images;
	}
	
	/**
	 * 
	 * @param string $text
	 * @param License_Cache $license_cache
	 * @param string[] $uploaders
	 * @return string
	 */
	private function get_formatted_text($text, License_Cache $license_cache, $uploaders) {
		$regions = array();
		try {
			$template_iterator = new TemplateIterator($text);
		} catch (TemplateParseException $e) {
			return sanitize($text);
		}
		
		foreach ($template_iterator as $template) {
			$name = TemplateUtils::normalize($template->getname());
			if ($name === "Self") {
				$name = TemplateUtils::normalize($template->fieldvalue(1));
			}
			foreach ($license_cache->license_regexes as $type => $license_regex) {
				if (preg_match($license_regex, $name)) {
					$start = strlen($template->__get("before"));
					$end = $start + strlen($template->__toString());
					@$regions[] = array($type, $start, $end);
					break;
				}
			}
		}
	
		$uploaders_quote = array();
		foreach ($uploaders as $uploader) {
			$uploaders_quote[] = preg_quote($uploader, "/");
		}
		$uploaders_regex = "/(?:" . implode("|", $uploaders_quote) . ")/ui";
		
		$previous = 0;
		$formatted_text = "";
		
		foreach ($regions as $region) {
			list($type, $start, $end) = $region;
			
			//search for uploader name
			$substring = substr($text, $previous, $start - $previous);
			
			$previous_local = 0;
			if ($uploaders) {
				preg_match_all($uploaders_regex, $substring, $matches, 
					PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
				if ($matches) {
					foreach ($matches[0] as $match) {
						$len = strlen($match[0]);
						$start_local = $match[1];
						$formatted_text .= sanitize(substr($substring, $previous_local, 
							$start_local - $previous_local));
						$formatted_text .= $this->replace_text_by_type(
							substr($substring, $start_local, $len), "uploader");
						$previous_local = $start_local + $len;
					}
				}
			}
			
			$formatted_text .= sanitize(substr($substring, $previous_local, $start - $previous_local));
			$formatted_text .= $this->replace_text_by_type(substr($text, $start, $end - $start), 
				$type);
			$previous = $end;
		}
		
		$substring = substr($text, $previous);

		$previous_local = 0;
		if ($uploaders) {
			preg_match_all($uploaders_regex, $substring, $matches, 
				PREG_OFFSET_CAPTURE);
			if ($matches) {
				foreach ($matches[0] as $match) {
					$len = strlen($match[0]);
					$start_local = $match[1];
				
					$formatted_text .= sanitize(
						substr($substring, $previous_local, $start_local - $previous_local));
					$formatted_text .= $this->replace_text_by_type(substr($substring, $start_local, $len),
						"uploader");
					$previous_local = $start_local + $len;
				}
			}
		}
		$formatted_text .= sanitize(substr($substring, $previous_local));
		
		return $formatted_text;
	}
	
	/**
	 * 
	 * @param string $text
	 * @param string $type
	 * @return string
	 */
	private function replace_text_by_type($text, $type) {
		$sanitized_text = sanitize($text);
		return "<mark class=\"mark-$type\">$sanitized_text</mark>";
	}
	
	/**
	 * 
	 * @return int
	 */
	public function get_license_cache_time() {
		return $this->license_cache_time;
	}
	
	/**
	 * 
	 * @param int $license_cache_time
	 * @return void
	 */
	public function set_license_cache_time($license_cache_time) {
		$this->license_cache_time = $license_cache_time;
	}
	
	/**
	 * 
	 * @return int|null
	 */
	public function get_limit() {
		return $this->limit;
	}
	
	/**
	 * 
	 * @param int|null $limit
	 * @return void
	 */
	public function set_limit($limit) {
		global $validator;
		
		$validator->validate_arg($limit, "numeric");
		$this->limit = $limit;
	}
	
	/**
	 * 
	 * @return License_Cache[]
	 */
	private function load_licenses() {
		global $logger;
			
		/* @var $cache Multitype::License_Cache[] */
		if (is_file(NOW_COMMONS_LIST_LICENSE_CACHE_FILE)) {
			$cache_string = file_get_contents(NOW_COMMONS_LIST_LICENSE_CACHE_FILE);
			$cache = unserialize($cache_string);
			if ($cache === false) {
				$cache = array();
			}
		} else {
			$cache = array();
		}
		
		$local_cache = @$cache[$this->local_project_key];
		$commons_cache = @$cache[self::COMMONS_PROJECT_KEY];
		
		$refresh_cache = false;
		if (!$local_cache || $local_cache->load_time < $this->start_time - $this->license_cache_time) {
			$cache[$this->local_project_key] = $this->load_project_licenses(
				$this->local_project_data->getWiki(), $this->local_project_key);
			$refresh_cache = true;
		} else {
			$logger->debug("License cache for local templates loaded.");
		}
		
		if (!$commons_cache || $commons_cache->load_time < $this->start_time - $this->license_cache_time) {
			$cache[self::COMMONS_PROJECT_KEY] = $this->load_project_licenses(
				$this->commons_project_data->getWiki(), self::COMMONS_PROJECT_KEY);
			$refresh_cache = true;
		} else {
			$logger->debug("License cache for Commons templates loaded.");
		}
		
		if ($refresh_cache) {
			$cache_string = serialize($cache);
			file_put_contents_ensure(NOW_COMMONS_LIST_LICENSE_CACHE_FILE, $cache_string);
		} else {
			//keep cache from being cleaned out of the temp folder
			touch(NOW_COMMONS_LIST_LICENSE_CACHE_FILE);
		}
		
		return $cache;
	}
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @param string $project_key
	 * @return License_Cache
	 */
	private function load_project_licenses(Wiki $wiki, $project_key) {
		global $logger, $wiki_interface;
		
		$logger->info("Loading licenses $project_key.");

		static $licenses = null;
		
		if ($licenses === null) {
			load_property_file_into_variable($licenses, "nowcommonslist");
		}
		
		$license_cache = new License_Cache();
		$license_cache->load_time = $this->start_time;
		
		foreach (self::$license_types as $license_type) {
			$exact_licenses_local = array_key_or_empty($licenses, 
				"license.$license_type.$project_key");
			$exact_licenses_global = array_key_or_empty($licenses, "license.$license_type.global");
			$exact_licenses_all = array_merge($exact_licenses_local, $exact_licenses_global);
			
			$regex_local = array_key_or_empty($licenses, "license.$license_type.regex.$project_key");
			$regex_global = array_key_or_empty($licenses, "license.$license_type.regex.global");
			$regex_all = array_merge($regex_local, $regex_global);
			
			$license_regexes = $regex_all;
			
			$exact_licenses_with_prefix = preg_replace_callback("/^./", 
				function ($match) {
					return "Template:" . mb_strtoupper($match[0]);
				}, $exact_licenses_all);
			
			$redirects_by_name = $wiki_interface->get_all_redirects($wiki, 
				$exact_licenses_with_prefix, 10);
			
			foreach ($exact_licenses_with_prefix as $name_prefix) {
				$redirects_prefix = array_key_or_empty($redirects_by_name, $name_prefix);
				$this->append_template_regexes($license_regexes, [$name_prefix]);
				$this->append_template_regexes($license_regexes, $redirects_prefix);
			}

			if ($license_regexes) {
				$license_cache->license_regexes[$license_type] = "/^(?:" .
					 implode("|", $license_regexes) . ")$/u";
			}
		}
		
		return $license_cache;
	}
	
	/**
	 * 
	 * @param string $link
	 * @return string
	 */
	private function get_talk_link($link) {
		return "File talk:" . substr($link, 5);
	}
	
	/**
	 * 
	 * @param string[] $append_array
	 * @param string[] $redirects
	 */
	private function append_template_regexes(&$append_array, $redirects) {
		$template_len = strlen(self::TEMPLATE_PREFIX);
		foreach ($redirects as $i => $redirect) {
			if (str_starts_with($redirect, self::TEMPLATE_PREFIX)) {
				$append_array[] = regexify_template(mb_substr($redirect, $template_len));
			}
		}
	}
	
	/**
	 * 
	 * @param unknown $key
	 * @return Now_Commons_List
	 * @throws ArrayIndexNotFoundException if the instance is not found
	 */
	public static function get_instance_by_key($key) {
		self::init_instances();
		return array_key_or_exception(self::$instances, $key);
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public static function get_instance_keys() {
		self::init_instances();
		return array_keys(self::$instances);
	}
	
	
	/**
	 * @return void
	 */
	private static function init_instances() {
		if (self::$instances === null) {
			$instances = Classloader::get_all_instances_of_type(self::class);
			self::$instances = map_array_function_keys($instances, function ($instance) {
				return [$instance->get_messages_key(), $instance];
			});
		}
	}
	
	/**
	 * @return ProjectData
	 */
	abstract protected function get_project_data();
	
	/**
	 *
	 * @abstract
	 * @return string
	 */
	abstract protected function get_messages_key();
	
	/**
	 * Return a list of strings for all images currently marked as
	 * NowCommons
	 * 
	 * @abstract
	 * @return string[]
	 */
	abstract protected function get_all_marked_images();
	

	/**
	 * Return a list of strings for all images currently marked as
	 * NowCommons.
	 * 
	 * Override as needed.
	 *
	 * @param string $local_title
	 * @param string $local_text
	 * @return string[]
	 * @throws Now_Commons_Lookup_Exception
	 */
	protected function get_listed_commons_image($local_title, $local_text) {
		try {
			$parser = new Page_Parser($local_text);
		} catch (XmlError $e) {
			Environment::get()->get_logger()->error($e);
			throw new Now_Commons_Not_Found_Exception();
		}
		$listed_image = get_listed_commons_image($parser->get_text(), $local_title, $error_flag);
		
		$parser->set_text($listed_image);
		$parser->unparse();
		
		if ($error_flag & (COMMONS_LISTED_ZERO | COMMONS_LISTED_ILLEGAL_CHAR)) {
			throw new Now_Commons_Not_Found_Exception();
		}
		
		if ($error_flag & COMMONS_LISTED_MULTIPLE) {
			throw new Now_Commons_Multiple_Found_Exception($listed_image);
		}
		
		return $parser->get_text();
	}
	
	/**
	 * Override as needed
	 * @return bool
	 */
	protected function is_get_local_links() {
		return true;
	}
	
	/**
	 * Override as needed
	 * @return string
	 */
	public function get_base_output_path() {
		return $this->override_output_path ? $this->override_output_path : (BASE_DIRECTORY .
			 "/public_html/commons_images_$this->local_project_key");
	}
	
	/**
	 * 
	 * @return string
	 */
	private function get_output_index_page_path() {
		return $this->get_base_output_path() . ".htm";
	}
	
	/**
	 * 
	 * @return string
	 */
	private function get_output_gallery_page_path($count) {
		return $this->get_base_output_path() . "-$count.htm";
	}
	
	/**
	 *
	 * @return int|null
	 */
	public function get_min_time_between_runs() {
		return $this->min_time_between_runs;
	}
	
	/**
	 *
	 * @param int|null $min_time_between_runs
	 * @return void
	 */
	public function set_min_time_between_runs($min_time_between_runs) {
		$this->min_time_between_runs = $min_time_between_runs;
	}
	
	/**
	 * 
	 * @return number
	 */
	public function get_start_time() {
		return $this->start_time;
	}
}

Classloader::include_directory(__DIR__ . Now_Commons_List::TEMPLATE_DIRECTORY);
Classloader::include_directory(__DIR__ . Now_Commons_List::PROJECTS_DIRECTORY);