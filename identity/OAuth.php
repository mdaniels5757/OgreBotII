<?php

/**
 * 
 * @author magog
 *
 */
interface OAuth {
		
	/**
	 * Get authorization redirect
	 * 
	 * @throws OAuthException
	 * @return string
	 */
	public function do_authorization_redirect(): string;
	
	
	/**
	 * Handle a callback to fetch the access token
	 * @return void
	 * @throws OAuthException
	 */
	public function fetch_access_token(): void;
	
	/**
	 *
	 * @throws OAuthException
	 * @return string|null
	 */
	public function do_identify(): ?string;
	
}