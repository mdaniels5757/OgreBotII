<?php
class Comment_Element_Type extends Unparsed_Element_Type {

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Element_Type::find_next()
	 */
	public function find_next(&$text, $start) {
		return strpos($text, "<!--", $start);
	}

	/**
	 * 	(non-PHPdoc)
	 * @see Unparsed_Element_Type::find_close()
	 */
	public function find_close(&$text, $start) {
		$end = strpos($text, "-->", $start);
		return $end !== false ? $end + strlen("-->") : false;
	}


	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Element_Type::get_replace_string()
	 */
	public function get_replace_string() {
		return "COMMENT";
	}
}