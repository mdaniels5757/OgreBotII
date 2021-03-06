<?php

/**
 * 
 * @author magog
 *
 */
abstract class AbstractOAuthImpl implements OAuth {
		
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
	 * @param string $secretsKey
	 */
	public function __construct(string $secretsKey) {
		list($this->gConsumerSecret, $this->gConsumerKey) = Environment::props("secrets", 
			["$secretsKey.private", "$secretsKey.consumer"]);
		list($this->gUserAgent, $this->mwOAuthAuthorizeUrl, $this->mwOAuthUrl, 
			$this->mwOAuthIW) = Environment::props("auth", ["useragent", "url.authorize", 
					"url.base", "url.interwiki"]);
			
		$this->gTokenSecret = '';
		session_start();
		if (isset($_SESSION['tokenKey'])) {
			$this->gTokenSecret = $_SESSION['tokenSecret'];
		}
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
	private function sign_request(string $method, string $url, array $params = []): string {	
		global $logger;
		
		$parts = parse_url($url);
	
		// We need to normalize the endpoint URL
		$scheme = @$parts['scheme'] ?: "http";
		$host = @$parts['host'] ?: "";
		$port = @$parts['port'] ?: ($scheme == 'https' ? '443' : '80');
		$path = @$parts['path'] ?: '';
		if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
			// Only include the port if it's not the default
			$host = "$host:$port";
		}
	
		// Also the parameters
		parse_str($parts['query'] ?: '', $query);
		$query += $params;
		unset($query['oauth_signature']);
		$pairs = array_map_pass_key($query, function (string $k, string $v): string {
			// rawurlencode follows RFC 3986 since PHP 5.3
			return rawurlencode($k). "=" . rawurlencode($v);
		});
		sort($pairs, SORT_STRING);
		
		$toSign = rawurlencode(strtoupper($method)) . '&' . rawurlencode("$scheme://$host$path") .
			 '&' . rawurlencode(join('&', $pairs));
		$key = rawurlencode($this->gConsumerSecret) . '&' . rawurlencode($this->gTokenSecret);
		
		$result = base64_encode(hash_hmac('sha1', $toSign, $key, true));
		$logger->debug("sign_request($method, $url, ...) => $result");		
		return $result;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see OAuth::do_authorization_redirect()
	 */
	public function do_authorization_redirect(): string {
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
	 * {@inheritDoc}
	 * @see OAuth::fetch_access_token()
	 */
	public function fetch_access_token(): void {
		session_start();
		
		$url = "{$this->mwOAuthUrl}/token";
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
	 * {@inheritDoc}
	 * @see OAuth::do_identify()
	 */
	public function do_identify(): ?string {
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
	
}