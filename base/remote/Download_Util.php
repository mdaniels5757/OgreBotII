<?php

/**
 * 
 * @author patrick
 *
 */
class Download_Util extends Remote_Util {
	
	
	/**
	 * 
	 * @var string[]
	 */
	private $urls = [];
	
	/**
	 * 
	 * @var string[]
	 */
	private $filenames = [];
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Remote_Util::next()
	 */
	protected function next() {
		global $logger;
		
		if (!$this->urls) {
			return false;
		}
		
		$url = array_shift($this->urls);
		
		$logger->debug("d/l $url");
		$this->set_url($url);
		
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Remote_Util::clear()
	 */
	protected function clear() {
		$this->urls = [];
		$this->filenames = [];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Remote_Util::after_call()
	 */
	protected function after_call(Remote_Response $remote_response) {
		global $logger;

		$filename = array_shift($this->filenames);
		if (!$remote_response->error) {
			$logger->debug("-> $filename");
			file_put_contents_ensure($filename, $remote_response->response_text);
			$remote_response->filename = $filename;
		}
	}
	
	

	/**
	 *
	 * @param string $url
	 * @param string $filename
	 * @param string[] $post_parameters
	 * @return void
	 */
	public function add_download($url, $filename) {
		global $validator;
	
		$validator->validate_args("string", $url, $filename);
	
		$this->urls[] = $url;
		$this->filenames[] = $filename;
	}
}