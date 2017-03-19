<?php
abstract class PNG_Creator {

	/**
	 * 
	 * @param string $svg_text
	 * @param int $height height in pixels
	 * @return string the blob of image data
	 * @throws CantOpenFileException
	 */
	public abstract function convert($svg_text, $height);
	
	/**
	 *
	 * @param string $name_in
	 * @param int $height height in pixels
	 * @return string the blob of image data
	 */
	public abstract function get_thumb($svg_text, $height);
	
}