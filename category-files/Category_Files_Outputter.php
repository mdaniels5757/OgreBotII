<?php
class Category_Files_Outputter {
	/**
	 * 
	 * @var string
	 */
	private static $file_output_path;
	
	/**
	 * 
	 * @var int
	 */
	private static $gallery_max;
	
	/**
	 * 
	 * @var int
	 */
	private static $overflow_max;
	
	/**
	 * 
	 * @var int
	 */
	private static $overflow_max_files;
	
	/**
	 * 
	 * @var string
	 */
	private static $log_link;
	
	/**
	 * 
	 * @var int
	 */
	private static $page_max;
	
	/**
	 * 
	 * @var CategoryFilesSingleConfiguration[]
	 */
	private $configurations;
	
	/**
	 * 
	 * @var Project_Data
	 */
	private $project_data;
	
	/**
	 * 
	 * @var CategoryFilesRunner
	 */
	private $runner;
	
	/**
	 * @return void
	 */
	private static function _autoload() {
		global $validator;
		
		list($file_output_path, self::$gallery_max, self::$overflow_max,
			self::$page_max, self::$log_link) = Environment::props(	"constants", 
			['category_files.output_path', 'category_files.limit.gallery', 
				"category_files.limit.overflow", 'category_files.limit.page', 'category_files.log.url']);
			
		self::$file_output_path = BASE_DIRECTORY . "/" . $file_output_path;
		self::$overflow_max_files = self::$page_max * self::$overflow_max;
		
		$validator->validate_args("positive", self::$gallery_max, self::$page_max, 
			self::$overflow_max, self::$overflow_max_files);
	}
	
	/**
	 * 
	 * @param Project_Data $project_data
	 * @param string|null $file_override
	 */
	public function __construct(Project_Data $project_data, $file_override = null) {
		$this->project_data = $project_data;
		$this->runner = new CategoryFilesRunner($project_data->getWiki());
		
		if (@$file_override) {
			$this->configurations = CategoryFilesSingleConfiguration::initCategoryFilesConfigurationsFromLocalFile(
				$file_override);
		} else {
			$this->configurations = CategoryFilesSingleConfiguration::initCategoryFilesConfigurationsFromProperties(
				$project_data->getWiki(), Environment::prop('constants', 'category_files.gallery_definition'));
		}
	}
	
	/**
	 * 
	 * @param int $start
	 * @param int $end
	 * @return void
	 */
	public function run($start = null, $end = null) {
		global $constants, $logger, $validator, $wiki_interface;
		
		if ($start === null) {
			$start = date('Ymd000000', time() - SECONDS_PER_DAY);
		}
		if ($end === null) {
			$end = date('Ymd235959', strtotime($start));
		}
		
		preg_match("/^(\d{8})/", $start, $start_time_match);
		$start_date_only = array_key_or_exception($start_time_match, 1);

		preg_match("/^(\d{4})(\d{2})(\d{2})\d{6}$/", $end, $datematcher);
		$validator->assert(is_array($datematcher) && count($datematcher) == 4);
		$dateformat = "== {{date|$datematcher[1]|$datematcher[2]|$datematcher[3]}} ==\n";
		$logger->debug("\$dateformat: $dateformat");
				
		$existing_texts = $wiki_interface->get_text($this->project_data->getWiki(), 
			array_map(
				function (CategoryFilesSingleConfiguration $config) use ($end) {
					return $config->get_subpage_for_date($end);
				}, $this->configurations));
		
		$categoryTree = $this->runner->runFromFileUploadDates($start, $end);
		// $categoryTree = $configRunner->runFromPageNames(array("File:Cargolux B748F LX-VCG.jpg"));
		
		$logger->info("Organizing");
		$filelists = [];
		$configs = [];
		$i = 0;
		
		if ($wiki_interface->get_live_edits() || Environment::prop('environment', 'environment') !== 'prod') {
			$category_tree_logger = new RollingCategoryTreeLogger(self::$file_output_path);
			$category_tree_logger->init($start_date_only, $this->project_data);
		} else {
			$category_tree_logger = null;
		}
				
		foreach ($this->configurations as $config) {
			$logger->debug("**************************");
			$logger->debug(++$i . " of " . count($this->configurations) . ": " . $config->getGalleryname());
			$logger->debug("**************************");
			$logger->debug("");
		
			$hash = $config->getHash();
			$configs[$hash] = $config;
			$validator->assert(@$filelists['hash'] === null); // validate array key exists
			foreach ($config->getCategories() as $category) {
				$nextFilesAndCategories = $categoryTree->pagesInTree($category, 
					$config->getIgnoredSubcats(), $config->getGalleryname(), $category_tree_logger);
				
				// remove everything but files
				$next = array_filter($nextFilesAndCategories, 
					function ($nextFileOrCat) {
						return substr($nextFileOrCat, 0, 5) === "File:";
					});
				
				$galleryName = $config->getGalleryname();
				if (isset($filelists[$hash])) {
					// combine galleries
					$filelists[$hash] = array_unique(array_merge($filelists[$hash], $next));
				} else {
					$filelists[$hash] = $next;
				}
			}
		}
		
		$logger->info("Writing logs");
		if ($category_tree_logger) {
			$category_tree_logger->complete();
		}
		
		$logger->info("Outputting to file pages");
		
		foreach ($filelists as $hash => $files) {
			$config = $configs[$hash];
			$logger->debug("****************************************");
			$logger->debug($config->getGalleryName());
		
			$count = count($files);
		
			if ($count === 0) {
				$logger->info("Skipped");
				continue;
			}
		
			$logger->debug("$count files");
			$target = $config->getGalleryName();

			$subpage = $config->get_subpage_for_date($end);
			
			$existing_text_response = $existing_texts[$subpage];
			$existing_length = ($existing_text_response->exists ? substr_count(
				$existing_text_response->text, "\nFile:") : 0);
			
			try {
				$all_write_data = $this->get_write_data($existing_length, $files);
			} catch (Category_Files_Overflow_Exception $e) {
				$this->write_error($datematcher, $target, "category_files.limit.gallery.text",
					"category_files.limit.gallery.title",
					["max" => self::$overflow_max_files, "count" => $count]);
				continue;
			}
			
			$page_and_write_data = [];

			$gallery_count = 0;
			foreach ($all_write_data as $i => $write_data) {
				$text = "";
				foreach ($write_data as $j => $gallery_files) {
					if ($gallery_files) {

						$text .= "\n\n== {{date|$datematcher[1]|$datematcher[2]|$datematcher[3]}}";
						if ($gallery_count++) {
							$text .= " (" . ($gallery_count) . ")";
						}
						$text .= " ==\n<gallery";
						if ($config->isShowFilename()) {
							$text .= " showfilename=\"true\"";
						}
						if ($config->getMode() !== null) {
							$text .= " mode=\"" . $config->getMode() . "\"";
						}
						if ($config->getWidth() !== null) {
							$text .= " widths=\"" . $config->getWidth() . "px\"";
						}
						if ($config->getHeight() !== null) {
							$text .= " heights=\"" . $config->getHeight() . "px\"";
						}
						$text .= ">\n" . join("\n", $gallery_files) . "\n</gallery>";
					}
				}
				if ($i === 0) {
					$range_pagename = $config->get_subpage_for_date($end, false);
				} else {
					$range_pagename = $config->get_subpage_overflow_for_date($datematcher[1],
						$datematcher[2], $datematcher[3], $i);
					if (!$config->getSubpage()) {
						$this->write_error($datematcher, $target, "category_files.limit.subpage.text",
							"category_files.limit.subpage.title",
							["max" => self::$page_max, "count" => $count + $existing_length]);
						continue;
					}
				}
				if ($text) {
					$page_and_write_data[$range_pagename] = $text;
				}
			}
		
			
			if ($config->getSubpage()) {
				/* output new line to list page if it's not already present */
				$logger->debug("Subpage edit TRUE");
				
				$list_text = "";
				$current_list_text = $wiki_interface->get_text($this->project_data->getWiki(), $target)->text;
				foreach ($page_and_write_data as $page => $text) {
					
					$page_wikitext = "\n*[[$page]]";
					$logger->debug("\$range_page_wikitext: $page");
					
					if (strpos($current_list_text, $page_wikitext) === false) {
						$logger->debug("Range not found in gallery; adding it.");
						$list_text .= $page_wikitext;
					}	
				}
				if ($list_text) {
					$edit_summary = replace_named_variables(
						Environment::prop("messages", "category_files.gallery.new"), 
						["galleries" =>
							join(", ", 
								array_map_pass_key($page_and_write_data, 
									function ($key) {
										return "[[$key]]";
									}
								)
							)
						]
					);
					
					try {
						$listpg = $wiki_interface->new_page($this->project_data->getWiki(), $target);
						$result = $wiki_interface->edit($listpg, $list_text, $edit_summary, 
							EDIT_APPEND);
					} catch (Exception $e) {
						ogrebotMail($e);
					}
				}
			} else {
				$logger->debug("Subpage edit FALSE");
			}
		
			foreach ($page_and_write_data as $subpage => $text) {
				$pg = $wiki_interface->new_page($this->project_data->getWiki(), 
					"$target$subpage");
				
				if (!$pg->get_exists()) {
					$text = "__TOC__\n$text";
					if ($config->isNoIndex()) {
						$text = "__NOINDEX__\n$text";
					}
					if ($config->isWarning()) {
						$text = "{{" . $constants["category_files.warning_template"] . "}}\n$text";
					}
				}
				
				$firstCatname = $config->getCategories()[0]->category;
				$catText = count($config->getCategories()) == 1 ? "[[$firstCatname]]" : "multiple categories";
				$summary = "BOT: updating gallery for files in $catText";
				$wiki_interface->set_curl_timeout(300); // the page takes a long time for Mediawiki to render...
				try {
					$result = $wiki_interface->edit($pg, $text, $summary, EDIT_APPEND);
				} catch (Exception $e) {
					ogrebotMail($e);
				}
				$wiki_interface->reset_curl_timeout();
			}
		}
		
	}
	
	/**
	 * 
	 * @param int $existing_length
	 * @param string[] $files
	 * @return string[][][]
	 * @throws Category_Files_Overflow_Exception
	 */
	private function get_write_data($existing_length, array $files) {
		//$count = count($files);
		
		$split_files = [];
		for($i = 0; $i < self::$overflow_max; $i++) {
			$next_split = [];
			for($j = 0; $j < self::$page_max; $j += self::$gallery_max) {
				$max = $i === 0 ? max([min([self::$page_max - $j - $existing_length, self::$gallery_max]), 0]) : self::$page_max;
				
				$next = array_splice($files, 0, $max);
				if ($next) {
					$next_split[] = $next;
					if (!$files) {
						break;
					}
				}
			}
			if ($next_split) {
				$split_files[] = $next_split;
			}
			if (!$files) {
				return $split_files;
			}
		}
		throw new Category_Files_Overflow_Exception();
	}
	
	/**
	 * 
	 * @param int[] $datematcher
	 * @param string $target
	 * @param string $message_key
	 * @param string $summary_key
	 * @param array $variables
	 * @return void
	 */
	private function write_error(array $datematcher, $target, $message_key, $summary_key, array $variables) {
		global $logger, $wiki_interface;
		try {
			$date_string = "$datematcher[1]-$datematcher[2]-$datematcher[3]";
			$gallery = rawurlencode($target);
			$log_link = self::$log_link . "?date=$date_string&gallery=$gallery";
			
			$error_summary = replace_named_variables(Environment::prop("messages", $summary_key), 
				["gallery" => $target]);
			
			$error_text = replace_named_variables(Environment::prop("messages", $message_key), 
				array_replace($variables, ["gallery" => $target, "log_link" => $log_link]));
			
			$logger->warn($error_text);
			

			$talk_page = $wiki_interface->new_page($this->project_data->getWiki(),
				$this->project_data->get_talk_page_name(Project_Data::get_base_page_name($target)));
			$wiki_interface->edit($talk_page, $error_text, $error_summary, EDIT_APPEND);
		} catch (Exception $e) {
			ogrebotMail($e);
		}
	}

}
?>
