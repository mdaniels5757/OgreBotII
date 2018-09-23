<?php
class Regex_Relink extends Relink {
	
	/**
	 *
	 * @var string
	 */
	private $from;
	
	/**
	 *
	 * @var string
	 */
	private $to;
	
	/**
	 *
	 * @var string
	 */
	private $search;
	
	/**
	 *
	 * @var string
	 */
	private $replacement;
	
	/**
	 *
	 * @param Wiki $local        	
	 * @param Wiki $shared        	
	 * @param string[] $post        	
	 * @throws IllegalArgumentException
	 */
	public function __construct(Wiki $local, Wiki $shared, array &$post) {
		global $wiki_interface;
		
		parent::__construct($local, $shared); // /ra(\d?\s*\=\s*)_blacklowerthin(\s)/
		
		foreach (["from", "search", "replacement"] as $variable) {
			$this->$variable = find_command_line_arg($post, $variable, false);
			if ($this->$variable === null) {
				echo "Enter $variable: ";
				$this->$variable = stdin_get();
			}
		}
		
		// test regular expression
		if (preg_match($this->search, null) === true) {
			throw new IllegalArgumentException("Regex validation failed.");
		}
		

		$page_text_response = $wiki_interface->get_text($local, "File:$this->from", false);
		$to = get_listed_commons_image($page_text_response->text, $this->to, $error_flag);
		
		if ($error_flag) {
			throw new IllegalArgumentException("NowCommons tag not found on page.");
		}
		
		$this->to = self::remove_namespace($to);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Relink::relink_text()
	 */
	protected function relink_text(string $page_name, string $page_text, string $str_old_image,
			string $str_new_image, bool $ignored): string {
		$length = preg_match_all($this->search, $page_text, $matches, PREG_SET_ORDER);
		
		if ($length === 0) {
			throw new CantDelinkException("regex not found");
		}
		
		$new_page_text = preg_replace($this->search, $this->replacement, $page_text);
		
		list($from_included, $to_included) = $this->test_image_inclusion($page_name, $new_page_text, 
			preg_replace("/\s+/", "_", [$this->from, $this->to]));
		
		if ($from_included) {
			throw new CantDelinkException("regex replacement failed: from inclusions still present.");
		}
		
		if (!$to_included) {
			throw new CantDelinkException("regex replacement failed: to inclusions not present.");
		}
		
		return $new_page_text;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Relink::post_delink()
	 */
	protected function post_delink(string $from, bool $same) {
		// do nothing
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Relink::get_files_same_name()
	 */
	protected function get_files_same_name(): array {
		// only delinking different name
		return [];
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Relink::get_files_different_name()
	 */
	protected function get_files_different_name(): array {
		return ["File:$this->from" => "File:$this->to"];
	}
}