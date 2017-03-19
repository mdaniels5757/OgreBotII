<?php
/**
 * @author magog
 *
 */
class Cleanup_Human_Changes implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		/**
		 * Rm botmove iff a human is calling us.
		 */
		if ($ci->is_human()) {
			$ci->preg_replace("/\{\{BotMoveToCommons.*\}\}\s*\n/u", "");
			$ci->preg_replace(
				"/\{\{CH2MoveToCommons\|[a-z\-]+.w[a-z]+\|year\=\d+\|month=\w+\|day\=\d\}\}\s*\n/u", 
				"");
			$ci->preg_replace(
				"/The tool and the bot are operated by \[\[User:Jan Luca\]\] and \[\[User:Magnus Manske" .
					 "\]\]\./u", "");
			$ci->preg_replace(
				"/The upload bot is \[\[User:CommonsHelper2 Bot\]\] which is called by " .
					 "\[http:\/\/toolserver\.org\/~commonshelper2\/index\.php CommonsHelper2\]\./u", 
					"");
		}
	}
}