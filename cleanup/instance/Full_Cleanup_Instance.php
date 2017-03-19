<?php
/**
 * 
 * @author magog
 *
 */
class Full_Cleanup_Instance extends Simple_Cleanup_Instance {
	
	/**
	 *
	 * @var Template_Cache
	 */
	private $template_cache;
	
	/**
	 *
	 * @var Author_Information|null
	 */
	private $author_information;
	
	/**
	 *
	 * @var string
	 */
	private $upload_time;
	
	/**
	 *
	 * @var Page_Parser
	 */
	private $page_parser;
	
	/**
	 *
	 * @param string $text       
	 * @param bool $human 	
	 * @param Page_Parser|null $page_parser        	
	 * @param string $upload_time        	
	 * @param Template_Cache $template_cache        	
	 */
	public function __construct($text, $human, $page_parser, $upload_time, Template_Cache $template_cache) {
		parent::__construct($text, $human);
		$this->page_parser = $page_parser;
		$this->upload_time = $upload_time;
		$this->template_cache = $template_cache;
	}
	
	/**
	 *
	 * @return string
	 */
	public function get_upload_time() {
		return $this->upload_time;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Instance::get_template()
	 */
	public function get_template($template_name) {
		global $logger;
		if (strpos($template_name, "\n") !== false) {
			$logger->warn("Bad Template name: $template_name");
			$logger->warn(get_backtrace_string(get_backtrace(false)));
			return false;
		}
		return $this->template_cache->get_template($this->get_text(), $template_name);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Instance::get_author_information()
	 */
	public function get_author_information() {
		return $this->author_information;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Cleanup_Instance::set_author_information()
	 */
	public function set_author_information(Author_Information $author_information) {
		$this->author_information = $author_information;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see Simple_Cleanup_Instance::get_page_parser()
	 */
	public function get_page_parser() {
		return $this->page_parser;
	}
}