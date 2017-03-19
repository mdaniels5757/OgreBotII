<?php
class XmlParser {
	
	/**
	 *
	 * @param string $filename        	
	 * @param bool $properties_directory        	
	 * @throws XMLParserException
	 * @return array
	 */
	public static function xmlFileToStruct($filename, $properties_directory = true) {
		global $logger;
		
		if ($properties_directory) {
			$filename = BASE_DIRECTORY . "/properties/$filename";
		}
		
		if (@$logger) {
			$logger->debug("xmlFileToStruct($filename)");
		}
		
		$text = file_get_contents_ensure($filename, true);
		
		$parser = xml_parser_create('');
		
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 1);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $text, $xmlValues);
		xml_parser_free($parser);
		
		if (!$xmlValues) {
			throw new XMLParserException("Can't parse file: $filename");
		}
		
		return XmlParser::parse($xmlValues);
	}
	
	/**
	 *
	 * @param array $data        	
	 * @param number $ptr        	
	 * @param number $level        	
	 * @return array
	 */
	private static function parse($data, &$ptr = 0, $level = 0) {
		global $validator;
		$validator->validate_arg($data, "array");
		
		$xmlData = [];
		do {
			$next = $data[$ptr++];
			if ($next['type'] === 'close') {
				if ($level == $next['level']) {
					// we're done; pass it up the chain
					return $xmlData;
				}
			}
			
			//for HHVM
			if ($next['type'] === 'cdata') {
				continue;
			}
			
			$element = [];
			
			if ($next['type'] === 'open') {
				$element['elements'] = XmlParser::parse($data, $ptr, $next['level']);
			} else if ($next['type'] !== 'complete') {
				$validator->assert(false, "Unrecognized element type: $next[type]");
			}
			
			if (isset($next['attributes'])) {
				$element['attributes'] = $next['attributes'];
			}
			
			if (isset($next['value'])) {
				$element['value'] = mb_trim($next['value']);
			}
			
			$tag = $next['tag'];
			if (!isset($xmlData[$tag])) {
				$xmlData[$tag] = [];
			}
			$xmlData[$tag][] = $element;
		} while ($ptr < count($data));
		
		return $xmlData;
	}
}
?>
