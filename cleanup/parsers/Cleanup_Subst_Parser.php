<?php
/**
 * @author magog
 *
 */
class Cleanup_Subst_Parser implements Cleanup_Module {

	/**
	 * 
	 * @var string[]
	 */
	private $namespaces;
	
	/**
	 * TODO why is this opening a connection to Wikimedia inside the constructor??
	 */
	public function __construct() {
		global $logger, $wiki_interface;
		
		$logger->debug("Loading " . self::class . "->__construct()");
		
		$this->namespaces = map_array_function_keys(
			(new Project_Data("commons.wikimedia"))->get_namespaces(), 
			function (Wiki_Namespace $namespace) {
				return [$namespace->get_id(), $namespace->get_name()];
			});
		

		$logger->debug("Done loading namespaces in " . self::class . "->__construct()");
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$this->subst_namespace($ci);
		
		$text = $ci->get_text();
		$num_matches = preg_match_all("/\{\{\s*(\#if\s*\:\s*([^\{\}\|]*?))\s*\|/u", $text, $matches);
		for($i = count($matches[0]) - 1; $i >= 0; $i--) {
			$templ = Template::extract($text, $matches[1][$i], 0, true);
			if ($templ !== false) {
				$text = $templ->__get("before");
				$text .= mb_trim(
					preg_match("/^\s*$/u", $matches[2][$i]) ? $templ->fieldvalue(2, false) : $templ->fieldvalue(
						1, false));
				$text .= $templ->__get("after");
				$ci->set_text($text);
				$this->cleanup($ci);
				return;
			}
		}
		$text = preg_replace("/\{\{\s*#ifeq:\s*\{\{\{\w\}\}\}\s*\|\s*\w*\s*\|/u", "{{#ifeq:1|2|",
			$text);
		$num_matches = preg_match_all("/\{\{\s*(\#ifeq\s*\:\s*([^\{\}\|]*?))\s*\|/u", $text,
			$matches);
		for($i = count($matches[0]) - 1; $i >= 0; $i--) {
			$templ = Template::extract($text, $matches[1][$i], 0, true);
			if ($templ !== false) {
				if (mb_trim($matches[2][$i]) == mb_trim($templ->fieldvalue(1, false))) {
					$text = $templ->__get("before") . mb_trim($templ->fieldvalue(2, false)) .
					$templ->__get("after");
					$ci->set_text($text);
					$this->cleanup($ci);
					return;
				}
				if (preg_match("/^[^\|\{\}]*$/u", $matches[2][$i]) && preg_match(
					"/^(?:[^\{\}]*|\s*\{\{\{\w+\}\}\}\s*)$/u", $templ->fieldvalue(1, false))) {
					$text = $templ->__get("before") . mb_trim($templ->fieldvalue(3, false)) .
					$templ->__get("after");
					$ci->set_text($text);
					$this->cleanup($ci);
					return;
				}
			}
		}
		$ci->preg_replace("/\{\{\s*((?:BASE|FULL|SUB|ARTICLE|TALK)?PAGENAMEE?)\s*\:?\s*\}\}/u",
			"{{subst:$1}}");
		$ci->preg_replace("/\{\{\{\s*[\w]+\s*\|([^\|\{\}]*?)\}\}\}/u", "$1");
	}
	

	/**
	 *
	 * @param Cleanup_Instance $ci
	 * @return void
	 */
	private function subst_namespace(Cleanup_Instance $ci) {
		$ci->preg_replace_callback("/\{\{\s*(?:NAMESPACE)\s*\}\}/u",
			function ($match) {
				$match_array = array($match[0], 6);
				return $this->subst_namespace_callback($match_array);
			}, false);
		$ci->preg_replace_callback("/\{\{\s*ns:(\d+)\s*\}\}/u",
			array($this, "subst_namespace_callback"), false);
	}
	

	/**
	 *
	 * @param string $match
	 * @return string
	 */
	public function subst_namespace_callback($match) {
		$val = _array_key_or_value($this->namespaces, null, [$match[1]]);
		if ($val !== null) {
			return $val;
		} else {
			return $match[0];
		}
	}
}