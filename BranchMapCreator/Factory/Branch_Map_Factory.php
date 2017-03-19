<?php
/**
 * 
 * @author magog
 * 
 */
abstract class Branch_Map_Factory {
	
	/**
	 *
	 * @throws IllegalArgumentException
	 * @return Latitude_Longitude_Svg_Creator
	 */
	public function create() {
		global $logger;
		
		$args = $this->get_args();
		$type = $args['type'];
		
		$instance_names = Classloader::get_all_class_names_of_type(
			Latitude_Longitude_Svg_Creator::class);
		
		foreach ($instance_names as $name) {
			if ($type === $name::get_arg_key()) {
				$logger->info("Loading $name");
				return $name::load_from_request_args($args);
			}
		}
		
		throw new IllegalArgumentException("Type not recognized.");
	}
	
	/**
	 * A bit of a cludge until when/if I write something less lazy
	 * 
	 * @return string[]
	 */
	protected abstract function get_args();
}