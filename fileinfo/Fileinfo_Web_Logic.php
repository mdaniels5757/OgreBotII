<?php
class Fileinfo_Web_Logic {
	
	/**
	 *
	 * @var string
	 */
	const DEFAULT_FILEINFO_PROJECT = "en.wikipedia";
	
	/**
	 *
	 * @var string
	 */
	const COOKIE_NAME = "fileinfo_project";
	
	/**
	 *
	 * @var string[]
	 */
	private $all_projects;
	
	/**
	 *
	 * @var string
	 */
	private $authordateformtext;
	
	/**
	 *
	 * @var string
	 */
	private $edit_file_commons_link;
	
	/**
	 *
	 * @var string
	 */
	private $edit_file_local_link;
	
	/**
	 *
	 * @var string
	 */
	private $errmessage;
	
	/**
	 *
	 * @var string
	 */
	private $fieldsformtext;
	
	/**
	 *
	 * @var string
	 */
	private $global_error;
	
	/**
	 *
	 * @var bool
	 */
	private $informationChecked;
	
	/**
	 *
	 * @var string
	 */
	private $licenseformtext;
	
	/**
	 *
	 * @var string[]
	 */
	private $messages;
	
	/**
	 *
	 * @var string
	 */
	private $project;
	
	/**
	 *
	 * @var string
	 */
	private $src_sanitized;
	
	/**
	 *
	 * @var bool
	 */
	private $startpage;
	
	/**
	 * 
	 * @var string
	 */
	private $style;
	
	/**
	 *
	 * @var string
	 */
	private $text;
	
	/**
	 *
	 * @var string
	 */
	private $view_file_commons_link;
	
	/**
	 *
	 * @var string
	 */
	private $view_file_local_link;
	
	/**
	 */
	public function __construct() {
		load_property_file_into_variable($this->messages, "fileinfo_messages");
	}
	
	/**
	 *
	 * @return void
	 */
	private function fileinfoLogicInternal() {
		global $env, $MB_WS_RE_OPT, $environment, $logger, $wiki_interface;
		
		$POST = $env->get_request_args();
		$this->all_projects = get_all_project_names();
		
		/*
		 * security issues with POST['project']... better safe than sorry...
		 * graceful termination by just ignoring obviously malicious requests
		 */
		$src = @$POST['src'];
		$this->project = array_key_or_value($POST, array('project'), self::DEFAULT_FILEINFO_PROJECT);
		
		if ($src && $this->project) {
			if ($this->project  === "wts.wikivoyage") {
				$this->project  = "wts.wikivoyage-old";
			}
			
			$src = mb_trim(
				preg_replace("/^$MB_WS_RE_OPT(?:file|image)$MB_WS_RE_OPT\:/ui", "", $src));
			
			/*
			 * set cookie: remember the individual's project for default, because the list is so large it's a pain to type it in every time.
			 * Functionality is not critical, so don't handle problem if setcookie returns false
			 */
			setcookie(self::COOKIE_NAME, $this->project, 0x7fffffff, $environment['cookie.path']);
			
			try {
				$name = cleanup_wikilink($src, true);
				if (preg_match("/^$MB_WS_RE_OPT$/u", $name)) {
					$this->errmessage = $this->messages['error.emptyfilename'];
				}
			} catch (IllegalWikiNameException $e) {
				$this->errmessage = replace_named_variables($this->messages["error.badfilename"], 
					array("chars" => sanitize($constants["illegal_pagename_chars"], false)));
			}
			
			if (!$this->errmessage) {
				$co = $wiki_interface->new_wiki("OgreBotCommons");
				
				$project_data = new Project_Data($this->project);
				$project_data->setDefaultHostWiki("commons.wikimedia");
				$proj_type = $project_data->getProject();
				$lang = $project_data->getSubproject();
				$wiki = $project_data->getWiki();
				
				$file_data_array = $wiki_interface->new_query_pages($wiki, ["File:$name"], 
					"revisions|imageinfo");
				$title = key($file_data_array);
				$file_data = current($file_data_array);
				if (@$file_data["missing"] || @$file_data['imagerepository'] !== 'local') {
					$this->errmessage = $this->messages['error.filenotfound'];
				}
			}
			
			if ($this->errmessage) {
				$this->startpage = true;
			} else {
				$this->startpage = false;
			}
		} else {
			$this->startpage = true;
			if (array_key_exists(self::COOKIE_NAME, $_COOKIE) &&
				 in_array($_COOKIE[self::COOKIE_NAME], $this->all_projects)) {
				$this->project = $_COOKIE[self::COOKIE_NAME];
			}
		}
		
		$this->src_sanitized = sanitize($src);
		
		$information = @$POST['information'] ? true : false;
		$authordate = $information && @$POST['authordate'];
		$license = $information && @$POST['license'];
		$fields = $information && @$POST['fields'];
		
		/*
		 * only do the whole change-commons-name-to-local-name if it's the start page (makes sense); or the two were the same to begin with on
		 * the non-start page (makes sense). Otherwise, best not to link them
		 */
		$this->style=_array_key_or_value($POST,	(new Legacy_CommonsHelper_History_Text_Writer())->get_name(),
				"style");
		$logger->info("Style is $this->style");
		$this->authordateformtext = $authordate ? " checked=\"checked\"" : ($information ? "" : " disabled=\"disabled\"");
		$this->licenseformtext = $license ? " checked=\"checked\"" : ($information ? "" : " disabled=\"disabled\"");
		$this->fieldsformtext = $fields ? " checked=\"checked\"" : ($information ? "" : " disabled=\"disabled\"");
		
		$this->informationChecked = $information ? "checked=\"checked\"" : "";
		
		if (!$this->startpage) {
			// links before text field
			$page_text = array_key_or_empty($file_data, "revisions", 0, "*");
			$commons_listed = get_listed_commons_image($page_text, $title);
			
			$view_local_text = replace_named_variables($this->messages["view_local"], 
				array("file" => $this->src_sanitized, "proj" => $this->project));
			$edit_local_text = replace_named_variables($this->messages["edit_local"], 
				array("file" => $this->src_sanitized, "proj" => $this->project));
			
			$this->view_file_local_link = wikilink($title, $this->project, true, false, 
				$view_local_text, "");
			$this->edit_file_local_link = wikilink($title, $this->project, true, false, 
				$edit_local_text, "", false, "edit");
			
			$dest = $commons_listed ? $commons_listed : $src;
			$dest_sanitized = sanitize($dest);
			$view_commons_text = replace_named_variables($this->messages["view_commons"], 
				array("file" => $dest_sanitized));
			$edit_commons_text = replace_named_variables($this->messages["edit_commons"], 
				array("file" => $dest_sanitized));
			
			$this->view_file_commons_link = wikilink($dest, "commons.wikimedia", true, false,
				$view_commons_text, "");
			$this->edit_file_commons_link = wikilink($dest, "commons.wikimedia", true, false,
				$edit_commons_text, "", false, "edit");
				
			if ($information) {
				$options = 0;
				if ($authordate) {
					$options |= Transfer_To_Commons_Writer::INCLUDE_AUTHOR_DATE;
				}
				if ($license) {
					$options |= Transfer_To_Commons_Writer::INCLUDE_LICENSE;
				}
				if ($fields) {
					$options |= Transfer_To_Commons_Writer::INCLUDE_FIELDS;
				}
				$this->text = (new Transfer_To_Commons_Writer($project_data, $title, $page_text, 
					$file_data["imageinfo"]))->write($options);
			} else {
				$this->text = "";
			}
			
			$this->text .= "\n\n";
			
			$history_writer = Upload_History_Wiki_Text_Writer::get_instance($this->style);			
			$this->text .= $history_writer->write_from_image_info(
				$this->project, preg_replace("/^.+?:/", "", $title), $file_data['imageinfo'], 
				true, true);
		}
	}
	
	/**
	 *
	 * @param string $key        	
	 * @return string
	 */
	public function msg($key) {
		return array_key_or_exception($this->messages, $key);
	}
	
	/**
	 *
	 * @return void
	 */
	public function load() {
		try {
			$this->fileinfoLogicInternal();
		} catch (Exception $e) {
			$this->global_error = $this->messages['error.unknown'];
		}
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function get_all_projects() {
		return $this->all_projects;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_authordateformtext() {
		return $this->authordateformtext;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_edit_file_commons_link() {
		return $this->edit_file_commons_link;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_edit_file_local_link() {
		return $this->edit_file_local_link;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_errmessage() {
		return $this->errmessage;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_fieldsformtext() {
		return $this->fieldsformtext;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_global_error() {
		return $this->global_error;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_informationChecked() {
		return $this->informationChecked;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_licenseformtext() {
		return $this->licenseformtext;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_project() {
		return $this->project;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_src_sanitized() {
		return $this->src_sanitized;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_startpage() {
		return $this->startpage;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_style() {
		return $this->style;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_view_file_commons_link() {
		return $this->view_file_commons_link;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_view_file_local_link() {
		return $this->view_file_local_link;
	}
}