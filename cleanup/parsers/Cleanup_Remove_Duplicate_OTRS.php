<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Duplicate_OTRS implements Cleanup_Module {
	
	/**
	 *
	 * @var string
	 */
	private $otrs_re;
	
	/**
	 *
	 * @var string
	 */
	private $otrs_re_template_name;
	/**
	 *
	 * @param Cleanup_Package $cleanup_package        	
	 */
	public function __construct(Cleanup_Package $cleanup_package) {
		$constants = $cleanup_package->get_constants();
		
		$this->otrs_re = "/\{\{\s*$constants[otrs_regex]\s*\|\s*(?:id\s*\=\s*)?(\S[\s\S]*?)\s*\}\}/u";
		$this->otrs_re_template_name = "(?:$constants[otrs_regex])";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if ($t) {
			$field = $t->fieldvalue(Cleanup_Shared::PERMISSION);
			preg_match_all($this->otrs_re, $field, $matches);
			
			foreach ($matches[1] as $match) {
				if (is_numeric($match)) {
					$ci->preg_replace(
						"/" . Cleanup_Shared::LICENSE_HEADER . "(" .
							 Cleanup_Shared::INTERMEDIATE_TEMPLATES . ")\{\{\s*" .
							 $this->otrs_re_template_name .
							 "\s*(?:\|\s*(?:id\s*\=\s*)?$match\s*)?\}\}(?:\s*?\\n)?/u", "$1$3");
				}
			}
		}
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 ")\{\{\s*" . $this->otrs_re_template_name .
				 "\s*(?:\|\s*(?:id\s*\=\s*))?\}\}(?:\s*?\\n)?(" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES . "\s*\{\{\s*" . $this->otrs_re_template_name .
				 "\s*\|\s*(?:id\s*\=\s*)?\d*\s*\}\})/u", "$1$3$4");
		$ci->preg_replace(
			"/" . Cleanup_Shared::LICENSE_HEADER . "(" . Cleanup_Shared::INTERMEDIATE_TEMPLATES .
				 "\{\{\s*" . $this->otrs_re_template_name . "\s*\|\s*(?:id\s*\=\s*)?(\d+)\s*\}\}" .
				 Cleanup_Shared::INTERMEDIATE_TEMPLATES . "\s*)\{\{\s*" .
				 $this->otrs_re_template_name .
				 "\s*(?:\|\s*(?:id\s*\=\s*)?(\\4)\s*)?\}\}(?:\s*?\\n)?/u", "$1$3");
		
		$otrs_warning = false;
		$ci->preg_replace("/\{\{\s*" . $this->otrs_re_template_name . "\s*\}\}(?:\s*?\\n)?/u", "", 
			true, $otrs_warning);
		if ($otrs_warning) {
			$ci->add_warning(Cleanup_Shared::OTRS_REMOVED);
		}
	}
}