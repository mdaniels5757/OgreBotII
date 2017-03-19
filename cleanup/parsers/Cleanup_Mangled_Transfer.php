<?php
/**
 * @author magog
 *
 */
class Cleanup_Mangled_Transfer implements Cleanup_Module {
	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		if (preg_match(
			"/\{\{\s*original\s+description\s*\|\s*[a-z\-]+\s*\#\s*w[a-z]+\s*" .
				 "\|[^\s\#\}\{\[\]]+#[A-Za-z]+\s*\}\}/u", $ci->get_text())) {
			$ci->add_warning(Cleanup_Shared::MANGLED_TRANSFER);
		}
	}
}