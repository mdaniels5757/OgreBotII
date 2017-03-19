<?php
/**
 * @author magog
 *
 */
class Cleanup_Remove_NOTOC implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$ci->preg_replace("/^\s*?__NOTOC__(\s*?\\n)?/mu", "", false);
	}
}