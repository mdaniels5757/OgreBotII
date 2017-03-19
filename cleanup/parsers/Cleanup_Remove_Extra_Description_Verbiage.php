<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_Extra_Description_Verbiage implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		/**
		 * No description fix
		 */
		$ci->preg_replace(
			"/(\|\s*[Dd]escription\s*\=\s*)\{\{\s*[a-z\-]{2,}\s*\|\s*'*\s*[Nn]o original description" .
			"\s*'*\s*\}\}/u", "$1");
		
	}
}