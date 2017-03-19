<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Original_Uploader implements Cleanup_Module {

	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		// rm original uploader if other author info present, and it's in the upload log
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($t) {
			$author = $t->fieldvalue(Cleanup_Shared::AUTHOR);
			if ($this->remove_original_uploader($author, $ci->get_text(), false)) {
				$t->updatefield(Cleanup_Shared::AUTHOR, $author);
				$ci->set_text($t->wholePage());
			}
			
			// rm original upload date if other date info present, and it's in the upload log
			$date = $t->fieldvalue(Cleanup_Shared::DATE);
			$re = "/(\S\s*)\s*" . Cleanup_Shared::OPT_BR .
				 "\s*\(\{\{[Oo]riginal upload date\|(\d{4})\-(\d{2})\-(\d{2})\}\}\)(\s*)$/u";
			$re2 = "/(\S\s*)\s*" . Cleanup_Shared::OPT_BR .
				 "\s*\(\{\{Date\|(\d{4})\|(\d{2})\|(\d{2})\}\} \(first version\)\; \{\{Date\|\d{4}\|" .
				 "\d{2}\|\d{2}\}} \(last version\)\)(\s*)$/u";
			if (preg_match($re, $date, $original_date) && preg_match(
				"/\=\=\s*\{\{(?:int\:)?[Oo]riginal upload log\}\}\s*\=\=[\s\S]+$original_date[2]\-" .
					 "$original_date[3]\-$original_date[4]/u", $ci->get_text())) {
				$t->updatefield(Cleanup_Shared::DATE, preg_replace($re, "$1$5", $date));
				$ci->set_text($t->wholePage(), false);
			}
			if (preg_match($re, $date, $original_date) && preg_match(
				"/\=\=\s*\{\{(?:int\:)?[Oo]riginal upload log\}\}\s*\=\=[\s\S]+$original_date[2]" .
					 "\-$original_date[3]\-$original_date[4]/u", $ci->get_text())) {
				$t->updatefield(Cleanup_Shared::DATE, preg_replace($re, "$1$5", $date));
				$ci->set_text($t->wholePage(), false);
			}
			if (preg_match($re2, $date, $original_date) && preg_match(
				"/\=\=\s*\{\{(?:int\:)?[Oo]riginal upload log\}\}\s*\=\=[\s\S]+$original_date[2]" .
					 "\-$original_date[3]\-$original_date[4]/u", $ci->get_text())) {
				$t->updatefield(Cleanup_Shared::DATE, preg_replace($re2, "$1$5", $date));
				$ci->set_text($t->wholePage(), false);
			}
		}
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