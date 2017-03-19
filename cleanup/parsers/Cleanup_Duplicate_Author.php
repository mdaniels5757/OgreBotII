<?php
class Cleanup_Duplicate_Author implements Cleanup_Module {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		preg_match_all("/\{\{[_ ]*([Ss]elf2?)[_ ]*\s*\|/u", $ci->get_text(), $all_selfs,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		foreach ($all_selfs as $next_self) {
			$substr = substr($ci->get_text(), $next_self[0][1]);
			$self_tmpl = $ci->get_template($next_self[1][0]);
			// we have our self template; now look for a duplicate author field; have to go through some machinations because Template::extract() just combines the fields by default
			if ($self_tmpl) {
				$template_text = substr($substr, 0,
					strlen($substr) - strlen($self_tmpl->__get("after")));
				$template_text = iter_replace("/([\S\s])\{\{[\S\s]*?\}\}([\S\s])/u", "$1$2",
					$template_text); // remove nested templates for easier analysis
					$template_text = iter_replace("/\[\[.*?\]\]/u", "$1$2", $template_text); // remove links for easier analysis
					if (preg_match("/\|\s*[Aa]uthor\s*\=[\S\s]*?\|\s*[Aa]uthor\s*\=([\S\s]*?)(?:\||\}\})/u",
						$template_text, $match)) {
							$ci->add_warning(Cleanup_Shared::DUPLICATE_AUTHOR);
							$ci->add_duplicate_author($match[1]);
							return;
						}
			}
		}
	}
}