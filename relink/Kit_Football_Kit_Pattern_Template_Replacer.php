<?php
/** 
 * 
 * @author magog
 *
 */
class Kit_Football_Kit_Pattern_Template_Replacer extends Kit_Replacer {

	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Kit_Replacer::replace_templates()
	 */
	protected function replace_templates($text, $type, $old_match, $new_match) {
		$value_matcher = "/^(\s*)_" . str_replace("_", "[_\s]+", preg_quote($old_match, "/")) .
			 "(\s*)$/";
		$escaped_replacement = "$1_" . escape_preg_replacement($new_match)."$2";
		$it = new TemplateIterator($text);
		
		foreach ($it as $template) {
			if ($template->getname() === "Football kit/pattern") {
				$val = $template->fieldvalue(2);
				$new_val = preg_replace($value_matcher, $escaped_replacement, $val);
				if ($new_val !== $val) {
					$fields = $template->__get("fields");
					$fields[2] = $new_val;
					$template->__set("fields", $fields);
					$text = $template->wholePage();
					$new_str = $this->replace_templates($text, $type, $old_match, $new_match);
					return $new_str !== null ? $new_str : $text;
				}
			}
		}
		
		return null;
	}
}