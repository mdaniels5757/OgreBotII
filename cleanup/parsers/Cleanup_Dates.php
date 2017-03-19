<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Dates implements Cleanup_Module {
	
	/**
	 * 
	 * @var string[]
	 */
	private $date_fields;
	
	/**
	 *
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 *
	 * @var Cleanup_Submodule[]
	 */
	private $modules;
	
	/**
	 *
	 * @var Template_Cache $template_cache
	 */
	private $template_cache;
		
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
		$this->template_cache = new Template_Cache($this->template_factory);
		$this->constants = $cleanup_package->get_constants();
		$this->modules = [
			"localized_dates_parser" => new Cleanup_Localized_Dates_Parser($this->constants), 
			"cleanup_numerical_dates" => new Cleanup_Numerical_Dates(), 
			"templated_dates" => new Cleanup_Templated_Dates()];
		
		//const...
		$initial_date_fields = [
			"Information" => ["date", "Date"],
			"Information2" => ["date", "Date"],
			"Flickr" => ["taken"]
		];
		$this->date_fields = array_merge_all(
			array_map_pass_key($initial_date_fields, 
				function ($name, $fields) {
					return array_fill_keys(XmlTemplate::get_by_name($name)->get_aliases_and_name(), 
						$fields);
				}));
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		
		foreach ($this->date_fields as $template_name => $date_fields) {
			$t = $ci->get_template($template_name);
			
			if (!$t) {
				continue;
			}
			
			/**
			 * Original upload date (only change when it's in the date field)
			 */
			foreach ($date_fields as $date_field) {
				$date = $t->fieldvalue($date_field);
				if ($date !== false) {
					break;
				}
			}
			
			if ($date === false) {
				continue;
			}
			
			$date_tracker = new Full_Cleanup_Instance($date, false, null, null, 
				$this->template_cache);
			$date_tracker->preg_replace(
				"/(?:\\{\\{\s*[Dd]ate\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\}\}|(\d{4})\-" .
					 "(\d{2})\-(\d{2})) \((?i:original upload date)\)/u", 
					"{{original upload date|$1$4-$2$5-$3$6}}");
			$date_tracker->preg_replace(
				"/\\{\\{\s*[Dd]ate\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\|\s*(\d+)\s*\}\} " .
					 "\(first version\)\; {\{\s*[Dd]ate\s*\|\s*\\1\s*\|\s*\\2\s*\|\s*\\3\s*\}\} " .
					 "\(last version\)/u", "{{original upload date|$1-$2-$3}}");
			$date_tracker->preg_replace(
				"/(\d{4})\-(\d{2})\-(\d{2}) \(first version\)\; (\d{4})\-(\d{2})\-(\d{2}) " .
					 "\(last version\)/u", "{{original upload date|$1-$2-$3}}");
			
			/**
			 * century
			 */
			$date_tracker->preg_replace(
				"/^\s*(\d{1,2})(?:(?:st|nd|rd|th)?\s*century|\.\s*(?:století|århundrede|jahrhundert" .
					 "|sajand|stoljeće|évszázad|johrhunnert|e eeuw)|ος αιώνας|\s*سدهٔ|\s*המאה ה|世紀|" .
					 "-ആം നൂറ്റാണ്ട്)\s*?" . Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "$/iu", 
					"{{other date|century|$1}}$2");
			$century_regexes = [
				"/^\s*([XVI]+)(?i:[èe]\s+siècle|\s+sec.|\s+век|\s+wiek|\s+century|\s+siglo)\s*?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "$/ui", 
					"/^\s*(?:siglo|secle|século|century)\s+([XVI]+)\s*?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "$/iu"];
			$date_tracker->preg_replace_callback($century_regexes, 
				function ($match) {
					return "{{other date|century|" . roman_to_numeral($match[1]) . "}}$match[2]";
				});
			
			/**
			 * decade(s)
			 */
			$date_tracker->preg_replace(
				"/^(\s*)(\d?\d[1-9]0)\s*s\s*?" . Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE .
					 "$/iu", "{{other date|s|$2}}$3");
			
			$this->modules["localized_dates_parser"]->cleanup($date_tracker);
			
			/**
			 * or (years)
			 */
			$date_tracker->preg_replace(
				"/^(\s*)(\d{4})\s*(?i:or|أو|o|nebo|eller|oder|ή|ó|või|یا|tai|ou|או|vagy" .
					 "|または|или|അഥവാ|of|lub|ou|sau|или|ali|หรือ|和)\s*?(\d{4})" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", 
					"{{other date|or|$2|$3}}$4");
			
			/**
			 * between (years)
			 */
			$date_tracker->preg_replace(
				"/^(\s*)(?i:sometime )?(?i:between)\s*(\d{4})\s*(?i:and|\-)?\s*?(\d{4})" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "{{between|$2|$3}}$4");
			$between_regexes = [
				"/^\s*(\d{4})\s*\-\s*(\d{4})" . Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", 
				"/^\s*(?i:between)\s*(\d{4})\s*(?i:and)\s*(\d{4})" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u"];
			$date_tracker->preg_replace_callback($between_regexes, 
				function ($match) {
					if ($match[2] > $match[1] && $match[2] < 2100) {
						// bizarre "third" syntax is for Eclipse formatting bug workaround
						return "{{between|$match[1]|$match[2]}}$match[3]";
					}
					// nevermind!
					return $match[0];
				});
			
			$date_tracker->preg_replace(
				"/^(\s*)\{\{\s*((?:[Oo]ther[ _]+date\s*\|)?\s*[Bb]etween)\s*\|\s*(.+?)\}\}\s*?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "{{" . "$2|$3}}$4");
			
			// FtCG annoyance
			$date_tracker->preg_replace(
				"/(\s*)\{\{\s*[Ii]SOdate\s*\|\s*([\d\-]+)\s*\}\} \(from metadata\)(\s*)/u", 
				"$1{{according to EXIF|$2}}$3");
			
			/* originally uploaded at Commons information is useless; it's already in the log on the screen */
			$date_tracker->preg_replace(
				"/(?:Uploaded on Commons at [\d\-]+ [\d:]+ \(UTC\)\/)?Original(?:ly)? uploaded at ([\d\-]+) [\d:]+/u", 
				"{{original upload date|$1}}");
			$date_tracker->preg_replace(
				"/{{\s*[Oo]riginal[ _]+upload[ _]+date\s*\|\s*(\d+)\-(\d)\-/u", 
				"{{original upload date|$1-0$2-");
			$date_tracker->preg_replace("/\(Original uploaded at ([\d\-]+) [\d\:]+\)/u", 
				"({{original upload date|$1}})");
			$date_tracker->preg_replace(
				"/{{\s*[Oo]riginal[ _]+upload[ _]+date\s*\|\s*(\d+)\-(\d+)\-(\d)\s*(\||\}\})/u", 
				"{{original upload date|$1-$2-0$3$4");
			
			foreach (range(1, 12) as $month) {
				$month_re = $this->constants["month_${month}_regex"];
				$month_two_digits = $month < 10 ? "0$month" : $month;
				$this->smart_date_replace($date_tracker, $month_re, $month_two_digits);
			}
			
			if (strpos($ci->get_text(), "{{original description|de.wikipedia|") !== false) {
				$date_tracker->preg_replace(
					"/^(\s*)(2\d|3[0-1])[\.\-\,\\\\\/ ]+(\d{2})[\.\-\,\\\\\/ ]+(0\d)" .
						 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "\${1}20$4-$3-$2$5");
				$date_tracker->preg_replace(
					"/^(\s*)(2\d|3[0-1])[\.\-\,\\\\\/ ]+(\d{2})[\.\-\,\\\\\/ ]+(1\d)" .
						 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "\${1}20$4-$3-$2$5");
			}
			
			$force_dmy = strpos($ci->get_text(), "== {{Original upload log}} ==") !== false && preg_match(
				"/\{\{original description(?: page)?\|(?:ca|de|fi|ga|nl|he|it|sr|sv)\.w[a-z]+\|/u", 
				$ci->get_text());
			$this->modules["cleanup_numerical_dates"]->cleanup($date_tracker, $force_dmy);
			$this->modules["templated_dates"]->cleanup($date_tracker);
			
			$date_tracker->preg_replace(
				"/^(\s*)(?i:unknown?(?:\s*date)?|\?)" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "$1{{unknown|date}}$2", 
					false);
			
			// remove superfluous parens around field
			/* not a major change */
			$date_tracker->preg_replace("/^\s*\(([^\(\)]+)\)(\s*)$/u", "$1$2", false);
			
			/**
			 * Axe the whole "let's put the current upload timestamp in the date field stupidity
			 */
			// in order to accomplish, we need to know the upload date; get it from command
			// line arguments (this assumes no more than one date handled at a time)
			
			$day = 0 + substr($ci->get_upload_time(), 6, 2);
			$timestamp_re = "[\d\: ]+\,? " . (($day < 10) ? "0?" : "") . "$day " .
				 date("F", mktime(0, 0, 0, substr($ci->get_upload_time(), 4, 2), 1, 2000)) . " " . substr(
					$ci->get_upload_time(), 0, 4);
			$uploadtime_re = "/^(\s*)" . substr($ci->get_upload_time(), 0, 4) . "\-" . substr(
				$ci->get_upload_time(), 4, 2) . "\-" . (($day < 10) ? "0?" : "") .
				 "$day [\d\:]+\s*\(UTC\) \s*\(?(\{\{original upload date\|[\d\-]+\}\}|\{\{Date[\d\|]+\}\}" .
				 " \(first version\)\; \{\{Date[\d\|]+}} \(last version\))\)?(\s*)$/u";
			$uploadtime_re2 = "/^(\s*)$timestamp_re\s*\(UTC\)\s*" . Cleanup_Shared::OPT_BR .
				 "\s*\(?(\{\{original upload date\|[\d\-]+\}\}|\{\{Date[\d\|]+\}\} \(first version\)\;" .
				 " \{\{Date[\d\|]+}} \(last version\))\)?(\s*)$/u";
			
			$date_tracker->preg_replace($uploadtime_re, "$1$2$3");
			$date_tracker->preg_replace($uploadtime_re2, "$1$2$3");
			
			if (preg_match("/^\s*taken\s+on\s+(.+?)(\s*)$/u", $date_tracker->get_text(), $match)) {
				$taken_date = $match[1];
				if ($this->parse_date($taken_date)) {
					$date_tracker->set_text("{{taken on|$taken_date}}$match[2]");
				}
			} else if (preg_match("/^\s*taken\s+in\s+(.+?)(\s*)$/u", $date_tracker->get_text(), 
				$match)) {
				$taken_date = $match[1];
				if ($this->parse_date($taken_date)) {
					$date_tracker->set_text("{{taken in|$taken_date}}$match[2]");
				}
			}
			
			// put variables back together
			$t->updatefield($date_field, $date_tracker->get_text());
			$ci->set_text($t->wholePage(), $date_tracker->get_significant_changes());
		}
	}
	
	/**
	 *
	 * @param Cleanup_Instance $replacement_tracker        	
	 * @param string $month_re        	
	 * @param string $month_no_two_digits        	
	 * @return void
	 */
	private function smart_date_replace(Cleanup_Instance $replacement_tracker, $month_re, 
		$month_no_two_digits) {
		$replacement_tracker->preg_replace(
			[
				"/\s*(\d{1,2}\:\d{2}\,? (\d{1,2})\.? (?i:$month_re) (\d{4}) \((?:UTC|CES?T)\))\s*?" .
					 Cleanup_Shared::OPT_BR .
					 "\s*?\(?{{\s*[Oo]riginal[ _]+upload[ _]+date\s*\|\s*\\3\-" .
					 "($month_no_two_digits)\-0?\\2\s*\}\}\)?/u", 
					"/\s*(\d{1,2}\:\d{2}\,? (\d{1,2})\.? (?i:$month_re) (\d{4}) \((?:UTC|CES?T)\))\s*" .
					 Cleanup_Shared::OPT_BR .
					 "\s*\(?{{\s*[Dd]ate\s*\|\s*\\3\s*\|\s*($month_no_two_digits)" .
					 "\s*\|\s*0?\\2\s*\}\}( \(first version\); {{Date\|\d+\|\d+\|\d+\}\} \(last " .
					 "version\))\)?/u"], "$1");
		$replacement_tracker->preg_replace(
			[
				"/^\s*(\d{1,2})(?:st|nd|rd|th)?[\.\-\,\\\\\/ ]+(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", 
					"/^\s*(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{1,2})(?:st|nd|rd|th)?[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u"], 
			"$2-$month_no_two_digits-$1$3");
		$replacement_tracker->preg_replace(
			[
				"/^\s*(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", 
					"/^\s*(\d{4})[\.\-\,\\\\\/ ]+(?i:$month_re)(?:\s*г(?:\.|ода?)?)?" .
					 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u"], 
			"$1-$month_no_two_digits$2");
		$replacement_tracker->preg_replace(
			"/^\s*(\d{4})[\.\-\,\\\\\/ ]+(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{1,2})(?:st|nd|rd|th)?(?:\s*г(?:\.|ода?)?)?" .
				 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", 
				"$1-$month_no_two_digits-$2$3");
		$replacement_tracker->preg_replace(
			"/^(\s*)(\d{4})\-(\d{2})\-(\d)(?:\s*г(?:\.|ода?)?)?(\s*)$/u", "$1$2-$3-0$4$5");
	}
	
	/**
	 * 
	 * @param string $text
	 * @return bool
	 */
	private function parse_date(&$text) {
		$madechange = false;
		foreach (range(1, 12) as $month) {
			$month_re = $this->constants["month_${month}_regex"];
			$month_two_digits = $month < 10 ? "0$month" : $month;
			preg_replace_track(
				"/^\s*(\d{1,2})(?:st|nd|rd|th)?[\.\-\,\\\\\/ ]+(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?/u", 
				"$2-$month_two_digits-$1$3", $text, $madechange);
			preg_replace_track(
				"/^\s*(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{1,2})(?:st|nd|rd|th)?[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?/u", 
				"$2-$month_two_digits-$1$3", $text, $madechange);
			preg_replace_track("/^\s*(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{4})(?:\s*г(?:\.|ода?)?)?/u", 
				"$1-$month_two_digits$2", $text, $madechange);
			preg_replace_track("/^\s*(\d{4})[\.\-\,\\\\\/ ]+(?i:$month_re)(?:\s*г(?:\.|ода?)?)?/u", 
				"$1-$month_two_digits$2", $text, $madechange);
			preg_replace_track(
				"/^\s*(\d{4})[\.\-\,\\\\\/ ]+(?i:$month_re)[\.\-\,\\\\\/ ]+(\d{1,2})(?:st|nd|rd|th)?(?:\s*г(?:\.|ода?)?)?/u", 
				"$1-$month_two_digits-$2$3", $text, $madechange);
			preg_replace_track("/^(\s*)(\d{4})\-(\d{2})\-(\d)(?:\s*г(?:\.|ода?)?)?(\s*)$/u", 
				"$1$2-$3-0$4$5", $text, $madechange);
			if ($madechange) {
				return true;
			}
		}
		return false;
	}
}