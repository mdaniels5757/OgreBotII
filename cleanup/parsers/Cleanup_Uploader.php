<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Uploader implements Cleanup_Module {
	
	/**
	 *
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 *
	 * @var Template_Utils
	 */
	private $template_utils;
	
	/**
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 *
	 * @var string[]
	 */
	private $self_author_regexes;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_utils = $cleanup_package->get_template_utils();
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
		$this->constants = $cleanup_package->get_constants();
		
		$langlink_regex = $this->constants["langlinks_regex"];
		$opt_br = Cleanup_Shared::OPT_BR;
		
		$this->self_author_regexes = [];
		$this->self_author_regexes[] = "/^(?<original>(?<user>[^\|\]]+))\s*(?:\.|\;)?" .
			 "$opt_br\s*Original uploader was \[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?(?<lang>" .
			 "$langlink_regex)\s*:User\s*:\s*\\g{user}\s*\|\s*\\g{user}\s*\]\] at \[(?:https?" .
			 "\:)?\/\/\\g{lang}\.(?<project>w[a-z]+)\.org\/? \\g{lang}\.\\g{project}\]\.?";
		$this->self_author_regexes[] = "/^(?<original>\[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?(?<lang>$langlink_regex" .
			 ")\s*(?:\:\s*(?<namespace>[^\|\]]+)?\s*\:\s*(?<user>[^\|\]]+)\s*\|(?:\\s*\\g{lang}\\:)?(?:\\s*\\g{namespace}\s*\:\s*)?" .
			 "\s*\\g{user}\s*\]\](?:\s*\d{1,2}\:\d{2}\, \d{1,2}\.? [^\|\]|[\}\{]+\.? \d{4} \((?:UTC|CES?T)\))?\s*))" .
			 "\.?$opt_br\s*original uploader was \[\[\s*\:(?:w[a-z]+\s*\:\s*)?\s*\\g{lang}\s*:User\s*:\s*\\g{user}\s*\|" .
			 "\s*\\g{user}\s*\]\] at \[(?:https?\:)?\/\/\\g{lang}\.(?<project>w[a-z]+)\.org\/? \\g{lang}\.\\g{project}\]\.?";
		$this->self_author_regexes[] = "/^(?<original>\[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?(?<lang>$langlink_regex)\:\s*" .
			 "(?<namespace>[^\|\]]+)\:\s*(?<user>[^\|\]]+)\s*\|\s*\\g{user}\s*\]\]\s+\(\[\[\s*\:\\g{lang}\:[^\|\]]+" .
			 "\\g{user}\s*\|\s*[^\|\]]+\s*\]\]\)(?:\s*\d{1,2}\:\d{2}\, \d{1,2}\.? [^\|\]|[\}\{]+\.? \d{4} " .
			 "\((?:UTC|CES?T)\))?)\.?$opt_br\s*Original uploader was \[\[\s*(?:w[a-z]+\s*\:\s*)?\:\s*\\g{lang}" .
			 "\s*:(?:\\g{namespace}|user)\s*:\s*\\g{user}\s*\|\s*\\g{user}\s*\]\] at \[(?:https?\:)?\/\/" .
			 "\\g{lang}\.(?<project>w[a-z]+)\.org\/? \\g{lang}\.\\g{project}\]\.?";
		
		$this->self_author_regexes = str_append($this->self_author_regexes, 
			"$opt_br\s*?(?:\s*Later version\(s\) were uploaded by .+? at \[https?\:\/\/\\g{lang}+\." .
				 "\\g{project}\.org \\g{lang}\.\\g{project}\]\.?)?/iu");
		
		$this->self_author_regexes[] = "/^\[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?(?<lang>$langlink_regex" .
			 ")\s*\:(?<namespace>[^\|\]]+)\:\s*(?<user>[^\|\]]+)\s*\|\s*\\g{user}\s*\]\]/iu";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$author_information = $this->read_uploader_information($ci);
		if ($author_information) {
			$ci->set_author_information($author_information);
			$this->gfdl_de_wp($ci, $author_information);
			$this->gfdl_redirection($ci, $author_information);
			$this->self_redirection($ci, $author_information);
			$this->pd_release($ci, $author_information);
			$this->to_user_at_project($ci, $author_information);
		}
	}
	
	/**
	 * GFDL message specific to specific de.wp license
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param Author_Information $author_information        	
	 * @return void
	 */
	private function gfdl_de_wp(Cleanup_Instance $ci, Author_Information $author_information) {
		if ($author_information->get_self_replace()) {
			// the way it appears directly from CommonsHelper
			$de_wp_re1 = "/(?:\{\{Bild\-GFDL\-Neu(?:\s*\|\s*[Mm]igration\s*\=.*?)?\}\}" .
				 "\s*\r?\n)?\{\{(?:\s*self\s*\|\s*author\=.*\|)?Cc\-by\-sa\-3\.0\}\}\s*\r?\n" .
				 "\{\{Cc\-by\-sa\-3\.0\-de\}\}\s*\r?\n\{\{GFDL(?:\-user\-de)?(?:\|\s*(.+?)?)?" .
				 "\s*(?:\|migration=.*?)?\}\}/u";
			// the way it appears after MGA73bot2 mangles it :D
			$de_wp_re2 = "/\{\{GFDL(?:\-user\-de)?(?:\|\s*(.+?)?)?\s*(?:\|migration=.*?)?\}\}" .
				 "\s*\r?\n\{\{(?:\s*self\s*\|\s*author\=.*\|)?Cc\-by\-sa\-3\.0\}\}\s*\r?\n" .
				 "\{\{Cc\-by\-sa\-3\.0\-de\}\}/u";
			if (preg_match($de_wp_re1, $ci->get_text(), $match)) {
				if (array_key_exists(1, $match)) {
					$ci->preg_replace($de_wp_re1, 
						"{{self|author={{user at project|1=" .
							 escape_preg_replacement($author_information->get_username()) .
							 "|2=wikipedia|3=de}}|GFDL|cc-by-sa-3.0|cc-by-sa-3.0-de|migration=redundant}}");
				} else if (strtolower($author_information->get_project()) == 'wikipedia' &&
					 strtolower($author_information->get_language()) == 'de') {
					$ci->preg_replace($de_wp_re1, 
						"{{self|author={{user at project|1=" .
						 escape_preg_replacement($author_information->get_username()) .
						 "|2=wikipedia|3=de}}|GFDL|cc-by-sa-3.0|cc-by-sa-3.0-de|migration=redundant}}");
				}
			}
			if (preg_match($de_wp_re2, $ci->get_text(), $match)) {
				if (array_key_exists(1, $match)) {
					$ci->preg_replace($de_wp_re2, 
						"{{self|author={{user at project|1=" .
							 escape_preg_replacement($author_information->get_username()) .
							 "|2=wikipedia|3=de}}|GFDL|cc-by-sa-3.0|cc-by-sa-3.0-de|migration=redundant}}");
				} else if (strtolower($author_information->get_project()) == 'wikipedia' &&
					 strtolower($author_information->get_language()) == 'de') {
					$ci->preg_replace($de_wp_re2, 
						"{{self|author={{user at project|1=" .
						 escape_preg_replacement($author_information->get_username()) .
						 "|2=wikipedia|3=de}}|GFDL|cc-by-sa-3.0|cc-by-sa-3.0-de|migration=redundant}}");
				}
			}
		}
	}
	
	/**
	 * GFDL redirection and fixes
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param Author_Information $author_information        	
	 * @return void
	 */
	private function gfdl_redirection(Cleanup_Instance $ci, Author_Information $author_information) {
		if (preg_match("/\{\{\s*[Oo]riginal[ _]+upload[ _]+log\s*\}\}/u", $ci->get_text())) {
			
			if ($author_information->get_project() == "wikipedia") {
				// en.wp disclaimers mean we need to handle en.wp uniquely
				if ($author_information->get_language() == "en") {
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{\s*[Gg]FDL\-[Ss]elf(?:\-no-disclaimers)?\s*(?:\|\s*(?:1\s*\=\s*)?" .
							 preg_quote($author_information->get_username()) . ")?([\}\|])/u", 
							"$1$3{{GFDL-user-en-no-disclaimers|1=" .
							 escape_preg_replacement($author_information->get_username()) . "$4");
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{\s*[Gg]FDL\-self\-(?:en|with\-disclaimers)\s*(?:\|\s*(?:1\s*\=\s*)?" .
							 preg_quote($author_information->get_username()) . ")?([\}\|])/u", 
							"$1$3{{GFDL-user-en-with-disclaimers|1=" .
							 escape_preg_replacement($author_information->get_username()) . "$4");
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{\s*[Gg]FDL\-user\-en\-(no|with)\-disclaimers\s*\|\s*([^\|\}\{]+)\s*\|\s*[Mm]igration\=([a-z\-]+)\s*\|\s*\\5\s*\}\}/u", 
							"$1$3{{GFDL-user-en-$4-disclaimers|$5|migration=$6}}");
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{GFDL\-user\-en\-(no|with)\-disclaimers\s*\|\s*([^\|\}\{]+)\s*\}\}\s*\{\{\s*[Gg]FDL\-user\-en\-\\4\-disclaimers\s*\|\s*\\5\s*\|\s*[Mm]igration\=([a-z\-]+)\s*\}\}/u", 
							"$1$3{{GFDL-user-en-$4-disclaimers|$5|migration=$6}}");
				} else if (in_array($author_information->get_language(), 
					array("als", "ar", "bat-smg", "beta", "bg", "bs", "cs", "de", "el", "eo", "es", 
						"fa", "fi", "fr", "ga", "gl", "gn", "he", "hi", "hr", "hu", "id", "it", "ja", 
						"ko", "lt", "ml", "nl", "nn", "no", "pl", "pt", "ru", "sk", "sl", "sq", "th", 
						"tr", "uk", "vi", "vls", "zh"))) {
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{\s*[Gg]FDL\-[Ss]elf(?:\-no-disclaimers)?\s*([\}\|])/u", 
							"$1$3{{GFDL-user-" . $author_information->get_language() . "|" .
							 escape_preg_replacement($author_information->get_username()) . "$4");
				} else {
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
							 "\s*)\{\{\s*[Gg]FDL\-[Ss]elf(?:\-no-disclaimers)?\s*([\}\|])/u", 
							"$1$3{{GFDL-user-w|" . $author_information->get_language() . "|" .
							 $author_information->get_language() . ".wikipedia|" .
							 escape_preg_replacement($author_information->get_username()) . "$4");
				}
				
				$ci->preg_replace(
					"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
						 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
						 "\s*)\{\{\s*[Gg]FDL\-user\-([a-z\-]+)\s*\|\s*[Mm]igration\s*\=\s*([a-z\-]+)\s*\}\}/u", 
						"$1$3{{GFDL-user-$4|1=" .
						 escape_preg_replacement($author_information->get_username()) .
						 "|migration=$5}}");
			}
		}
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Gg]FDL\-user\s*\|\s*([^\|\}\{]+)\s*\|\s*([a-z]+)\-(with|no)\-disclaimers\s*\|/u", 
				"$1$3{{GFDL-user-$5-$6-disclaimers|$4|");
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Gg]FDL\-user\s*\|\s*([^\|\}\{]+)\s*\|\s*([a-z]+)\-en\s*\|/u", 
				"$1$3{{GFDL-user-$5-with-disclaimers|$4|");
	}
	
	/**
	 * self redirection to local uploader
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param Author_Information $author_information        	
	 * @return void
	 */
	private function self_redirection(Cleanup_Instance $ci, Author_Information $author_information) {
		global $logger;
		
		foreach (array("self", "self2", "multilicense replacing placeholder", 
			"multilicense replacing placeholder new") as $license) {
			if ($author_information->get_self_replace() || !preg_match("/^self\d*$/iu", $license)) {
				$madechange = false;
				// in case there are multiple instance of template
				$re = "/\{\{\s*[" . ucfirst(substr($license, 0, 1)) . substr($license, 0, 1) . "]" .
					 str_replace(" ", "[ _]+", substr($license, 1)) . "\s*(?:\||\}\})/u";
				preg_match_all($re, $ci->get_text(), $matches, PREG_OFFSET_CAPTURE);
				
				for($i = count($matches[0]) - 1; $i >= 0; $i--) {
					$match = $matches[0][$i];
					$offset = $i == 0 ? 0 : $match[1];
					$t = $this->template_factory->extract($ci->get_text(), $license, $offset);
					if ($t === false) {
						$logger->info("Warning: Template not found; template, text: ");
						$logger->info($license);
						$logger->info($ci->get_text());
						continue;
					}
					if (!$t->fieldisset(Cleanup_Shared::AUTHOR) && !$t->fieldisset("attribution")) {
						$t->addfield(
							"{{user at project|1=" .
								 escape_preg_replacement($author_information->get_username()) . "|2=" .
								 $author_information->get_project() . "|3=" .
								 $author_information->get_language() . "}}", Cleanup_Shared::AUTHOR);
						$ci->set_text($t->wholePage());
					}
				}
			}
		}
	}
	
	/**
	 * PD-release redirection
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param Author_Information $author_information        	
	 * @return void
	 */
	private function pd_release(Cleanup_Instance $ci, Author_Information $author_information) {
		if ($author_information->get_indicated_author() &&
			 preg_match("/\{\{\s*[Pp]D\-release\s*\}\}/u", $ci->get_text())) {
			$ci->preg_replace("/\{\{\s*[Pp]D\-release\s*\}\}/u", 
				"{{PD-user-w|project=" . $author_information->get_project() . "|language=" .
				 $author_information->get_language() . "|user=" .
				 escape_preg_replacement($author_information->get_username()) . "}}");
		}
	}
	
	/**
	 * original uploader -> user at project, where appropriate
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param Author_Information $author_information        	
	 * @return void
	 */
	private function to_user_at_project(Cleanup_Instance $ci, Author_Information $author_information) {
		if (preg_match(
			"/" . Cleanup_Shared::LICENSE_HEADER . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*(?:[PP]D\-User\-en|[Pp][Dd]\-user\-[a-z]+|" .
				 "[Pp][Dd]\-self|[Pp]D\-Self|[Pp]D\-author\-w|[Ss]elf2?|[Mm]ultilicense[ _]" .
				 "+requiring[ _]+placeholder(?:[ _]+new)?|[Gg]FDL\-[Uu]ser.*?|[Gg]FDL\-self.*?|" .
				 "[Pp]D\/Oma)\s*([\}\|])/u", $ci->get_text()) || preg_match(
			"/" . Cleanup_Shared::LICENSE_HEADER . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			 "\s*\{\{\s*(?:[Pp][Dd]\-user\|[^\{\}\[\]\|]+\|(?:\s*2\s*\=)?\s*[a-z\-]+)\s*\}\}/u", 
			$ci->get_text())) {
			$ci->preg_replace(
				"/(\|[Aa]uthor\s*\=)\s*\.?\s*\{\{\s*[Oo]riginal[ _]+uploader\s*" .
				 "(\|[^\|\}]+|[^\|\}]+|[^\|\}]+\}\}\s*\|)/su", "$1{{user at project$2");
		}
		$ci->iter_replace(
			"/(\{\{\s*[Uu]ser[ _]+at[ _]+project\s*\|\s*(?:1\s*\=\s*)?([^\|\}\=]+)\s*\|\s*(?:2\s*\=\s*)" .
				 "?w(?:ikipedia)?\s*\|\s*(?:3\s*\=\s*)?fi\s*\}\}[\s\S]+)\{\{\s*PD\/Oma\s*(\||\}\})/u", 
				"$1{{PD-user-fi|$2$3");
		$ci->iter_replace(
			"/(\{\{\s*[Uu]ser[ _]+at[ _]+project\s*\|\s*(?:1\s*\=\s*)?([^\|\}]+)\s*\|\s*(?:2\s*\=\s*)?" .
				 "w(?:ikipedia)?\s*\|\s*(?:3\s*\=\s*)?fi\s*\}\}[\s\S]+)\{\{\s*PD\/Oma\s*(\||\}\})/u", 
				"$1{{PD-user-fi|1=$2$3");
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @return Author_Information|null
	 */
	private function read_uploader_information(Cleanup_Instance $ci) {
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		
		if (!$t) {
			return;
		}
		
		$authorfield = $t->fieldvalue(Cleanup_Shared::AUTHOR);
		if ($authorfield) {
			$author_information = new Author_Information();
			
			$original_description_page_present = !!$this->template_utils->get_all_templates(
				$ci->get_text(), XmlTemplate::get_by_name("Original description page"));
			
			$uploader_re_2 = "/Original uploader was \[\[\:(?:w[a-z]+\s*\:\s*)?(" .
				 $this->constants["langlinks_regex"] .
				 ")\s*:User\s*:\s*([^\|\]]+)\s*\|\s*\\2\s*\]\] at \[(?:https?\:)?\/\/\\1\.(w[a-z]+)" .
				 "\.org\/? \\1\.\\3\]/u";
			$uploader_re_3 = "/Original(?:ly)? uploaded by \[\[\:(?:w[a-z]+\s*\:\s*)?(" .
				 $this->constants["langlinks_regex"] .
				 ")\s*(?::User)?\s*:\s*([^\]\|]+)\s*\|\s*\\2\s*\]\](?: at \[(?:https?\:)?\/\/\\1\." .
				 "(w[a-z]+)\.org\/? \\1\.\\3\]\.?)?/u";
			$uploader_re_5 = "/^\s*\[\[\s*\:\s*(?:w[a-z]+\s*\:\s*)?(" .
				 $this->constants["langlinks_regex"] .
				 ")\s*\:[^\:\|\]\}]+\:\s*([^\:\|\]\}]+)\s*\|\s*\\2\s*\]\] at \[(?:https?\:)" .
				 "?\/\/\\1\.(w[a-z]+)\.org\/? \\1\.\\3\]/u";
			$uploader_re_6a = "/\{\{\s*(?:original[ _]+uploader)\s*\|\s*(?:1\s*\=\s*)?" .
				 "(.+?)\s*\|\s*(?:2\s*\=\s*)?(.+?)\s*\|\s*(?:3\s*\=\s*)?(.+?)\s*\}\}/u";
			$uploader_re_6b = "/\{\{\s*(?:user[ _]+at[ _]+project)\s*\|\s*(?:1\s*\=\s*)?" .
				 "(.+?)\s*\|\s*(?:2\s*\=\s*)?(.+?)\s*\|\s*(?:3\s*\=\s*)?(.+?)\s*\}\}/u";
			
			foreach ($this->self_author_regexes as $regex) {
				if (preg_match($regex, $authorfield, $match)) {
					$author_information->set_indicated_author(
						preg_match("/^\s*\*?\s*$/u", preg_replace($regex, "", $authorfield, 1)));
					
					$author_information->set_self_replace(
						$author_information->get_indicated_author() &&
							 $original_description_page_present);
					
					$author_information->set_username($match["user"]);
					$author_information->set_project(
						isset($match["project"]) ? $match["project"] : "wikipedia");
					$author_information->set_language($match["lang"]);
					
					$original = @$match["original"];
					if ($original) {
						$t->updatefield(Cleanup_Shared::AUTHOR, $original);
						$ci->set_text($t->wholePage());
							
						return $author_information;
					}
				}
			}
			
			if (preg_match($uploader_re_2, $authorfield, $matches)) {
				$author_information->set_indicated_author(
					preg_match_array($this->self_author_regexes, $authorfield) !== false &&
						 $original_description_page_present);
				$author_information->set_username($matches[2]);
				$author_information->set_project($matches[3]);
				$author_information->set_language($matches[1]);
				$authorfield = iter_replace($uploader_re_2, 
					"{{original uploader|1=" .
						 escape_preg_replacement($author_information->get_username()) . "|2=" .
						 $author_information->get_project() . "|3=" .
						 $author_information->get_language() . "}}", $authorfield);
				$t->updatefield(Cleanup_Shared::AUTHOR, $authorfield);
			}
			
			if (preg_match($uploader_re_3, $authorfield, $matches)) {
				$author_information->set_self_replace(
					preg_match("/^\s*\*?\s*$/u", preg_replace($uploader_re_3, "", $authorfield, 1)));
				$author_information->set_username($matches[2]);
				$author_information->set_project("wikipedia");
				$author_information->set_language($matches[1]);
				$authorfield = iter_replace($uploader_re_3, 
					"{{original uploader|1=" .
						 escape_preg_replacement($author_information->get_username()) . "|2=" .
						 $author_information->get_project() . "|3=" .
						 $author_information->get_language() . "}}", $authorfield);
				$t->updatefield(Cleanup_Shared::AUTHOR, $authorfield);
			}
			if (preg_match($uploader_re_5, $authorfield, $matches)) {
				$author_information->set_indicated_author(
					preg_match("/^\s*\*?\s*$/u", preg_replace($uploader_re_5, "", $authorfield, 1)));
				$author_information->set_self_replace($author_information->get_indicated_author());
				$author_information->set_username($matches[2]);
				$author_information->set_project($matches[3]);
				$author_information->set_language($matches[1]);
				$authorfield = iter_replace($uploader_re_5, 
					"{{user at project|1=" .
						 escape_preg_replacement($author_information->get_username()) . "|2=" .
						 $author_information->get_project() . "|3=" .
						 $author_information->get_language() . "}}", $authorfield);
				$t->updatefield(Cleanup_Shared::AUTHOR, $authorfield);
			}
			if (preg_match($uploader_re_6a, $authorfield, $matches)) {
				$author_information->set_self_replace(
					preg_match("/^\s*\*?(?:\s*\.\s*Later version\(s\) were uploaded by .+?)?\s*$/u", 
						preg_replace($uploader_re_6a, "", $authorfield, 1)));
				$author_information->set_username($matches[1]);
				$author_information->set_project($matches[2]);
				$author_information->set_language($matches[3]);
			}
			if (preg_match($uploader_re_6b, $authorfield, $matches)) {
				$author_information->set_indicated_author(
					preg_match("/^\s*\*?\s*$/u", preg_replace($uploader_re_6b, "", $authorfield, 1)));
				$author_information->set_self_replace($author_information->get_indicated_author());
				$author_information->set_username($matches[1]);
				$author_information->set_project($matches[2]);
				$author_information->set_language($matches[3]);
			}
			
			/* i.e., we have made a change: save it */
			if ($author_information->get_username() !== null) {
				$ci->set_text($t->wholePage());
				return $author_information;
			}
		}
	}
}