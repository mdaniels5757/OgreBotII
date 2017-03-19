<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Incorrect_Trailing_License implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace(
			"/((\=+)\s*\{\{int\:license\-header\}\}\s*\\2\s*(?:\s*%%%MTONOPARSE_COMMENT\d+%%%\s*)*\n" .
				 "\s*\{\{.+\}\}\s*\n[\s\S]*\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log\s*\}\}\s*" .
				 "\=+[\s\S]+)(\=+)\s*\{\{int\:license\-header\}\}\s*\\3\s*\n\s*\{\{(?:\{\{\s*" .
				 "[Uu]ser at project\s*\|[^\}\{]+\}\}|[^\}\{])+\}\}\s*((?i:\[\[\s*category\s*" .
				 "[^\|\[\]\{\}]+\]\]\s*)*)$/u", "$1$4");
	}
}