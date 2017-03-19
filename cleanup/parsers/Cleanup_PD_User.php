<?php
/**
 * @author magog
 *
 */
class Cleanup_PD_User implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		// {{PD-user-w|en|wikipedia}} -> {{PD-user-en}}
		$ci->preg_replace(
			"/\{\{\s*PD\-user\-w\s*\|\s*(als|ar|az|bg|ca|cs|da|de|en|es|fi|fr|he|hi|hr|hu|it|ja|ka" .
				 "|lt|lv|ml|nl|nn|no|pl|pt|ro|ru|simple|sk|sl|sv|th|uk|vls|zh)\s*\|\s*wikipedia\s*\|" .
				 "\s*(.+?)\s*\}\}/u", "{{PD-user-$1|$2}}", false);
		$ci->preg_replace(
			"/\{\{\s*PD\-user\-w\s*\|\s*project\s*\=\s*wikipedia\s*\|\s*language\s*\=\s*" .
				 "(als|ar|az|bg|ca|cs|da|de|en|es|fi|fr|he|hi|hr|hu|it|ja|ka|lt|lv|ml|nl|nn|no|pl|pt|ro|" .
				 "ru|simple|sk|sl|sv|th|uk|vls|zh)\s*\|\s*user\s*\=\s*([^\[\]\{\}\|]+)\}\}/u", 
				"{{PD-user-$1|1=$2}}", false);
		$ci->preg_replace(
			"/\{\{\s*PD\-user\-([a-z]{2,})\s*\|\s*1\s*\=\s*([^\[\]\{\}\|\=]+)\s*\}\}/u", 
			"{{PD-user-$1|$2}}", false);
	}
}