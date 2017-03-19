<?php
/**
 * @author magog
 *
 */
class Cleanup_Transferred_From implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		// rm {{transferred from}} if unnecessary
		$ci->preg_replace(
			"/(\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log[ _]*\}\}" .
				 "\s*\=+\s*\n\s*)\{\{[ _]*[Tt]ransferred[ _]+from[ _]*\|\s*[a-z\-]+" .
				 "\.w[a-z]+\s*\|?\s*\}\}\s*(\{\{[ _]*[Oo]riginal[ _]+description(?:[ _]+page)?\s*\|)/u", 
				"$1$2", false);
		
		$ci->iter_replace(
			"/(\\n(\=+) *\{\{\s*[Ii][Nn][Tt]\s*\:[Ll]icen[cs]e\-header\s*\}\} *\:? *\\2\s*?\\n)" .
				 "\s*(\=+) *\{\{\s*[Ii][Nn][Tt]\s*\:[Ll]icen[cs]e\-header\s*\}\} *\:? *\\3\s*?\\n/u", 
				"$1");
	}
}