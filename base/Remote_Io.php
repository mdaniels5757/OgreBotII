<?php

class Remote_Io {
	
	/**
	 * 
	 * @var string
	 */
	private $ident_username;
	
	/**
	 * 
	 * @var string
	 */
	private $ident_password;
	
	/**
	 * 
	 * @var string
	 */
	private $ident_url;
	
	public function __construct() {
		list($this->ident_username, $this->ident_password) = Environment::props("secrets", 
			["ident.authtool.magog.username", "ident.authtool.magog.password"]);
		list($this->ident_url) = Environment::props("environment", ["ident.url"]);
	}
	
	/**
	 * 
	 * @param string $cookie
	 * @throws TuscException
	 * @return string|null
	 */
	public function verify_ident($cookie) {
		
		$environment = Environment::get();
		$logger = $environment->get_logger();
		
		$logger->debug("verify_ident($cookie)");
		
		$crl = curl_init($this->ident_url);
		$fields = "tooluser=" . rawurlencode($this->ident_username) . "&toolpass=" .
			 rawurlencode($this->ident_password) . "&cookie=" . rawurlencode($cookie);
		curl_setopt($crl, CURLOPT_HEADER, 0);
		curl_setopt($crl, CURLOPT_POST, 3 /* number of fields */);
		curl_setopt($crl, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($crl, CURLOPT_USERAGENT, OGREBOT_USERAGENT);
		$ret = curl_exec($crl);
		$ret_json = json_decode($ret, true);
		curl_close($crl);
		
		if (!$ret_json || $ret_json["error"]) {
			throw new TuscException($ret_json, curl_getinfo($crl, CURLINFO_HTTP_CODE));
		}
		return $ret_json["username"];
	}
}

/**
 * 
 * @param mixed $message
 * @return void
 */
function ogrebotMail($message = "") {
	global $environment, $constants;
	
	static $count = 0, $max;
	
	if ($max === null) {
		$max = (int)array_key_or_exception($constants, "error.email.max_per_session");
	}
	
	logger_or_stderr(LEVEL::TRACE, "Entering ogrebotMail($message)");
	logger_or_stderr(LEVEL::ERROR, $message);
	
	if ($constants === null) {
		logger_or_stderr(LEVEL::ERROR, "Constants environment not yet initialized; can't proceed.");
		return;
	}
	
	if ($message instanceof BaseException) {
		if ($message->isSentMail()) {
			logger_or_stderr(LEVEL::DEBUG, "Already sent mail; bypassing.");
			return;
		} else {
			$message->setMailSent();
		}
	}
	
	if ($message instanceof Exception) {
		$message = exceptionToString($message);
	}
	
	if ($environment['environment'] !== 'local') {
		logger_or_stderr(LEVEL::DEBUG, "Sending email: $message");
		
		$email = new Email($constants["error.email.from"], "OgreBot", 
			"OgreBot error - $_SERVER[PHP_SELF]", $message);
		
		foreach ($constants["error.email.to"] as $to) {
			$email->addTarget($to);
		}
		logger_or_stderr(LEVEL::DEBUG, "sending message");
		if (++$count <= $max) {
			$result = $email->send();
			
			if ($result) {
				logger_or_stderr(LEVEL::DEBUG, "message SENT.");
			} else {
				logger_or_stderr(LEVEL::DEBUG, "message FAILED to send");
			}
			if ($count === $max) {
				$email = new Email($constants["error.email.from"], "OgreBot", 
					"OgreBot error - Emails STOPPED ($_SERVER[PHP_SELF])",
					"Limit reached on maximum emails per session.");
				foreach ($constants["error.email.to"] as $to) {
					$email->addTarget($to);
				}
				
				$result = $email->send();
					
				if ($result) {
					logger_or_stderr(LEVEL::DEBUG, "message SENT.");
				} else {
					logger_or_stderr(LEVEL::DEBUG, "message FAILED to send");
				}
			}
		} else {
			logger_or_stderr(LEVEL::ERROR, "Already sent the maximum number of emails!");
		}
	} else {
		logger_or_stderr(LEVEL::ALL, "Not live; can't send email: $message");
	}
}

/**
 *
 * @param string $url        	
 * @return string
 * @throws CURLError
 */
function http_get_content($url) {
	global $logger;
	
	$logger->debug("http_get_content($url)");
	
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_MAXCONNECTS, 100);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
	curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_HTTPGET, 1);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, "OgreBot");
	
	$data = curl_exec($curl);
	
	if (curl_errno($curl) != 0) {
		throw new CURLError(curl_errno($curl), curl_error($curl));
	}
	$logger->debug("Exiting http_get_content($url)");
	
	return $data;
}

/**
 *
 * @param string $user        	
 * @param string $password        	
 * @return bool
 * @throws TuscException
 */
function tusc_verify($user) {
	global $logger, $environment, $tusc_password;
	
	$logger->debug(
		"tusc_verify($user, " . ($tusc_password ? "non-empty string" : "empty string") . ")");
	
	if ($environment['live']) {
		$logger->trace("live");
		$tusc_user = rawurlencode(str_replace(" ", "_", $user));
		$tusc_pswd = rawurlencode($tusc_password);
		$tusc_password = null; // clear global
		$crl = curl_init($environment['tusc.url']);
		$fields = "check=1&botmode=1&user=$tusc_user&language" .
			 "=commons&project=wikimedia&password=$tusc_pswd";
		curl_setopt($crl, CURLOPT_HEADER, 0);
		curl_setopt($crl, CURLOPT_POST, 6 /* number of fields */);
		curl_setopt($crl, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($crl, CURLOPT_USERAGENT, "OgreBot");
		$logger->trace("Tusc request: $fields");
		$ret = curl_exec($crl);
		$logger->trace("Tusc response: $ret");
		curl_close($crl);
	} else {
		$tusc_password = null; // clear global
		$logger->info("not live");
		$ret = "1";
		$tusc_user = "Magog_the_Ogre";
	}
	
	if ($ret === "1") {
		return true;
	} else if ($ret === "0") {
		return false;
	}
	
	throw new TuscException($ret, curl_getinfo($crl, CURLINFO_HTTP_CODE));
}

/**
 * Downloads cURL contents, optionally into a file.
 * 
 * @deprecated Use Download_Util
 * @param resource $curl_handler
 *        	cURL resource
 * @param string $url        	
 * @param string $filename        	
 * @param string $postvars
 *        	(e.g., "question=1&answer=no&uname=test"). Default: NULL -> GET request. FIXME: not yet implemented.
 * @throws CantOpenFileException
 * @throws CURLError
 * @return resource handle to the file.
 */
function curl_download($curl_handler, $url, $filename, $postvars = NULL) {
	global $logger, $validator;
	/* pre-function assertions */
	$validator->validate_arg($curl_handler, "resource");
	$validator->validate_arg($url, "string");
	$validator->validate_arg($filename, "string");
	$validator->validate_arg($postvars, "string", true);
	
	$logger->debug(
		"curl_download($curl_handler, $url, $filename, " . query_to_string($postvars) . ")");
	
	$logger->debug("d/l $url -> $filename");
	$file = fopen($filename, 'wb');
	if ($file === false) {
		throw new CantOpenFileException($filename);
	}
	
	if ($logger->isTraceEnabled()) {
		$fileowner = posix_getpwuid(fileowner($filename));
		$fileperms = decoct(fileperms($filename));
		
		$logger->trace("File owner: $fileowner[name]");
		$logger->trace("File permissions: $fileperms");
	}

	if (str_starts_with($url, "https://") && !Environment::prop("environment", "ssl.verify")) {
		curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, 0);
	}
	
	curl_setopt($curl_handler, CURLOPT_URL, $url);
	curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, true);
	$content = curl_exec($curl_handler);
	$code = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
	$success = ($code >= 200 && $code <= 203);
	
	$logger->debug("Return code: $code");
	if (is_string($content)) {
		$logger->debug("Size of downloaded content: " . strlen($content));
	} else {
		$logger->warn("Empty \$content");
	}
	
	if ($success && $file) {
		$return_result = fwrite($file, $content);
		
		if ($return_result === false) {
			$logger->error("Error on write.");
			return "Unknown error.";
		}
		$logger->debug("Size of file write: $return_result");
	} else if (!$success) {
		$logger->fatal("HTTP code $code.\nURL:$url\nContent: $content");
		throw new CURLError($code, $content);
	}
	return $file;
}