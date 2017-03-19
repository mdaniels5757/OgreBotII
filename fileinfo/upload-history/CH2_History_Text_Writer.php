<?php
/**
 * 
 * @author magog
 *
 */
class CH2_History_Text_Writer extends Upload_History_Wiki_Text_Writer {
	
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Upload_History_Wiki_Text_Writer::serialize_upload_instance()
	 */
	protected function serialize_upload_instance(Upload_History_Instance $instance) {
		$timestamp = preg_replace("/^(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})Z$/", "$1 $2",
				$instance->timestamp);
		
		$user = $this->project_data->formatPageLink("User:$instance->user", $instance->user);
		$bytes = str_replace(",", " ", $instance->size);
		$comment = preg_replace("/\s+/", " ", $instance->comment);
		$height = (int)$instance->height;
		$width = (int)$instance->width;

		return "*$timestamp | $user | $bytes | ${width}×$height | <small><nowiki>$comment</nowiki></small>\n";
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
		return "\nUpload date | User | Bytes | Dimensions | Comment\n\n";
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Upload_History_Wiki_Text_Writer::get_footer()
	 */
	protected function get_footer() {
		return "";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Upload_History_Wiki_Text_Writer::get_name()
	 */
	public function get_name() {
		return "CH2";
	}
}