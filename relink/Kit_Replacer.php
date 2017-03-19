<?php
/**
 * 
 * @author prgpes
 *
 */
abstract class Kit_Replacer implements Relink_Text_Replacer {
	
	/**
	 *
	 * @var string[]
	 */
	private $kit_types;
	
	/**
	 *
	 * @var string
	 */
	private $kit_types_regex;
	
	/**
	 */
	public function __construct() {
		$this->kit_types = Kit_Constants::get_kit_types();
		$this->kit_types_regex = "/^Kit_(" . join("|", preg_quote_all(array_keys($this->kit_types))) .
			 ")_(.+)\.png$/";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Relink_Text_Replacer::replace()
	 */
	public final function replace(Relink_Text $relink_text) {
		global $logger;
		
		// not a kit body
		if (!preg_match($this->kit_types_regex, $relink_text->get_file_pre(), $old_match)) {
			return null;
		}
		
		// same name
		if ($relink_text->get_file_pre() === $relink_text->get_file_post()) {
			return null;
		}
		
		if (!preg_match($this->kit_types_regex, $relink_text->get_file_post(), $new_match) ||
			 $old_match[1] !== $new_match[1]) {
			$logger->warn("Can't replace kit file with a non-kit file.");
			return null;
		}
		
		try {
			$parser = new Page_Parser($relink_text->get_text_pre());
		} catch (XmlError $e) {
			$logger->error($e);
			return null;
		}
		
		$new_text = $this->replace_templates($parser->get_text(), $this->kit_types[$old_match[1]], 
			$old_match[2], $new_match[2]);
		
		if ($new_text !== null) {
			$parser->set_text($new_text);
			$parser->unparse();
			return $parser->get_text();
		}
	}
	
	/**
	 *
	 * @param string $text        	
	 * @param string $type        	
	 * @param string $old_match        	
	 * @param string $new_match        	
	 * @return string|NULL
	 */
	protected abstract function replace_templates($text, $type, $old_match, $new_match);
}