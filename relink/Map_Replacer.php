<?php

/**
 * 
 * @author magog
 *
 */
class Map_Replacer implements Relink_Text_Replacer {

	const MAP_PARAM = "map";
	const TYPE_PARAM = "filetype";
	const DEFAULT_TYPE = "png";
	const TEMPLATE_NAMES = ["Bilateral", "Infobox Bilateral", "Infobox Bilateral relations", 
		"Infobox bilateral relations"];
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Relink_Text_Replacer::replace()
	 */
	public function replace(Relink_Text $rt) {		
		return $this->do_replace($rt, $rt->get_text_pre(), 0);
	}
	
	/**
	 * 
	 * @param Relink_Text $rt
	 * @param string $text
	 * @param int $start
	 * @return string
	 */
	private function do_replace(Relink_Text $rt, $text, $start) {

		$it = new TemplateIterator($text);
		$change = false;
		
		foreach ($it as $index => $templ) {
			if ($index < $start) {
				continue;
			}
			if (array_search(TemplateUtils::normalize($templ->getname()), self::TEMPLATE_NAMES)
					!== false) {
				$map = $templ->fieldvalue(self::MAP_PARAM);
		
				if ($map) {
					$maps = [$map];
				} else {
					$maps = [];
					preg_match("/^(.+)–(.+) relations/", $rt->get_page_name(), $countries_m);
						
					if ($countries_m) {
						$type = $templ->fieldvalue(self::TYPE_PARAM);
						if (!$type) {
							$type = self::DEFAULT_TYPE;
						}
						$countries = array_map(function($country) {
							$country_array = [$country];
							if ($country === "United States") {
								$country_array[]= "USA";
							}
							return $country_array;
						}, array_splice($countries_m, 1));
											
						foreach ($countries[0] as $first) {
							foreach ($countries[1] as $second) {
								$maps[] = "$first $second Locator.$type";
								$maps[] = "$second $first Locator.$type";
							}
						}
					}
				}
		
				foreach ($maps as $map) {
					$map = preg_replace("/[ _]+/", "_", $map);
					if ($map === $rt->get_file_pre()) {
						$templ->updatefield(self::MAP_PARAM, str_replace("_", " ", $rt->get_file_post()));
						$templ->removefield(self::TYPE_PARAM);
						$text = $templ->wholePage();
						$new_text = $this->do_replace($rt, $text, $index + 1);
						return $new_text ? $new_text : $text;
					}
				}
			}
		}
	}
}