<?php
/**
 * 
 * @author magog
 *
 */
class Web_Script {
	
	const DIRECTORY = "public_html";
	
	const SCRIPT = "s";
	
	const TIMESTAMP = "t";
	
	const TYPE = "y";
	
	const DEBUG = "d";
	
	const DEBUG_DIR = "src";
	
	const MINIFY_DIR = "bin";
	
	/**
	 *
	 * @var string
	 */
	private $files;
	
	/**
	 * 
	 * @var string[]
	 */
	private $scripts;
	
	/**
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * 
	 * @var bool
	 */
	private $debug;
	
	/**
	 * 
	 * @param string[] $scripts
	 * @param string $type
	 * @param bool $debug
	 * @throws IllegalArgumentException
	 * @throws CantOpenFileException
	 */
	public function __construct(array $scripts, string $type, bool $debug) {
		$this->type = $type;
		if ($this->type !== "js" && $this->type !== "css") {
			throw new IllegalArgumentException("Illegal script name: " . print_r($scripts, true));
		}
		$this->scripts = $scripts;
		$this->debug = $debug;
		
		$this->files = array_map(function (string $script): string {
			return BASE_DIRECTORY . DIRECTORY_SEPARATOR . self::DIRECTORY .
				DIRECTORY_SEPARATOR . $this->type . DIRECTORY_SEPARATOR .
				($this->debug ? self::DEBUG_DIR :  self::MINIFY_DIR) .
				DIRECTORY_SEPARATOR . $script . ($this->debug ? "" : ".min") . 
				"." . $this->type;
		}, $this->scripts);		
		
		foreach ($this->files as $file) {
			if (!file_exists($file)) {
				throw new CantOpenFileException("Script not found: $file");
			}
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_load_url() : string {
		$params = [self::SCRIPT => join("%7C", $this->scripts),
			self::TIMESTAMP => $this->get_last_modified(),
			self::TYPE => $this->type
		];
		
		if ($this->debug) {
			$params[self::DEBUG] = "true";
		}
		
		return "load.php?" .query_to_string($params);
	}
	
	/**
	 * 
	 * @param array $params
	 * @return self
	 */
	public static function from_request_params(array $params) : self {
		return new self(explode("|", @$params[self::SCRIPT]),
				@$params[Web_Script::TYPE], !!@$params[Web_Script::DEBUG]);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_text(): string {
		return join("\n", array_map(function(string $file) : string {
			return file_get_contents_ensure($file);
		}, $this->files));
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}
	
	/**
	 *
	 * @return int
	 */
	private function get_last_modified(): int {
		return max(array_map(function(string $file): int {
			return filemtime($file);
		}, $this->files));
	}
}