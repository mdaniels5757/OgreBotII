<?php
interface Cleanup_Submodule {
	
	/**
	 * 
	 * @param Cleanup_Instance $cleanup_instance
	 * @return void
	 */
	public function cleanup(Cleanup_Instance $cleanup_instance);
}