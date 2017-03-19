<?php
class Array_Parameter_Extractor {
	
	/**
	 * 
	 * @var array[]
	 */
	private $params;
	
	/**
	 * Assocative array in the form: $param => $required or
	 *   * Just a $param, in which case $required is assumed
	 * @param boolean[] $params
	 */
	public function __construct($params) {
		$this->params = $params;
	}
	
	/**
	 * 
	 * @param array $array
	 * @throws ArrayIndexNotFoundException
	 * @return array
	 */
	public function extract($array) {
		$new_array = [];
		foreach ($this->params as $param => $required) {
			if ($required && !array_key_exists($param, $array)) {
				throw new ArrayIndexNotFoundException(print_r($param, true));
			}
			$new_array[] = @$array[$param];
		}
		return $new_array;
	}
}