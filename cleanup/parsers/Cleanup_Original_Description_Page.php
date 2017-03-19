<?php
class Cleanup_Original_Description_Page implements Cleanup_Module {	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace(
			"/The\s+original\s+description\s+page\s+(?:is\/was|is|was)\s+\[(?:https?:)?\/\/(?:www\.)?" .
			"((?:[a-z\-]+\.)?wik[a-z]+(?:\-old)?)\.org\/w((?:\/shared)?)\/index\.php\?" .
			"title\=(?i:file?|image)(?:\:|%3[Aa])(.+?)\s+here(?:\]\.|\.\])\s+All\s+" .
			"following\s+user\s+names\s+refer\s+to\s+(?:\\1(?:\.org)?\\2|(?:wts|shared)" .
			"\.oldwikivoyage)\./u", "{{original description page|$1$2|$3}}");
		$ci->preg_replace(
			"/This file was originally uploaded at ([a-z\-]+\.wik[a-z]+) as \[(?:https?:)?" .
			"\/\/\\1\.org\/wiki\/(?i:file?|image)(?:\:|%3[Aa])([\w\%\-\.\~\:\/\?\#\[\]" .
			"\@\!\$\&\'\(\)\*\+\,\;\=]+?)(?:|\s+[^\]\n]*)](?:\, before it was transferr?ed" .
			" to [Cc]ommons)?\.?/u", "{{original description page|$1|$2}}$3");
		$ci->preg_replace(
			"/(\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log\s*\}\}\s*\=+\s*)(\{\{\s*[Oo]riginal" .
			"[ _]+description[ _]+page\s*\|\s*([a-z\-]+\.w[a-z]+)\s*\|\s*[^\}\|]+}}) using " .
			"\[\[\:en\:WP\:FTCG\|FtCG\]\]\./u",
			"$1{{transferred from|$3||[[:en:WP:FTCG|FtCG]]}} $2");
		$ci->preg_replace(
			"/(\{\{\s*[Oo]riginal[ _]+description[ _]+page\s*\|[^\}\|]*?\|[^\}\|]\|(?:)?}}) " .
			"using \[\[\:en\:WP\:FTCG\|FtCG\]\]\./u",
			"$1{{transferred from|$3||[[:en:WP:FTCG|FtCG]]}} $2");
	}
}