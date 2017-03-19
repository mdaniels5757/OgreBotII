<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Fastily implements Cleanup_Module {
	
	/**
	 * 
	 * @var string[] 
	 */
	private $fastily_format_texts;
	
	/**
	 * 
	 */
	public function __construct() {
		$this->fastily_format_texts = [
				"== {{Original upload log}} ==\n{{original description|",
				"{| class=\"wikitable\"\n! {{int:filehist-datetime}} !! {{int:filehist-" . 
					"dimensions}} !! {{int:filehist-user}} !! {{int:filehist-comment}}\n|-"
		];
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Module::cleanup()
	 */
	public function cleanup(Cleanup_Instance $ci) {

		$information = $ci->get_template(Cleanup_Shared::INFORMATION);

		if (!$information || !$this->is_fastily_format($ci)) {
			return;
		}
		
		$changes = false;
		$source = $information->fieldvalue(Cleanup_Shared::SOURCE);
		if ($source) {
			$new_source = preg_replace(
				[
					"/^\{\{Transferred from\|en\.wikipedia\}\}\n\(Original text : ''([\s\S]+?)''\)$/m", 
					"/^\{\{Transferred from\|en\.wikipedia\}\}\n\(\{\{original text\|nobold=1\|1=([\s\S]+?)\}\}\)$/m"], 
				"$1", $source);
			if ($new_source !== $source) {
				$information->updatefield(Cleanup_Shared::SOURCE, $new_source);
				$changes = true;
			}
		}
		
		$date = $information->fieldvalue(Cleanup_Shared::DATE);
		if ($date) {
			$new_date = preg_replace(
				"/^(\s*\S[\s\S]*)<br\/>\n\(\{\{Original upload date\|[\d\-]+\}\}\)$/m", "$1", $date);
			if ($new_date !== $date) {
				$information->updatefield(Cleanup_Shared::DATE, 
					preg_replace(
						"/^(\s*\S[\s\S]*)<br/>\n\(\{\{Original upload date\|[\d\-]+\}\}\)$/m", "$1", 
						$new_date));
				$changes = true;
			}
		}
		
		$author = $information->fieldvalue(Cleanup_Shared::AUTHOR);
		if ($author) {
			$new_author = preg_replace(
				"/^([\S\s]*?)\.\n\{\{Original uploader\|.+?\|wikipedia\|en\}\}$/", "$1", $author);
			if ($new_author !== $author) {
				$information->updatefield(Cleanup_Shared::AUTHOR, $new_author);
				$changes = true;
			}
		}
		
		if ($changes) {
			$ci->set_text($information->wholePage());
		}
	}
	
	/**
	 *
	 * @param Cleanup_Instance $ci        	
	 * @return void
	 */
	private function is_fastily_format(Cleanup_Instance $ci) {
		foreach ($this->fastily_format_texts as $format_text) {
			if (strpos($ci->get_text(), $format_text) === false) {
				return false;
			}
		}
		return true;
	}
}