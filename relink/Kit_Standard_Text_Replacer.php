<?php
/** 
 * 
 * @author magog
 *
 */
class Kit_Standard_Text_Replacer extends Kit_Replacer {

	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Kit_Replacer::replace_templates()
	 */
	protected function replace_templates($text, $type, $old_match, $new_match) {
		$name_matcher = "/^\s*([ah\d]_)?pattern_$type\d*\s*$/";
		$value_matcher = "/^(\s*)_" . str_replace("_", "[_\s]+", preg_quote($old_match, "/")) .
			 "(\s*)$/";
		$escaped_replacement = "$1_" . escape_preg_replacement($new_match)."$2";
		$it = new Template_Iterator($text);
		
		$change = false;
		foreach ($it as $template) {
			$fields = $template->__get("fields");
			foreach ($fields as $name => $val) {
				if (preg_match($name_matcher, $name)) {
					$val = $template->fieldvalue($name);
					$new_val = preg_replace($value_matcher, $escaped_replacement, $val);
					if ($new_val !== $val) {
						$change = true;
						$template->updatefield($name, $new_val);
					}
				}
			}
			if ($change) {
				$text = $template->wholePage();
				$new_str = $this->replace_templates($text, $type, $old_match, $new_match);
				return $new_str !== null ? $new_str : $text;
			}
		}
		
		return null;
	}
}