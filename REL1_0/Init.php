<?php

/*
This file is part of Peachy MediaWiki Bot API

Peachy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @file
 * Main Peachy file
 * Defines constants, initializes global variables
 * Stores Peachy class
 */

/**
 * The version that Peachy is running 
 */
define( 'PEACHYVERSION', '1.0' );

/**
 * Minimum MediaWiki version that is required for Peachy 
 */
define( 'MINMW', '1.15' );

/**
 * Minimum PHP version that is required for Peachy 
 */
define( 'MINPHP', '5.2.1' );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_VERBOSE', Level::TRACE );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_NORMAL', Level::INFO );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_NOTICE', Level::INFO );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_WARN', Level::WARN );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_ERROR', Level::ERROR );

/**
 * PECHO constants, used for {@link outputText}()
 */
define( 'PECHO_FATAL', Level::FATAL );

$pgIP = dirname(__FILE__) . '/';

require_once( $pgIP . 'Includes/Exceptions.php' );

peachyCheckPHPVersion();

require_once( $pgIP . 'Includes/Autoloader.php' );
require_once( $pgIP . 'GenFunctions.php' );
require_once( $pgIP . 'Includes/Hooks.php' );
require_once( $pgIP . 'HTTP.php' );

$pgVerbose = array(0,1,2,3,4);
$pgUA = 'Peachy MediaWiki Bot API Version ' . PEACHYVERSION;
$pgIRCTrigger = array( '!', '.' );

$pgProxy = array();
$pgHTTP = new HTTP;

//Last version check
$tmp = null;
$PeachyInfo = MWReleases::load( $tmp, true );

if( !$PeachyInfo->isSupported( PEACHYVERSION ) ) {
//framework apparently hasn't loaded PECHO requirements yet... so we have to disable this notation Magog 6/30/11
	//pecho( "Peachy version is below minimum version {$PeachyInfo->get_min_version()}\n\n", PECHO_ERROR );
}
elseif( $PeachyInfo->newerVersionExists( PEACHYVERSION ) ) {
//framework apparently hasn't loaded PECHO requirements yet... so we have to disable this notation Magog 6/30/11
	//pecho( "New version of Peachy available: {$PeachyInfo->get_current_version()}\n\n", PECHO_WARN );
}

if( function_exists( 'mb_internal_encoding' ) ) {
	mb_internal_encoding( "UTF-8" );
}


/**
 * Base Peachy class, used to generate all other classes
 */
class Peachy {

	
	/**
	 *
	 * @var Password_Manager|null
	 */
	protected static $password_manager;
	
	/**
	 * Initializes Peachy, logs in with a either configuration file or a given username and password
	 * 
	 * @static
	 * @access public
	 * @param string $config_name Name of the config file stored in the Configs directory, minus the .cfg extension. Default null
	 * @param string $username Username to log in if no config file specified. Default null
	 * @param string $password Password to log in with if no config file specified. Default null
	 * @param string $base_url URL to api.php if no config file specified. Defaults to English Wikipedia's API.
	 * @return Wiki Instance of the Wiki class, where most functions are stored
	 */
	public static function newWiki( $config_name = null, $username = null, $password = null, $base_url = 'http://en.wikipedia.org/w/api.php', $classname = 'Wiki' ) {
		global $pgIP;

		//framework apparently hasn't loaded PECHO requirements yet... so we have to disable this notation Magog 6/30/11
		//pecho( "Loading Peachy (version " . PEACHYVERSION . ")...\n\n", PECHO_NORMAL );
		
		if( !is_null( $config_name ) ) {
			$config_params = self::parse_config( $config_name );
		
		}
		else {
			$config_params = array(
				'username' => $username,
				'password' => $password,
				'baseurl' => $base_url
			);
			
		}
		
		if( is_null( $config_params['baseurl'] ) || !isset( $config_params['baseurl'] ) ) {
			throw new LoginError( array( "MissingParam", "The baseurl parameter was not set." ) );
		}
		
		if( !isset( $config_params['username'] ) || !isset( $config_params['password'] ) ) {
			$config_params['nologin'] = true;
		}
		
		list( $version, $extensions ) = Peachy::wikiChecks( $config_params['baseurl'] );
		
		Hooks::runHook( 'StartLogin', array( &$config_params, &$extensions ) );
		
		$config_params['encodedparams'] = rawurlencode( serialize( $config_params ) );
		$w = new $classname( $config_params, $extensions, false, null , $version);
		
		return $w;
	}
	
	/**
	 * Performs various checks and settings
	 * Checks if MW version is at least {@link MINMW}
	 * 
	 * @static
	 * @access public
	 * @param string $base_url URL to api.php
	 * @return array Installed extensions
	 */
	public static function wikiChecks( $base_url ) {
		global $pgHTTP;

		if ($base_url==='http://www.wikivoyage-old.org/w/shared/api.php')
		{
		  $version = '1.13';
		  $extensions = array();
		  return array( $version, $extensions );
		}
		
		$siteinfo = unserialize( $pgHTTP->get( 
			$base_url,
			 array( 'action' => 'query',
				'meta' => 'siteinfo',
				'format' => 'php',
				'siprop' => 'extensions|general',
		)));

		$version = preg_replace("/(\d)wmf(\d)/", "$1.$2", $siteinfo['query']['general']['generator']);
		$version = preg_replace( '/[^0-9\.]/', '', $version );
		
		if( version_compare( $version, MINMW ) < 0 ) {
			throw new DependencyError( "($version < MediaWiki " . MINMW .")", "http://mediawiki.org" );
		}

		$extensions = array();

		foreach( $siteinfo['query']['extensions'] as $ext ) {
			if( isset( $ext['version'] ) ) {
				$extensions[$ext['name']] = $ext['version'];
			}
			else {
				$extensions[$ext['name']] = '';
			}
		}
		
		return array( $version, $extensions );
	}
	
	/**
	 * Loads a specific plugin into memory
	 * 
	 * @static
	 * @access public
	 * @param string|array $plugins Name of plugin(s) to load from Plugins directory, minus .php ending
	 * @return void
	 * @deprecated
	 */
	public static function loadPlugin( $plugins ) {
		self::deprectaedWarn( null, null, "Warning: Peachy::loadPlugin() is deprecated. Thanks to the wonders of PHP 5, the call can just be removed." );
	}
	
	/**
	 * Loads all available plugins
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @deprecated
	 */
	public static function loadAllPlugins() {
		self::deprectaedWarn( null, null, "Warning: Peachy::loadAllPlugins() is deprecated. Thanks to the wonders of PHP 5, the call can just be removed." );

	}
	
	/**
	 * Checks for config files, parses them. 
	 * 
	 * @access private
	 * @static
	 * @param string $config_name Name of config file
	 * @return array Config params
	 */
	private static function parse_config( $config_name ) {
		global $pgIP;
		if( !is_file( $config_name ) ) {
			if( !is_file( $pgIP . 'Configs/' . $config_name . '.cfg' ) ) {
				throw new BadEntryError( "BadConfig", "A non-existent configuration file was specified." );
			}
			else {
				$config_name = $pgIP . 'Configs/' . $config_name . '.cfg';
			}
		}
		
		
		
		$config_params = parse_ini_file( $config_name );
		
		if( isset( $config_params['useconfig'] ) ) {
			$config_params = self::parse_config( $config_params['useconfig'] );
		}
		
		if (self::$password_manager) {
			if (!is_null(@$config_params['username'])) {
				$password = self::$password_manager->get_password($config_params);
				if (is_null($password)) {
					throw new Exception("No password loaded but username present.");
				}
				$config_params['password'] = $password;
			}
		}
		
		return $config_params;
	}
	
	public static function deprecatedWarn( $method, $newfunction, $message = null ) {
		if( is_null( $message ) ) {
			$message = "Warning: $method is deprecated. Please use $newfunction instead.";
		}
		
		$message = "[$message|YELLOW_BAR]\n\n";
		
		pecho( $message, PECHO_WARN, 'cecho' );
	}	
	
	public static function getSvnInfo() {
		global $pgIP;
		
		// http://svnbook.red-bean.com/nightly/en/svn.developer.insidewc.html
		$entries = $pgIP . '/.svn/entries';

		if( !file_exists( $entries ) ) {
			return false;
		}

		$lines = file( $entries );
		if ( !count( $lines ) ) {
			return false;
		}

		// check if file is xml (subversion release <= 1.3) or not (subversion release = 1.4)
		if( preg_match( '/^<\?xml/', $lines[0] ) ) {			
			return false;
		}

		// Subversion is release 1.4 or above.
		if ( count( $lines ) < 11 ) {
			return false;
		}
		
		$info = array(
			'checkout-rev' => intval( trim( $lines[3] ) ),
			'url' => trim( $lines[4] ),
			'repo-url' => trim( $lines[5] ),
			'directory-rev' => intval( trim( $lines[10] ) )
		);
		
		return $info;
	}
	
	/**
	 *
	 * @return Password_Manager|null
	 */
	public static function get_password_manager() {
		return self::$password_manager;
	}
	
	/**
	 *
	 * @param Password_Manager|null $password_manager
	 * @return void
	 */
	public static function set_password_manager(Password_Manager $password_manager = null) {
		self::$password_manager = $password_manager;
	}
}

/**
 * Simple phpversion() wrapper
 * @return void
 */
function peachyCheckPHPVersion( $check_version = null ) {
	if( is_null( $check_version ) ) $check_version = phpversion();
	
	$version = explode( '.', $check_version );
	
	$min_version = explode( '.', MINPHP );
	
	if( 
		( $version[0] < $min_version[0] ) ||
		( $version[0] == $min_version[0] && $version[1] < $min_version[1] ) ||
		( $version[0] == $min_version[0] && $version[1] == $min_version[1] && $version[2] < $min_version[2] )
	) throw new DependancyError( "PHP " . MINPHP, "http://php.net/downloads.php" );
	
	return $version;
}
