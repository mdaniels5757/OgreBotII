<?php
/**
 * @author magog
 *
 */
class Cleanup_Transferred_From_Jan_Luca_Bot implements Cleanup_Module {
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {
		$t = $ci->get_template(Cleanup_Shared::INFORMATION);
		if (!$t) {
			return;
		}
		/**
		 * (Transferred by [[User:*|*]]) -> source field (Jan Luca & Magnus bot)
		 */
		$transferred_wrong_field_re = "/\s*(\(Transferr?ed by\s+\[\[User\:[^\]\|]+\|[^\]\|]+\]\]\))(\s*)$/mu";
		$transferred_wrong_field_re_2 = "/\((Transferr?ed by\s+\[\[User\:[^\]\|]+\|[^\]\|]+\]\])\s*\//u";
		$authorfield = $t->fieldvalue(Cleanup_Shared::AUTHOR);
		$sourcefield = $t->fieldvalue(Cleanup_Shared::SOURCE);
		if ($authorfield !== null && $sourcefield !== null) {
			if (preg_match($transferred_wrong_field_re, $authorfield, $match_bad_transfer)) {
				$ci->set_sigificant_changes();
				$t->updatefield(Cleanup_Shared::AUTHOR, 
					preg_replace($transferred_wrong_field_re, "$2", $authorfield));
				$t->updatefield(Cleanup_Shared::SOURCE, 
					preg_replace("/^([\s\S]+?)(\s*)$/u", 
						"$1 " . escape_preg_replacement($match_bad_transfer[1]) . "$2", $sourcefield));
				$ci->set_text($t->wholePage());
			} else if (preg_match($transferred_wrong_field_re_2, $authorfield, $match_bad_transfer)) {
				$ci->set_sigificant_changes();
				$t->updatefield(Cleanup_Shared::AUTHOR, 
					preg_replace($transferred_wrong_field_re_2, "(", $authorfield));
				$t->updatefield(Cleanup_Shared::SOURCE, 
					preg_replace("/^(.+?)(\s*)$/u", 
						"$1 (" . escape_preg_replacement($match_bad_transfer[1]) . ")$2", 
						$sourcefield));
				$ci->set_text($t->wholePage());
			}
		}
	}
}