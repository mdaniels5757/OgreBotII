<?php
/**
 * A class for the actual information template
 * @author magog
 *
 */
class Infobox_Template_Logic extends Infobox_Style_Template_Logic {
	
	/**
	 *
	 * @var string[]
	 */
	private $names;
	
	/**
	 * 
	 */
	public function __construct() {
		$xml_template_type = XmlTemplateType::get_all_types()["Infobox"];
		$this->names = array_fill_keys(
			array_merge_all(
				array_map(
					function (XmlTemplate $xml_template) {
						return $xml_template->get_aliases_and_name();
					}, $xml_template_type->get_xmlTemplates())), 1);
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Whitespace_Preserve_Template_Logic::is_add_carriage_return_new_field()
	 */
	protected function is_add_carriage_return_new_field() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Template_Logic::is_eligible()
	 */
	public function is_eligible(Template $template) {
		return isset($this->names[ucfirst_utf8(mb_trim($template->getname()))]);
	}
}