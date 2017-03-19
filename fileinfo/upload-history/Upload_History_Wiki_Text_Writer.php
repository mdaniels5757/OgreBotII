<?php
/**
 * 
 * @author magog
 *
 */
abstract class Upload_History_Wiki_Text_Writer {
	
	/**
	 * 
	 * @var ProjectData $project_data
	 */
	protected $project_data;
	
	/**
	 * 
	 * @param string $project
	 * @param string $title
	 * @param array $image_infos
	 * @param bool $omit_dupes
	 * @param bool $header  	
	 * @return string
	 */
	public function write_from_image_info($project, $title, array $image_infos, $omit_dupes, $header) {
		return $this->write($project, $title, 
			Upload_History_Instance::read_from_wiki_image_info($image_infos), $omit_dupes, $header);
	}
	
	/**
	 *
	 * @param string $project        	
	 * @param string $title        	
	 * @param Upload_History_Instance[] $upload_history_instances        	
	 * @param bool $omit_dupes
	 * @param bool $header  	
	 * @return string
	 */
	public function write($project, $title, array $upload_history_instances, $omit_dupes, $header) {
		global $validator;
		
		$validator->validate_arg($project, "string");
		$validator->validate_arg($title, "string");
		$validator->validate_arg_array($upload_history_instances, "Upload_History_Instance");
		$validator->validate_arg($omit_dupes, "bool");
		
		$this->project_data = new ProjectData($project);
		$this->project_data->setDefaultHostWiki("commons.wikimedia");
		
		$urlencode = urlencode(preg_replace("/\s+/", "_", $title));
		if ($header) {
			$header_string = "== {{Original upload log}} ==\n";
		} else {
			$header_string = "";
		}
		$header_string .= "{{original description page|$project|$urlencode}}";
		
		$upload_history_instances = array_reverse($upload_history_instances);
		if ($omit_dupes) {
			$upload_history_instances = array_filter($upload_history_instances, 
				function(Upload_History_Instance $instance) {
					return !$instance->unchanged;
				});
		}
		$history = join("", 
			array_map_filter($upload_history_instances, 
				(new ReflectionMethod(get_class($this), "serialize_upload_instance"))->getClosure($this)));
		
		return "$header_string\n" . $this->get_header() . $history . $this->get_footer();
	}
	
	/**
	 * @return Upload_History_Wiki_Text_Writer[]
	 */
	public static function get_instances() {
		return Classloader::get_all_instances_of_type(self::class);
	}
	
	/**
	 * @param string $name
	 * @return self
	 * @throws ArrayIndexNotFoundException
	 */
	public static function get_instance($name) {
		return array_search_callback(self::get_instances(),
			function (Upload_History_Wiki_Text_Writer $writer) use($name) {
				return $name === $writer->get_name();
			}, true);
	}
	
	
	/**
	 * 
	 * @param Upload_History_Instance $instance
	 * @return string
	 */
	protected abstract function serialize_upload_instance(Upload_History_Instance $instance);
	
	/**
	 * @return string
	 */
	protected abstract function get_header();

	/**
	 * @return string
	 */
	protected abstract function get_footer();

	/**
	 * @return string
	 */
	public abstract function get_name();
}