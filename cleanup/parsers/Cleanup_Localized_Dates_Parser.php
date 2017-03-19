<?php

class Cleanup_Localized_Dates_Parser implements Cleanup_Submodule {
	
	/**
	 * 
	 * @var string
	 */
	const SINGLES = "before|after|spring|summer|autumn|winter|circa";
	
	/**
	 * 
	 * @var string
	 */
	private $circa_between_regex;
	
	/**
	 * 
	 * @var array
	 */
	private $constants;

	/**
	 * 
	 * @var singles[]
	 */
	private $singles = [];
	
	/**
	 * 
	 * @var string
	 */
	private $singles_regex;
	
	/**
	 * 
	 * @param array $constants
	 */
	public function __construct($constants) {
		$this->constants = $constants;
		
		$all_singles = map_array_function_keys(explode("|", self::SINGLES), 
			function ($key) {
				$lowercase = array_map(
					function ($translation) {
						return mb_strtolower($translation);
					}, $this->constants[$key]);
				
				return [$key, array_fill_keys($lowercase, $key)];
			});
		
		$this->singles = array_merge_no_conflicts($all_singles);
		$this->singles_regex = "/^(\s*)(" . $this->string_to_regex(array_keys($this->singles)) .
			 ")\s*(\d{4})\s*?" . Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/iu";
		$this->circa_between_regex = "/^(\s*)(?:" .
			 $this->string_to_regex(array_keys($all_singles["circa"])) . ")\s*(\d{4})\s*\-\s*(\d{4})" .
			 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/iu";
	}
	
	/**
	 * 
	 * @param string[] $strings
	 * @return string
	 */
	private function string_to_regex(array $strings) {
		return implode("|", preg_quote_all($strings));	
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $date_tracker) {
		
		$date_tracker->preg_replace_callback($this->singles_regex, function ($match) {
			$lowercase = mb_strtolower($match[2]);
			
			$type = _array_key_or_exception($this->singles, [$lowercase]);
			
			return "$match[1]{{other date|$type|$match[3]}}$match[4]";
		});
		
		/**
		 * circa (year)
		 */
		$date_tracker->preg_replace(
			"/^(\s*)\{\{\s*[Cc]irca\s*\|\s*(.+?)\}\}\s*?" .
				 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "$1{{circa|$2}}$3");
		$date_tracker->preg_replace(
			"/^(\s*)\{\{\s*[Oo]ther[ _]+date\s*\|\s*circa\|\s*([^\|]+?)\}\}\s*?" .
				 Cleanup_Shared::ORIGINAL_UPLOAD_DATE_IGNORABLE . "/u", "$1{{circa|$2}}$3");
		
		/**
		 * circa between
		 */
		$date_tracker->preg_replace($this->circa_between_regex, "$1{{other date|circa|$2|$3}}$4");
	}
}