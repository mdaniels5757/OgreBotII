<?php
/**
 * @author magog
 *
 */
class Cleanup_Warn_Citation_Removed implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * Warning about the presence of certain blank templates
		 */
		if (preg_match(
			"/\{\{(?:\s*[Cc]itation|[Cc]ite|[Cc]ite[ _]+book|[Cc]itation\/authors|[Cc]itation\/make[ _]+" .
				 "link|[Cc]itation\/identifier|[Cc]ite[ _]+journal|[Cc]ite[ _]+patent|[Cc]ite[ _]+web)" .
				 "\s*\}\}/u", $ci->get_text()) && !preg_match(
			"/\{\{(?:\s*[Cc]itation|[Cc]ite|[Cc]ite[ _]+book|[Cc]itation\/authors|[Cc]itation\/make[ _]+" .
			 "link|[Cc]itation\/identifier|[Cc]ite[ _]+journal|[Cc]ite[ _]+patent|[Cc]ite[ _]+web)" .
			 "\s*\|/u", $ci->get_text())) {
			$ci->add_warning(Cleanup_Shared::CITE_NO_REPLACEMENT);
		}
	}
}