<?php
class Imagick_PNG_Creator extends PNG_Creator {
	
	/**
	 * 
	 * @var Imagick
	 */
	private $imagick;
	
	/**
	 * 
	 * @var float
	 */
	private $blur_factor;
	
	public function __construct() {
		$this->imagick = new Imagick();
		$this->blur_factor = .6;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PNG_Creator::convert()
	 */
	public function convert($svg_text, $height) {
		$this->imagick->readimageblob($svg_text);
		$ratio = $this->imagick->getimagewidth() / $this->imagick->getimageheight();
		$this->imagick->resizeImage((int)($height * $ratio), $height, Imagick::FILTER_GAUSSIAN, 
			$this->blur_factor);
		$this->imagick->setimageformat("png");
		$data = $this->imagick->getimageblob();
		$this->imagick->clear();
		
		return $data;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PNG_Creator::get_thumb()
	 */
	public function get_thumb($svg_text, $height) {
		$read_success = $this->imagick->readimageblob($svg_text);
		if (!$read_success) {
			throw new CantOpenFileException($name_in);
		}
		$ratio = $this->imagick->getimagewidth() / $this->imagick->getimageheight();
		$this->imagick->thumbnailimage((int)($height * $ratio), $height, true);
		$this->imagick->setimageformat("png");
		$data = $this->imagick->getimageblob();
		$this->imagick->clear();
		
		return $data;
	}
}