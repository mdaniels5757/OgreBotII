<?php

/**
 * 
 * @author magog
 *
 */
class File_System_Identity_Dao implements Identity_Dao {
	
	/**
	 * 
	 * @var string
	 */
	const DIRECTORY = "/tmp/ident/";
		
	/**
	 */
	public function __construct() {
		list($this->cookie_hash) = Environment::props("secrets", ["ident.cookie.hash"]);
	}
	
	/**
	 * 
	 * @param string $cookie
	 * @throws IllegalArgumentException
	 * @return NULL|string[]
	 */
	private function get_cookie_data($cookie) {
		global $logger;
		
		if (!$cookie) {
			$logger->debug("No cookie value provided.");
			return null;
		}
		
		$logger->debug("Cookie value provided: $cookie");
		try {
			list(,$filename, $cookie_hash) = Regex_Util::match("/^(\w+)\.(\w+)$/", $cookie);
		} catch (Regex_Exception $e) {
			$logger->warn($e);
			return null;
		}
		
		$logger->debug("Filename: $filename");
		$logger->debug("Cookie hash: $cookie_hash");
		
		$full_filename = BASE_DIRECTORY . self::DIRECTORY . $filename;	
		
		if (!file_exists($full_filename)) {
			$logger->info("Cookie not found: $full_filename.");
			return null;
		}
		
		list(,$username, $hash) = Regex_Util::match("/^(.+)\|(.+?)$/",
			file_get_contents_ensure($full_filename));
		
		if ($hash !== $cookie_hash) {
			$logger->warn("Hash doesn't match $hash != $file_data");
			return null;
		}
		
		return [$username, $full_filename];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Identity_Dao::get()
	 */
	public function get($cookie) {		
		$cookie_data = $this->get_cookie_data($cookie);
		
		if ($cookie_data === null) {
			return null;
		}
			
		return $cookie_data[0];		
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Identity_Dao::set()
	 */
	public function set($username) {
		global $logger, $string_utils;
		
		$logger->debug("set($username)");
		
		$full_filename = tempnam(BASE_DIRECTORY . self::DIRECTORY, "");
		$rand = $string_utils->get_rand_chars();
		$logger->debug("\$filename = $full_filename");		

		file_put_contents_ensure($full_filename, "$username|$rand");
		
		return pathinfo($full_filename, PATHINFO_FILENAME) . ".$rand";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Dao::logout()
	 */
	public function logout($cookie) {
		$file_data = $this->get_cookie_data($cookie);
		
		if ($file_data !== null) {
			unlink($file_data[1]);
		}
	}
}