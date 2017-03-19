<?php
class Hook {
	
	/**
	 * 
	 * @var callback[]
	 */
	private $callbacks = array();
	
	/**
	 * 
	 * @var bool
	 */
	private $is_run;
	
	/**
	 * 
	 * @param callback $callback
	 * @return void
	 */
	private function run($callback) {
		global $logger;
		if ($logger) {
			$logger->trace("Running callback.".print_r($callback, true));
		}
		$callback();
	}

	/**
	 * 
	 * @param callback $callback
	 * @return void
	 */
	public function add($callback) {
		global $validator;
		
		if ($validator) {
			$validator->validate_arg($callback, "function");
		}
		
		if ($this->is_run) {
			$this->run($callback);
		} else {
			$this->callbacks[] = $callback;
		}
	}
	
	/**
	 * @return void
	 */
	public function trigger() {
		global $logger;
		
		if ($this->callbacks) {
			if ($logger) {
				$logger->trace("Running " . count($this->callbacks) . " callbacks.");
			}
			foreach ($this->callbacks as $callback) {
				$this->run($callback);
			}
		}
		
		$this->is_run = true;
	}
}