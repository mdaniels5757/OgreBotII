<?php
class Replacement_Tracker {
	
	/**
	 * 
	 * @var bool
	 */
	private $changed;
	
	/**
	 * 
	 * @var string
	 */
	private $text;
	
	/**
	 * 
	 * @var string[]
	 */
	private $warnings = [];
	
	/**
	 * 
	 * @param string $text
	 */
	public function __construct($text) {
		$this->text = $text;
	}
	
	/**
	 * 
	 * @param mixed $regex
	 * @param mixed $replace
	 * @param bool $tracked DEFAULT true
	 * @return void
	 */
	public function preg_replace($regex, $replace, $tracked = true)  {
		$original = $this->text;
		$this->text = preg_replace($regex, $replace, $this->text);
		
		if ($tracked && $original !== $this->text) {
			$this->changed = true;
		}
	}
	
	/**
	 *
	 * @param mixed $regex
	 * @param mixed $replace
	 * @param bool $tracked DEFAULT true
	 * @return void
	 */
	public function preg_replace_callback($regex, $callback, $tracked = true) {
		$original = $this->text;
		$this->text = preg_replace_callback($regex, $callback, $this->text);
		
		if ($tracked && $original !== $this->text) {
			$this->changed = true;
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}
	
	/**
	 * 
	 * @param string $text
	 * @return void
	 */
	public function set_text($text) {
		$this->text = $text;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function change() {
		return $this->changed;
	}
	
	/**
	 * 
	 * @param bool $text
	 * @return void
	 */	
	public function set_changed($changed) {
		$this->changed = $changed;
	}
	
	/**
	 * @return string[]
	 */
	public function get_warnings() {
		return $this->warnings;
	}
	
	/**
	 * 
	 * @param string $warning
	 * @return void
	 */
	public function add_warning($warning) {
		$this->warnings[] = $warning;
	}
}