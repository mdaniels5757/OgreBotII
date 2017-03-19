<?php
/**
 * 
 * @author magog
 *
 */
interface Cleanup_Instance {
	
	/**
	 *
	 * @param string $author        	
	 * @return void
	 */
	public function add_duplicate_author($author);
	
	
	/**
	 * @return string[]
	 */
	public function get_duplicate_authors();
	
	/**
	 * The original underlying page parser
	 * @return Page_Parser
	 */
	public function get_page_parser();
	
	/**
	 *
	 * @return boolean
	 */
	public function get_significant_changes();
	
	/**
	 *
	 * @param string $template_name
	 * @return Template|null
	 */
	public function get_template($template_name);
	
	/**
	 *
	 * @param bool $condition (default true)
	 * @return void
	 */
	public function set_sigificant_changes($condition = true);
	
	/**
	 *
	 * @return string
	 */
	public function get_text();
	
	/**
	 *
	 * @param string $text        	
	 * @param boolean $major_change
	 *        	OPTIONAL default = true
	 * @return bool if a change was made
	 */
	public function set_text($text, $major_change = true);	
	
	
	/**
	 * @return string
	 */
	public function get_upload_time();
	
	
	/**
	 * @return string[]
	 */
	public function get_warnings();
	
	/**
	 *
	 * @param string $warning        	
	 * @return void
	 * @throws IllegalArgumentException
	 */
	public function add_warning($warning);
	
	/**
	 *
	 * @param boolean $wikitext        	
	 * @return string[]
	 */
	public function get_formatted_warnings($wikitext);
	
	/**
	 * 
	 * @return Author_Information
	 */
	public function get_author_information();
	
	/**
	 * 
	 * @param Author_Information $author_information
	 * @return void
	 */
	public function set_author_information(Author_Information $author_information);
	
	
	/**
	 *
	 * @param mixed $regex        	
	 * @param mixed $replace        	
	 * @param bool $tracked
	 *        	DEFAULT true
	 * @param bool $local_track
	 * @return void
	 */
	public function preg_replace($regex, $replace, $tracked = true, &$local_track = null);
	
	/**
	 *
	 * @param mixed $regex        	
	 * @param mixed $replace        	
	 * @param bool $tracked
	 *        	DEFAULT true
	 * @param bool $local_track
	 * @return void
	 */
	public function iter_replace($regex, $replace, $tracked = true, &$local_track = null);
	
	/**
	 *
	 * @param mixed $regex        	
	 * @param mixed $replace        	
	 * @param bool $tracked
	 *        	DEFAULT true
	 * @param bool $local_track
	 * @return void
	 */
	public function preg_replace_callback($regex, $callback, $tracked = true, &$local_track = null);
	
	/**
	 * 
	 * @param string $string
	 * @param string $replace
	 * @param bool $tracked
	 * @return void
	 */
	public function str_replace($string, $replace, $tracked = true);
	
	/**
	 * 
	 * @param Cleanup_Instance $instance
	 * @return void
	 */
	public function merge(Cleanup_Instance $instance);
	
	/**
	 * @return bool
	 */
	public function is_human();
}