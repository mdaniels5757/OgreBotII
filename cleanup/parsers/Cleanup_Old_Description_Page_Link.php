<?php
/**
 * @author magog
 *
 */
class Cleanup_Old_Description_Page_Link implements Cleanup_Module {
	
	/**
	 *
	 * @var string
	 */
	private $langlink_regex;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->langlink_regex = $cleanup_package->get_constants()["langlinks_regex"];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$information = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($information) {
			$source = $information->fieldvalue(Cleanup_Shared::SOURCE);
			if (preg_match(
				"/^\s*Originally from \[https?:\/\/((?:" . $this->langlink_regex .
					 ")\.wik[a-z]+)\.org \\1\]; description page is\/was \[https?:\/\/\\1\.org\/w\/index\.php\?title\=(?:File|Image)(?:\:|%3[Aa])(\S+?)\s+here\]\.([\S\s]+)$/u", 
					$source, $match)) {
				$information->updatefield(Cleanup_Shared::SOURCE, 
					"{{Transferred from|$match[1]}}$match[3]");
				$text2 = $information->wholePage();
				preg_replace_track(
					"/(\n\=\= \{\{[Oo]riginal upload log\}\} \=\=\r?\n(?:.+?\r\n)?)\(All user names refer to " .
						 preg_quote($match[1]) . "\)/u", 
						"$1{{original description page|$match[1]|" .
						 escape_preg_replacement($match[2]) . "}}", $text2, $madechange);
				if ($madechange) {
					$ci->set_text($text2);
					return;
				}
			}
		}
	}
}