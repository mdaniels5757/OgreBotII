<?php

interface Progress_Indicator {
	
	/**
	 * 
	 * @param int $step
	 * @return void
	 */
	function setup($step_count);
	
	/**
	 * 
	 * @param mixed $data
	 * @return void
	 */
	function step($data);
	
	/**
	 * 
	 * @return void
	 */
	function complete();
	
	/**
	 * When a fatal error has occurred and the function must be aborted
	 * @return void
	 */
	function error($message);
}