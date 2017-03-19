<?php
/**
 * 
 * @author magog
 *
 */
class Simple_Cleanup_Instance implements Cleanup_Instance {
	
	/**
	 *
	 * @var string[]
	 */
	private $duplicate_authors = [];
	
	/**
	 * 
	 * @var bool
	 */
	private $significant_changes = false;
	
	
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
	 * @var bool
	 */
	private $human;
	
	/**
	 * 
	 * @param string $text
	 * @param bool $human
	 */
	public function __construct($text, $human) {
		$this->set_text($text, false);
		$this->human = $human;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Instance::add_duplicate_author()
	 */
	public function add_duplicate_author($author) {
		if (!in_array($author, $this->duplicate_authors)) {
			$this->duplicate_authors[] = $author;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_duplicate_authors()
	 */
	public function get_duplicate_authors() {
		return $this->duplicate_authors;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_page_parser()
	 */
	public function get_page_parser() {
		throw new IllegalStateException("Method not implemented");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_significant_changes()
	 */
	public function get_significant_changes() {
		return $this->significant_changes;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::set_sigificant_changes()
	 */
	public function set_sigificant_changes($condition = true) {
		$this->significant_changes = $condition;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_template()
	 */
	public function get_template($template_name) {
		throw new IllegalStateException("Method not implemented");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_text()
	 */
	public function get_text() {
		return $this->text;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::set_text()
	 */
	public function set_text($text, $major_change = true) {
		if ($this->text !== $text) {
			if ($major_change) {
				$this->significant_changes = true;
			}
			$this->text = $text;
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return string
	 */
	public function get_upload_time() {
		throw new IllegalStateException("Method not implemented");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::add_warning()
	 */
	public function add_warning($warning) {
		global $messages;
		if (!@$messages["cleanup.$warning"]) {
			throw new IllegalArgumentException("Illegal warning added: $warning");
		}
		if (!in_array($warning, $this->warnings)) {
			$this->warnings[] = $warning;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_formatted_warnings()
	 */
	public function get_formatted_warnings($wikitext) {
		return array_map(
			function ($key) use($wikitext) {
				return $this->get_warning_message($key, $wikitext);
			}, $this->warnings);
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_author_information()
	 */
	public function get_author_information() {
		throw new IllegalStateException("Method not implemented");
		
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::set_author_information()
	 */
	public function set_author_information(Author_Information $author_information) {
		throw new IllegalStateException("Method not implemented");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::preg_replace()
	 */
	public function preg_replace($regex, $replace, $tracked = true, &$local_tracked = null)  {
		$original = $this->text;
		$this->text = preg_replace($regex, $replace, $this->text);
	
		$local_tracked = $original !== $this->text;
		if ($tracked && $local_tracked) {
			$this->significant_changes = true;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::preg_replace()
	 */
	public function iter_replace($regex, $replace, $tracked = true, &$local_track = null)  {
		$this->text = iter_replace($regex, $replace, $this->text, $local_track);	
		if ($tracked && $local_track) {
			$this->significant_changes = true;
		}
	}
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::preg_replace_callback()
	 */
	public function preg_replace_callback($regex, $callback, $tracked = true, &$local_track = null) {
		$original = $this->text;
		$this->text = preg_replace_callback($regex, $callback, $this->text);
	
		$local_track = $original !== $this->text;
		if ($tracked && $local_track) {
			$this->significant_changes = true;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::str_replace()
	 */
	public function str_replace($string, $replace, $tracked = true) {
		$original = $this->text;
		$this->text = str_replace($string, $replace, $this->text);
		
		if ($tracked && $original !== $this->text) {
			$this->significant_changes = true;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::get_warnings()
	 */
	public function get_warnings() {
		return $this->warnings;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::merge()
	 */
	public function merge(Cleanup_Instance $instance) {
		$this->text = $instance->get_text();
		$this->human = $instance->is_human();
		$this->significant_changes |= $instance->get_significant_changes();
		
		$warnings = $instance->get_warnings();
		$authors = $instance->get_duplicate_authors();
		
		array_walk($warnings, [$this, "add_warning"]);
		array_walk($authors, [$this, "add_duplicate_author"]);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Cleanup_Instance::is_human()
	 */
	public function is_human() {
		return $this->human;
	}
	
	
	/**
	 *
	 * @param string $warning     	
	 * @param bool $wikitext        	
	 * @return string
	 */
	private function get_warning_message($warning, $wikitext) {
		global $messages;
		
		$message = $wikitext ? @$messages["cleanup.$warning.wikitext"] : null;
		if ($message === null) {
			$message = array_key_or_exception($messages, "cleanup.$warning");
		}
		
		if ($warning === Cleanup_Shared::DUPLICATE_AUTHOR) {
			$message = replace_named_variables($message, ["author" => $this->duplicate_author]);
		}
		
		return $message;
	}
}