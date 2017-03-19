<?php

/**
 * 
 * @author magog
 *
 */
abstract class Whitespace_Preserve_Template_Logic implements Template_Logic {
	
	/**
	 *
	 * @return bool
	 */
	protected abstract function is_add_carriage_return_new_field();
	
	/**
	 *
	 * {@inheritDoc}
	 * @see Template_Logic::read_value()
	 */
	public function read_value($value, $original_name, Template $template) {
		return mb_trim($value);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Logic::write_value()
	 */
	public function write_value($value, $original_name, Template $template) {
		global $MB_WS_RE_OPT;
		
		$value = mb_trim($value);
	
		$original_value = $template->fieldvalue($original_name);
		if ($original_value !== false) {
			preg_match("/^([\p{Z}\s]*)[\S\s]*?([\p{Z}\s]*)$/u", $original_value, $whitespace_match);
			return "$whitespace_match[1]$value$whitespace_match[2]";
		}
	
		if ($this->is_add_carriage_return_new_field()) {
			return preg_replace("/(?:\s*?\\n)?$/u", "\r\n", $value);
		}
	
		return $value;
	}
}