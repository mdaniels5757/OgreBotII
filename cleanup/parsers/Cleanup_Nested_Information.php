<?php
class Cleanup_Nested_Information implements Cleanup_Module {
	
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
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$this->template_factory = $cleanup_package->get_infobox_template_factory();
		$this->constants = $cleanup_package->get_constants();
	}
	
	/**
	 *
	 * @param string $one        	
	 * @param string $two        	
	 * @param string $replacement        	
	 * @return string
	 */
	private function nested_info_fix($one, $two, 
		$replacement = "$2<br/>\r\n(Original text : ''$1'')") {
		$trimone = trim($one);
		$trimtwo = trim($two);
		if ($trimone == $trimtwo) {
			return $two;
		} else if ($trimone) {
			if ($trimtwo) {
				$replacement = str_replace("\$1", "\$MAGOG", $replacement);
				$replacement = str_replace("\$2", "\$1", $replacement);
				$replacement = str_replace("\$MAGOG", escape_preg_replacement(mb_trim($one)), 
					$replacement);
				return preg_replace("/^\s*([\s\S]+?)\s*$/u", $replacement, $two);
			} else {
				return $one . $two;
			}
		} else {
			return $two;
		}
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		global $logger;
		
		$infinite_loop_check = 0;
		while (preg_match("/\{\{\s*[Ii]nformation\s*\|[\s\S]*\{\{\s*[Ii]nformation\s*\|/u", 
			$ci->get_text()) && $t = $ci->get_template(Cleanup_Shared::INFORMATION)) {
			$infinite_loop_check++;
			if ($infinite_loop_check > 20) {
				$logger->error(
					"Infinite looping on nested information. Text:\n$original_text\n\n\n");
				break;
			}
			$desc = $t->fieldvalue(Cleanup_Shared::DESCRIPTION);
			if ($desc) {
				if (preg_match(
					"/\s*\{\{\s*(" . $this->constants["langlinks_regex"] .
						 ")\s*\|(?:\s*1\s*\=)?\s*\{\{\s*[Ii]nformation\s*\|/u", $desc, 
						$langlink_match)) {
					$lang_template = $ci->get_template($langlink_match[1]);
					if ($lang_template) {
						$nested_info_text = $lang_template->fieldisset("1") ? $lang_template->fieldvalue(
							"1") : "";
						$nested_info_template = $this->template_factory->extract($nested_info_text, 
							Cleanup_Shared::INFORMATION);
						
						if ($nested_info_template) {
							
							$after = preg_replace("/^\s*(?:1\s*\=\s*)?([\s\S]*?)/u", "$1", 
								$nested_info_template->__get("before")) .
								 $nested_info_template->__get("after");
							$nested_desc = $nested_info_template->fieldvalue(
								Cleanup_Shared::DESCRIPTION);
							$nested_src = $nested_info_template->fieldvalue(
								Cleanup_Shared::SOURCE);
							$nested_auth = $nested_info_template->fieldvalue(
								Cleanup_Shared::AUTHOR);
							$nested_date = $nested_info_template->fieldvalue(
								Cleanup_Shared::DATE);
							$nested_permission = $nested_info_template->fieldvalue(
								Cleanup_Shared::PERMISSION);
							$nested_otherv = $nested_info_template->fieldvalue(
								Cleanup_Shared::OTHER_VERSIONS);
							
							$nested_desc = str_replace($nested_info_text, 
								$nested_desc . ($after ? "\n$after" : ""), 
								$t->fieldvalue(Cleanup_Shared::DESCRIPTION));
							$nested_auth = $this->nested_info_fix($nested_auth, 
								$t->fieldvalue(Cleanup_Shared::AUTHOR), "$1.\r\n$2");
							$nested_date = $this->nested_info_fix($nested_date, 
								$t->fieldvalue(Cleanup_Shared::DATE), "$1<br/>\r\n$2");
							$nested_src = $this->nested_info_fix($nested_src, 
								$t->fieldvalue(Cleanup_Shared::SOURCE));
							$nested_permission = $this->nested_info_fix($nested_permission, 
								$t->fieldvalue(Cleanup_Shared::PERMISSION));
							$nested_otherv = $this->nested_info_fix($nested_otherv, 
								$t->fieldvalue(Cleanup_Shared::OTHER_VERSIONS), "$1\r\n$2");
							
							$t->updatefield(Cleanup_Shared::DESCRIPTION, $nested_desc);
							$t->updatefield(Cleanup_Shared::SOURCE, $nested_src);
							$t->updatefield(Cleanup_Shared::DATE, $nested_date);
							$t->updatefield(Cleanup_Shared::AUTHOR, $nested_auth);
							$t->updatefield(Cleanup_Shared::PERMISSION, $nested_permission);
							$t->updatefield(Cleanup_Shared::OTHER_VERSIONS, $nested_otherv);
							$ci->set_text($t->wholePage());
						} else {
							break;
						}
					} else {
						break;
					}
				} else {
					break;
				}
			} else {
				break;
			}
		}
		
		/**
		 * Remove language template within language template (happens mostly after nested
		 * {{information}} fix above)
		 */
		$sanity_check = 0;
		while (preg_match(
			"/\s*\{\{\s*(" . $this->constants["langlinks_regex"] .
				 ")\s*\|(?:\s*1\s*\=)?\s*\{\{\s*\\1\s*\|/u", $ci->get_text(), $langlink_match) &&
				 $t = $ci->get_template($langlink_match[1])) {
			$value = $t->fieldvalue("1");
			
			// see https://commons.wikimedia.org/w/index.php?action=edit&oldid=152580266
			if ($sanity_check++ === 20) {
				ogrebotMail("sanity_check = 20! aborting.");
				break;
			}
			$sublang_template = Template::extract($value, $langlink_match[1]);
			if ($sublang_template) {
				$value = $sublang_template->__get("before") . $sublang_template->fieldvalue("1") .
					 $sublang_template->__get("after");
				$t->updatefield("1", $value);
				$ci->set_text($t->wholePage());
			}
		}
	}
}