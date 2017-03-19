<?php
interface Cleanup_Module {
	
	/**
	 * 
	 * @param Cleanup_Instance $cleanup_instance
	 * @return void
	 */
	public function cleanup(Cleanup_Instance $cleanup_instance);
}