<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_By_Author_Type implements Cleanup_Module {
	
	/**
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 *
	 * @var string
	 */
	private $otrs_re;
	
	/**
	 *
	 * @var TemplateUtils
	 */
	private $template_utils;
	
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->constants = $cleanup_package->get_constants();
		$this->otrs_re = "/\{\{\s*" . $this->constants["otrs_regex"] .
			 "\s*\|\s*(?:id\s*\=\s*)?(\S[\s\S]*?)\s*\}\}/u";
		$this->template_utils = $cleanup_package->get_template_utils();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		$own_templates = $this->template_utils->getPredefinedClassTypesMulti($ci->get_text(), "own");
		
		/**
		 * License/authorship/date parameters which indicate uploader NOT the author
		 */
		if ($t && $this->check_license_tags_exclusive(
			$this->constants["non_uploader_licenses_regex"], $ci->get_text(), $t)) {
			$description = $t->fieldvalue(Cleanup_Shared::DESCRIPTION);
			$source = $t->fieldvalue(Cleanup_Shared::SOURCE);
			$author = $t->fieldvalue(Cleanup_Shared::AUTHOR);
			$date = $t->fieldvalue(Cleanup_Shared::DATE);
			$madechange = false;
			if ($source) {
				$source2 = $source;
				preg_replace_track(
					"/^\s*\{\{[ _]*[Tt]ransferred[ _]+from[ _]*[^\{\}]+\}\}\.?(\s*)$/u", "$1", 
					$source2, $madechange);
				$after = $t->__get("after");
				if ($madechange) {
					$source = $source2;
					$trim = trim($source);
					$after = preg_replace(
						"/(\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log\s*\}\}\s*\=+\s*)/u", 
						"$1" . (strlen($trim) > 0 ? escape_preg_replacement($trim) . " " : ""), 
						$after);
					$t->updatefield(Cleanup_Shared::SOURCE, $source2);
					$t->__set("after", $after);
				}
			}
			if ($author) {
				preg_replace_track(
					"/\s*\.?\s*\*?\s*\{\{[ _]*[Oo]riginal[ _]+uploader[ _]*\|[^\{\}\[\]\|]+\|" .
						 "\s*w[a-z]*\s*(?:\|\s*[a-z\-]+\s*)?\}\}\s*?\.?(?:\s*Later version\(s\) were " .
						 "uploaded by .+? at \[https?\:\/\/[a-z\-]+\.w[a-z]+\.org [a-z\-]+\.w[a-z]+" .
						 "\]\.?)?(\s*)$/u", "$1", $author, $madechange);
				$t->updatefield(Cleanup_Shared::AUTHOR, $author);
			}
			if ($date) {
				preg_replace_track(
					"/\s*?\.?\s*?" . Cleanup_Shared::OPT_BR .
						 "(?:\s*\*?\s*\(?(?:\{\{\s*[Oo]riginal[ _]+upload[ _]+date\s*\|\s*[\d\-]+\s*" .
						 "\}\}|\{\{\s*Date[\d\|]+\}\} \(first version\)\; \{\{Date[\d\|]+}} " .
						 "\(last version\))\)?)(\s*)(\s*)/u", "$1", $date, $madechange);
				preg_replace_track(
					"/\s*\d{1,2}\:\d{2}\,? \d{2}\.? [^\s\|\d]+ \d{4} \((?:UTC|CES?T)\)(\s*)/u", "$1", 
					$date, $madechange);
				$t->updatefield(Cleanup_Shared::DATE, $date);
			}
			if (trim($source) === "") {
				if (preg_match(
					"/^\s*\{\{\s*(?:" . $this->constants["langlinks_regex"].
						 ")\s*\|(?:\s*1\s*\=)?\s*(?i:(?:source|retrieved\s*from)\s*[\=\:])?\s*" .
						 "\[?(https?\:\/\/[\w:#@%\/;\$\(\)~_\?\\\+\-\=\.&]+)\]?\s*\}\}(\s*)$/u", 
						$description, $match)) {
					// description field with ONLY the source URL in it
					$t->updatefield(Cleanup_Shared::SOURCE, $match[1] . $source);
					$t->updatefield(Cleanup_Shared::DESCRIPTION, $match[2]);
					$madechange = true;
				}
			}
			if ($madechange) {
				$ci->set_text($t->wholePage());
			}
		}
		
		/**
		 * License/authorship/date parameters which indicate uploader isn't author, but came
		 * through OTRS
		 */
		if ($t && preg_match($this->otrs_re, $ci->get_text()) && $this->check_license_tags_exclusive(
			"(?:[Cc]opyrighted[ _]+free[ _]+use|[Cc][Cc][\-\.a-zA-Z\d]*|[Bb]ild\-by|[Gg][Ff][Dd][Ll]" .
				 "(?!\-user|\-self)[a-zA-Z\-]+)\s*(?:\|[^\|\}\{\]\[]+)?", $ci->get_text(), $t)) {
			foreach ($self_templates as $self) {
				$author = $t->fieldvalue(Cleanup_Shared::AUTHOR);
				if ($this->remove_original_uploader($author, $ci->get_text())) {
					$t->updatefield(Cleanup_Shared::AUTHOR, $author);
					$ci->set_text($t->wholePage());
				}
			}
		} /**
		 * uploader IS the author through self template and thus original uploader -> user at project
		 */
		else if ($t && $own_templates) {
			
			$selfauthor = null;
			foreach ($own_templates as $self) {
				$_selfauthor = $self->fieldvalue(Cleanup_Shared::AUTHOR);
				if ($_selfauthor !== false) {
					$selfauthor = $self->fieldvalue(Cleanup_Shared::AUTHOR);
					if (is_string($selfauthor) && mb_trim($selfauthor)) {
						break;
					}
				}
			}
			
			$madechange = false;
			if ($selfauthor === null) {
				$author = $t->fieldvalue(Cleanup_Shared::AUTHOR);
				preg_replace_track(
					"/^\{\{[ _]*[Oo]riginal[ _]+uploader[ _]*\|\s*(.+?)\s*\|\s*" .
					"(w[a-z]*)\s*(\|\s*[a-z\-]+)?\s*\}\}$/u",
					"{{user at project|$1|$2$3}}$4", $author, $madechange);
				if ($madechange) {
					$t->updatefield(Cleanup_Shared::AUTHOR, $author);
				}
			} else if ($selfauthor && preg_match(
				"/\s*\.?\s*" . Cleanup_Shared::OPT_BR .
					 "\s*\s*\{\{[ _]*[Uu]ser[ _]+at[ _]+project[ _]*\|\s*([^\|\{\}\[\]]+)\s*[\|A-Za-z\-\s]+" .
					 "\}\}\s*/u", $selfauthor, $uploadername)) {
				$uploadername_quote = preg_quote($uploadername[1], "/");
				$author = $t->fieldvalue(Cleanup_Shared::AUTHOR);
				if ($author) {
					preg_replace_track(
						"/^\s*\{\{[ _]*[Oo]riginal[ _]+uploader[ _]*\|\s*($uploadername_quote)\s*\|\s*" .
							 "(w[a-z]+)\s*\|\s*([a-z\-]+)\s*\}\}(\s*)$/u", 
							"{{user at project|$1|$2|$3}}$4", $author, $madechange);
					if ($madechange) {
						$t->updatefield(Cleanup_Shared::AUTHOR, $author);
					}
				}
			}

			if ($madechange) {
				$ci->set_text($t->wholePage());
			}
		}
	}
	private function check_license_tags_exclusive($license_re, $text, $template) {
		$category_links_re = "(?i:" . $this->constants["category_regex"] . ")";
		
		$licenses = "";
		$text = preg_replace($this->otrs_re, "", $text);
		if (preg_match("/(\=+)\s*\{\{int\:license\-header\}\}\s*\\1([\s\S]+?)(?=\s*\=|\s*$)/u", 
			$text, $head_license)) {
			$licenses = preg_replace(
				["/%%%MTONOPARSE_COMMENT\d+%%%s*/u", 
					"/\[\[\:(?:w[a-z]+\:)?(?:" . $this->constants["langlinks_regex"] . ")\:$category_links_re" .
						 "\:[^\]\|\}]+\]\]\s*/u"], "", $head_license[2]);
		}
		if ($template) {
			$licenses .= preg_replace($this->otrs_re, "", 
				$this->information_field_get($template, Cleanup_Shared::PERMISSION));
		}
		return preg_match("/^\s*(\{\{$license_re\s*(?:\|[\s\w\-]*)*\}\}\s*)+$/u", $licenses) ? true : false;
	}
	
	/**
	 *
	 * @param Template $template        	
	 * @param string|int $fieldname        	
	 * @return string|null
	 */
	private function information_field_get(&$template, $fieldname) {
		$field = null;
		$fieldname = str_replace("_", " ", $fieldname);
		$underscore_uc = ucfirst(str_replace(" ", "_", $fieldname));
		$underscore_lc = lcfirst(str_replace(" ", "_", $fieldname));
		$uc = ucfirst($fieldname);
		$lc = lcfirst($fieldname);
		
		if ($template->fieldisset($underscore_lc)) {
			$field = $underscore_lc;
		} else if ($template->fieldisset($underscore_uc)) {
			$field = $underscore_uc;
		} else if ($template->fieldisset($lc)) {
			$field = $lc;
		} else if ($template->fieldisset($uc)) {
			$field = $uc;
		}
		
		if ($field !== null) {
			return $template->fieldvalue($field);
		}
		return null;
	}
	


	private function remove_original_uploader(&$author_field, $wholePage, $allow_blank_field = true) {
		$re = "/([^\.\s]\s*\.*?)" . ($allow_blank_field ? "?" : "") . "\s*\.?\s*?" .
			 Cleanup_Shared::OPT_BR .
			 "\s*?\(?\s*\{\{original uploader\|\s*?([^\[\]\|\{\}]+)\s*?\|(w[a-z]+)\|" .
			 "([a-z\-]+)\}\}\s*?\)?\s*?\.?(?:\s*Later version\(s\) were uploaded by .+?" .
			 " at \[https?\:\/\/[a-z\-]+\.w[a-z]+\.org [a-z\-]+\.w[a-z]+\]\.?)?(\s*)$/u";
		$re2 = "/([^\.\s]\s*\.*?)\s*?\.?\s*?" . Cleanup_Shared::OPT_BR .
			 "(?:\s*Later version\(s\) were " .
			 "uploaded by .+? at \[https?\:\/\/[a-z\-]+\.w[a-z]+\.org [a-z\-]+\.w[a-z]" .
			 "+\]\.?)(\s*)$/u";
		
		$made_change = false;
		if (preg_match($re, $author_field, $original_uploader) && preg_match(
			"/\=\=\s*\{\{(?:int\:)?[Oo]riginal upload log\}\}\s*\=\=[\s\S]+" . "\[\[\:(?:w[a-z]+\:)?" .
				 preg_quote($original_uploader[4], "/") . "\:[Uu]ser\:" .
				 preg_quote($original_uploader[2], "/") . "(?:\||\]\])/u", $wholePage)) {
			preg_replace_track($re, "$1$5", $author_field, $made_change);
		}
		preg_replace_track($re2, "$1$2", $author_field, $made_change);
		return $made_change;
	}
}