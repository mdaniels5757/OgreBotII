<?php
/**
 * @author magog
 *
 */
class Cleanup_Magnus_Date_Bugs implements Cleanup_Module {

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace(
			"/\{\{[Oo]riginal upload date\|(\d{4})\-(\d{2})-(\d{2})\}\} \(original upload date\)/u",
			"{{Date|$1|$2|$3}} (original upload date)");
		$ci->preg_replace(
			"/\{\{[Oo]riginal upload date\|(\d{4})\-(\d{2})-(\d{2})\}\} \(first version\); " .
			"\{\{[Oo]riginal upload date\|(\d{4})\-(\d{2})-(\d{2})\}\} \(last version\)/u",
			"{{Date|$1|$2|$3}} (first version); {{Date|$4|$5|$6}} (last version)");
	}
	

}