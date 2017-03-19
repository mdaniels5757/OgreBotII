<?php
class Process_Upload_Logic {
	const Write_History_True_Header = 1;
	const Write_History_True_No_Header = 2;
	
	/**
	 *
	 * @var array
	 */
	private $POST;
	
	/**
	 *
	 * @var string
	 */
	public $com_title;
	
	/**
	 *
	 * @var string
	 */
	public $delete_text;
	
	/**
	 *
	 * @var string
	 */
	private $dir;
	
	/**
	 * The error that caused the application to fail.
	 * 
	 * @var string
	 */
	public $error;
	
	/**
	 *
	 * @var array
	 */
	private $history_com;
	
	/**
	 *
	 * @var array
	 */
	private $history_loc;
	
	/**
	 *
	 * @var string
	 */
	public $last_edit_time;
	
	/**
	 *
	 * @var string
	 */
	public $loc_title;
	
	/**
	 *
	 * @var int
	 */
	private $most_recent_local_index;
		
	/**
	 * 
	 * @var string
	 */
	public $nowcommons_summary;
	
	/**
	 * 
	 * @var string
	 */
	public $nowcommons_text;
	
	/**
	 *
	 * @var ProjectData
	 */
	private $project_data_com;
	
	/**
	 *
	 * @var string
	 */
	public $proj;
	
	/**
	 *
	 * @var ProjectData
	 */
	private $project_data_loc;
	
	/**
	 *
	 * @var bool
	 */
	private $rev_to_most_recent;
	
	/**
	 *
	 * @var Image
	 */
	private $src;
	
	/**
	 *
	 * @var string
	 */
	public $start_time;
	
	/**
	 * 
	 * @var Number
	 */
	public $start_time_wiki;
	
	/**
	 *
	 * @var Image
	 */
	private $trg;
	
	/**
	 *
	 * @var int
	 */
	public $tusc_time;
	
	/**
	 *
	 * @var string
	 */
	private $tusc_user;
	
	/**
	 *
	 * @var boolean
	 */
	public $tusc_verified;
	
	/**
	 *
	 * @var Process_Upload_Upload[]
	 */
	public $uploads = [];
	
	/**
	 *
	 * @var int
	 */
	public $wrote_history;
	
	/**
	 *
	 * @var int
	 */
	public $wrote_time;
	
	/**
	 *
	 * @return void
	 */
	private function load_projects() {
		global $logger, $wiki_interface;
		
		/*
		 * Project Data
		 */
		$this->proj = $this->POST['project'];
		$logger->info("\$proj = $this->proj");
		$this->project_data_loc = new ProjectData($this->proj);
		$this->project_data_loc->setDefaultHostWiki("commons.wikimedia");
		$this->project_data_com = new ProjectData("commons.wikimedia");
		
		/*
		 * src, trg
		 */
		$srcName = mb_trim($this->POST['src']);
		$logger->debug("\$src = $srcName");
		$trgName = mb_trim($this->POST['trg']);
		$logger->debug("\$src = $trgName");
		$wiki = $this->project_data_loc->getWiki();
		$co = $this->project_data_com->getWiki();
		$this->src = $wiki_interface->new_image($wiki, $srcName);
		$this->trg = $wiki_interface->new_image($co, $trgName);
		$this->history_loc = array_reverse($wiki_interface->get_upload_history($this->src));
		$this->history_com = $wiki_interface->get_upload_history($this->trg);
		
		$this->loc_title = $this->src->get_page()->get_title(true);
		
		$this->com_title = $this->trg->get_page()->get_title(true);
		
		$this->nowcommons_text = replace_named_variables_defaults(
			$this->getNowCommonsMessage("nowcommons.tag"));
		$this->nowcommons_summary = $this->getNowCommonsMessage("nowcommons.editsummary");
		$this->delete_text = $this->getNowCommonsMessage("nowcommons.deletetext");
		
		$this->last_edit_time = preg_replace(
			"/^(\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/", "$1$2$3$4$5$6", 
			$this->trg->get_page()->get_lastedit());
	}
	
	/**
	 *
	 * @return void
	 * @throws BaseException
	 */
	private function set_temp_dir() {
		global $logger;
		
		$this->dir = TMP_DIRECTORY_SLASH;
		$logger->debug("Temp directory: $this->dir");
		if (!file_exists($this->dir)) {
			ogrebotMail("Unable to access directory to download files: $this->dir");
			throw new BaseException();
		}
	}
	
	/**
	 *
	 * @return void
	 * @throws Process_Uploads_Exception
	 */
	private function verify_tusc() {
		
		$time_tusc = time();
		
		try {
			$this->tusc_user = Environment::get()->get_remote_io()->verify_ident($this->POST['ident-cookie']);
			$this->tusc_verified = !!$this->tusc_user;
		} catch (TuscException $e) {
			ogrebotMail($e);
			throw new Process_Uploads_Exception("upload.error.tusc.error");
		}
		
		$this->tusc_time = time() - $time_tusc;
		
		if (!$this->tusc_verified) {
			throw new Process_Uploads_Exception("upload.error.tusc.failed");
		}
	}
	
	/**
	 *
	 * @return void
	 */
	private function load_history() {
		global $wiki_interface;
		
		$this->history_loc = array_reverse($wiki_interface->get_upload_history($this->src));
		$this->history_com = $wiki_interface->get_upload_history($this->trg);
	}
	
	/**
	 *
	 * @return void
	 * @throws Process_Uploads_Exception
	 */
	private function load_params() {
		global $env, $logger;
		$this->POST = $env->get_request_args();
		
		if ($logger->isInfoEnabled()) {
			$post_clone = $this->POST;
			$logger->info("\$post_clone = ");
			$logger->info($post_clone);
		}
		
		try {
			array_keys_exist_exception($this->POST, 'project', 'src', 'trg');
		} catch (ArrayIndexNotFoundException $e) {
			throw new Process_Uploads_Exception("upload.error.no_data");
		}
	}
	
	/**
	 *
	 * @return void
	 * @throws Process_Uploads_Exception
	 */
	private function load_index_information() {
		global $logger;
		
		$most_recent_hash = $this->history_com[0]['sha1'];
		$this->most_recent_local_index = -1;
		
		$count_l = count($this->history_loc);
		for($i = 0; $i < $count_l; $i++) {
			if ($this->history_loc[$i]['sha1'] === $most_recent_hash) {
				$this->most_recent_local_index = $i;
				break;
			}
		}
		
		$this->rev_to_most_recent = @$this->POST["upload" . ($this->most_recent_local_index + 1)] ||
			 @$this->POST["revert"];
		
		/* ensure it's not a request for an empty upload! */
		$empty_upload = true;
		for($i = 0; $i < $count_l; $i++) {
			/* only reverting to the upload currently on Commons does nothing */
			if ($i === $this->most_recent_local_index) {
				continue;
			}
			
			if (@$this->POST["upload" . ($i + 1)]) {
				$empty_upload = false;
				break;
			}
		}
		
		if ($empty_upload) {
			$logger->info("No uploads!");
			throw new Process_Uploads_Exception("upload.error.none_chosen");
		}
	}
	
	/**
	 *
	 * @throws Process_Uploads_Exception
	 */
	private function download_files() {
		
		$process_id = getmypid();
		$comtitle = $this->trg->get_page()->get_title(false);
		
		$count_l = count($this->history_loc);
		
		$download_util = new Download_Util([CURLOPT_FOLLOWLOCATION => 1, CURLOPT_CONNECTTIMEOUT => 900]);
						
		for($i = 0; $i <= $count_l; $i++) {
			
			$upload = new Process_Upload_Upload();
			if ($i == $count_l) {
				if (!$this->rev_to_most_recent) {
					continue;
				}
				
				/*
				 * I'm using the obnoxious form of $i=count_l for when we're talking about the "revert to
				 * this" upload, because I want to avoid duplicate code, and because pushing it out into a
				 * separate function is too messy at this point
				 */
				$instance = $this->history_com[0];
				$upload->edit_summary = Oldver_Shared::msg("upload.edit_summary.revert");
			} else {
				if ($i === $this->most_recent_local_index || !@$this->POST["upload" . ($i + 1)]) {
					continue;
				}
				$instance = $this->history_loc[$i];
				preg_match(
					"/^(\d{4}\-(?:0\d|1[012])\-(?:[012]\d|3[01]))T((?:[01]\d|2[0123]):" .
						 "[0-5]\d:[0-5]\d)Z$/", $instance['timestamp'], $matches) || $validator->_assert(
					false, 
					"Invalid timestamp received from server: " . "$instance[timestamp]");
				$timestamp = "$matches[1] $matches[2]";
				$user = $instance['user'];
				$userlink = $this->project_data_loc->formatPageLinkAuto("User:$user", null, 
					true);
				$upload->edit_summary = Oldver_Shared::msg("upload.edit_summary.new", 
					array("project" => $this->proj, "timestamp" => $timestamp, 
						"userlink" => $userlink));
			}

			$upload->size = parse_number($instance['size']);
			
			$upload->download_attempted = true;
			$download_util->add_download($instance['url'], "$this->dir$comtitle#$process_id#$i");
			$remote_response = $download_util->execute()[0];
			
			if ($remote_response->error) {
				throw new Process_Uploads_Exception("upload.error.download");
			}
			
			$upload->download_time = $remote_response->time;
			$upload->download_response = $remote_response;
			$this->uploads[] = $upload;
		}
	}
	
	/**
	 *
	 * @throws Process_Uploads_Exception
	 */
	private function upload_files() {
		global $wiki_interface;
		
		/* upload all the files */
		foreach ($this->uploads as $upload) {
			
			try {
				$upload->upload_attempted = true;
				$filename = $upload->download_response->filename;
				$mime = $upload->download_response->mime;
				$time = time();
				$wiki_interface->upload_mw($this->trg, $upload->edit_summary, $filename, $mime, 900, 30);
				$upload->upload_time = time() - $time;
			} catch (UploadException $e) {
				$upload->upload_error = true;
				// no uploads have yet been made. Can fail gracefully.
				if ($i === 0) {
					throw new Process_Uploads_Exception("upload.error.upload");
				}
			}
		}
	}
	
	/**
	 *
	 * @return void
	 * @throws APIError
	 * @throws EditConflictException
	 * @throws PermissionsError
	 * @throws CURLError
	 */
	private function post_upload_history() {
		global $logger, $MB_WS_RE_OPT, $wiki_interface;
		
		$write_history = @$this->POST['upload_history'] ? true : false;
		$logger->info("\$write_history = $write_history");
		if ($write_history) {
			
			/*
			 * search for original upload log already on page... break up into a few parts
			 * because lookarounds are proving too difficult to learn
			 */
			$filetext = $wiki_interface->get_page_text($this->trg->get_page());
			$filetext = preg_replace(
				"/\{\{${MB_WS_RE_OPT}original${MB_WS_RE_OPT}upload${MB_WS_RE_OPT}" .
					 "log$MB_WS_RE_OPT\}\}/ui", "original upload log", $filetext);
			
			$header = preg_match(
				"/\=+${MB_WS_RE_OPT}original${MB_WS_RE_OPT}upload${MB_WS_RE_OPT}log" .
					 "$MB_WS_RE_OPT\=+$MB_WS_RE_OPT\n/ui", $filetext);
			
			$this->wrote_history = $header ? self::Write_History_True_No_Header : self::Write_History_True_Header;
			$projlink = $this->proj === 'www.wikivoyage-old-shared' ? 'wikivoyage/shared' : $this->proj;
			$apptext = "\n" . $wiki_interface->file_history_to_text($this->src, true, !$header);
			
			$time = time();
			
			$wiki_interface->edit($this->trg->get_page(), $apptext, 
				"(BOT): appending upload information for reference", EDIT_APPEND);
			
			$this->wrote_time = time() - $time;
		}
	}
	
	/**
	 *
	 * @return void
	 * @throws CantWriteToFileException
	 */
	private function write_to_log() {
		/* post authorizing user to log */
		$output_user = rawurldecode(preg_replace("/[\s_\|]+/", " ", $this->tusc_user));
		$loctitle = $this->src->get_page()->get_title(true);
		$comtitle = $this->trg->get_page()->get_title(true);
		
		$text = "|*$this->start_time $this->proj/$loctitle|$comtitle|$output_user\n";
		
		try {
			file_put_contents_ensure("../uploadlog", $text, FILE_APPEND);
		} catch (CantWriteToFileException $e) {
			throw $e;
		}
	}
	
	/**
	 *
	 * @return void
	 */
	private function unlink_handles() {
		foreach ($this->uploads ? $this->uploads : [] as $upload) {
			if ($upload->download_response && $upload->download_response->filename) {
				unlink($upload->download_response->filename);
			}
		}
	}
	
	/**
	 */
	public function __construct() {
		$this->start_time = date('Y-m-d H:i:s');
		$this->start_time_wiki = date('YmdHis');
		
		Oldver_Shared::load_messages();
		
		try {
			$this->load_params();
			$this->set_temp_dir();
			$this->verify_tusc();
			$this->load_projects();
			$this->load_history();
			$this->load_index_information();
			$this->download_files();
			$this->post_upload_history();
			$this->write_to_log();
			$this->upload_files();
		} catch (Process_Uploads_Exception $e) {
			$this->error = Oldver_Shared::msg($e->getMessage_code());
		} catch (Exception $e) {
			ogrebotMail($e);
			$this->error = Oldver_Shared::msg("upload.error.unknown");
		}
		
		$this->unlink_handles();
	}
	
	/**
	 *
	 * @param string $key        	
	 * @return string
	 */
	private function getNowCommonsMessage($key) {
		global $msg;
		
		$args = array("pgName" => $this->trg->get_page()->get_title(false));
		if (array_key_exists("$key.$this->proj", $msg)) {
			$string = Oldver_Shared::msg("$key.$this->proj", $args);
		} else {
			$string = Oldver_Shared::msg("$key.default", $args);
		}
		
		return $string;
	}
	
	/**
	 * *************
	 * getters
	 * ************
	 */
	
	/**
	 *
	 * @return string
	 */
	public function getCom_html() {
		return @$this->com_html;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getLoc_html() {
		return @$this->loc_html;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getProj() {
		return @$this->proj;
	}
}