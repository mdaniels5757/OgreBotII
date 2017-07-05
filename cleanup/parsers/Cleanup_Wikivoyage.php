<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Wikivoyage implements Cleanup_Module {
	
	/**
	 *
	 * @var Template_Factory
	 */
	private $template_factory;
	
	/**
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 * 
	 * @param Cleanup_package $cleanup_package
	 */
	public function __construct(Cleanup_package $cleanup_package) {
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
		$this->constants = $cleanup_package->get_constants();
	}
	
	/**
	 *
	 * @param string $text        	
	 * @return User_at_project|null
	 */
	private function get_user_at_project_wikivoyage($text) {
		if (preg_match(
			"/^\* \d{4}\-\d{2}\-\d{2} \d{2}:\d{2} \[\[:?(?<langlink>[a-z]+):(?i:user):(?<username>[^\[\]\|]+)\|\\2\]\] \d+(?:&times;|×)\d+ \(\d+ bytes\)/um", 
			$text, $match) || (preg_match(
			"/^\* \d{4}\-\d{2}\-\d{2} \d{2}:\d{2} (?<escaped_userlink>\[https?:\/\/(?<langlink>[a-z]+)\.wikivoyage-old\.org\/wiki\/(?i:user)%3A[^\[\]\|\s]+" .
				 " (?<username>[^\[\]\|]+)\]) \d+(?:&times;|×)\d+ \(\d+ bytes\)/um", $text, $match) && $match['escaped_userlink'] == wikivoyage_link_to_commons_link(
			"[[user:$match[username]|$match[username]]]", $match['langlink']))) {
			$username = $match['username'];
			$userlink = (strpos($username, '=') !== false ? "1=" : "") . $username;
			$langlink = $match['langlink'];
			switch ($match['langlink']) {
				case 'wts' :
					$base_url = "http://wts.wikivoyage-old.org/wiki/";
					$directlink = wikivoyage_link_to_commons_link("[[User:$username|$username]]", 
						'wts');
					$str = "{{User at wikivoyage old|$userlink|$match[langlink]}}";
					break;
				
				case 'shared' :
				case 'tech' :
				case 'general' :
					$base_url = "http://www.wikivoyage-old.org/$langlink/";
					$directlink = wikivoyage_link_to_commons_link("[[User:$username|$username]]", 
						$langlink);
					$str = "{{User at wikivoyage old|$userlink|$langlink}}";
					break;
				
				case "de" :
				case "fr" :
				case "it" :
				case "nl" :
				case "ru" :
				case "sv" :
					$base_url = "http://$langlink.wikivoyage.org/wiki/";
					$directlink = "[[voy:$langlink:User:$username|$username]]";
					$str = "{{user at project|$userlink|wikivoyage|$langlink}}";
					break;
				
				case "en" :
					$base_url = "http://en.wikivoyage.org/wiki/";
					$directlink = "[[voy:User:$username|$username]]";
					$str = "{{user at project|$userlink|wikivoyage|en}}";
					break;
				
				default :
					return null;
			}
			return new User_At_Project($str, "wikivoyage", $username, $langlink, $directlink, 
				$base_url);
		}
		return null;
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param string $location        	
	 * @return bool
	 */
	private function fix_wikivoyage_location_internal(Cleanup_Instance $ci, $location) {
		if ($location) {
			$parameter_one = null;
			foreach ($location->__get("fields") as $key => $field) {
				if ($key == "1") {
					$parameter_one = $field;
				} else if (preg_replace("/\s+/u", "", $field) !== "") {
					$parameter_one = null;
					break;
				}
			}
			if ($parameter_one !== null) {
				$txt2 = $location->__get("before") . $location->__get("after");
				$information = $this->template_factory->extract($txt2, 
					Cleanup_Shared::INFORMATION);
				if ($information) {
					if (!preg_match("/[\{\}\[\]\|\=\!@\#\\$\%\^\&\*\<\>:;'\"]/u", $parameter_one)) {
						$parameter_one = "{{city|$parameter_one}}";
					}
					$information->updatefield("Other fields", 
						"{{Information field|name={{ucfirst:{{location/i18n}}}}|value=" .
							 str_replace("_", " ", $parameter_one) . "}}");
					$ci->set_text($information->wholePage());
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Fix {{location}} template transferred from wikivoyage
	 * 
	 * @param Cleanup_Instance $ci        	
	 * @return void
	 */
	private function fix_wikivoyage_location(Cleanup_Instance $ci) {
		global $logger;
		
		$information = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($information) {
			$information_text = $information->__toString();
			$locations = [];
			while (true) {
				foreach (new Template_Iterator($information_text, $this->template_factory) as $template) {
					$name = ucfirst_utf8(mb_trim($template->getname()));
					if ($name === "Location" || $name == "IsIn") {
						if ($template->fieldisset(1) && count($template->__get("fields")) == 1) {
							$location = $template->fieldvalue(1);
							if (strlen($location) > 0) {
								$locations[] = mb_trim($location);
								$information_text = Cleanup_Shared::remove_template_and_trailing_newline(
									$template);
								continue 2;
							}
						}
						$logger->info("Template with bad location template found: $template");
						continue;
					}
				}
				break;
			}
			if ($locations) {
				$new_information = $this->template_factory->extract($information_text, 
					Cleanup_Shared::INFORMATION);
				if (!$new_information) {
					//???
					$logger->error("Can't reextract information? Original text: " . $ci->get_text());
					return;
				}
				$locations_text = join("", 
					array_reverse(array_map(
						function ($location) {
							return strpbrk($location, "{}[]|=!@#\$%^&*<>:;'\"") ? $location : "{{city|" .
								 str_replace("_", " ", $location) . "}}";
						}, $locations)));
				$new_information->updatefield("Other fields", 
					"{{Information field|name={{ucfirst:{{location/i18n}}}}|value=$locations_text}}");
				$ci->set_text($information->__get("before") . $new_information->wholePage()
					. $information->__get("after"));
			}
		}
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$flag = false;
		$suspicious_interwikis = null;
		$wts = $ci->get_template("Wikivoyage");
		if ($wts) {
			$this->fix_wikivoyage_location($ci);
			
			$langlink = $wts->fieldvalue(1);
			$user_at_project = $this->get_user_at_project_wikivoyage($ci->get_text());
			if ($user_at_project !== null) {
				$ci->preg_replace(
					"/([\|\=]\s*)" . preg_quote($user_at_project->get_username(), '/') .
						 " at $langlink\.wikivoyage(?:\-old)?(?:\.org)?(\s*(?:\||\}\}))/u", 
						"$1" . $user_at_project->get_template_string() . "$2");
				$ci->preg_replace(
					"/([\|\=]\s*)" . preg_quote($user_at_project->get_username(), '/') .
						 " at (?:old )?(?:www\.)?wikivoyage(?:\-old)?(?:\.org)?[\/ ]$langlink(\s*(?:\||\}\}))/u", 
						"$1" . $user_at_project->get_template_string() . "$2");
				$ci->preg_replace(
					"/^(\* \d{4}\-\d{2}\-\d{2} \d{2}:\d{2} )\[\[:$langlink:(?i:user):([^\[\]\|]+)\|\\2\]\]( \d+(?:&times;|×)\d+ \(\d+ bytes\))/um", 
					"$1" . $user_at_project->get_direct_link() . "$3");
				
				switch ($langlink) {
					case "wts" :
					case "shared" :
						preg_match_all(
							"/(\[" . preg_quote($user_at_project->get_base_url(), '/') .
								 "User%3A\S+ ([^\[\]]+)\]) at \[" .
								 preg_quote($user_at_project->get_base_url(), '/') .
								 " wts\.wikivoyage(?:\-old)?\]/u", $ci->get_text(), $matches, 
								PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
						for($i = count($matches) - 1; $i >= 0; $i--) {
							$nextmatch = $matches[$i];
							if (wikivoyage_link_to_commons_link(
								"[[User:" . $nextmatch[2][0] . "|" . $nextmatch[2][0] . "]]", 
								$langlink) == $nextmatch[1][0]) {
								$ci->set_text(substr($ci->get_text()), 0, $nextmatch[0][1]) .
									 "{{User at wikivoyage old|" .
									 (strpos($nextmatch[2][0], '=') !== false ? "1=" : "") .
									 $nextmatch[2][0] . "|$langlink}}" . substr($ci->get_text(), 
										$nextmatch[0][1] + strlen($nextmatch[0][0]));
							}
						}
						preg_match_all(
							"/\[\[\s*:*\s*(?i:$langlink\s*:\s*user)\s*:\s*([^\[\]\|\{\}]+)\s*\|\s*\\1\s*\]\] at \[(?:https?:)?\/\/$langlink\.(?:oldwikivoyage|wikivoyage(?:\-old)?)\.org $langlink\.(?:oldwikivoyage|wikivoyage(?:\-old)?)\]/u", 
							$ci->get_text(), $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
						for($i = count($matches) - 1; $i >= 0; $i--) {
							$nextmatch = $matches[$i];
							$ci->set_text(
								substr($ci->get_text(), 0, $nextmatch[0][1]) .
									 $user_at_project->get_template_string() .
									 substr($ci->get_text(), 
										$nextmatch[0][1] + strlen($nextmatch[0][0])));
						}
						$self = $ci->get_template("Self");
						if ($self) {
							$author = $self->fieldvalue(Cleanup_Shared::AUTHOR);
							if (preg_match(
								"/^([^\[\]\|\{\}]+) at wts\.wikivoyage\-old(?:\.org)?$/u", $author, 
								$match)) {
								$newauthor = "{{User at wikivoyage old|" .
									 (strpos($match[1], '=') !== false ? "1=" : "") .
									 "$match[1]|wts}}";
								$self->updatefield(Cleanup_Shared::AUTHOR, $newauthor);
								$ci->set_text($self->wholePage());
							}
						}
						/**
						 * *******************************************************************************************************************************************************************************
						 * Bad wikivoyage links *
						 * ******************************************************************************************************************************************************************************
						 */
						preg_match_all(
							"/\[\[\s*:?\s*(?i:$langlink)\s*:+\s*([^\[\{\]\}\|]+(?:\|[^\[\{\]\}\|]+))\]\]/u", 
							$ci->get_text(), $matches, PREG_OFFSET_CAPTURE);
						// var_dump($matches);die();
						for($i = count($matches[1]) - 1; $i >= 0; $i--) {
							$newlink = wikivoyage_link_to_commons_link(
								"[[:" . $matches[1][$i][0] . "]]", $langlink);
							$ci->set_text(
								substr($ci->get_text(), 0, $matches[0][$i][1]) . $newlink . substr(
									$ci->get_text(), $matches[0][$i][1] + strlen($matches[0][$i][0])), 
								false);
						}
						$ci->str_replace("[http://wts.wikivoyage-old wts.wikivoyage-old]", 
							"[http://wts.wikivoyage-old.org wts.wikivoyage-old]");
						$ci->str_replace("[http://wts.oldwikivoyage.org wts.oldwikivoyage]", 
							"[http://wts.wikivoyage-old.org wts.wikivoyage-old]");
						$ci->str_replace("http://wts.oldwikivoyage.org", 
							"http://wts.wikivoyage-old");
						break;
					
					case "de" :
					case "fr" :
					case "it" :
					case "nl" :
					case "ru" :
					case "sv" :
					case "en" :
						$ci->preg_replace(
							"/\[\[:$langlink:(?i:user):([^\[\]\|]+)\|\\1\]\] at \[(?:https?:)?\/\/$langlink\.wikivoyage(?:\-old)?\.org $langlink\.wikivoyage(?:\-old)?\]/u", 
							"{{user at project|$1|wikivoyage|$langlink}}");
						if (preg_match_all(
							"/\[\[\s*\:\s*(?:de|fr|it|nl|ru|sv|en)\s*:[^\[\]\|]+\s*\|\s*[^\[\]\|]+\s*\]\]/u", 
							$ci->get_text(), $suspicious_interwikis_unparsed)) {
							$ci->add_warning(Cleanup_Shared::SUSPICIOUS_INTERWIKIS);
						}
						break;
				}
			}
		}
	}
}