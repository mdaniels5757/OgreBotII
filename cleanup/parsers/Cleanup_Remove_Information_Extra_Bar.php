<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Information_Extra_Bar implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$information = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($information) {
			$fields = $information->__get("fields");
			$previousField = null;
			foreach ($fields as $fieldname => $fieldvalue) {
				// echo "$fieldname; $previousField; $fieldvalue ".(is_int($fieldname) && strlen(mb_trim($fieldvalue))==0)."\n";
				if (is_int($fieldname)) {
					$regex = "/^(\s*)$fieldname(\s*)\=(\s*)$/u";
					$fieldvalueTrim = mb_trim(preg_replace($regex, "", $fieldvalue));
					if (strlen($fieldvalueTrim) == 0) {
						unset($fields[$fieldname]);
						// maintain spacing by moving it to the previous field. If none exists, we have to change the name itself
						$spacing = preg_replace($regex, "$1$2$3", $fieldvalue);
						if ($previousField == null) {
							$information->rename($information->__get("name") . $spacing);
						} else {
							$fields[$previousField] .= $spacing;
						}
						$information->__set("fields", $fields);
						$ci->set_text($information->wholePage());
						$this->cleanup($ci);
						return;
					}
				}
				$previousField = $fieldname;
			}
		}
	}
}