<?php

/**
 * 
 * @author patrick
 *
 */
abstract class Remote_Util {
	
	const DEFAULT_OPTS = [CURLOPT_USERAGENT => OGREBOT_USERAGENT];
	const REQUIRED_OPTS = [CURLOPT_RETURNTRANSFER => 1];
	
	/**
	 * 
	 * @var resource
	 */
	private $ch;
	
	/**
	 * 
	 * @var array[]
	 */
	private $downloads;
	
	/**
	 * 
	 * @var array
	 */
	private $headers = [];
	
	/**
	 * 
	 * @param array $options (DEFAULT [])
	 */
	public function __construct(array $options= []) {
		$this->ch = curl_init();
		curl_setopt_array($this->ch, array_replace(self::DEFAULT_OPTS, $options, self::REQUIRED_OPTS));
	}
	
	/**
	 * 
	 */
	public function __destruct() {
		curl_close($this->ch);
	}
	
	/**
	 * TODO multithreaded requests
	 * @param boolean $stop_on_error default true
	 * @return Remote_Response[]
	 */
	public final function execute($stop_on_error = true) {
		global $logger;
	
		$responses = [];
		while ($this->next()) {
			$response = new Remote_Response();
			try {
				if ($this->headers) {
					curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
				}
				$response->response_text = curl_exec($this->ch);
				$response->response_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
				$response->time = curl_getinfo($this->ch, CURLINFO_TOTAL_TIME);
				$response->size = strlen($response->response_text);
				$response->mime = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
	
				if ($response->response_code < 200 || $response->response_code > 203) {
					throw new CURLError($remote_response->response_code,
						$remote_response->response_text);
				}
	
			} catch (Exception $e) {
				$logger->error($e);
				$response->error = $e->getMessage();
				if ($stop_on_error) {
					break;
				}
			} finally {
				$this->after_call($response);
				$responses[] = $response;
			}
		}
	
		$this->clear();
	
		return $responses;
	}
	
	/**
	 * 
	 * @param array $headers
	 * @return void
	 */
	public function set_headers(array $headers) {
		$this->headers = $headers;
	}
	
	/**
	 * By contract, called immediately after the request is complete, whether successful or not.
	 * Will be called before next(). Override as needed.
	 * @return void
	 */
	protected function after_call(Remote_Response $response) {
	}
	
	/**
	 * By contract, called after all requests are complete, whether successful or not.
	 */
	protected function clear() {
	}
	
	/**
	 * 
	 * @return resource
	 */
	protected final function get_ch() {
		return $this->ch;
	}
	
	/**
	 * @return boolean if there are more items to iterate over
	 */
	protected abstract function next();
	
	
	/**
	 * 
	 * @param string $url
	 * @return void
	 */
	protected final function set_url($url) {
		if (str_starts_with($url, "https://") && !Environment::prop("environment", "ssl.verify")) {
			curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		curl_setopt($this->ch, CURLOPT_URL, $url);
	}
	
	
}