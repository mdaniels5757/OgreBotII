<?php
/**
 * 
 * @author magog
 *
 */
class Web_Script {
	
	const DIRECTORY = "public_html";
	
	const WEB_ARGUMENT = "s";
	
	/**
	 * 
	 * @var string
	 */
	private $file;
	
	/**
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * 
	 * @param string $script
	 * @throws IllegalArgumentException
	 * @throws CantOpenFileException
	 */
	public function __construct($script) {
		if (!$script) {
			throw new IllegalArgumentException("Script not specified.");
		}
		
		preg_match("/^[\w.-]+\.(\w+)$/", $script, $match);
		$this->type = @$match[1];
		if ($this->type[1] === "css" && $this->type[1] === "js") {
			throw new IllegalArgumentException("Illegal script name: $script.");
		}
		
		$this->file = str_replace("/", DIRECTORY_SEPARATOR, BASE_DIRECTORY . "/" . self::DIRECTORY
				. "/$this->type/$script");
				
		
		if (!file_exists($this->file)) {
			throw new CantOpenFileException("Script not found: $this->file");
		}
	}
	
	/**
	 * 
	 * @return number
	 */
	public function get_last_modified() {
		return filemtime($this->file);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_text() {
		return file_get_contents($this->file);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}
}