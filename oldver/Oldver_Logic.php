<?php
class Oldver_Logic {
	
	/**
	 *
	 * @param string[]|string $instance
	 * @return string[]|string
	 */
	public function get_human_readable_timestamp($instance) {
		return preg_replace(
			"/^(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})Z$/",
			"$1 $2",
			$instance['timestamp']
		);
	}
	
	/**
	 * @return void
	 */
	public function run() {
		global
		 	/* globals used by HTML */
			$comimg, $comTitleHtml, $comTitleLink, $localerrmessage, $localname, $localTitleHtml, 
			$localTitleLink, $locimg, $proj, $project_data_commons, $project_data_local, $projects, 
			$sharedname, $sharederrmessage, $src, $srcval, $startpage, $trg, $trgval, $tusc_header, 
			
			/* globals used by this function */
			$env, $environment, $MB_WS_RE_OPT, $wiki_interface;
		
		$post = $env->get_request_args();
		
		/* load list of projects from disk */
		$projects = get_all_project_names();
			
		/* define variables, including defaults */
		$localname = null;
		$sharedname = null;
		$localerrmessage = null;
		$sharederrmessage = null;
		$src = null;
		$trg = null;
		$proj = "en.wikipedia";
			
			/*
		 * security issues with POST['project']... better safe than sorry...
		 * graceful termination by just ignoring obviously malicious requests
		 */
		if (array_keys_exist($post, 'src', 'project')) {
			$proj = $post['project'];
			if ($proj === "wts.wikivoyage") {
				$proj = "wts.wikivoyage-old";
			}
			
			$src = $post['src'];
			
			/* default to same name for source and target; mostly useful for GET */
			$trg = array_key_or_value($post, 'trg', $src);
			
			/*
			 * set cookie: remember the individual's project for default,
			 * because the list is so large it's a pain to type it in every time. Functionality is not
			 * critical, so don't handle problem if setcookie returns false
			 */
			setcookie("project_cookie", $proj, 0x7fffffff, $environment['cookie.path']);
			
			try {
				$localname = cleanup_wikilink($src, true);
				if (preg_match("/^$MB_WS_RE_OPT$/u", $localname)) {
					$localerrmessage = "load.empty";
				}
			} catch (IllegalWikiNameException $e) {
				$localerrmessage = "load.notfound";
			}
			
			try {
				$sharedname = cleanup_wikilink($trg, true);
				if (preg_match("/^$MB_WS_RE_OPT$/u", $sharedname)) {
					$sharederrmessage = "load.empty";
				}
			} catch (IllegalWikiNameException $e) {
				$localerrmessage = "load.notfound";
			}
			
			if (!$localerrmessage && !$sharederrmessage) {
				$project_data_commons = new Project_Data("commons.wikimedia");
				$co = $project_data_commons->getWiki();
				
				try {
					$project_data_local = new Project_Data($proj);
				} catch (ProjectNotFoundException $e) {
					$error = "Unknown error: unable to locate project: $proj";
					ogrebotMail($error);
					die($error);
				}
				$project_data_local->setDefaultHostWiki("commons.wikimedia");
				$proj_type = $project_data_local->getProject();
				$lang = $project_data_local->getSubproject();
				$wiki = $project_data_local->getWiki();
				
				$locimg = $wiki_interface->new_image($wiki, $localname);
				$comimg = $wiki_interface->new_image($co, $sharedname);
				if (!$locimg->get_exists()) {
					$localerrmessage = "load.notfound";
				}
				if (!$comimg->get_exists()) {
					$sharederrmessage = "load.notfound";
				}
			}
			
			if ($localerrmessage ||
				 ($sharederrmessage !== NULL && $sharederrmessage !== "load.notfound.alt")) {
				$startpage = true;
			} else {
				$startpage = false;
			}
		} else {
			$startpage = true;
			if (in_array(@$_COOKIE['project_cookie'], $projects)) {
				$proj = $_COOKIE['project_cookie'];
			}
		}
		$tusc_text = Oldver_Shared::msg("tusc.tusc");
		$tusc_header = Oldver_Shared::msg("tusc.intro", ["tusc" => "<a href=\"/tusc\">$tusc_text</a>"]);
		
		$srcval = sanitize($src);
		$trgval = sanitize($trg);
		
		if (!$startpage) {
			$localTitleLink = $project_data_local->getRawLink($locimg->get_page()->get_title(true));
			$localTitleHtml = $locimg->get_page()->get_title(false);
			
			$comTitleLink = $project_data_commons->getRawLink($comimg->get_page()->get_title(true));
			$comTitleHtml = sanitize($comimg->get_page()->get_title(false));
		}
	}	
}
