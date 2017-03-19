<?php
/**
 * @author magog
 *
 */
class Cleanup_Known_Author_Types implements Cleanup_Module {
	
	/**
	 * 
	 * @var string[]
	 */
	private $replace_author_regex_keys;

	/**
	 *
	 * @var string[]
	 */
	private $replace_author_regex_replacements;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$replace_author_regex_keys = ["unknown" => "unknown|author", 
			"unknown-photographer" => "unknown photographer", "anonymous" => "anonymous"];
		$constants = $cleanup_package->get_constants();
		
		
		$this->replace_author_regex_keys = array_map(
			function ($key) use($constants) {
				return $constants["${key}_author_regex"];
			}, array_keys($replace_author_regex_keys));
		
		$this->replace_author_regex_replacements = array_map(
			function ($replacement) {
				return "{{{$replacement}}}$1";
			}, array_values($replace_author_regex_keys));
	}
	
	/**
	 * 
	 * @param string $authorfield
	 * @param string[] $replacements
	 * @return bool
	 */
	private function replace_author_field_with_template(&$authorfield, $replacements) {
		$authorfield_copy = preg_replace($this->replace_author_regex_keys,
			$this->replace_author_regex_replacements, $authorfield);
	
		$regex = "/^\s*\{\{\s*([A-Za-z\-\s]+?)\s*\}\}\s*$/";
		$changed = $authorfield !== $authorfield_copy;
		if ($changed) {
			if (preg_match($regex, $authorfield, $authorfield_match) &&
				preg_match($regex, $authorfield_copy, $authorfield_copy_match) &&
				ucfirst_utf8($authorfield_match[1]) === ucfirst_utf8($authorfield_copy_match[1])) {
					return false;
				}
				$authorfield = $authorfield_copy;
		}
	
		return $changed;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if (!$t) {
			return;
		}
		
		$authorfield = $t->fieldvalue(Cleanup_Shared::AUTHOR);
		preg_replace_track(
			"/^(\s*)unknown\s*" . Cleanup_Shared::OPT_BR .
				 "\s*\(\s*life time\s*\:\s*unknown\s*\)(\s*)$/i", "$1{{unknown|author}}$2", 
				$authorfield, $unknown_flag);
		
		if (!$unknown_flag) {
			$unknown_flag = $this->replace_author_field_with_template($authorfield, 
				["unknown" => "unknown|author", 
					"unknown-photographer" => "unknown photographer", "anonymous" => "anonymous"]);
		}
		
		if ($unknown_flag) {
			$t->updatefield(Cleanup_Shared::AUTHOR, $authorfield);
			$ci->set_text($t->wholePage());
		}
	}
}