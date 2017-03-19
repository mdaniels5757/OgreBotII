<?php

abstract class Unparsed_Xml_Element_Type extends Unparsed_Element_Type {

	/**
	 *
	 * @var string
	 */
	private $open_regex;
	
	/**
	 * 
	 * @var string
	 */
	private $tag;

	public final function find_next(&$text, $start) {
		if ($this->open_regex === null) {
			$name = preg_quote($this->get_name());
			$this->open_regex = "/<${name}[\>\s]/i";
			$this->tag = $name;
		}

		preg_match($this->open_regex, $text, $match, PREG_OFFSET_CAPTURE, $start);

		return $match ? $match[0][1] : false;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Unparsed_Element_Type::find_close()
	 */
	public final function find_close(&$text, $start) {
		global $logger;
		
		$xml_type = Xml_Reader::simple_parse_element($text, $start);
		
		if (strcasecmp($xml_type->open_tag, $this->tag) !== 0) {
			ogrebotMail("Why isn't $xml_type->open_tag === $this->tag? \$start = $start; \$text = $text");
			return false;
		}
		
		return $xml_type->end_position;
	}

	/**
	 *
	 * @abstract
	 * @return string
	 */
	public abstract function get_name();
}