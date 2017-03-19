<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Original_Text implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		/**
		 * Original text
		 */
		$ci->preg_replace(
			"/\(original text\s*:\s*(\'*)((?:.|(?:" . Cleanup_Shared::BR .
			")?\s*(\'*)(?i:previously published|original publication|immediate source|" .
			"immediate)\'*\:?)*?)\\1\)/iu", "({{original text|nobold=1|1=$2}})");
		
		/**
		 * if only "original text" is present in source field; remove it, as it's redundant; {{own}} work
		 */
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($t) {
			$field = $t->fieldvalue(Cleanup_Shared::SOURCE);
			if ($field) {
				$madechange = false;
				
				/**
				 * Rm {{Transferred from}} where not of use
				 */
				if (preg_match("/\=+\s*\{\{\s*[Oo]riginal[ _]+upload[ _]+log[ _]*\}\}\s*\=+\s*\n/u", 
					$ci->get_text())) {
					preg_replace_track(
						"/(\S)\s*" . Cleanup_Shared::OPT_BR .
							 "\s*\{\{\s*[Tt]ransferred[ _]+from\s*\|\s*[a-z\-]+\.w[a-z]+\s*\}\}(\s*)$/u", 
							"$1$2", $field, $madechange);
				}
				if ($madechange) {
					$ci->set_sigificant_changes();
				}
				
				$t2 = Template::extract($field, "original text");
				if ($t2) {
					$field2 = preg_replace("/^\s*1\s*\=\s*/u", "", $t2->fieldvalue("1"));
					$replacement = str_replace($t2, "", $field);
					if (preg_match("/^\s*$/u", $replacement) ||
						 preg_match("/^\s*\(\s*\)\s*$/u", $replacement)) {
						$field = preg_replace("/^(\s*)[\s\S]+?(\s*)$/u", 
							"\${1}" . escape_preg_replacement($field2) . "\${2}", $field); /* not a major change */
						$madechange = true;
					}
				}
				
				// remove some weird bug from my JS script or another script
				$field = preg_replace_track("/" . Cleanup_Shared::BR . "\.?(\s*)$/u", "$1", $field, 
					$madechange);
				
				// remove superfluous parens around field
				preg_replace_track("/^\s*\(([^\(\)]+\)\s*)$/u", "$1", $field, $madechange);
		
				if ($madechange) {
					$t->updatefield(Cleanup_Shared::SOURCE, $field);
					$ci->set_text($t->wholePage(), false);
				}
			}
		}
		
		/**
		 * ditto with permission field, with a bit of extra work added
		 */
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($t) {
			$field = $t->fieldvalue(Cleanup_Shared::PERMISSION);
			if ($field) {
				$t2 = Template::extract($field, "original text");
				if ($t2) {
					$field2 = preg_replace("/^\s*1\s*\=\s*/u", "", $t2->fieldvalue("1"));
					$replacement = str_replace($t2, "", $field);
					if (preg_match("/^\s*$/u", $replacement) ||
						 preg_match("/^\s*\(\s*\)\s*$/u", $replacement)) {
						$field = str_replace($t2, $field2, $field); /* not a major change */
						$madechange = true;
					}
				}
				
				// remove superfluous parens around field
				preg_replace_track("/^\s*\(([^\(\)]+)\)(\s*)$/u", "$1$2", $field, $madechange); /* not a major change */
				
				// unnecessary phrase
				preg_replace_track("/^\s*(?:see below|下記を参照)\s*?\,?\.?;?(\s*)$/iu", "$1$2", $field, 
					$madechange); /* not a major change */
				
				if ($madechange) {
					$t->updatefield(Cleanup_Shared::PERMISSION, $field);
					$ci->set_text($t->wholePage(), false);
				}
			}
		}
	}
}