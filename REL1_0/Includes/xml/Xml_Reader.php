<?php
class Xml_Reader {
	
	/**
	 * 
	 * @var string
	 */
	private static $open_regex = "/^<([a-z][\w\-]*)\b/i";
	
	/**
	 *
	 * @param string $text        	
	 * @param int $offset OPTIONAL [default = 0]
	 * @throws XmlError Thrown if there is malformed XML content
	 * @return NULL|Xml_Type Returns null if there is no XML content at all at the beginning
	 *         marker. Returns Xml_Type if one is found. The close_tag parameter of the Xml_Type
	 *         will not be set if it is a self-closing tag.
	 *        
	 */
	public static final function simple_parse_element($text, $offset = 0) {		
		$xml_type = new Xml_Type();
		
		$text = substr($text, $offset);
		
		preg_match(self::$open_regex, $text, $match, PREG_OFFSET_CAPTURE);
		
		if (!$match) {
			return null;
		}
		
		$xml_type->start_position = $offset;
		$xml_type->open_tag_position = $offset;
		$xml_type->open_tag = $match[1][0];
		
		$start = strlen($match[0][0]);
		
		while (true) {
			$start += strcspn($text, ">'\"/", $start);
			
			if ($start >= strlen($text)) {
				throw new XmlError("Unclosed Xml tag");
			}
			
			$element = $text[$start];
			
			if ($element === '/') {
				/**
				 * self-closing tag
				 */
				if (@$text[$start + 1] === '>') {
					$xml_type->end_position = $start + $offset + 2;
					return $xml_type;
				}
				
				/**
				 * Illegal tag
				 */
				throw new XmlError("Illegal character in Xml: '/'");
			}
			
			if ($element === '>') {
				// found!
				break;
			}
			
			// find close
			$start++;
			$strlen = strlen($text);
			while (true) {
				$start += strcspn($text, "\\$element", $start);
				
				if ($start > $strlen) {
					throw new XmlError("start > len. \$text: $text");
				}
				$next = $text[$start];
				$start++;
				
				if ($next === $element) {
					break;
				}
				
				// something escaped
				$start++;
			}
		}

		preg_match("/<\/(" . preg_quote($xml_type->open_tag) . ")\s*>/i", $text, $matches,
			PREG_OFFSET_CAPTURE, $start);
		if (!$matches) {
			throw new XmlError("Unclosed element: " . $xml_type->open_tag);
		}
		$xml_type->close_tag_position = $matches[0][1] + $offset;
		$xml_type->close_tag = $matches[1][0];
		$xml_type->end_position = $matches[0][1] + strlen($matches[0][0]) + $offset;
		
		return $xml_type;
	}
}