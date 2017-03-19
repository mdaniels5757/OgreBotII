<?php
class Http_Io {
	const NO_MINIFY_START = "<!-- HTML START NO MINIFY -->";
	const NO_MINIFY_END = "<!-- HTML END NO MINIFY -->";
	
	/**
	 * Are we planning to minify HTML, JS, and CSS?
	 * 
	 * @var bool
	 */
	private $minify;
	
	/**
	 *
	 * @param bool $use_request_debug
	 *        	DEFAULT true. If true, then the function will look at the request
	 *        	parameter for the "debug" parameter and allow it to override the minify setting in the
	 *        	properties.
	 */
	public function __construct($use_request_debug = true) {
		if ($use_request_debug) {
			if (@$_REQUEST['debug'] === 'true') {
				$this->minify = false;
				return;
			} else if (@$_REQUEST['debug'] === 'false') {
				$this->minify = true;
				return;
			}
		}
		$this->minify = boolean_or_exception($GLOBALS['environment']['minify']);
	}
	
	/**
	 *
	 * @param string|string[] $pageName        	
	 * @param string|string[] $type
	 * @param string|null $directory (DEFAULT: null)
	 * @param string[] $deferred
	 * @return void
	 */
	public function transcludeScript($pageName, $type, $directory = null, array $options = []) {
		global $environment, $logger, $validator;
		
		if (is_array($type)) {
			array_walk($type, function($type) use ($pageName, $directory, $options){
				$this->transcludeScript($pageName, $type, $directory, $options);
			});
			return;
		}
		
		if (is_array($pageName)) {
			array_walk($pageName, function($pageName) use ($type, $directory, $options){
				$this->transcludeScript($pageName, $type, $directory, $options);
			});
			return;
		}

		$full_page_name = "$pageName." . ($this->minify ? "min." : "") . $type;
		$ws = new Web_Script($full_page_name);
		if ($this->minify) {
			$url = "{$directory}load.php?s=$full_page_name&";
		} else {
			$url = "{$directory}$type/$full_page_name?";
		}
		
		$url .= "t={$ws->get_last_modified()}";
		if ($type === "js") {
			$text = "<script type=\"text/javascript\" src=\"$url\" " . join(" ", $options) .
				 "></script>";
		} else {
			$text = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\"" . join(" ", $options) .
				 "/>";
		}
		echo $text;
		
	}
	
	public function set_no_cache_headers() {
		header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
	}

	public function transclude_no_cache_tags() {
		echo '<meta http-equiv="expires" content="Sun, 01 Jan 2016 00:00:00 GMT"/>'.
			'<meta http-equiv="pragma" content="no-cache" />';
	}
	
	/**
	 *
	 * @param string|string[] $key        	
	 * @return void
	 * @throws IllegalStateException
	 */
	public function transcludeScriptRemote($key) {
		global $constants, $validator;

		if (is_array($key)) {
			array_walk($key, [$this, "transcludeScriptRemote"]);
			return;
		}
		
		if ($this->minify) {
			$key = "$key.min";
		}
		
		$url = array_key_or_exception($constants, $key);
		
		if (str_ends_with($url, ".js")) {
			echo "<script type=\"text/javascript\" src=\"$url\"></script>";
		} else if (str_ends_with($url, ".css")) {
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\" />";
		} else {
			throw new IllegalStateException("Can't determine script type for url $url");
		}
	}
	
	/**
	 * Quick and dirty HTML minifier.
	 * Doesn't work with some tags, but good enough for
	 * most purposes.
	 * 
	 * @param string $string        	
	 * @return string
	 */
	public function quick_html_minify($string) {
		$string = preg_replace("/<\s*textarea\W[\S\s]+?<\s*\/\s*textarea\s*>/i", 
			self::NO_MINIFY_START . "$0" . self::NO_MINIFY_END, $string);
		preg_match("/<\s*textarea\s*>[\S\s]+?<\s*\/\s*textarea\s*>/i", $string);
		$previous = 0;
		$minified_chunks = [];
		$unminified_chunks = [];
		preg_match_all(
			"/" . preg_quote(self::NO_MINIFY_START) . "([\S\s]+?)" . preg_quote(self::NO_MINIFY_END) .
				 "/", $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		
		foreach ($matches as $match) {
			$minified_chunks[] = substr($string, $previous, $match[0][1] - $previous);
			$unminified_chunks[] = $match[1][0];
			$previous = $match[0][1] + strlen($match[0][0]);
		}
		$minified_chunks[] = substr($string, $previous);
		
		$new_string = "";
		
		$minified_chunks = preg_replace(["/<!\-\-.*?\-\->/", "/\s+/"], ["", " "], 
			$minified_chunks);
		foreach ($unminified_chunks as $i => $unminified_chunk) {
			$new_string .= $minified_chunks[$i] . $unminified_chunk;
		}
		$new_string .= $minified_chunks[count($unminified_chunks)];
		return $new_string;
	}
	
	/**
	 *
	 * @return callable
	 */
	public function ob_start() {
		if ($this->minify) {
			ob_start([$this, "quick_html_minify"]);
		}
	}
	
	/**
	 *
	 * @return bool
	 */
	public function get_minify_scripts() {
		return $this->minify_scripts;
	}
	
	/**
	 *
	 * @param bool $minify        	
	 * @return void
	 */
	public function set_minify($minify) {
		$this->minify = $minify;
	}
}

