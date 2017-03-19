<?php
/**
 * 
 * @author magog
 *
 */
abstract class Infobox_Style_Template_Logic extends Whitespace_Preserve_Template_Logic {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Logic::get_field_name_map()
	 */
	public function get_field_name_map($field, Template $template) {
		if (strpbrk($field, " _") !== false) {
			$field = str_replace("_", " ", $field);
			return [
					lcfirst(str_replace(" ", "_", $field)), //lc underscore
					ucfirst(str_replace(" ", "_", $field)), //uc underscore
					lcfirst($field), //lc
					ucfirst($field) //uc
			];
		}
		
		return [
			lcfirst($field), //lc
			ucfirst($field) //uc
		];
	}
}