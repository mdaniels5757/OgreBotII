<?php

class Gallery_Opt_Outs {

	const OPT_OUT_PAGE_NAME = "User:OgreBot/gallery-opt-out";
	const OPT_OUT_PAGE_SEPARATOR = "<!-- add usernames below this line -->";
	
	/**
	 * 
	 * @param Wiki $wiki
	 * @return string[]
	 */
	public static function get_opt_outs(Wiki $wiki) {
		global $logger, $wiki_interface;
	
		$logger->debug(self::class . "::get_opt_outs($wiki)");
		
		$raw_text = $wiki_interface->get_text($wiki, self::OPT_OUT_PAGE_NAME)->text;
		$separator_index = strpos($raw_text, self::OPT_OUT_PAGE_SEPARATOR);
		if ($separator_index !== false) {
			$raw_text = substr($raw_text, $separator_index + strlen(self::OPT_OUT_PAGE_SEPARATOR));
		}
		$lines = explode("\n", $raw_text);
	
		$opt_outs = array_map_filter($lines, function($line) {
			$line = mb_trim($line);
				
			if (strlen($line) !== 0) {
				return Template_Utils::normalize($line);
			}
		});
		

		$logger->debug(count($opt_outs) . " opt-outs found.");
		
		return $opt_outs;
	}
}