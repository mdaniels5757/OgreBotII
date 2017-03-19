<?php
/**
 * English Wikipedia bug wherein the description is duplicated
 * @author magog
 *
 */
class Cleanup_Duplicate_Transfer_Text implements Cleanup_Module {
	
	/**
	 *
	 * @var string[]
	 */
	private $replacements = ["/\{\{\!\}\}([^\|\]\]]*)\|\]\]/" => "|$1]]",
			"/\{\{\!\}\}/" => "|"
	];
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $tracker) {
		
		$information_template = $tracker->get_template(Cleanup_Shared::INFORMATION);
		if ($information_template) {
			$description = $this->information_field_get($information_template, "description");
			if ($description !== null) {
				$description_replaced = preg_replace(array_keys($this->replacements), 
					array_values($this->replacements), $description);
				preg_match(
					"/^(\{\{\s*[Ee]n\s*\|\s*(?:1\s*\=\s*)?)([\S\s]*)(\<br\/\>\r?\n\=\=\s*\{\{" .
						 "\s*int:filedesc\s*\}\}\s*\=\=\s*\n\s*\\2\s*)(\}\}\s*)$/mu", 
						$description_replaced, $match);
				if ($match) {
					$description = substr($description, 0, 
						strlen($description) - strlen($match[4]) - strlen($match[3])) . $match[4];
					$this->information_field_set($information_template, "description", $description);
					$tracker->set_text($information_template->wholePage());
				}
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