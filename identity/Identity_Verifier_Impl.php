<?php

/**
 * 
 * @author magog
 *
 */
class Identity_Verifier_Impl extends AbstractOAuthImpl implements Identity_Verifier {
	
	private const COOKIE_NAME = "magog-ident";
	
	/**
	 * 
	 * @var string
	 */
	private $cookie_path;
	
	/**
	 * 
	 * @var Identity_Dao
	 */
	private $identity_dao;
	
	public function __construct() {
		parent::__construct("ident.key");
		list($this->cookie_path, $identity_dao_class) = Environment::props("environment", 
			["ident.cookie.path", "identity_dao"]);		
		$this->identity_dao = new $identity_dao_class();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Verifier::get_cookie()
	 */
	public function get_cookie(): ?string {
		return @$_COOKIE[self::COOKIE_NAME];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Verifier::get_username()
	 */
	public function get_username(): ?string {
		$username = $this->get_username_by_cookie(@$_COOKIE[self::COOKIE_NAME]);
		Environment::get()->get_logger()->info("Username: $username");
		if ($username === null) {
			$this->erase_cookie();
		}
		return $username;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Verifier::get_username_by_cookie()
	 */
	public function get_username_by_cookie(?string $cookie): ?string {
		return $this->identity_dao->get($cookie);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Verifier::get_auth_tool_keys()
	 */
	public function get_auth_tool_keys() : array {
		return map_array_function_keys(Environment::prop("secrets", "ident.authtool"),
				function ($key) {
					$colon = strrpos($key, ":");
					return [substr($key, 0, $colon), substr($key, $colon + 1)];
				});
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see OAuth::do_identify()
	 */
	public function do_identify(): ?string {
		$username = Environment::prop("environment", "live") ? parent::do_identify() : "Magog the Ogre";

		$cookie_value = $this->identity_dao->set($username);
		setcookie(self::COOKIE_NAME, $cookie_value, time() + SECONDS_PER_DAY * 30, 
			$this->cookie_path);
		$_COOKIE[self::COOKIE_NAME] = $cookie_value;
		
		return $username;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Identity_Verifier::logout()
	 */
	public function logout(): void {
		$this->identity_dao->logout($_COOKIE[self::COOKIE_NAME]);
		$this->erase_cookie();
	}
	
	private function erase_cookie(): void {
		setcookie(self::COOKIE_NAME, null, -1, $this->cookie_path);
	}
}