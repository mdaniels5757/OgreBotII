<?php
/**
 * @author magog
 *
 */
class Cleanup_Unicode implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->str_replace("&times;", "×", false);
		$ci->preg_replace(
			"/Upload date &#x7C; User &#x7C; Bytes &#x7C; Dimensions &#x7C; Comment(\s+(?i:\[\[\s*" .
			"category\s*:.+\]\]\s*)*\*[\d\-]+ [\d:]+ )&#x7C;( \[\[.+?]] )&#x7C; (\d+) &#x7C; " .
			"(\d+×\d+) &#x7C; <small>/u",
			"Upload date | User | Bytes | Dimensions | Comment$1|$2| $3 | $4 | <small>", false);
	}
}