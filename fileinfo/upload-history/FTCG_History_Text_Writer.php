<?php
/**
 * 
 * @author magog
 *
 */
class FTCG_History_Text_Writer extends Upload_History_Wiki_Text_Writer {
	
	private static $prefix_map = [
			"wikipedia" => "w:", "wiktionary" => "wikt:", "wikiquote" => "q:", "wikinews" => "n:", 
			"wikibooks" => "b:", "wikisource" => "s:", "wikiversity" => "v:", "wikivoyage" => "voy:",
			"mediawiki" => "mw:", "wikimedia" => ""
	];
	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Upload_History_Wiki_Text_Writer::serialize_upload_instance()
	 */
	protected function serialize_upload_instance(Upload_History_Instance $instance) {
		$timestamp = date("H:i, j F Y", parseMediawikiTimestamp($instance->timestamp));
		
		$user = "{{uv|$instance->user|" . $this->get_interwiki_prefix(). "}}";
		$bytes = number_format($instance->size);
		$comment = $instance->comment;
		$height = (int)$instance->height;
		$width = (int)$instance->width;
		return "|-\n| $timestamp || ${width} × ${height} ($bytes bytes) || $user || <nowiki>$comment</nowiki>\n";
	}
	
	private function get_interwiki_prefix() {
		return array_key_or_exception(self::$prefix_map, $this->project_data->getProject()) . 
			$this->project_data->getSubproject() . ":";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Upload_History_Wiki_Text_Writer::get_header()
	 */
	protected function get_header() {
		return "\n{| class=\"wikitable\"\n! {{int:filehist-datetime}} !! {{int:filehist-dimensions}} !! " . 
			"{{int:filehist-user}} !! {{int:filehist-comment}}\n";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Upload_History_Wiki_Text_Writer::get_footer()
	 */
	protected function get_footer() {
		return "|}\n";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Upload_History_Wiki_Text_Writer::get_name()
	 */
	public function get_name() {
		return "FtCG";
	}
}