<?php
class Auto_Relink extends Relink {
	const CATEGORY_DIFFERENT = "Category:All Wikipedia files with a different name on Wikimedia Commons";
	const CATEGORY_SAME = "Category:All Wikipedia files with the same name on Wikimedia Commons";
	const DELINK_SUCCESSFUL_TEMPLATE = "User:OgreBot/delink successful";
	const ERROR_PAGE = "User:OgreBot/errors";
	const REVIEWED_CATEGORY = "Category:Wikipedia files reviewed on Wikimedia Commons";
	
	/**
	 *
	 * @var string[]
	 */
	private $same_files;
	
	/**
	 *
	 * @var string[]
	 */
	private $different_files;
	
	/**
	 *
	 * @var Page
	 */
	private $error_page;
	
	/**
	 * 
	 * @var bool
	 */
	private $write_warnings = true;
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Relink::handle_failure()
	 */
	protected function handle_failure(Relink_Failure $failure) {
		global $logger, $wiki_interface;
		
		// log the failure
		parent::handle_failure($failure);
		
		if ($this->error_page === null) {
			$this->error_page = $wiki_interface->new_page($this->get_local(), self::ERROR_PAGE);
		}
		
		if (!$this->write_warnings) {
			return;
		}
		
		$prefix = "[[:$failure->file_name]]";
		
		if ($failure->linked_page) {
			$prefix .= " ([[:$failure->linked_page]])";
		}
		$message = array_key_or_exception($this->get_properties(), "error.$failure->status_code");
		
		if ($failure->dest_name) {
			$message = replace_named_variables($message, array("to" => $failure->dest_name));
		}
		$message = "\n*$prefix: $message";
		$summary_raw = array_key_or_exception($this->get_properties(), "error_summary");
		$summary = replace_named_variables($summary_raw, array("local" => $failure->file_name));
		
		try {
			$wiki_interface->edit($this->error_page, $message, $summary, EDIT_APPEND);
		} catch (Exception $e) {
			$logger->error($e);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Relink::post_delink()
	 */
	protected function post_delink(string $title, bool $same) {
		global $logger, $wiki_interface;
		
		static $edit_summary = null;
		if ($edit_summary === null) {
			$edit_summary = array_key_or_exception($this->get_properties(), "filepage_summary");
		}
		$page = $wiki_interface->new_page($this->get_local(), $title);
		
		$append_text = "{{" . self::DELINK_SUCCESSFUL_TEMPLATE;
		if ($same) {
			$append_text .= "|same=1";
		}
		$append_text .= "}}";
		
		$logger->info("Tagging page for deletion.");
		
		try {
			$wiki_interface->edit($page, $append_text, $edit_summary, EDIT_APPEND);
			$logger->debug("Success.");
		} catch (Exception $e) {
			$logger->error($e);
			$failure = new Relink_Failure();
			$failure->file_name = $title;
			$failure->description_page = true;
			if ($e instanceof PermissionsError) {
				$failure->status_code = "protected";
			} else {
				$failure->status_code = "unknown";
			}
			$this->handle_failure($failure);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Relink::get_files_same_name()
	 */
	protected function get_files_same_name(): array {
		$this->init();
		return $this->same_files;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Relink::get_files_different_name()
	 */
	protected function get_files_different_name(): array {
		$this->init();
		return $this->different_files;
	}
	
	/**
	 * @return void
	 */
	private function init() {
		global $logger, $wiki_interface;
		
		if ($this->same_files === null) {
			$logger->debug("Querying subcategories");
			
			$subcatnames = $wiki_interface->new_category_traverse($this->get_local(), 
				self::REVIEWED_CATEGORY, false, 14, null);
			
			$logger->debug(" ...done");
			
			$all_users = array_map_filter($subcatnames, 
				function ($subcat) {
					global $logger;
					
					if (preg_match(
						"/^Category:Wikipedia files reviewed on Wikimedia Commons by (.+)$/", 
						$subcat, $reviewer_re)) {
						return $reviewer_re[1];
					} else {
						$logger->error("Unrecognized category: $subcat");
					}
				});
			
			$admin_data = $wiki_interface->is_sysop($this->get_local(), $all_users);
			$ignored_categories = array_map_filter($admin_data, 
				function ($is_sysop) {
					if (!$is_sysop) {
						return "Category:Wikipedia files reviewed on Wikimedia Commons by $is_sysop";
					}
				});
			
			$pages_in_cat = $wiki_interface->new_category_traverse($this->get_local(), 
				self::REVIEWED_CATEGORY, true, 6, null, $ignored_categories);
			
			$imagenames = array_filter($pages_in_cat, 
				function ($page) {
					return str_starts_with($page, "File:");
				});
			
			$all_content = $wiki_interface->new_query_pages($this->get_local(), $imagenames, 
				"categoriesnohidden|revisions|templates");
			
			// search for delink successful template, ignore page if found
			$all_content = array_filter_use_keys($all_content, 
				function ($data, $title) {
					global $logger;
					
					$templates_data = array_key_or_empty($data, "templates");
					$templates = map_array($templates_data, "title");
					if (in_array(self::DELINK_SUCCESSFUL_TEMPLATE, $templates)) {
						$logger->info("Already delinked: $title; skipping.");
						return false;
					}
					return true;
				});
			
			$this->same_files = array();
			$this->different_files = array();
			$different_files = array();
			foreach ($all_content as $title => $data) {
				$categories = array_key_or_empty($data, "categories");
				$same = false;
				$different = false;
				foreach ($categories as $category) {
					$category_title = array_key_or_exception($category, "title");
					if ($category_title == self::CATEGORY_SAME) {
						$same = true;
					} else if ($category_title == self::CATEGORY_DIFFERENT) {
						$different = true;
					}
				}
				
				if ($same) {
					if ($different) {
						$failure = new Relink_Failure();
						$failure->file_name = $title;
						$failure->status_code = "multiple";
						$this->handle_failure($failure);
					}
					$this->same_files[] = $title;
				} else if ($different) {
					$different_files[] = $title;
				} else {
					$logger->error("Can't find NowCommons category for $title.");
				}
			}
			
			foreach ($different_files as $different_file) {
				$content = array_key_or_exception($all_content, $different_file, "revisions", 0, 
					"*");
				$commons_file = get_listed_commons_image($content, $different_file, $errorflag);
				
				if ($errorflag) {
					$failure = new Relink_Failure();
					$failure->file_name = $different_file;
					if ($errorflag | COMMONS_LISTED_MULTIPLE) {
						$failure->status_code = "nowcommons";
					} else {
						$failure->status_code = "multiple";
					}
					$this->handle_failure($failure);
				} else {
					$this->different_files[$different_file] = $commons_file;
				}
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Relink::is_verify_mime()
	 */
	protected function is_verify_mime(): bool {
		return true;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_write_warnings(): bool {
		return $this->write_warnings;
	}
	
	/**
	 *
	 * @param bool $write_warnings
	 * @return void
	 */
	public function set_write_warnings(bool $write_warnings) {
		global $validator;
		$validator->validate_arg($write_warnings, "bool");
	
		$this->write_warnings = $write_warnings;
	}
}