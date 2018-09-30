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
	 * @var string[]
	 */
	private $files;
	
	/**
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * 
	 * @param string[] $scripts
	 * @throws IllegalArgumentException
	 * @throws CantOpenFileException
	 */
	public function __construct(array $scripts) {		
		$extensions = array_unique(preg_replace("/^[\w.-]+\./", "", $scripts, 1));
		if (count($extensions) !== 1) {
			throw new IllegalArgumentException("Conflicting script types.");
		}
		
		$this->type = $extensions[0];
		if ($this->type !== "js" && $this->type !== "css") {
			throw new IllegalArgumentException("Illegal script name: " . print_r($scripts, true));
		}
		$this->files = str_prepend($scripts, BASE_DIRECTORY . DIRECTORY_SEPARATOR . self::DIRECTORY . 
				DIRECTORY_SEPARATOR . $this->type . DIRECTORY_SEPARATOR);
		
		foreach ($this->files as $file) {
			if (!file_exists($file)) {
				throw new CantOpenFileException("Script not found: $file");
			}
		}
	}
	
	/**
	 * 
	 * @return int
	 */
	public function get_last_modified(): int {
		return max(array_map(function(string $file): int {
			return filemtime($file);
		}, $this->files));
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_text(): string {
		return join("\n", array_map(function(string $file){
			return file_get_contents($file);
		}, $this->files));
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}
}