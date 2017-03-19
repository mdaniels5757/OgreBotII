<?php
class Nowiki_Element_Type extends Unparsed_Xml_Element_Type {

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Xml_Element_Type::get_name()
	 */
	public function get_name() {
		return "nowiki";
	}

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Element_Type::modify_text()
	 */
	public function modify_text(&$text) {
		$text = preg_replace("/\s+/", " ", $text);
	}

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Element_Type::get_replace_string()
	*/
	public function get_replace_string() {
		return "NOWIKI";
	}
}