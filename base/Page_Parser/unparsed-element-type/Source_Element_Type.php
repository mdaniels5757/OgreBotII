<?php 

class Source_Element_Type extends Unparsed_Xml_Element_Type {

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Xml_Element_Type::get_name()
	 */
	public function get_name() {
		return "source";
	}

	/**
	 * (non-PHPdoc)
	 * @see Unparsed_Element_Type::get_replace_string()
	 */
	public function get_replace_string() {
		return "SOURCE";
	}
}