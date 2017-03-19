<?php
class Png_Creator_Factory {
	
	/**
	 * 
	 * @return PNG_Creator
	 */
	public function get_png_creator() {
		return new Imagick_PNG_Creator();
	}
}