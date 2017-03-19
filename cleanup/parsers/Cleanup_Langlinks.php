<?php
/**
 * Fix {{langlink|2+2=4}} -> {{langlink|1=2+2=4}}, also, headers at the end of the
 * description with nothing below them.
 * @author magog
 *
 */
class Cleanup_Langlinks implements Cleanup_Module {
	
	/**
	 *
	 * @var string
	 */
	private $category_links_regex;
	
	/**
	 *
	 * @var string
	 */
	private $langlinks_regex;
	
	/**
	 * 
	 * @param Cleanup_Package $cleanup_package
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		$this->category_links_regex = "(?i:$constants[category_regex])";
		$this->langlinks_regex = $constants["langlinks_regex"];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$this->do_cleanup($ci, false);
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @param bool $made_change        	
	 * @return void
	 * @throws Cleanup_Abort_Exception
	 */
	private function do_cleanup(Cleanup_Instance $ci, $made_change) {
		preg_match_all(
			"/\{\{\s*(" . $this->langlinks_regex . "|[Uu]nknown[ _]+language)\s*(?:\||\}\})/u", 
			$ci->get_text(), $langlinks, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		
		foreach ($langlinks as $langlink) {
			$langlink_uc = ucfirst($langlink[1][0]);
			
			/* first check for empty language description; remove it if applicable */
			$empty_desc = "/^\{\{\s*" . preg_quote($langlink[1][0]) .
				 "\s*(?:\|\s*(?:1\s*\=\s*)?)?\}\}(\s*\{\{\s*(?:" . $this->langlinks_regex .
				 "|[Uu]nknown[ _]+language)|\s*\||\}\})/u";
			
			if (preg_match_all($empty_desc, substr($ci->get_text(), $langlink[0][1]), $match, 
				PREG_OFFSET_CAPTURE)) {
				$ci->set_text(
					substr($ci->get_text(), 0, $langlink[0][1]) . preg_replace($empty_desc, "$1", 
						substr($ci->get_text(), $langlink[0][1])), false);
				return $this->do_cleanup($ci, $made_change);
			}
			
			/*
			 * need to cut text beforehand in case there is more than one template
			 * (which will cause the extract function to run over the same template
			 * twice and leave the second one alone)
			 */
			$t = Template::extract($ci->get_text(), $langlink_uc, $langlink[0][1]);
			if (!$t) {
				$ci->add_warning(Cleanup_Shared::TEMPLATE_UNCLOSED);
				break;
			}
			$madechange = false;
			$madechange2 = false;
			
			// 2+2=4 -> 1=2+2=4
			$subtemplates_removed = iter_replace("/\{\{[\s\S]+?\}\}/u", "", substr($t, 2));
			$needs_equal_replace = stripos($subtemplates_removed, "=") !== false;
			
			$fields = $t->__get("fields");
			if ($needs_equal_replace && !array_key_exists("1", $fields)) {
				foreach ($fields as $key => $val) {
					if ($key != "inline") {
						// preserve the order of the array
						$newfields = array();
						foreach ($fields as $oldkey => $oldval) {
							if ($oldkey === $key) {
								$newkey = 1;
								$oldval = "1=$oldval";
							} else {
								$newkey = $oldkey;
							}
							$newfields[$newkey] = $oldval;
						}
						$t->__set("fields", $newfields);
						$madechange = true;
						break;
					}
				}
			}
			
			/* check for language description with a header at the end; also remove categories, interwikis */
			$field1 = $t->fieldvalue(1);
			$field1 = preg_replace("/^\s*1\s*\=/u", "", $field1); /* buggy template class */
			$field1 = iter_replace(
				"/(?:^|\s*\n)\s*\[\[\:(?:" . $this->langlinks_regex . ")\:((?:" .
					 $this->langlinks_regex . ")\:[^\]\|\}]+)\s*\|\s*\\1?\s*\]\]\s*$/u", "", $field1, 
					$madechange);
			$field1 = ltrim(
				iter_replace(
					"/(?:^|\r?\n)\s*?(?:\[\[\:(?:w[a-z]+\:)?(?:" . $this->langlinks_regex . ")\:" .
						 $this->category_links_regex .
						 "\:[^\]\|\}]+\s*(?:\|\s*[^\]]*\s*)?\]\]\s*?)+\s*$/u", "$1", $field1, 
						$madechange));
			$field1 = iter_replace("/\s*" . Cleanup_Shared::BR . "\s*$/u", "", $field1, 
				$madechange2);
			
			$t->updatefield("1", $field1);
			$empty_desc = "/(?:\r?\n|^)\s*\=\=.+?\=\=\s*$/u";
			if (preg_match($empty_desc, $field1)) {
				$madechange = true;
				$newval = trim(preg_replace($empty_desc, "", $field1));
				
				// we can remove the template altogether now
				if (preg_match("/^(?:1\s*\=\s*)?\s*$/u", $newval)) {
					$ci->set_text($t->__get("before") . $t->__get("after"), false);
					return $this->do_cleanup($ci, $made_change);
				} 				

				// can't remove altogether but instead just axe the header
				else {
					$t->updatefield("1", $newval);
				}
			}
			
			if ($madechange || $madechange2) {
				if ($madechange) {
					$made_change = true;
				}
				$ci->set_text($t->wholePage(), false);
				
				// if this was wrong, we need to rerun the WHOLE thing if there is a nested template
				if (preg_match("/\{\{\s*[Ii]nformation\s*\|[\s\S]*\{\{\s*[Ii]nformation\s*\|/u", 
					$ci->get_text())) {
					throw new Cleanup_Abort_Exception(
						"Nested {{information}}, my " .
							 "author made me didn't make me smart enough to know how to handle this.");
				}
				
				return $this->do_cleanup($ci, true);
			}
		}
		if ($made_change) {
			$ci->set_sigificant_changes();
		}
	}
}