<?php
class Web_Branch_Map_Factory extends Branch_Map_Factory {
	
	/**
	 *
	 * @var string
	 */
	private $key;
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Branch_Map_Factory::get_args()
	 */
	protected function get_args() {
		global $env;
		
		return $env->get_request_args();
	}
	
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Branch_Map_Factory::create()
	 */
	public function create() {
		$branch_mapper = $this->get_stored();
		
		if ($branch_mapper === null) {
			$branch_mapper = parent::create();
			$branch_mapper->load_and_cache(); // cache results before storing
			$_SESSION["branchmapper-" . get_request_key()] = $branch_mapper;
		}
		
		return $branch_mapper;
	}
	
	/**
	 *
	 * @return Latitude_Longitude_Svg_Creator|null
	 */
	public function get_stored() {
		session_start();
		return @$_SESSION["branchmapper-" . get_request_key()];
	}
}