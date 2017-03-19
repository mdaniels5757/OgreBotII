<?php
/**
 * @author magog
 *
 */
class Cleanup_Prettify implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/* not major change */
		$ci->preg_replace(
			"/\{\{([Oo]riginal[ _]+uploader|[Uu]ser[ _]+at[ _]+project)\|1\=([^\]\|\=]+?)\|" .
				 "2=([^\]\|]+?)\|3=([^\]\|]+?)\}\}/u", "{{" . "$1|$2|$3|$4}}", false);
	}
}