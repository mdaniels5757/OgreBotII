<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Redundant_Fields implements Cleanup_Module {

	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		/**
		 * Various known useless permission messages just repeating the license
		 */
		$useless_permission_re = "(?:PD[A-Z_0-9\-\|]*\d*(?:\/LAWS|\/LANG)?|PD\-US\-NOT RENEWED|" .
			"PD\-US\-NO NOTICE|PD\-USGOV\-MILITARY\-AIR FORCE|CC\-(?:ZERO|BY(?:[\w\-\,\.]*)?)|" .
			"PD\/O[Mm][Aa]|CC|(?:BILD\-)?GFDL[A-Z\-\|]*|ATTRIBUTION|NORIGHTSRESERVED|" .
			"COPYRIGHTEDFREEUSE\-LINK|BILD\-BY|BSD|PD\-EU\-NO AUTHOR DISCLOSURE|[Tt]his image " .
			"is in the (?:\[\[)?public domain(?:\]\])?(?: due to its age| because it is ineligible" .
			" for copyright)?|[Rr]eleased under the \[\[GNU Free Documentation License\]\]|" .
			"GNU Free Documentation License 1\.2|COPYRIGHTEDFREEUSE|[Rr]eleased into the " .
			"public domain \(?by the author\)?|[Ll]icensed under the \[\[GFDL\]\] \(?by the" .
			" author\)?|CC\-A\-2\.5|\-+|(?i:see below|下記を参照))";
		
		/* not a major change */
		$ci->preg_replace(
			"/(\|\s*[Pp]ermission\s*\=[^\|\n]*?)\s*" . Cleanup_Shared::OPT_BR .
			"\s*?\\n?\(Original +text *: *(?:\'\')?$useless_permission_re\.?\s*(?:\'\')?\)" .
			"\s*?\\n(\s*?\|)/u", "$1\n$2", false);
		
		/* not a major change */
		$ci->preg_replace(
			"/(\|\s*[Pp]ermission\s*\=\s*)(?:\s*$useless_permission_re" . "[\;\.\,])+\s*" .
			Cleanup_Shared::OPT_BR . "(\s*$)/mu", "$1$2", false);
		
		/* not a major change */
		$ci->iter_replace("/(\|\s*[Pp]ermission\s*\=)\s*\n\s*([^\}\|\s])/u", "$1$2", false);
		
		/* not a major change */
		$ci->iter_replace("/(\|\s*[Pp]ermission\s*\=[^\n\S]*)\|/u", "$1\n|", false);
		
		/* not a major change */
		$ci->preg_replace(
			"/(\|\s*[Pp]ermission\s*\=)\s*(\'\')?(?:see +license +section|see +below|下記を参照)" .
			"\.?\s*\\2\s*\\n/ui", "$1\n", false);
		
		
		/* not a major change */
		$ci->preg_replace(
			"/(\|\s*[Pp]ermission\s*\=.*);+(?:\s*(\.))?(\s*\|(?:description|date|author|" .
			"permission|other[ _]+(?:versions|fields)))/iu", "$1$2$3", false);
		
		/**
		 * Various known useless other_version messages
		 */
		$ci->preg_replace(
			"/(\|\s*other[_ ]versions\s*\=\s*)(?:no|none(?:\s+known)?|nein|yes|keine|\-+)\s*\.?" .
			"\s*\n(\s*(?:\||\}\}))/iu", "$1\n$2", false);
		
	}
}