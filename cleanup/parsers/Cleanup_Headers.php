<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Headers implements Cleanup_Module {

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace(
			"/^(\=+) *(?:summary|(?:Краткое[ _]+)?описание|Beschreibung\,[ _]+Quelle|वर्णन) *\:? *\\1\s*$/umi", 
			"$1 {{int:filedesc}} $1");
		$ci->preg_replace(
			"/^(\=+) *(?:li[cz]en(?:s(?:e|ing)(?:\s+information)?|za(?: +d\'uso)?|z)|Лицензирование|\[\[Commons:Copyright tags\|Licensing\]\]:) *\:? *\\1\s*$/umi", 
			"$1 {{int:license-header}} $1");
		$ci->preg_replace(
			"/^(\=+) (?:original upload log|\{\{int:wm\-license\-original\-upload\-log\}\}|) *\:? *\\1\s*$/umi", 
			"$1 {{original upload log}} $1");
		$ci->preg_replace(
			"/^(\=+) *\{\{\s*(?:[Ii][Nn][Tt]|[Mm][Ee][Dd][Ii][Aa][Ww][Ii][Kk][Ii])\s*\:\s*[Ll]icense\s*\}\} *\:? *\\1\s*?$/mu", 
			"$1 {{int:license-header}} $1");
		
		
		//Duplicate headers
		$ci->preg_replace("/(^|\n)(\=+)\s*(\S[^\=]*?)\s*\=+\s*\n\=+\s*\\3\s*\=+\s*\n/u", 
			"$1$2 $3 $2\n");
	}
}