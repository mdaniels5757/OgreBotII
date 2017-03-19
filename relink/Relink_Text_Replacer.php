<?php
interface Relink_Text_Replacer {
	
	/**
	 * 
	 * @param Relink_Text $relink_text
	 * @return string|null returns null if no changes made.
	 */
	public function replace(Relink_Text $relink_text);
}