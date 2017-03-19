<?php

/**
 * 
 * @author magog
 *
 */
class Identity_Verifier {
	
	const COOKIE_NAME = "magog-ident";
	
	/**
	 * 
	 * @var string
	 */
	private $cookie_path;
	
	/**
	 * 
	 * @var string
	 */
	private $gConsumerSecret;
	
	/**
	 * 
	 * @var string
	 */
	private $gConsumerKey;
	
	/**
	 * 
	 * @var string
	 */
	private $gUserAgent;
	
	/**
	 * 
	 * @var string
	 */
	private $mwOAuthAuthorizeUrl;
	
	/**
	 * 
	 * @var string
	 */
	private $mwOAuthUrl;
	
	/**
	 * 
	 * @var string
	 */
	private $mwOAuthIW;
	
	/**
	 * 
	 * @var string
	 */
	private $gTokenSecret;
	
	/**
	 * 
	 * @var Identity_Dao
	 */
	private $identity_dao;
	
	/**
	 * @return void
	 */
	public function __construct() {
		list($this->gConsumerSecret, $this->gConsumerKey) = Environment::props("secrets", 
			["ident.key.private", "ident.key.consumer"]);
		list($this->gUserAgent, $this->mwOAuthAuthorizeUrl, $this->mwOAuthUrl, 
			$this->mwOAuthIW) = Environment::props("auth", ["useragent", "url.authorize", 
					"url.base", "url.interwiki"]);
		list($this->cookie_path, $identity_dao_class) = Environment::props("environment", 
			["ident.cookie.path", "identity_dao"]);
		
		$this->identity_dao = new $identity_dao_class();
			
		$this->gTokenSecret = '';
		session_start();
		if (isset($_SESSION['tokenKey'])) {
			$this->gTokenSecret = $_SESSION['tokenSecret'];
		}
	}
	
	/**
	 * @return string[]
	 */
	public function get_auth_tool_keys() {
		return map_array_function_keys(Environment::prop("secrets", "ident.authtool"), 
			function ($key) {
				$colon = strrpos($key, ":");
				return [substr($key, 0, $colon), substr($key, $colon + 1)];
			});
	}
	
	/**
	 * Utility function to sign a request
	 *
	 * Note this doesn't properly handle the case where a parameter is set both in
	 * the query string in $url and in $params, or non-scalar values in $params.
	 *
	 * @param string $method
	 *        	Generally "GET" or "POST"
	 * @param string $url
	 *        	URL string
	 * @param array $params
	 *        	Extra parameters for the Authorization header or post
	 *        	data (if application/x-www-form-urlencoded).
	 * @return string Signature
	 */
	private function sign_request($method, $url, $params = array()) {	
		global $logger;
		
		$parts = parse_url($url);
	
		// We need to normalize the endpoint URL
		$scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
		$host = isset($parts['host']) ? $parts['host'] : '';
		$port = isset($parts['port']) ? $parts['port'] : ($scheme == 'https' ? '443' : '80');
		$path = isset($parts['path']) ? $parts['path'] : '';
		if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
			// Only include the port if it's not the default
			$host = "$host:$port";
		}
	
		// Also the parameters
		$pairs = [];
		parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
		$query += $params;
		unset($query['oauth_signature']);
		if ($query) {
			$query = array_combine(
				// rawurlencode follows RFC 3986 since PHP 5.3
				array_map('rawurlencode', array_keys($query)),
				array_map('rawurlencode', array_values($query)));
			ksort($query, SORT_STRING);
			foreach ($query as $k => $v) {
				$pairs[] = "$k=$v";
			}
		}
		
		$toSign = rawurlencode(strtoupper($method)) . '&' . rawurlencode("$scheme://$host$path") .
			 '&' . rawurlencode(join('&', $pairs));
		$key = rawurlencode($this->gConsumerSecret) . '&' . rawurlencode($this->gTokenSecret);
		
		$result = base64_encode(hash_hmac('sha1', $toSign, $key, true));
		$logger->debug("sign_request($method, $url, ...) => $result");		
		return $result;
	}
	
	
	/**
	 * Get authorization redirect
	 * 
	 * @throws OAuthException
	 * @return string
	 */
	public function do_authorization_redirect() {
		global $logger;
		
		$url = $this->mwOAuthUrl . '/initiate';
		$url .= strpos($url, '?') ? '&' : '?';
		$url .= http_build_query(
			[
				'format' => 'json',
				// OAuth information
				'oauth_callback' => 'oob', // Must be "oob" for MWOAuth
				'oauth_consumer_key' => $this->gConsumerKey,
				'oauth_version' => '1.0',
				'oauth_nonce' => md5(microtime() . mt_rand()),
				'oauth_timestamp' => time(),
						
				// We're using secret key signatures here.
				'oauth_signature_method' => 'HMAC-SHA1'
			]);
		$this->gTokenSecret = '';
		$signature = $this->sign_request('GET', $url);
		$url .= "&oauth_signature=" . urlencode($signature);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Environment::prop("environment", "ssl.verify"));
		curl_setopt($ch, CURLOPT_USERAGENT, $this->gUserAgent);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		try {
			if (!$data) {
				throw new OAuthException('Curl error: ' . curl_error($ch));
			}
		} finally {
			curl_close($ch);
		}
		$token = json_decode($data);
		$logger->debug("do_authorization_redirect: $data");
		if (is_object($token) && isset($token->error)) {
			throw new OAuthException("Error retrieving token: $token->error; $token->message");
		}
		if (!is_object($token) || !isset($token->key) || !isset($token->secret)) {
			throw new OAuthException('Invalid response from token request');
		}
		
		// Now we have the request token, we need to save it for later.
		session_start();
		$_SESSION['tokenKey'] = $token->key;
		$_SESSION['tokenSecret'] = $token->secret;
		session_write_close();
		
		// Then we send the user off to authorize
		$url = $this->mwOAuthAuthorizeUrl;
		$url .= strpos($url, '?') ? '&' : '?';
		$url .= http_build_query(
			['oauth_token' => $token->key, 'oauth_consumer_key' => $this->gConsumerKey]);
		return $url;
	}
	
	/**
	 * 
	 * @return string|null
	 */
	public function get_cookie() {
		return @$_COOKIE[self::COOKIE_NAME];
	}
	
	/**
	 * 
	 * @return NULL|string
	 */
	public function get_username() {
		$username = $this->get_username_by_cookie(@$_COOKIE[self::COOKIE_NAME]);
		Environment::get()->get_logger()->info("Username: $username");
		if ($username === null) {
			$this->erase_cookie();
		}
	}
	
	/**
	 * 
	 * @param string $cookie
	 * @return string|NULL
	 */
	public function get_username_by_cookie($cookie) {
		return $this->identity_dao->get($cookie);
	}
	
	/**
	 * Handle a callback to fetch the access token
	 * @return void
	 * @throws OAuthException
	 */
	public function fetch_access_token() {
		session_start();
		
		$url = $this->mwOAuthUrl . '/token';
		$url .= strpos($url, '?') ? '&' : '?';
		$url .= http_build_query(
			['format' => 'json', 'oauth_verifier' => $_GET['oauth_verifier'], 
				'oauth_consumer_key' => $this->gConsumerKey, 'oauth_token' => $_SESSION['tokenKey'], 
				'oauth_version' => '1.0', 'oauth_nonce' => md5(microtime() . mt_rand()), 
				'oauth_timestamp' => time(), 'oauth_signature_method' => 'HMAC-SHA1']);
		$signature = $this->sign_request('GET', $url);
		$url .= "&oauth_signature=" . urlencode($signature);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Environment::prop("environment", "ssl.verify"));
		curl_setopt($ch, CURLOPT_USERAGENT, $this->gUserAgent);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		try {
			if (!$data) {
				throw new OAuthException('Curl error: ' . curl_error($ch));
			}
		} finally {
			curl_close($ch);	
		}
		$token = json_decode($data);
		if (is_object($token) && isset($token->error)) {
			throw new OAuthException("Error retrieving token: $token->error; $token->message");
		}
		if (!is_object($token) || !isset($token->key) || !isset($token->secret)) {
			throw new OAuthException('Invalid response from token request');
		}

		// Save the access token
		$_SESSION['tokenKey'] = $token->key;
		$_SESSION['tokenSecret'] = $this->gTokenSecret = $token->secret;
		session_write_close();
	}
	
	/**
	 * 
	 * @throws OAuthException
	 * @return string|null
	 */
	private function identify_remote() {
		global $logger;
		
		$this->fetch_access_token();
		
		$url = $this->mwOAuthUrl . '/identify';
			
		$headerArr = ['oauth_consumer_key' => $this->gConsumerKey,
				'oauth_token' => $_SESSION['tokenKey'], 'oauth_version' => '1.0',
				'oauth_nonce' => md5(microtime() . mt_rand()), 'oauth_timestamp' => time(),
				'oauth_signature_method' => 'HMAC-SHA1'];
		
		$signature = $this->sign_request('GET', $url, $headerArr);
		$headerArr['oauth_signature'] = $signature;
		
		$header = [];
		foreach ($headerArr as $k => $v) {
			$header[] = rawurlencode($k) . '="' . rawurlencode($v) . '"';
		}
		
		$header = 'Authorization: OAuth ' . join(', ', $header);
		
		$logger->debug("do_identify => header = $header");
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, Environment::prop("environment", "ssl.verify"));
		curl_setopt($ch, CURLOPT_USERAGENT, $this->gUserAgent);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		
		$logger->debug("do_identify => response = $data");
		try {
			if (!$data) {
				throw new OAuthException('Curl error: ' . curl_error($ch));
			}
		} finally {
			curl_close($ch);
		}
		
		$err = json_decode($data);
		if (is_object($err) && isset($err->error) &&
			 $err->error === 'mwoauthdatastore-access-token-not-found') {
			throw new OAuthException('You haven\'t authorized this application yet!');
		}
		
		// There are three fields in the response
		$fields = explode('.', $data);
		if (count($fields) !== 3) {
			throw new OAuthException("Invalid identify response: $data");
		}
		
		// Validate the header. MWOAuth always returns alg "HS256".
		$header = base64_decode(strtr($fields[0], '-_', '+/'), true);
		if ($header !== false) {
			$header = json_decode($header);
		}
		if (!is_object($header) || $header->typ !== 'JWT' || $header->alg !== 'HS256') {
			throw new OAuthException("Invalid header in identify response: $data");
		}
		
		// Verify the signature
		$sig = base64_decode(strtr($fields[2], '-_', '+/'), true);
		$check = hash_hmac('sha256', $fields[0] . '.' . $fields[1], $this->gConsumerSecret, true);
		if ($sig !== $check) {
			throw new OAuthException("JWT signature validation failed: $data");
		}
		
		// Decode the payload
		$payload = base64_decode(strtr($fields[1], '-_', '+/'), true);
		if ($payload !== false) {
			$payload = json_decode($payload);
		}
		if (!is_object($payload)) {
			throw new OAuthException("Invalid payload in identify response: $data");
		}
		return $payload->username;
	}
	
	private function erase_cookie() {
		setcookie(self::COOKIE_NAME, null, -1, $this->cookie_path);
	}
	
	/**
	 * Request a JWT and verify it
	 *
	 * @return void
	 */
	public function do_identify() {
		$username = Environment::prop("environment", "live") ? $this->identify_remote() : "Magog the Ogre";

		$cookie_value = $this->identity_dao->set($username);
		setcookie(self::COOKIE_NAME, $cookie_value, time() + SECONDS_PER_DAY * 30, 
			$this->cookie_path);
		$_COOKIE[self::COOKIE_NAME] = $cookie_value;
	}
	
	public function logout() {
		$this->identity_dao->logout($_COOKIE[self::COOKIE_NAME]);
		$this->erase_cookie();
	}
}