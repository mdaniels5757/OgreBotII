<?php
/**
 * @author magog
 *
 */
class Cleanup_Location_Field implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * *******************************************************
		 * Location field in information template, if applicable *
		 * *******************************************************
		 */
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($t) {
			$locationfield = $t->fieldvalue(Cleanup_Shared::LOCATION);
			if ($locationfield) {
				$other_fields = $t->fieldvalue(Cleanup_Shared::OTHER_FIELDS);
				$front_spacing = "";
				if (preg_match("/^(\s*)(\S[\s\S]*)$/u", $other_fields, $matches)) {
					$front_spacing = $matches[1];
					$other_fields = "\r\n" . $matches[2];
				}
				$newval = "$front_spacing{{Information field|name={{ucfirst:" .
					 "{{location/i18n}}}}|value=" . mb_trim($locationfield) . "}}$other_fields";
				$t->updatefield(Cleanup_Shared::OTHER_FIELDS, $newval);
				$t->removefield(Cleanup_Shared::LOCATION);
				$ci->set_text($t->wholePage());
			}
		}
	}
}