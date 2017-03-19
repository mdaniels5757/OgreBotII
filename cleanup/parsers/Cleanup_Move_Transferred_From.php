<?php
/**
 * Move the Transferred From text to the top of the original upload log
 * @author magog
 *
 */
class Cleanup_Move_Transferred_From implements Cleanup_Module {
	
	/**
	 * 
	 * @var array
	 */
	private $constants;
	
	/**
	 *
	 * @var string
	 */
	private $original_upload_log_regex;
	
	/**
	 *
	 * @var string[]
	 */
	private $transferred_from_regexes;
	
	/**
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->constants = $cleanup_package->get_constants();
		$this->original_upload_log_regex = "/^(\=\=)\s*\{\{\s*" .
			 regexify_template("original upload log") . "\s*\}\}\s*\\1\s*\n/m";
		
		$transferred_from_template = "(?<transferred>\{\{\s*" .
			 regexify_template(["Transferred from", "Wikivoyage"]) . "\s*\|.*?\}\})";
		$this->transferred_from_regexes = [];
		$this->transferred_from_regexes[] = "/^\s*$transferred_from_template\.?\s*" .
			 Cleanup_Shared::BR . "(?:\s*(?<foot>))?/";
		$this->transferred_from_regexes[] = "/" . Cleanup_Shared::BR . "\.?\s*\*?\s*\(\s*" .
			 $transferred_from_template . "\s*\)\s*?\.?(?<foot>\s*)$/";
		$this->transferred_from_regexes[] = "/" . Cleanup_Shared::BR . "\.?\s*" .
			 $transferred_from_template . "\s*?\*?(?<foot>\s*)$/";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $tracker) {
		/**
		 * {{Transferred from}}
		 */
		$tracker->preg_replace(
			"/Transferr?ed from \[(?:https?\:)?\/\/([a-z\-]+\.w[a-z]+)\.org\/? \\1\]" .
			"(?:(?:; transferr?ed)? to Commons)?(?: by \[\[\:?(?:((?:" . $this->constants["langlinks_regex"] .
			")(?::w[a-z]*)?):)?User:([^\]\|]+)(?:\|\\3)?]\])?(?: using (\[[^\]\|]+\]))?\.?/u",
			"{{transferred from|1=$1|2=$3|3=$4|localuser=$2}}");
		$tracker->preg_replace(
			"/\{\{transferred from\|1\=(.*?)\|2\=\|3\=\|localuser\=}}; [Tt]ransfer was stated to be" .
			" made by \[\[\:?(?:((?:" . $this->constants["langlinks_regex"] .
			")(?::w[a-z]*)?):)?User:([^\]\|]+)\]\](?: using (\[[^\]\|]+\]))?\.?/u",
			"{{transferred from|1=$1|2=$3|3=$4|localuser=$2}}");
		$tracker->preg_replace(
			"/(\()?original(?:ly)? uploaded on ([a-z\-]+.w[a-z]+)(\)?) \(transferr?ed by\s+\[\[" .
			"\s*user\s*:\s*([^\]\|]+)\s*\|\s*\\4\s*\]\]\)/iu",
			"$1{{transferred from|1=$2|2=$4}}$3");
		$tracker->preg_replace("/(\()?original(?:ly)? uploaded on ([a-z\-]+.w[a-z]+)(\)?)/iu",
			"{{transferred from|$2}}");
		$tracker->preg_replace(
			"/Transferr?ed from \[(?:https?\:)?\/\/([a-z\-]+\.w[a-z]+)\.org\/? \\1\]/u",
			"{{transferred from|$1}}");
		$tracker->preg_replace(
			"/Transferr?ed from https?:\/\/([a-z\-]+\.w[a-z]+)\.org\/?([^A-Za-z\%\/])/u",
			"{{transferred from|$1}}$2");
		
		$tracker->preg_replace(
			"/\{\{\s*[Tt]ransferred[ _]+from\s*\|\s*[^\}\|]+\s*\|\s*(?:2\s*\=\s*?)?[Bb]oteas\s*\|[^\}]+\}\}" .
			Cleanup_Shared::OPT_BR . "\s*(\{\{\s*[Oo]riginal[ _]+text\s*\|)/u", "$1", false);
		$tracker->preg_replace(
			"/\{\{\s*[Tt]ransferred[ _]+from\s*\|\s*[^\}\|]+\s*\}\}" . Cleanup_Shared::OPT_BR .
			"\s*(\(?\{\{\s*[Oo]riginal[ _]+text\s*\|)/u", "$1", false);
		$tracker->preg_replace(
			"/\{\{\s*[Tt]ransferred[ _]+from\s*\|\s*(.+?)\s*\|\s*(.+?)\s*\|\s*(3\=)?\s*\[https?\:\/\//u",
			"{{transferred from|$1|$2|$3[//", false);
		$tracker->preg_replace(
			"/\{\{transferred from\|\s*1\=(.*?)\s*\|\s*2\=(.*?)\s*\|\s*3\=(.*?)\s*\|\s*localuser\s*\=\s*\}\}/u",
			"{{transferred from|1=$1|2=$2|3=$3}}", false);
		$tracker->preg_replace(
			"/\{\{transferred from\|\s*1\=([^\=]*?)\|\s*2\=([^\=]*?)\|\s*3\=([^\=]*?)(\|\s*localuser\=[^\=]*?)?\}\}/u",
			"{{transferred from|$1|$2|$3$4}}", false);
		$tracker->preg_replace("/\{\{transferred from(.*?)\|\s*\}\}/u", "{{transferred from$1}}",
			false);
		$tracker->preg_replace("/\{\{transferred from\|(.*?)\|(.*?)\|\s*(?:3\s*\=)?\s*\}\}/u",
			"{{transferred from|$1|$2}}", false);
		$tracker->preg_replace(
			"/(\{\{transferred from\|.*?\|.*?\|\s*(?:3\s*\=)?\s*)\[(?:https?:)?\/\/(?:tools\.wikimedia" .
			"\.de|toolserver\.org)\/~magnus\/commonshelper\.php ((?i:commonshelper))\](\s*\}\})/u",
			"$1$2$3", false);
		$tracker->preg_replace(
			"/(\{\{transferred from\|.*?\|.*?\|\s*(?:3\s*\=)?\s*)\[\[:en\:WP\:FTCG\s*\|\s*(?i:ftcg|for" .
			" the common good)\s*\]\](\s*\}\})/u", "$1FtCG$2", false);
		$tracker->preg_replace(
			"/(\{\{transferred from\|.*?\|.*?\|\s*(?:3\s*\=)?\s*)\[(?:https?:)?\/\/bots\.wmflabs" .
			"\.org\/~richs\/commonshelper\.php (?i:commonshelper on labs)\](\s*\}\})/u",
			"$1CommonsHelperLabs$2", false);
		
		$information_template = Template::extract($tracker->get_text(), Cleanup_Shared::INFORMATION);
		if (!$information_template) {
			return;
		}
		$after = $information_template->__get("after");
		preg_match($this->original_upload_log_regex, $after, $original_upload_log_match, 
			PREG_OFFSET_CAPTURE);
		
		if (!$original_upload_log_match) {
			return;
		}
		
		$source = $this->information_field_get($information_template, Cleanup_Shared::SOURCE);
		if (!$source) {
			return;
		}
		foreach ($this->transferred_from_regexes as $transferred_from_regex) {
			if (preg_match($transferred_from_regex, $source, $match, PREG_OFFSET_CAPTURE)) {
				
				$foot = @$match["foot"];
				
				$start = $match[0][1];
				$end = $foot ? $foot[1] : $match[0][1] + strlen($match[0][0]);
				$after_index = $original_upload_log_match[0][1] +
					 strlen($original_upload_log_match[0][0]);
				
				$source = substr($source, 0, $start) . substr($source, $end);
				$after = substr($after, 0, $after_index) . $match["transferred"][0] . " " .
					 substr($after, $after_index);
				$this->information_field_set($information_template, Cleanup_Shared::SOURCE, $source);
				$information_template->__set("after", $after);
				
				$tracker->set_text($information_template->wholePage());
				return;
			}
		}
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
	

	/**
	 *
	 * @param Template $template
	 * @param string|int $fieldname
	 * @param string $val
	 * @param bool $manipulate_newline
	 *        	[OPTIONAL] default true
	 */
	private function information_field_set(&$template, $fieldname, $val,
		$manipulate_newline = true) {
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
			} else {
				if ($manipulate_newline)
					$val = preg_replace("/(?:\s*?\\n)?$/u", "\r\n", $val);
					$field = $uc;
			}
	
			if (trim($val) === "" && !$template->fieldisset($field)) {
				return "";
			}
	
			return $template->updatefield($field, $val);
	}
}