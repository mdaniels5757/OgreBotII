<?php
/**
 * English Wikipedia bug wherein the description is duplicated
 * @author magog
 *
 */
class Cleanup_Duplicate_Licenses implements Cleanup_Module {
	
	/**
	 * 
	 * @var array
	 */
	private $constants;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->constants = $cleanup_package->get_constants();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		$pd_art_re = $this->constants["pd_art_regex"];
		
		/**
		 * |Commons=ja (thanks de.wp), |Commons |pdsource=yes (thanks en.wp)
		 */
		/* not a major change */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*.+)\s*\|\s*[Cc]ommons\s*(?:\=\s*[JjYy][Aa]\s*)?(\||\}\})/u", "$1$3$4", 
				false);
		
		/* not a major change */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*.+)\s*\|\s*[Pp]dsource\s*(?i:\=\s*yes\s*)?(\||\}\})/u", "$1$3$4");
		
		/**
		 * PD-old work
		 */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-[Oo]ld\-70\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-[Oo]ld\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-old-70}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-[Oo]ld\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-[Oo]ld\-70\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-old-70}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-old(?:\-\d+)?\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-old\-100\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-old-100}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-old\-100\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-old(?:\-\d+)?\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-old-100}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-US\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-US\-1923\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-US-1923}}$3");
		
		/**
		 * PD-art/PD-scan/Free screenshot work
		 */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*$pd_art_re\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-[Oo]ld(\-\d+)?\s*\}\}(?:\s*?\\n)?/u", 
				"$1{{PD-art|PD-old$4}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp]D\-[Ss]can\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-[Oo]ld(\-\d+)?\s*\}\}(?:\s*?\\n)?/u", 
				"$1{{PD-scan|PD-old$4}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-old(\-\d+)?\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*$pd_art_re\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art|PD-old$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-old(\-\d+)?\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp]D\-[Ss]can\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-scan|PD-old$3}}$4");
		
		// $text = iter_replace("/".Cleanup_Shared::LICENSE_HEADER."\{\{\s*$pd_art_re\s*\|\s*[Pp][Dd]\-[Oo]ld\-100\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art-100}}\n", $text);
		// $text = iter_replace("/".Cleanup_Shared::LICENSE_HEADER."\{\{\s*$pd_art_re\s*\|\s*[Pp][Dd]\-[Oo]ld(\-70)?\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art$3}}\n", $text);
		// $text = iter_replace("/".Cleanup_Shared::LICENSE_HEADER."\{\{\s*[Pp]D\-[Ss]can\s*\|\s*[Pp][Dd]\-[Oo]ld\-100\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-scan-100}}\n", $text);
		// $text = iter_replace("/".Cleanup_Shared::LICENSE_HEADER."\{\{\s*[Pp]D\-[Ss]can\s*\|\s*[Pp][Dd]\-[Oo]ld\-70\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-scan$}}\n", $text);
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*$pd_art_re\s*(\-\d+)\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-old\\4\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp]D\-[Ss]can\s*(\-\d+)\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-[Oo]ld\\4\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-scan$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*([Pp][Dd]\-[Oo]ld(?:\-\d+)?)\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*$pd_art_re\s*\\3\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art|$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*([Pp][Dd]\-[Oo]ld(?:\-\d+)?)\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp]D\-[Ss]can\s*\|\s*\\3\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-scan|$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*[Pp][Dd]\-[Uu][Ss]\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*$pd_art_re\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art|PD-US}}$3");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*$pd_art_re\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Pp][Dd]\-US\s*\}\}(?:\s*?\\n)?/u", "$1{{PD-art|PD-US}}$3");
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*" .
				 $this->constants["free_screenshot_regex"] . "\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*([Ll]?[Gg]PL(?:\-2|v\d\+?(?:[_\s]*only)?)?|[Mm]PL2?|[Aa]PL|" .
				 "BSD(?:withdisclaimer)?|[Dd]SL)\s*\}\}/u", "$1{{Free screenshot|$4}}$5");
		
		/**
		 * Templates and their redirects
		 */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-Pre1978\}\}\s*\n\s*\{\{\s*[Pp]D\-US\-not[ _]+renewed\s*\}\}/u", 
				"$1$3{{PD-US-no notice}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-([A-Za-z\- ]+)\s*\}\}/u", 
				"$1$3{{PD-USGov-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-HHS\-([A-Z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-" .
				 "\\4\s*\}\}/u", "$1$3{{PD-USGov-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-([A-Z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-Interior\-" .
				 "\\4\s*\}\}/u", "$1$3{{PD-USGov-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-Interior\-([A-Z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov" .
				 "\-\\4\s*\}\}/u", "$1$3{{PD-USGov-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-([A-Z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-DOC\-\\4\s*\}\}/u", 
				"$1$3{{PD-USGov-DOC-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-DOC\-([A-Z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-" .
				 "\\4\s*\}\}/u", "$1$3{{PD-USGov-DOC-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-([A-Za-z\-]+)\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-DOC" .
				 "\-\\4\s*\}\}/u", "$1$3{{PD-USGov-DOC-$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-DOT\-FAA\s*\}\}\s*\n\s*\{\{\s*[Pp]D\-USGov\-FAA\s*\}\}/u", 
				"$1$3{{PD-USGov-FAA}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-NASA\s*\}\}\s*\n\s*\{\{[Pp]D\-USGov\-NASA\s*\}\}/u", 
				"$1$3{{PD-USGov-NASA}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-USGov\-Military-([A-Z][a-z]+)\s*\}\}\s*\n\s*\{\{[Pp]D\-USGov" .
				 "\-Military\-\\4\-([A-Z][A-Za-z\-]+)\s*\}\}/u", "$1$3{{PD-USGov-Military-$4-$5}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Pp]D\-BritishGov\s*\}\}\s*\n\s*\{\{[Pp]D\-UKGov\s*\}\}/u", 
				"$1$3{{PD-UKGov}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*[Cc]opyrightedFreeUseProvidedThat\s*(\|[^\{\}\|]*?)?\s*\}\}\s*\n\s*" .
				 "\{\{\s*Copyrighted[ _]+free[ _]+use[ _]+provided[ _]+that\s*\}\}/u", 
				"$1$3{{Copyrighted free use provided that$4}}");
		
		/**
		 * Exact duplicate license templates (it happens)
		 */
		$text_noimagenotes = $this->parse_imagenotes($ci->get_text());
		$ci->set_text($text_noimagenotes[0], false);
		
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*(.+?)\s*\}\}(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES . "\s*)\{\{\s*\\3\s*\}\}(?:\s*?\\n)?/iu", 
				"$1{{" . "$3}}$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "\{\{\s*(.+?)\s*\}\}(?:\s*?\\n)?(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*self2?\s*(?:\|.*)?\|\s*\\3\s*(?:\||\}\}))/iu", "$1$4");
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER .
				 "\{\{\s*GFDL\-self\s*(\|\s*migration\s*\=\s*.+)?\s*\}\}(?:\s*?\\n)?(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\s*\{\{\s*GFDL\-(user\-[a-z\-]+|self)\-(no|with)\-disclaimers\s*\|\s*" .
				 "([^\|\{\}\[\]]+?)\s*(\|\s*migration\s*\=\s*.+)?\s*\}\}/ui", 
				"$1{{GFDL-$5-$6-disclaimers|$7$3$8}}$4");
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER .
				 "\{\{\s*GFDL\-user\-([a-z\-]+)\-(no|with)\-disclaimers\s*\|\s*([^\|\{\}\[\]]+?)\s*(\|\s*migration\s*\=\s*.+)?\s*\}\}(?:\s*?\\n)?(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\s*\{\{\s*GFDL\-self\-\\4\-disclaimers\s*\|\s*(?:1\s*\=\s*)?\\5\s*(\|\s*migration" .
				 "\s*\=\s*.+)?\s*\}\}/ui", "$1{{GFDL-user-$3-$4-disclaimers|$5$6}}$7");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER .
				 "\{\{\s*GFDL\-no\-disclaimers\s*\|\s*([^\|\{\}\[\]]+)\s*(?:\|\s*migration\s*\=\s*" .
				 "redundant)?\s*\}\}(?:\s*?\\n)?(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*self2?\s*(?:\|.*?)?\s*\|\s*.*?author\s*\=\s*\[\[\:(?:" . $this->constants["langlinks_regex"] .
				 "):User:\s*\\3\s*|.*\]\])/iu", "$1$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER .
				 "\{\{\s*PD\-VietnamGov\s*\}\}(?:\s*?\\n)?\{\{\s*PD\-VietnamGov\/lang\s*\}\}/u", 
				"$1{{PD-VietnamGov}}");
		
		$ci->set_text($this->rebuild_imagenotes($ci->get_text(), $text_noimagenotes[1]), false);
		
		$this->duplicate_license_cleanup($ci, 
			"(?:[Aa]nonymous(?:[ _]+|\-)EU|[Pp]D-EU\-no[ _]+author[ _]+disclosure)", "Anonymous-EU");
		$this->duplicate_license_cleanup($ci, 
			"(?:[Cc]c\-by\-sa\-3\.0\,2\.5\,2\.0\,1\.0|[CC]c\-by\-sa\-all)", 
			"Cc-by-sa-3.0,2.5,2.0,1.0");
		$this->duplicate_license_cleanup($ci, "(?:[Cc]c\-by\-sa\-1\.0|[CC]c\-by\-sa|[Cc]C\-BY\-SA)", 
			"Cc-by-sa-1.0");
		$this->duplicate_license_cleanup($ci, "(?:[Pp]D\-Pre1964|[Pp]D\-US\-not[ _]+renewed)", 
			"PD-US-not renewed");
		

		/**
		 * Badjpeg, Convert to SVG, Badgif, Convert to PNG, Inkscape, football kit template
		 */
		$ci->iter_replace(
			"/\{\{\s*(?:[Ss]VG\-Logo|[Tt]rademark(?:ed)?)\s*\}\}([\s\S]*)\{\{\s*(?:[Ss]VG\-Logo|" .
			"(?:SVG\-)?[Tt]rademark(?:ed)?)\s*\}\}(?:\s*?\\n)?/u", "{{Trademarked}}$1");
		/**
		 * Bild-LogoSH
		 */
		if (preg_match("/\{\{\s*(?:[Pp]D\-[Tt]extlogo|[Pp]d\-textlogo|[Tt]extlogo)\s*\}\}/u", $ci->get_text()) &&
			preg_match("/\{\{\s*(?:[Ss]VG\-Logo|[Tt]rademark(?:ed)?)\s*\}\}/u", $ci->get_text())) {
			$ci->iter_replace(
				"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[BB]ild\-LogoSH\s*\}\}(?:\s*?\\n)?/u", "$1$3");
		}
		
		/**
		 * Superfluous GFDL tag
		 */
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Gg]FDL\s*\}\}(?:\s*?\\n)?(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*[Gg][Ff][Dd][Ll])/u", "$1$3$4");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*)\{\{\s*[Gg]FDL\s*\|\s*[Mm]igration\s*\=\s*(.+)\s*\}\}(?:\s*?\\n)?(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\s*\{\{\s*[Gg][Ff][Dd][Ll].*?\|)\s*[Mm]igration\s*\=\s*\\4\s*(\||\}\})/u", 
				"$1$5migration=$4$6");
	}
	
	/**
	 * ********************
	 * Function which removes {{ImageNote}}, and replaces them with a set of temporary strings.
	 * This is
	 * necessary, because if there is a duplicate image note, the script would otherwise remove it.
	 * The temporary strings function as "markers", so that
	 * rebuild_imagenotes() can later be called and the imagenotes will return to the correct
	 * position, untouched.
	 * Returns: An array, whose 0th element contains the altered string, and whose 1st element contains
	 * another array which will focus as a set "tokens", which should be kept unaltered and passed to
	 * imagenotes() later.
	 * ********************
	 */
	private function parse_imagenotes($text) {
		$regex = "/{{\s*ImageNote\s*\|.+?\}\}[\s\S]+?\{\{\s*ImageNoteEnd.+?\}\}/u";
	
		$imagenotes = array();
		while (preg_match($regex, $text, $match)) {
			$index = count($imagenotes);
			array_push($imagenotes, $match[0]);
			$text = preg_replace($regex, "%%%MTOIMAGENOTE$index%%%", $text, 1);
		}
	
		return array($text, $imagenotes);
	}
	
	/**
	 * ********************
	 * See function immediately above for explanation
	 * Returns: Rebuilt text.
	 * ********************
	 */
	private function rebuild_imagenotes($text, $imagenotes) {
		while (count($imagenotes) > 0) {
			$next = array_pop($imagenotes);
			$text = str_replace("%%%MTOIMAGENOTE" . count($imagenotes) . "%%%", $next, $text);
		}
		return $text;
	}
	


	/**
	 * ********************
	 * See function immediately above for explanation
	 * $licenses_re: the regular expression in a license for which to search (e.g., "(?:[Cc]c\-by\-sa\-3\.0(?:\-migrated)?")
	 * $replacement: the license as it should appear when replaced
	 * Returns: Rebuilt text.
	 * ********************
	 */
	private function duplicate_license_cleanup(Cleanup_Instance $ci, $licenses_re, $replacement) {
		$replacement = escape_preg_replacement($replacement);
	
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)\{\{\s*$licenses_re\s*((?i:\|\s*migration\s*\=\s*[a-z\-]*))?\s*\}\}" .
			"(?:\s*?\\n)?((?:[\S\s]*?\n\s*)?\{\{\s*self2?\s*(?:\|.*?)?$licenses_re(?:\|.*?)?)\}\}/u",
			"$1$3$5$4}}");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)(\{\{\s*self2?\s*(?:\|.*?)?$licenses_re(?:\|.*?)?)\}\}([\S\s]*?\n\s*)" .
			"?\{\{\s*$licenses_re\s*((?i:\|\s*migration\s*\=\s*[a-z\-]*))?\s*\}\}(?:\s*?\\n)?/u",
			"$1$3$4$6}}$5");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)\{\{\s*$licenses_re(\|.*?)?\s*\}\}(?:\s*?\\n)?((?:[\S\s]*?\n\s*))?\{\{" .
			"\s*$licenses_re(\||\}\})/u", "$1$3$5{{" . "$replacement$4$6");
	
		// cleanup after ourselves...
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)(\{\{\s*$licenses_re\s*\|\s*(.+?)\s*\=\s*(.*?)\s*)\|\s*\\5\s*\=\s*\\6(\||\}\})/u",
			"$1$3$4$7");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)(\{\{\s*\s*self2?\s*(?:\|.*?)?\|$licenses_re\s*(?:\|.*)?)((?i:\|\s*" .
			"migration\s*\=\s*relicense))(\|.*)?(?i:\|\s*migration\s*\=\s*.+)(\||\}\})/u",
			"$1$3$4$5$6$7");
		$ci->iter_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
			"\s*)(\{\{\s*\s*self2?\s*(?:\|.*?)?\|$licenses_re\s*(?:\|.*)?)" .
			"(?i:\|\s*migration\s*\=\s*[a-z\-]+)(\|.*)?((?i:\|\s*migration\s*\=\s*relicense))" .
			"(\||\}\})/u", "$1$3$4$5$6$7");
	}
}