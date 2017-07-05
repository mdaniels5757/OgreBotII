<?PHP

/**
 * 
 * @author magog
 *
 */
class CommonsHelper_Service_Impl implements CommonsHelper_Service {
	
	/**
	 *
	 * @var array[]
	 */
	private $constants;
	
	/**
	 * 
	 * @var CommonsHelper_Dao
	 */
	private $dao;
	
	/**
	 * 
	 * @var CommonsHelper_Debugger
	 */
	private $debugger;
	
	public function __construct() {
		$this->constants = CommonsHelper_Factory::get_constants();
		$this->dao = CommonsHelper_Factory::get_dao();
		$this->debugger = CommonsHelper_Factory::get_debugger();
	}
	
	private function remove_image_namespace($image) {
		$i = strtolower($image);
		foreach ($this->constants['image_aliases'] as $ia) {
			if (substr($i, 0, strlen($ia)) === strtolower($ia)) {
				return explode(':', $image, 2)[0];
			}
		}
		return $image;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see CommonsHelper_Service::get_upload_text()
	 */
	public function get_upload_text($image, $language, $project, $remove_categories) {
		try {
			return $this->_get_upload_text($image, $language, $project, $remove_categories);
		} catch (Exception $e) {
			$response = new CommonsHelper_Get_Upload_Text_Response();
			$response->error = "An unknown error has occurred. The bot owner has been notified.";
			ogrebotMail($e);
			return $response;
		}
	}
	
	/**
	 *
	 * @param string $image        	
	 * @param string $language        	
	 * @param string $project        	
	 * @param bool $remove_categories    	
	 * @return CommonsHelper_Get_Upload_Text_Response
	 */
	private function _get_upload_text($image, $language, $project, $remove_categories) {		
		$is_good = false;
		$response = new CommonsHelper_Get_Upload_Text_Response();
		
		$category_names = @$this->constants['category_names'][$language];
		$category_names_en = $this->constants['category_names']['en'];
		
		try {
			$project_data = Project_Data::load("$language.$project");
			$project_data->getWiki(); //check valid project
		} catch (ProjectNotFoundException $e) {
			$response->error = "invalid.project";
			return $response;
		}
		$image = $this->remove_image_namespace($image);
		
		if ($language == '' && $project == 'wikisource') {
			$language2 = $project;
		} else if ($project === "wikipedia") {
			$language2 = $language;
		} else {
			$language2 = "$project:$language";
		}
		
		// Init
		$original_uploader = '';
		$first_date = '';
		$cat = '';
		$summary = '';
		$categories = [];
		
		// _____________________________
		// Load metadata
		// print "<br/>" . $this->if['query_imagedata'] ; //myflush () ;
		// print "Querying image data ... " ; //myflush () ;
		//TODO redirect
		$q = Array_Utils::first(Environment::get()->get_wiki_interface()->new_query_pages($project_data->getWiki(), 
			["File:$image"], "templates|imageinfo|categoriesall|revisions"));
		
		/* not found or description page only */
		if (!$q || !@$q['imageinfo']) {
			$response->error = "not.found";
			return $response;
		}
		
		$response->title = $q["title"];
		
		$templates = array_map_filter($q['templates'], 
			function ($t) {
				if (+$t['ns'] === 10) {
					return explode(':', $t['title'], 2)[1];
				}
			});
		$image_cats = map_array($q['categories'], 'title');
		//print $this->if['done']."<br/>" ; //myflush () ;
		//print "done.<br/>" ; //myflush () ;
	
	
		# _____________________________
		# Load current text
		//print $this->if['retrieving_image_desc'] ; //myflush () ;
		//print "Retrieving image description ... " ; //myflush () ;
// 		$iot = $is_on_toolserver ;
// 		$is_on_toolserver = false ;
		$text = mb_trim(map_array($q['revisions'], '*')[0]);
		$this->debugger->write(0, $text);
		$text = str_replace ( '[[' , "[[:{$language2}:" , $text ) ;
		$this->debugger->write(1, $text);
// 		$is_on_toolserver = $iot ;
		//print $this->if['done']."<br/>" ; //myflush () ;
		//print "done.<br/>" ; //myflush () ;
	
		//	print $text ; myflush();
	
		# _____________________________
		# PermissionOTRS
		$otrs_data = array () ;
		$otrs_pattern = '/{{PermissionOTRS[^}]*}}/i' ;
		if (preg_match_all($otrs_pattern, $text, $otrs_matches)) {
			foreach ($otrs_matches as $o) {
				$otrs_data[] = $o[0];
			}
		}

		$otrs_orig = '';
		if ($language == 'de') {
			$o = array();
			$p = '/\{\{OTRS[^}]+\}\}/';
			preg_match_all($p, $text, $o);
			//TODO fix $x is not an array...
			$q2 = array();
			foreach ($o as $x) {
				$q2[] = $x[0];
				// print $x[0] . "<br/>" ;
			}
			$otrs_orig = implode("\n", $q2);
			$text = preg_replace($p, '', $text);
		}
		$this->debugger->write(2, $text);
		
		// _____________________________
		// Original upload history
		$log = (new New_CommonsHelper_History_Text_Writer())->write_from_image_info(
			"$language.$project", $image, $q['imageinfo'], false, true);
		
		if ($q['imageinfo']) {
			$splice_length = max([count($q['imageinfo']) - 2, 0]);
			$dates = @preg_replace(
				"/^(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}):\d{2}Z/", "$1 $2", 
				Array_Utils::map_array(Array_Utils::splice($q['imageinfo'], 1, $splice_length), 
					"timestamp"));
			$first_date = $dates[0];
			$last_date = @$dates[1];
			if (!$last_date) {
				$last_date = $first_date;
			}
		}
		$author = '';
		if (preg_match('/\|autore=(.+?)[\|\}]/', $text, $m)) {
			$author = $m[1] . "\n";
		}
		
		
		// _____________________________
		// Author

		$users = array_reverse(array_unique(map_array($q['imageinfo'], "user")));
		$original_uploader = current($users); //first uploader
		//TODO handle empty upload history
		$orig_wiki_link = "[https://{$language}.{$project}.org {$language}.{$project}]";
		$desc_page = $project_data->getRawLink("File:$image");
		//TODO handle user with equals sign in name
		$author_links = "{{User at project|{$original_uploader}|{$project}|{$language}}}";
		
		$author = "{{Original uploader|{$original_uploader}|{$project}|{$language}}}";
	
		if (count($users) > 1) {
			$author .= ".\nLater version(s) were uploaded by ";
			$a = array();
			foreach ($users as $u) {
				if ($u !== $original_uploader) {
					$a[] = "[[:{$language2}:User:{$u}|{$u}]]";
				}
			}
			$author .= implode(', ', $a) . " at {$orig_wiki_link}.";
		}
		if ($language == 'nl') {
			$text = str_ireplace('{{self|PD-auteur', '{{self|1=PD-self', $text);
		}
		$text = str_ireplace('{{self|', "{{Self|author=$author_links|", $text);

		$this->debugger->write(3, $text);
		// _____________________________
		// Source
		$orig_desc_link = "{{original description|$language.$project|" . urlencode($image) . '}}';
		
		$source = "{{transferred from|{$language}.{$project}}}";
		
		// _____________________________
		// Date
		$this->debugger->write("first_date.0", $first_date);
		$this->debugger->write("last_date.0", $last_date);
		if ($first_date == @$last_date) {
			$first_date = substr($first_date, 0, 10);
			$date = "{$first_date}";
			$date = preg_replace('/\b(\d{4})-(\d{2})-(\d{2})\b/', 
				'{{Original upload date|${1}-${2}-${3}}}', $date);
		} else {
			$first_date = substr($first_date, 0, 10);
			$last_date = substr($last_date, 0, 10);
			$date = "{$last_date} (first version); {$first_date} (last version)";
		}
		$this->debugger->write("date.-2", $first_date);
			
		// _____________________________
		// Permission and license
		$licenses = array();
		$permission = array();
		$license_params = array();
		$self_tl_params = array();
		
		if (false !== stripos($text, '{{Fair use bâtiment récent')) {
			$response->error = "Template prevents transfer to Commons!";
			return $response;
		}
		
		// Checking for license indicators
		foreach ($this->constants['license_indicators'] as $k => $v) { 
			if (false !== stripos($text, $k)) {
				$templates[] = $v;
			}
		}
		
		// The self/self2 orgy
		$p = Template::extract( $text, 'self' );
		if (false === $p) {
			$p = Template::extract($text, 'self2');
		}
		if (false !== $p) {
			foreach (array_values($p->__get("fields")) as $v) {
				$vl = strtolower($v);
				if (isset($transwiki_templates[$language][$vl])) {
					$v = $transwiki_templates[$language][$vl];
				}
				if (!preg_match("/^\s*(migration|author)\s*\=/i", $v)) {
					$v = ucfirst($v);
				}
				$self_tl_params[] = $v;
			}
			$text = $p->__get("before") . $p->__get("after");
			$this->debugger->write(4, $text);
			foreach ($templates as $k => $v) {
				if (in_array($v, $this->constants['deprecated_licenses'])) {
				} else if (in_array($v, $self_tl_params) || $v == 'Self' || $v == 'Self2') {
					unset($templates[$k]);
					$vl = strtolower($v);
					if (isset($permission_texts[$v])) {
						$is_good = true;
						$permission[$permission_texts[$v]] = $permission_texts[$v];
					} else if (in_array($vl, $this->constants['good_templates']) or
						 substr($vl, 0, 2) == 'cc' || substr($vl, 0, 2) == 'pd') {
						$vu = strtoupper($v);
						$permission[$vu] = $vu;
						$is_good = true;
					}
				}
			}
		}
		
		foreach ($templates as $t) {
			$ot = $t;
			$t = $this->get_commons_template($language, $t);
			$t = $this->fix_self_template($t, $text);
				
			// Not a license
			if (in_array($t, $this->constants["info_templates"]) ||
				 @$this->constants['info_template_names'][$language] === $t) {
				continue;
			}

			$t2 = strtolower ( $t ) ;
			$tx = 'Template:' . ucfirst ( $t ) ;
			
			$add_params = array();
			$ot2 = str_replace('/', '\/', $ot);
			$pattern = "/\{\{{$ot2}\|([^\}]*)\}\}/";
			if (preg_match($pattern, $text, $add_params) && isset($add_params[1])) {
				$add_params = $add_params[1];
			} else {
				$add_params = '';
			}
			$license_params[$tx] = $add_params;
			
			if (false === strpos($tx, '|')) {
				$licenses[$tx] = $tx;
			} else {
				$tx2 = explode('|', $tx, 2);
				$tn = array_shift($tx2);
				$p = array_pop($tx2);
				$license_params[$tn] = $p . "|" . $license_params[$tx];
				$license_params[$tx] = $p . "|" . $license_params[$tx];
				$licenses[$tn] = $tn;
			}
			
			$pattern = str_replace('_', ' ', $ot);
			$pattern = str_replace(' ', '[ _]', $pattern);
			$pattern = str_replace('/', '\/', $pattern);
			$pattern = str_replace(')', '\)', $pattern);
			$pattern = str_replace('(', '\(', $pattern);
			$pattern = "/\{\{${pattern}[^\}]*\}\}/i";
			$text = preg_replace($pattern, '', $text);
			
			$tu = strtoupper($t);
			if (in_array(ucfirst($t), $this->constants['deprecated_licenses']) && $project == 'wikipedia') {
			} else if (isset($this->constants['permission_texts'][ucfirst($t)])) {
				$permission[$this->constants['permission_texts'][ucfirst($t)]] = $this->constants['permission_texts'][ucfirst(
					$t)];
				$is_good = true;
			} else if (in_array($t2, $this->constants['good_templates'])) {
				$permission[$tu] = $tu;
				$is_good = true;
			} else if (substr($t2, 0, 2) == 'cc' || substr($t2, 0, 2) == 'pd') {
				$permission[$tu] = $tu;
				$is_good = true;
			}
		}
		if (!$is_good) {
			$response->warnings[] = "bad.license";
		}

		$this->debugger->write("licenses5", json_encode($licenses));
		$this->debugger->write(5, $text);
			
		// Check for preventive categories
		foreach ($image_cats as $c) {
			foreach ($this->constants['prevent_transfer_categories'] as $p) {
				if (substr($c, 0, strlen($p)) == $p) {
					$response->warnings[] = "Presence of category \"$c\" prevents transfer to Commons!";
				}
			}
		}
		foreach ($templates as $t) {
			foreach ($this->constants['prevent_transfer_templates'] as $p) {
				if (substr($t, 0, strlen($p)) == $p) {
					$response->warnings[] = "Presence of template \"$t\" prevents transfer to Commons!";
				}
			}
		}
		
		if (isset($permission[$this->constants['permission_texts']['GFDL 1.2']])) {
			unset($permission[$this->constants['permission_texts']['GFDL']]);
		}
	
		// Remove templates local to the source wiki
		$licenses = array_diff($licenses, str_prepend($this->constants['ignore_local_templates'], "Template:"));

		$this->debugger->write("licenses5.2", json_encode($licenses));
		
		foreach ($this->constants['ignore_local_templates'] as $ilt) {
			unset($permission[strtoupper($ilt)]);
		}
	
		// Which of these license templates do exist on commons?
		$licenses2 = $this->dao->get_templates_on_commons($licenses);
			
		// Stitch license templates and their parameters back together
		$this->debugger->write("licenses2.462", json_encode($licenses2, JSON_FORCE_OBJECT));
		foreach ($licenses2 as $k => $v) {
			if (@$license_params[$v]) {
				$s = $license_params[$v];
				while (substr($s, -1, 1) == '|') {
					$s = substr($s, 0, -1);
				}
				$licenses2[$k] .= "|$s";
			}
		}
		$this->debugger->write("licenses2.0", json_encode($licenses));	
		$this->debugger->write("licenses2.479", json_encode($licenses2, JSON_FORCE_OBJECT));
		
		$license = array() ;
		$self_subs = array () ;
		foreach ( $licenses2 AS $l) {
			$l2 = explode('|', $l, 2)[0];
			unset($licenses[$l2]);
			$l = explode(':', $l, 2)[1];
			$license[strtolower($l)] = "{{{$l}}}";
			
			// Hack to fix "self" licenses
			$ll = strtolower($l);
			if (substr($ll, 0, 5) == "self|" || substr($ll, 0, 6) == "self2|") {
				$n = explode("|", $l);
				array_shift($n); // Self
				$self_subs[] = "lang";
				if (substr($ll, 0, 6) == "self2|") {
					$self_subs[] = "self";
				}
				foreach ($n as $v) {
					$self_subs[] = strtolower($v);
					$v = array_shift(explode("-", $v));
					$self_subs[] = strtolower($v);
				}
			}
		}
		$this->debugger->write("licenses5.2.2", implode("\n", $license));
		$this->debugger->write("licenses2.1", json_encode($licenses));
		// Fix cc-by-sa-any
		if (isset($license['cc-by-sa-any'])) {
			foreach ($license as $k => $v) {
				if ($k == 'cc-by-sa-any') {
					continue;
				}
				if (substr($k, 0, 8) == 'cc-by-sa') {
					unset($license[$k]);
				}
			}
		}
			
		// Fix self "sub-licenses"
		foreach ($self_subs as $v) {
			if (isset($license[$v])) {
				unset($license[$v]);
			}
		}
		
		// Fix lonely "Self" ;
		foreach ($license as $k => $v) {
			$k2 = strtolower($k);
			if ($k2 == "self" || $k2 == "self2") {
				unset($license[$k]);
			}
		}
		
		$license = implode("\n", $license);
		$this->debugger->write("licenses5.3",$license);
		if (count($licenses) > 0) {
			$license .= "\n<!-- Templates \"" . implode('", "', $licenses) .
				 "\" were used in the original description page as well , but do not appear to " .
				 "exist on commons. -->";
		}
			
		// Fix *-self
		if ($language == "en") {
			// TODO equals...
			$license = str_replace('{{GFDL-self}}', 
				"{{GFDL-user-en-no-disclaimers|$original_uploader}}", $license);
		}
		
		if ($language == 'sr') {
			$license = str_replace('{{GFDL-self}}', 
				"{{GFDL-user-w|sr|Wikipedia|$original_uploader}}", $license);
			$license = preg_replace('/\{\{PD-self(\|date\=[\w\s]+)?\}\}/', 
				"{{PD-user-w|$language|$project|$original_uploader}}", $license);
		} else {
			$license = str_replace('{{GFDL-self}}', "{{GFDL-user-$language|$original_uploader}}", 
				$license);
			$license = preg_replace('/\{\{PD-self(\|date\=[\w\s]+)?\}\}/', 
				"{{PD-user-w|$language|$project|$original_uploader}}", $license);
		}
		
		if (false !== stripos($license, '{{GFDL-with-disclaimers}}')) {
			$license = trim(str_replace('{{GFDL}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-en}}')) {
			$license = trim(str_replace('{{GFDL}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-it}}')) {
			$license = trim(str_replace('{{GFDL}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-Utente}}')) {
			$license = trim(
				str_replace('{{GFDL-Utente}}', "{{GFDL-user-it|$original_uploader}}", $license));
		}
		if (false !== stripos($license, '{{GFDL 1.2}}')) {
			$license = trim(str_replace('{{GFDL}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-1.2-en}}')) {
			$license = trim(str_replace('{{GFDL}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-user-en-no-disclaimers}}')) {
			$license = trim(str_replace('{{GFDL-self-no-disclaimers}}', '', $license));
		}
		if (false !== stripos($license, '{{GFDL-user-en-with-disclaimers}}')) {
			$license = trim(str_replace('{{GFDL-self-en}}', '', $license));
		}
		$license = str_replace('-disclaimers}}', "-disclaimers|$original_uploader}}", $license);
		
		if (count($self_tl_params) > 0) {
			$license .= "\n{{self|" . implode('|', $self_tl_params) . "}}";
		}
			
		// Remove pseudo-dupe licenses like GFDL and GFDL-self
		foreach ($this->constants['license_dupes'] as $l1 => $l2) {
			if (false !== stripos($license, "{{{$l1}")) {
				$license = trim(str_replace("{{{$l2}}}", '', $license));
				$license = trim(str_replace("\n\n", "\n", $license));
			}
		}
		
		$permission = implode('; ', $permission);
		if ($permission != '') {
			$permission .= '.';
		}
			
		
		// _____________________________
		// Description
		$description = trim($text);
		$this->debugger->write(6, $description);
		do {
			$odesc = $description;
			if (substr($description, 0, 1) == '=') {
				$description = trim(Array_Utils::last(explode("\n", $description, 2)));
				continue;
			}
			$d = explode("\n", $description);
			$dl = array_pop($d);
			if (substr($dl, 0, 1) != '=') {
				array_push($d, $dl);
			}
			$description = trim(implode("\n", $d));
		} while ($description != $odesc);
		$this->debugger->write(7, $description);
		
		if (substr($description, 0, 1) == '*' || substr($description, 0, 1) == '#') {
			$description = "\n$description";
		}
		$this->debugger->write(8, $description);
		
		if ($remove_categories) {
			$pattern = "/\[\[\:$language2\:$category_names\:.*?]]/i";
			$description = preg_replace($pattern, '', $description);
			$pattern = "/\[\[\:$language2\:$category_names_en\:.*?]]/i";
			$description = preg_replace($pattern, '', $description);
			$description = trim($description);
		}
		$this->debugger->write(9, $description);
		
		// _____________________________
		// This is for testing only; it should not affect the live tool
		if (substr($description, 0, 12) == '{{NowCommons') {
			$a = explode("\n", $description, 2);
			$description = trim(array_pop($a));
		}
		$description = str_replace(":$language::$language:", ":$language:", $description);
		$description = str_replace("|\n|", "\n|", $description);
		$this->debugger->write(10, $description);
			
		// _____________________________
		// Extract data from {{Information}}
		$other_versions = '';
		if (isset($this->constants['info_template_names'][$language])) {
			$info = '{{' . $this->constants['info_template_names'][$language];
		}
		if (isset($this->constants['info_template_names'][$language]) &&
			 false !== stripos($description, $info)) {
			$localcat = "[[:$language:category:";
			$d2 = $info . explode($info, $description, 2)[1];
			$d2 = str_replace("|\n", "\n|", $d2);
			$d2 = str_replace("\n\n", "\n", $d2);
			$d = explode("\n", $d2);
			$description = array();
			$mode = 'in';
			$key = '';
			$data = array();
			$data['Beschreibung'] = '';
			$data['Quelle'] = '';
			$data['Urheber'] = '';
			$data['Datum'] = '';
			$data['Genehmigung'] = '';
			$data['Andere Versionen'] = '';
			foreach ($d as $l) {
				if ($mode == 'out') {
					$description[] = $l;
					continue;
				}
				$l = trim($l);
				if ($l == '}}') {
					$lkey = strtolower($key);
					if (isset($this->constants['information_keys'][$lkey])) {
						$key = ucfirst($this->constants['information_keys'][$lkey]);
					}
					if ($key != '' && $cat != '') {
						$data[$key] = $cat;
					}
					$mode = 'out';
					continue;
				}
				if (substr($l, 0, 1) != '|') {
					$cat .= "\n" . $l;
					continue;
				}
				$lkey = strtolower($key);
				if (isset($this->constants['information_keys'][$lkey])) {
					$key = ucfirst($this->constants['information_keys'][$lkey]);
				}
				if ($key != '' && $cat != '') {
					$data[$key] = $cat;
				}
				$l = explode('=', $l, 2);
				$key = trim(substr(array_shift($l), 1));
				$cat = trim(array_pop($l));
			}
			if ($cat != '') {
				$data[$key] = $cat;
			}
			$description = implode("\n", $description);
			$this->fix_localized_info_table($language, $data);
			$description = $this->add_original_description($language, $description, 
				$data['Beschreibung'], 1);
			$this->debugger->write("desc.0", $description);
			$source = $this->add_original_description($language, $source, $data['Quelle']);
			$this->debugger->write("source.0", $source);
			$this->debugger->write("date.-1", $date);
			$date = $this->add_original_description2($language, $date, $data['Datum']);
			$this->debugger->write("date.0", $date);
			$author = trim(
				$this->add_original_description($language, '', $data['Urheber']) . ".\n$author");
			$this->debugger->write("author.0", $author);
			$permission = $this->add_original_description($language, $permission, 
				$data['Genehmigung']);
			$this->debugger->write("permission.0", $permission);
			$other_versions = $this->add_original_description($language, $other_versions, 
				$data['Andere Versionen']);
			$this->debugger->write("ov.0", $other_versions);
		} else if ($language == 'de') { // Attempt to freestyle-parse German text...
			$beginnings = array('*', "'", ' ');
			$conts = array("'", ':', ' ');
			$letters = array();
			for($a = 'a'; $a <= 'z'; $a++){
				$letters[] = $a;
			}
			for($a = 'A'; $a <= 'Z'; $a++){
				$letters[] = $a;
			}
			$letters[] = '/';
			$lines = explode("\n", $description);
			$description = '';
			foreach ($lines as $line) {
				$orig_line = $line;
				$key = '';
				while ($line != '' && in_array($line[0], $beginnings)) {
					$line = substr($line, 1);
				}
				while ($line != '' && in_array($line[0], $letters)) {
					$key .= $line[0];
					$line = substr($line, 1);
				}
				while ($line != '' && in_array($line[0], $conts)) {
					$line = substr($line, 1);
				}
				$key = strtoupper($key);
				$line = ucfirst(trim($line));
				if ($key == 'QUELLE') {
					$source = $this->add_original_description($language, $source, $line);
				} else if ($key == 'LIZENZSTATUS') {
					$permission = $this->add_original_description($language, $permission, $line);
				} else if ($key == 'LIZENZ') {
					$permission = $this->add_original_description($language, $permission, $line);
				} else if ($key == 'DATUM') {
					$date = $this->add_br($line, $date);
				} else if ($key == 'FOTOGRAF') {
					$author = $this->add_br($line, $author);
				} else if ($key == 'ZEICHNER') {
					$author = $this->add_br($line, $author);
				} else if ($key == 'FOTOGRAF/ZEICHNER') {
					$add_br = trim($line, $author);
				} else if ($key == 'BESCHREIBUNG') {
					$description = $this->add_original_description($language, $description, $line) . "\n";
				} else if ($key == 'BILDBESCHREIBUNG') {
					$description = $this->add_original_description($language, $description, $line) . "\n";
				} else {
					$description .= $orig_line . "\n";
				}
			}
			$description = trim($description);
		}
		$this->debugger->write(11, $description);
		
		// Remove certain headings from description
		$desc = explode("\n", $description);
		$description = '';
		foreach ($desc as $d) {
			$d2 = trim(strtolower(str_replace('=', '', $d)));
			if (!in_array($d2, $this->constants['bad_headings'])) {
				$description .= "$d\n";
			}
		}
		$description = trim($description);
			
		// _____________________________
		// Final fixes
		if ($description == '') {
			$description = "''no original description''";
		}
		$this->debugger->write(12, $description);
		$permission = trim("$permission\n" . implode("\n", $otrs_data));
		if ($permission == '') {
			$permission = "''See license section.''";
		}
		$this->debugger->write(13, $permission);
		$license = trim(str_replace('{{Author}}', '', $license));
		$this->debugger->write(14, $permission);
		
		$permission = trim("$permission\n$otrs_orig");
		$this->debugger->write(15, $permission);
		
		$orig_cats = '';
		if (!$remove_categories && $category_names) {
			$a1 = array();
			$pattern = "/\[\[\:$language2\:($category_names\:.*?)]]/i";
			preg_match_all($pattern, $description, $a1);
			$a2 = array();
			$pattern = "/\[\[\:$language2\:($category_names_en\:.*?)]]/i";
			preg_match_all($pattern, $description, $a2);
			
			if (isset($a1[1])) {
				foreach ($a1[1] as $c) {
					$orig_cats .= "[[$c]]\n";
				}
			}
			if (isset($a2[1])) {
				foreach ($a2[1] as $c) {
					$orig_cats .= "[[$c]]\n";
				}
			}
		}
		
		if (false !== stripos($description, '{{Nach Commons verschieben (bestätigt)')) {
			$description = explode('{{Nach Commons verschieben (bestätigt)', $description);
			$description[1] = array_pop(explode('}}', $description[1], 2));
			$description = trim(implode('', $description));
		}
		
		$fin = "__NOTOC__\n";
		if (is_array($categories) && count($categories) > 0) {
			$fin .= "\n[[Category:" . utf8_encode(implode("]]\n[[Category:", $categories)) . ']]';
		} else if ($orig_cats != '') {
			$fin .= "\n" . $orig_cats;
		} else {
			$fin .= "\n{{subst:Unc}} <!-- Remove this line once you have added categories -->";
		}

		$this->debugger->write("fin.0", $fin);
		
		if ($language != '' && explode('|', $description, 2)[0] != "{{{$language}") {
			$description = "{{{$language}|{$description}}}";
		}
		$this->debugger->write(16, $description);
		
		$license = preg_replace($otrs_pattern, '', $license);
		$license = str_replace('$$UPLOADER$$', $author_links, $license);
	
	
		// _____________________________
		// Output
		$out = "
	== {{int:filedesc}} ==
	{{Information
	|Description={$description}
	|Source={$source}
	|Date={$date}
	|Author={$author}
	|Permission={$permission}
	|other_versions={$other_versions}
	}}

	== {{int:license-header}} ==
	{$license}

	{$log}	{$fin}" ;

	$this->debugger->write(17, $out);
		// Remove unwanted categories
		$remcats = array('Hidden categories', 'Stub icons');
		foreach ($remcats as $r) {
			$out = str_ireplace("[[Category:$r]]", '', $out);
			$r = str_replace(' ', '_', $r);
			$out = str_ireplace("[[Category:$r]]", '', $out);
		}
		$out = str_ireplace('{{PD}}', '', $out);
		$out = str_replace('|Migration=relicense', '|migration=relicense', $out);
		
		$pattern = "/\[\[(:$language2:[^|\]]+)\]\]/";
		$out = preg_replace($pattern, '[[${1}|]]', $out);
		
		$out = str_ireplace('[[:wikiversity:beta:', '[[:betawikiversity:', $out);
		
		if ($otrs_orig != '') {
			$out = str_replace('{{OTRS}}', '', $out);
		}
		
		$out = str_replace('Http://', 'http://', $out); // Oddity

		$out = str_replace("\t", '', $out);
		$this->debugger->write(18, $out);
		$this->debugger->commit();
		
		$response->text = $out;
		return $response;
	}
	
	private function add_br($k1, $k2) {
		$k1 = trim($k1);
		$k2 = trim($k2);
		if ($k2 == '') {
			return $k1;
		}
		if ($k1 == '') {
			return $k2;
		}
		return "$k1<br/>\n$k2";
	}
	
	/**
	 *
	 * @param string $ts
	 * @return string        	
	 */
	private function parse_timestamp($ts) {
		$ts = explode(':', $ts);
		array_pop($ts2);
		$ts = implode(':', $ts);
		$ts = str_replace('T', ' ', $ts);
		$ts = trim(str_replace('z', '', $ts));
		return $ts;
	}
	
	private function fix_localized_info_table($language, &$data) {
		if (!isset($this->constants["info_keys_$language"])) {
			return;
		}
		foreach ($this->constants["info_keys_$language"] as $k => $v) {
			$k2 = strtolower(substr($k, 0, 1)) . substr($k, 1);
			$k1 = ucfirst($k);
			$v = ucfirst($v);
			// Already have that
			if ($data[$v] != '') {
				continue;
			}
			if (isset($data[$k1])) {
				$data[$v] = $data[$k1];
			}
			if (isset($data[$k2])) {
				$data[$v] = $data[$k2];
			}
		}
	}
	
	private function add_original_description($language, $desc, $orig, $translate = false) {
		$orig = trim($orig);
		if ($orig == '') {
			return $desc;
		}
		$desc = trim($desc);
		if ($desc == '') {
			return $orig;
		}
		if ($translate) {
			return "{{{$language}|$orig<br/>\n$desc}}";
		}
		return $desc . "<br/>\n(Original text : ''$orig'')";
	}
	
	private function add_original_description2($language, $desc, $orig, $translate = false) {
		$orig = trim($orig);
		if ($orig == '') {
			return $desc;
		}
		$desc = trim($desc);
		if ($desc == '') {
			return $orig;
		}
		if ($translate) {
			return "{{{$language}|$orig<br/>\n$desc}}";
		}
		return "$orig<br/>\n($desc)";
	}
	
	private function get_commons_template($language, $t) {
		$t = strtolower(substr($t, 0, 1)) . substr($t, 1);
		if (isset($this->constants["transwiki_templates.$language"]) &&
			 isset($this->constants["transwiki_templates.$language"][$t])) {
			return $this->constants["transwiki_templates.$language"][$t];
		}
		$t = ucfirst($t);
		if (isset($this->constants["transwiki_templates.$language"]) &&
			 isset($this->constants["transwiki_templates.$language"][$t])) {
			return $this->constants["transwiki_templates.$language"][$t];
		}
		return $t;
	}
	
	private function fix_self_template($template, $text) {
		$tl = strtolower($template);
		if ($tl != 'pd' && $tl != 'gfdl') {
			return $template;
		}
		$text = strtolower(str_replace(' ', '', $text));
		foreach ($this->constants['self_texts'] as $st) {
			if (false !== strpos($text, $st)) {
				return "$template-self";
			}
		}
		return $template;
	}
}

// error_reporting(E_ERROR|E_CORE_ERROR|E_COMPILE_ERROR); // E_ALL|
// ini_set('display_errors', 'On');

// include_once ( 'php/wikiquery.php' ) ;
// include_once ( 'common_data.php' ) ;
// //require_once( 'php/peachy/Init.php' );

// $info_keys = array() ;
// $info_keys['fi'] = array (
// 		'Kuvaus' => 'Bechreibung',
// 		'Lähde' => 'Quelle',
// 		'Päiväys' => 'Datum',
// 		'Tekijänoikeuksien haltija' => 'Urheber',
// ) ;



// # ____________________________________________________________________________________________________________________
// # Main program

// # _____________________________
// # Initialize
// $ch = new CommonsHelper_Service();
// $image = trim ( $ch->remove_image_namespace ( trim ( get_request ( 'image' ) ) ) ) ;
// $language = trim ( get_request ( 'language' ) ) ;
// if ( $language == '' ) $language = get_request ( 'lang' , 'en' ) ;
// $project = trim ( get_request ( 'project' , 'wikipedia' ) ) ;

// /*
//  $language = fix_language_code ( $language ) ;
//  $project = check_project_name ( $project ) ;
//  */

// $interface_language = get_request ( 'interface' , 'en' ) ;
// $ignorewarnings = get_request ( 'ignorewarnings' , false ) ;
// $use_common_sense = isset ( $_REQUEST['commonsense'] ) ;
// $remove_categories = isset ( $_REQUEST['remove_categories'] ) ;
// $newname = get_request ( 'newname' , $image ) ;
// $orig_newname = $newname ;
// if ( $newname == "" ) $newname = $image ;
// $du = get_request ( 'directupload' , false ) ;
// $rdu = get_request ( 'reallydirectupload' , false ) ;
// $doit = isset ( $_REQUEST['doit'] ) ;
// $testing = isset ( $_REQUEST['test'] ) ;
// if ( !$doit ) $use_common_sense = true ;
// if ( $du ) $rdu = true ;
// if ( $use_common_sense AND isset ( $_REQUEST['commonsense'] ) AND $_REQUEST['commonsense'] == '0' ) $use_common_sense = false ;
// if ( $remove_categories AND $_REQUEST['remove_categories'] == '0' ) $remove_categories = false ;

// $newname = explode ( '.' , $newname ) ;
// if ( count ( $newname ) > 1 ) {
// 	$extension = trim ( array_pop ( $newname ) ) ;
// 	$last = rtrim ( array_pop ( $newname ) ) ;
// 	$newname[] = $last ;
// 	$newname[] = $extension ;
// }
// $newname = implode ( '.' , $newname ) ;
// # i18n

// $helptext = array () ;
// $helplink = array () ;
// $if = $ch->load_interface_localization ( 'https://commons.wikimedia.org/w/index.php?title=User:Magnus_Manske/Commonshelper_interface&action=raw' ,
// 	$interface_language ) ;

// $help = array() ;
// foreach ( $helptext AS $hl => $ht ) {
// 	$link = $helplink[$hl] ;
// 	$help[] = "<a target='_blank' href='{$link}'>{$ht}</a>" ;
// }
// $help = implode ( ' | ' , $help ) . "<br/>" ;

// $interface_links = array () ;
// foreach ( array_keys($helptext) AS $l ) {
// 	$url = "?interface={$l}" ;
// 	$interface_links[] = "<a href='$url'>" . strtoupper ( $l ) . "</a>" ;
// }
// $interface_links = implode ( ' | ' , $interface_links ) ;


// # _____________________________
// # Problems?
// $tophint = '' ;


// if ( $doit ) {
// 	$wq2 = new WikiQuery ( $language , $project ) ;
// 	if ( !$wq2->does_image_exist ( "File:$image" ) ) {
// 		$tophint = "Image \"$image\" does not exist on $language.$project!" ;
// 		$doit = false ;
// 	}
// }


// $newname2 = $newname ;
// /*$newname = ansi2ascii ( $newname ) ;
//  if ( $doit and $newname != $newname2 ) {
//  $tophint = "Image name has been ASCIIfied from \"$newname2\" to \"$newname\"!" ;
//  if ( !$ignorewarnings ) $doit = false ;
//  }*/

// if ( $doit ) {
// 	if ( $language == "xxx" ) $language = "" ;
// 	$wq = new WikiQuery ( "commons" , "wikimedia" ) ;
// 	$newname2 = $newname ;
// 	$count = 1 ;
// 	#	print "!!$newname2!!" ;
// 	while ( $wq->does_image_exist ( "File:$newname2" ) ) {
// 		$n = explode ( '.' , $newname ) ;
// 		$newname2 = " ($count)." . array_pop ( $n ) ;
// 		$newname2 = implode ( '.' , $n ) . $newname2 ;
// 		$count++ ;
// 	}
// 	if ( $newname2 != $newname ) {
// 		$url1 = "https://commons.wikimedia.org/wiki/File:" . myurlencode ( $newname ) ;
// 		$url2 = get_thumbnail_url ( "commons" , $newname , 120 , "wikimedia" ) ;
// 		$url_orig1 = "https://$language.$project.org/wiki/File:" . myurlencode ( $image ) ;
// 		$url_orig2 = get_thumbnail_url ( $language , $image , 120 , $project ) ;
// 		$dc = $ch->db_get_image_data ( $image , 'commons' , 'wikimedia' ) ;
// 		$do = $ch->db_get_image_data ( $image , $language , $project ) ;
// 		$dc = $dc->img_width . " &times; " . $dc->img_height . " px" ;
// 		$do = $do->img_width . " &times; " . $do->img_height . " px" ;
// 		$tophint = "<div style='float:right;text-align:center;border:1px solid grey'>
// 		<a href=\"$url1\"><img src=\"$url2\" border=0 /></a><br/><small><i>$newname</i><br/>exists on commons<br/>$dc</small>
// 		<hr/>
// 		<a href=\"$url_orig1\"><img src=\"$url_orig2\" border=0 /></a><br/><small>You are transfering<br/><i>$newname</i><br/>from $language.$project<br/>$do</small>
// 		</div>" ;



// 		$tophint .= "Image \"$newname\" already exists. Image name has been changed to \"$newname2\"!" ;
// 		$newname = $newname2 ;
// 		if ( !$ignorewarnings ) $doit = false ;
// 	}
// }


// $db = '' ;


// # _____________________________
// # Header
// print "<!DOCTYPE html>\n<html " ;
// print "lang='$interface_language' " ;
// if ( $interface_language == "he" ) print "dir='rtl' " ;
// print "><body>" ;
// print '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>' ;
// print get_common_header ( "commonshelper.php" , "CommonsHelper_Service" ) ;
// print "<h1>" . $if['title'] . "</h1>" ;

// if ( isset ( $newname ) and isset ( $image ) and isset ( $project ) and isset ( $language ) ) {
// 	$ch2 = "/commonshelper2/?language=$language&project=$project&target_file=" . urlencode ( $newname ) . "&file=" . urlencode ( $image ) ;
// } else $ch2 = '' ;
// if ( $use_common_sense ) $ch2 .= "&use_checkusage=1" ;

// print "<small><i>{$interface_links}</i><br/>" ;
// print $help . $if['desc1'] . "</small><br/>" ;
// print "<small><i>" . $if['loggedinwarning'] . "</i></small><br/>" ;
// if ( $tophint != '' ) print "<div class='alert alert-danger' role='alert'>$tophint</div><br/>" ;
// //myflush () ;

// if ( $doit ) {
// 	$upload_text = $ch->get_upload_text ( $image , $language , $project ) ;

// 	if ( $rdu ) {
// 		if ( !$is_good ) print "<div class='alert alert-danger' role='alert'>" . $if['bad_license'] . "</div>" ;
// 		else {

// 			$external_url = "https:" . get_image_url ( $language , $image , $project ) ;

// 			print '<script>
// var language="'.$language.'";
// var project="'.$project.'";

// function urldecode(url) {
//   return decodeURIComponent(url.replace(/\+/g, " "));
// }

// var params = {
// 	action:"upload",
// 	newfile:urldecode("'.urlencode($newname).'"),
// 	url:urldecode("'.urlencode($external_url).'"),
// 	desc:urldecode("'.urlencode($upload_text).'"),
// 	comment:"Transferred from "+language+"."+project,
// 	botmode:1
// } ;

// var months = {
// 0 : "January" ,
// 1 : "February" ,
// 2 : "March" ,
// 3 : "April" ,
// 4 : "May" ,
// 5 : "June" ,
// 6 : "July" ,
// 7 : "August" ,
// 8 : "September" ,
// 9 : "October" ,
// 10 : "November" ,
// 11 : "December"
// } ;

// $(document).ready ( function () {


// 	function showError ( msg ) {
// 		$("#uploading").html ( "<big>ERROR: "+msg+"</big>" ) ;
// 	}

// 	function doUpload () {
// 		$.post ( "/magnustools/oauth_uploader.php?rand="+Math.random() , params , function ( d ) {
// 			if ( d.error != "OK" ) {
// 				console.log ( params ) ;
// 				console.log ( d ) ;
// 				var msg = [] ;
// 				if ( d.error != null ) msg = [ d.error ] ;
// 				else if ( typeof d.res.error.info != "undefined" ) msg = [ d.res.error.info ] ;
// 				if ( typeof d.res != "undefined" ) {
// 					if ( typeof d.res.upload != "undefined" ) {
// 						if ( typeof d.res.upload.warnings != "undefined" ) {
// 							$.each ( d.res.upload.warnings , function ( k2 , v2 ) {
// 								msg.push ( "<b>" + k2 + "</b> : " + v2 ) ;
// 							} ) ;
// 						}
// 					}
// 				}
// 				showError ( msg.join("<br/>") ) ;
// 				return ;
// 			}
// 			$("#uploading").html ( "<big>Transfer successful!</big>" ) ;
// 			$("#newfile_link").attr("href","//commons.wikimedia.org/wiki/File:"+params.newfile) ;
// 			$("#now_commons_buttons").show() ;
// 		} , "json" ) .fail(function() { showError ( "OAuth uploader failed or does not respond" ) } ) ;
// 	}

// 	$.get ( "/magnustools/oauth_uploader.php?action=checkauth&botmode=1" , function ( d ) {
// 		if ( d.error != "OK" ) {
// 			alert ( "Auth not OK" ) ;
// 			return ;
// 		}
// 		var is_autopatrolled = false ;
// 		$.each ( d.data.query.userinfo.groups , function ( k , v ) {
// 			if ( v == "autopatrolled" ) is_autopatrolled = true ;
// 		} ) ;
// 		var now = new Date() ;
// 		var bmtc = "{{BotMoveToCommons|"+language+"."+project+"|"+now.getFullYear()+"|"+months[now.getMonth()]+"|"+now.getDate()+"}}" ;
// 		if ( !is_autopatrolled ) {
// 			params.desc = bmtc + "\n\n" + params.desc ;
// 		}
// 		doUpload () ;
// 	} , "json" ) ;

// } ) ;
// </script>
// <div id="uploading"><i>Transfer in progress...</i></div>
// ' ;
// 			//			print do_direct_upload ( $language , $image , $upload_text ) ;
// 			print "<div id='now_commons_buttons' style='display:none'>" ;
// 			$button_label = 'Add {{NowCommons}}' ;
// 			$title = "File:$image" ;
// 			$tn = "NowCommons" ;
// 			$ip = "" ;
// 			if ( $language == 'en' ) {
// 				$tn = 'subst:ncd' ;
// 				$ip = "File:" ;
// 			} else $tn = "NowCommons" ;
			
// 			if ( $image == $newname ) $text = '{{' . $tn . '}}' ;
// 			else $text = '{{' . $tn . '|' . $ip . $newname . '}}' ;
			
// 			$stuff = urlencode("File:$image")."&action=delete&wpReason=NowCommons%20[[:commons:File:".urlencode($newname)."]]";
// 			$summary = 'Now commons' ;
			
// 			print "<br/>" . cGetEditButton ( $text , $title , $language , $project , $summary , $button_label , false , true , false , false ) ;
// 			print " or <a href=\"https://$language.$project.org/w/index.php?title=$stuff\">delete the original image</a> straight away." ;
			
// 			print " <a id='newfile_link' href=''>Your new file should be here!</a>" ;
				
// 			print "</div>" ;
// 		}
// 	} else {
// 		$action = 'https://commons.wikimedia.org/wiki/Special:Upload' ;
// 		$uform = "<form id='upload' method=post enctype='multipart/form-data' action='{$action}'>" ;
// 		$uform .= "<textarea style='width:100%' rows='20' name='wpUploadDescription'>" ;
// 		$uform .= $text ;
// 		$uform .= "</textarea>" ;
		
		
// 		$image_url = "https:" . get_image_url ( $language , utf8_decode ( $image ) , $project ) ;
// 		if ( $language == '' and $project == 'wikisource' ) $is_good = true ;
// 		if ( $is_good ) {
// 			$s1 = $if['good_license'] ;
// 			$s1 = str_replace ( "$1" , "<a href='$image_url'>$image</a>" , $s1 ) ;
// 			$s1 = str_replace ( "$2" , "<input type='submit' name='fake_upload' value='" , $s1 ) ;
// 			$s1 = str_replace ( "$3" , "' />" , $s1 ) ;
// 			$uform .= $s1 ;
// 			//		$uform .= "Save <a href='" . $image_url ."'>{$image}</a> locally, then " ;
// 			//		$uform .= "<input type='submit' name='fake_upload' value='upload at commons' />" ;
// 		} else {
// 			$uform .= "<font color='red'>" . $if['bad_license'] . "</font>" ;
// 		}
// 		if ( $directupload ) {
// 			$uform .= "<input type='hidden' name='lang' value='{$lang}'/>" ;
// 			$uform .= "<input type='hidden' name='image' value='" . urlencode ( $image ) . "'/>" ;
// 			$uform .= "<input type='hidden' name='reallydirectupload' value='1'/>" ;
// 			$uform .= "<input type='hidden' name='directupload' value='1'/>" ;
// 			$uform .= "<input type='hidden' name='doit' value='1'/>" ;
// 		}
// 		$uform .= "</form>" ;
		
// 		print $uform;
// 	}

// } else {
// 	$ucs = $use_common_sense ? ' checked ' : '' ;
// 	$urc = $remove_categories ? ' checked ' : '' ;
// 	$checked_directupload = ( $du or $rdu ) ? "checked" : "" ;
// 	$checked_ignorewarnings = ( $ignorewarnings ) ? "checked" : "" ;
	
// 	$form = "<form method='post' action='?' class='form' >" ;
// 	$form .= "<table class='table table-condensed'><tbody>" ;
// 	$form .= "<tr><td nowrap>" . $if['langcode'] . "</td><td colspan=2>" ;
// 	$form .= "<div class='col-sm-2'><input type=text name=language value='{$language}' /></div><div class='col-sm-1'>.</div><div class='col-sm-2'><input type=text name=project value='{$project}' /></div> . org</td></tr>" ;
// 	$form .= "<tr><td>" . $if['imgname'] . "</td><td colspan=2><input type=text name=image value='{$image}' class='span5' /></td></tr>" ;
// 	$form .= "<tr><td>" . $if['newname'] . "</td><td colspan=2><input type=text name=newname value='{$newname}' class='span5'/> " . $if['directnote']  . "</td></tr>" ;
// 	$form .= "<tr><td/><td colspan=2>" . "<label class='checkbox inline' for='remove_categories'><input type=checkbox name=remove_categories id='remove_categories' value=1 {$urc} /> " . $if['removecategories'] . "</label><br/>" ;
// 	$form .= "<tr><td/><td colspan=2>" . "<label class='checkbox inline' for='ignorewarnings'><input type=checkbox name=ignorewarnings value=1 id='ignorewarnings' $checked_ignorewarnings/> Ignore warnings (overwrite existing images)</label><br/>" ;
// 	$form .= "</tbody></table>" ;
	
// 	$form .= "<div style='border:2px solid gray;padding:2px;margin:2px'>" ;
// 	$form .= "<label for='du'><input checked type=checkbox name=reallydirectupload value=1 id='du' $checked_directupload/> " . $if['reallydirectupload'] . "</label><br/>" ;
// 	$form .= "To use the automatic transfer function, you need to authorize <a href='/magnustools/oauth_uploader.php?action=authorize' target='_blank'>OAuth uploader</a> to perform uploads under your Commons user name.<br/>See also <a href='http://blog.magnusmanske.de/?p=183'>this blog entry</a>." ;
// 	$form .= "</div><br/>" ;
	
// 	$form .= "<input class='btn btn-primary' type=submit name=doit value='" . $if['gettext'] . "'/>" ;
// 	$form .= "<input type=hidden name=test value=$testing />" ;
// 	$form .= "</form>" ;
	
// 	print $form ;
	
// }

// print "</body></html>" ;

?>

