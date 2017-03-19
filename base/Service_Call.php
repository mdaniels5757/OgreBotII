<?php

class Service_Call {
	
	/**
	 * 
	 * @var int
	 */
	const DEFAULT_TIMEOUT = 120;
	
	/**
	 * 
	 * @var string
	 */
	const SECRETS_URL_KEY = "service_hash";
	
	/**
	 * 
	 * @var string
	 */
	private $url;
	
	/**
	 * 
	 * @var array
	 */
	private $post_params = [];
	
	/**
	 * 
	 * @param string $path
	 */
	public function __construct($path) {
		global $logger, $environment;
		
		$logger->debug("Service_Call($path)");
		
		$base_path = array_key_or_exception($environment, "service.url");
		
		$this->url = "$base_path/$path";
				
		$logger->trace("URL is $this->url");
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string|numeric $val
	 */
	public function add_post_param($key, $val) {
		$this->post_params[$key] = $val;
	}
	
	/**
	 * 
	 * @param array $params
	 * @return void
	 */
	public function add_post_params(array $params) {
		$this->post_params = array_merge($params, $this->post_params);
	}
	
	/**
	 * 
	 * @param int $timeout DEFAULT is self::DEFAULT_TIMEOUT
	 */
	public function call($timeout = self::DEFAULT_TIMEOUT) {
		self::call_multiple([$this], $timeout);		
	}
	
	/**
	 * 
	 * @param Service_Call[] $service_calls
	 * @param int $timeout
	 * @return void
	 */
	public static function call_multiple($service_calls, $timeout = self::DEFAULT_TIMEOUT) {
		global $logger, $validator;
		$logger->debug("Service_Call:call_multiple(" . count($service_calls) . ")");
		
		array_walk($service_calls, function($service_call) {
			$curl = $service_call->get_curl();
			curl_setopt($curl, CURLOPT_TIMEOUT, 3);
			curl_exec($curl);
			curl_close($curl);
		});
	}
	
	/**
	 * 
	 * @throws Service_Call_Exception
	 * @return string[]
	 */
	public static function read_service_call() {
		global $env, $logger;
		
		$logger->debug(self::class . "::read_service_call()");
		
		$post = $env->get_request_args();
		
		load_property_file_into_variable($secrets, "secrets");
		
		$key = $post[self::SECRETS_URL_KEY];
		if ($key !== array_key_or_exception($secrets, 'request_key')) {
			throw new Service_Call_Exception($key);
		}
		
		unset($post[self::SECRETS_URL_KEY]);
		
		$logger->debug("Key found and verified.");
		
		return $post;
	}
	
	/**
	 * 
	 * @return resource the cURL handle
	 */
	private function get_curl() {
		global $logger, $environment;
		
		$logger->debug("Service_Call:get_curl($this->url, " . count($this->post_params) . ")");
		
		
		static $hash = null;
		if ($hash === null) {
			load_property_file_into_variable($secrets, "secrets");
			$hash = array_key_or_exception($secrets, 'request_key');
		}
		
		$post_params_enc = map_array_function_keys($this->post_params,
			function ($val, $key) {
				$new_key = rawurlencode($key);
				$new_val = rawurlencode($val);
		
				return [$new_key, $new_val];
			}
		);
		$post_params_enc[self::SECRETS_URL_KEY] = $hash;
		$post_params_enc_string = query_to_string($post_params_enc);
		$logger->trace($post_params_enc_string);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POST, count($post_params_enc));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_params_enc_string);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_USERAGENT, "OgreBot internal service call");
		
		//Ugly form, but I don't know any other way to debug this remotely...
		$debug_curl = array_key_exists("service_call.debug_local_curl", $environment) &&
			boolean_or_exception($environment["service_call.debug_local_curl"]);
		
		if ($debug_curl) {
			$logger->all("Debugging local curl.");
			$logger->all("URL: $this->url");
			$logger->all("\$post_params_enc => $post_params_enc_string");
			$logger->all("Running...");
			
			$data = curl_exec($curl);
			$logger->all("Errno: " . curl_errno($curl));
			$logger->all("Data: $data");
			die();
		}
		
		return $curl;
	}
}