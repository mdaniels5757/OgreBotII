<?php

class Image {
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access protected
	 */
	protected $wiki;
	
	/**
	 * Page class
	 * 
	 * @var Page
	 * @access protected
	 */
	protected $page;
	
	
	/**
	 * MIME type of image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $mime;
	
	/**
	 * Bitdepth of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $bitdepth;
	
	/**
	 * SHA1 hash of image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $hash;
	
	/**
	 * Size of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $size;
	
	/**
	 * Metadata stored in the image
	 * 
	 * @var array
	 * @access protected
	 */
	protected $metadata = array();
	
	/**
	 * URL to direct image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $url;
	
	/**
	 * Timestamp that of the most recent upload
	 * 
	 * @var string
	 * @access protected
	 */
	protected $timestamp;
	
	/**
	 * Username of the most recent uploader
	 * 
	 * @var string
	 * @access protected
	 */
	protected $user;
	
	/**
	 * Width of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $width;
	
	/**
	 * Height of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $height;
	
	
	/**
	 * Whether or not the image is hosted locally
	 * This is not whether or not the page exists, use Image::get_exists() for that
	 * 
	 * @var bool 
	 * @access protected
	 */
	protected $local = true;
	
	
	/**
	 * Sanitized name for local storage (namespace, colons, etc all removed)
	 * 
	 * @var string
	 * @access protected
	 */
	protected $localname;
	
	/**
	 * Image name, with namespace
	 * 
	 * @var string
	 * @access protected
	 */
	protected $title;
	
	/**
	 * Image name, without namespace
	 * 
	 * @var string
	 * @access protected
	 */
	protected $rawtitle;
	
	
	/**
	 * List of pages where the image is used
	 * 
	 * @var array
	 * @access protected
	 */
	protected $usage = array();
	
	/**
	 * List of previous uploads
	 * 
	 * @var array
	 * @access protected
	 */
	protected $history = array();
	
	/**
	 * Other images with identical SHA1 hashes
	 * 
	 * @var array
	 * @access protected
	 */
	protected $duplicates = array();
	
	
	/**
	 * Construction method for the Image class
	 * 
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @param string $filename Filename
	 * @return void
	 */
	function __construct( &$wikiClass, $title = null ) {
		
		$this->wiki = &$wikiClass;
		
		$this->title = $title;
		
		if( $this->wiki->removeNamespace( $title ) == $title ) {
			$namespaces = $this->wiki->get_namespaces();
			$this->title = $namespaces[6] . ':' . $title;
		}
		
		$ii = $this->imageinfo();
		
		foreach( $ii as $x ) {
			
			$this->title = $x['title'];
			$this->rawtitle = $this->wiki->removeNamespace( $x['title'] );
			$this->localname = str_replace( array( ' ', '+' ), array( '_', '_' ), urlencode( $this->rawtitle ) );
			
			$this->page = &$this->wiki->initPage( $this->title );
			
			if( $x['imagerepository'] == "shared" ) $this->local = false;
			
			if( isset( $x['imageinfo'] ) ) {
				
				$this->mime = $x['imageinfo'][0]['mime'];
				$this->bitdepth = $x['imageinfo'][0]['bitdepth'];
				$this->hash = $x['imageinfo'][0]['sha1'];
				$this->size = $x['imageinfo'][0]['size'];
				$this->width = $x['imageinfo'][0]['width'];
				$this->height = $x['imageinfo'][0]['height'];
				$this->url = $x['imageinfo'][0]['url'];
				$this->timestamp = $x['imageinfo'][0]['timestamp'];
				$this->user = $x['imageinfo'][0]['user'];
				
				if( is_array( $x['imageinfo'][0]['metadata'] ) ) {
					foreach( $x['imageinfo'][0]['metadata'] as $metadata ) {
						$this->metadata[$metadata['name']] = $metadata['value'];
					}
				}
				
			}
		}
		
	}
	
	/**
	 * Returns various information about the image
	 * 
	 * @access public
	 * @param int $limit Number of revisions to get info about. Default 1
	 * @param int $width Width of image. Default -1 (no width)
	 * @param int $height Height of image. Default -1 (no height)
	 * @param string $start Timestamp to start at. Default null
	 * @param string $end Timestamp to end at. Default null
	 * @param array $prop Properties to retrieve. Default array( 'timestamp', 'user', 'comment', 'url', 'size', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' )
	 * @return array
	 */
	public function imageinfo( $limit = 1, $width = -1, $height = -1, $start = null, $end = null, $prop = array( 'timestamp', 'user', 'comment', 'url', 'size', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' ) ) {
	
		$imageInfoArray = array(
			'action' => 'query',
			'prop' => 'imageinfo',
			'iilimit' => $limit,
			'iiprop' => implode('|',$prop),
			'iiurlwidth' => $width,
			'iiurlheight' => $height,
			'titles' => $this->title
		);
		
		if( !is_null( $start ) ) $imageInfoArray['iistart'] = $start;
		if( !is_null( $end ) ) $imageInfoArray['iiend'] = $end;
		
		
		pecho( "Getting image info for {$this->title}...\n\n", PECHO_NORMAL );
		
		$ii = $this->wiki->apiQuery( $imageInfoArray );
		
		return $ii['query']['pages'];
	}
	
	/**
	 * Returns the upload history of the image. If function was already called earlier in the script, it will return the local cache unless $force is set to true. 
	 * 
	 * @access public
	 * @param bool $force Whether or not to always refresh. Default false
	 * @param string $dir Which direction to go. Default 'older'
	 * @param int $limit Number of revisions to get. Default null (all revisions)
	 * @return void
	 */
	public function get_history( $dir = 'older', $limit = null ) {
		
		$this->history = $this->page->history( $limit, $dir );
		return $this->history;
	}
	
	/**
	 * Returns all pages where the image is used. If function was already called earlier in the script, it will return the local cache unless $force is set to true. 
	 * 
	 * @access public
	 * @param bool $force Whether or not to regenerate list, even if there is a local cache. Default false, set to true to regenerate list.
	 * @param string|array $namespace Namespaces to look in. If set as a string, must be set in the syntax "0|1|2|...". If an array, simply the namespace IDs to look in. Default null.
	 * @param string $redirects How to filter for redirects. Options are "all", "redirects", or "nonredirects". Default "all".
	 * @param bool $followRedir If linking page is a redirect, find all pages that link to that redirect as well. Default false.
	 * @return array
	 */
	public function get_usage( $namespace = null, $redirects = "all", $followRedir = false, $limit = null ) {
		
		if( !count( $this->usage ) ) {
		
			$iuArray = array(
				'list' => 'imageusage',
				'_code' => 'iu',
				'_lhtitle' => 'title',
				'iutitle' => $this->title,
				'iufilterredir' => $redirects,
			);
			
			if( !is_null( $namespace ) ) {
			
				if( is_array( $namespace ) ) {
					$namespace = implode( '|', $namespace );
				}
				$iuArray['iunamespace'] = $namespace;
			}
			
			if( !is_null( $limit ) ) $iuArray['iulimit'] = $limit;
			
			if( $followRedir ) $iuArray['iuredirect'] = 'yes';
			
			pecho( "Getting image usage for {$this->title}..\n\n", PECHO_NORMAL );
			
			$this->usage = $this->wiki->listHandler( $iuArray );
			
		}
		
		return $this->usage;
	}
	
	/**
	 * Returns an array of all files with identical sha1 hashes
	 *
	 * @param int $limit Number of duplicates to get. Default null (all)
	 * @return array Duplicate files
	 */
	public function get_duplicates( $limit = null ) {
		
		if( !count( $this->duplicates ) ) {
			
			if( !$this->page->get_exists() ) {
				return $this->duplicates;
			}
		
			$dArray = array(
				'action' => 'query',
				'prop' => 'duplicatefiles',
				'dflimit' => ( $this->wiki->get_api_limit() + 1 ),
				'titles' => $this->title
			);
			
			$continue = null;
			
			pecho( "Getting duplicate images of {$this->title}..\n\n", PECHO_NORMAL );
			
			while( 1 ) {
				if( !is_null( $continue ) ) $tArray['dfcontinue'] = $continue;
				
				$dRes = $this->wiki->apiQuery( $dArray );
				
				foreach( $dRes['query']['pages'] as $x ) {
					if( isset( $x['duplicatefiles'] ) ) {
						foreach( $x['duplicatefiles'] as $y ) {
							$this->duplicates[] = $y['name'];
						}
					}
				}
				
				if( isset( $dRes['query-continue'] ) ) {
					foreach( $dRes['query-continue'] as $z ) {
						$continue = $z['dfcontinue'];
					}
				}
				else {
					break;
				}
				
				
			}
			
		}
		
		return $this->duplicates;
	}	
	
	/**
	 * Upload an image to the wiki
	 * 
	 * @access public
	 * @param string $mime
	 * @param mixed $localname Location of the file to upload. Either an absolute path, or the name of an image in the Images/ directory will work. Default null (/path/to/peachy/Images/<$this->localname>)
	 * @param string $text Text on the image file page (default: '')
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param bool $watch Should the upload be added to the watchlist (default: false)
	 * @param bool $ignorewarnings Ignore warnings about the upload (default: true)
	 * @return bool|void
	 */
	public function upload( $mime, $localname = null, $text = '', $comment = '', $watch = false, $ignorewarnings = true ) {
		global $pgIP, $logger, $validator;
		
		if( version_compare( $this->wiki->get_mw_version(), '1.16' ) < 0 ) {
			throw new DependencyError("Mediawiki 1.16");
		}
		
		$validator->validate_arg($mime, "string");
		
		if( !is_file( $localname ) ) {
		
			if( is_file( $pgIP . 'Images/' . $localname ) ) {
				$localname = $pgIP . 'Images/' . $localname;
			}
			else {
				$localname = $pgIP . 'Images/' . $this->localname;
			}

			if( !is_file( $localname ) ) {
				throw new BadEntryError( "FileNotFound", "The given file was not found" );
			}
		}
		
		$fileowner = posix_getpwuid(fileowner($localname));
		$fileperms = decoct(fileperms($localname));
		
		$logger->debug( "Uploading $localname to {$this->title}..");
		$logger->trace( "File owner: $fileowner[name]");
		$logger->trace( "File permissions: $fileperms");
		$logger->debug( "Size of file: " .filesize($localname));
		
		return $this->api_upload( $mime, $localname, $text, $comment, $watch, $ignorewarnings );
		
	}
	
	/**
	 * Upload an image to the wiki using api.php
	 * 
	 * @access public
	 * @param string $mime
	 * @param mixed $localname Absolute path to the image
	 * @param string $text Text on the image file page (default: '')
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param bool $watch Should the upload be added to the watchlist (default: false)
	 * @param bool $ignorewarnings Ignore warnings about the upload (default: true)
	 * @return bool
	 */
	public function api_upload( $mime, $localname, $text = '', $comment = '', $watch = false, $ignorewarnings = true ) {
		global $logger;
		
		$logger->debug($this->wiki->get_base_url());
		$logger->debug($this->rawtitle);
		$logger->debug("api_upload($mime, $localname, $text, $comment, $watch, $ignorewarnings);");
		
		$tokens = $this->wiki->get_tokens();
		
		$logger->trace("tokens:");
		$logger->trace($tokens);
		
		$apiArr = array(
			'action' => 'upload',
			'filename' => $this->rawtitle,
			'comment' => $comment,
			'text' => $text,
			'token' => $tokens['edit'],
			'ignorewarnings' => intval( $ignorewarnings ),
			'file' => new CURLFile($localname, $mime, $this->rawtitle)
		);
		
		Hooks::runHook( 'APIUpload', array( &$apiArr ) );
		
		$result = $this->wiki->apiQuery( $apiArr, true);
		
		if( @$result && isset( $result['upload'] ) &&
				isset( $result['upload']['result'] ) && 
				$result['upload']['result'] == "Success" ) {
			if (@$logger) {
				$logger->debug("Upload SUCCESS.");
			}
			$this->__construct( $this->wiki, $this->title );
			return true;
		} else {
			if (@$logger) {
				$logger->error("Upload error...");
				$logger->error($result);
			}
			return false;
		}

	}
	
	/**
	 * Downloads an image to the local disk
	 * 
	 * @param string $localname Filename to store image as. Default null.
	 * @param int $width Width of image to download. Default -1.
	 * @param int $height Height of image to download. Default -1.
	 * @return void
	 */
	public function download( $localname = null, $width = -1, $height = -1 ) {
		global $pgIP;
		global $logger;
		
		if( !$this->local && @$logger) {
			$logger->warn("Attempted to download a file on a shared respository instead "
				."of a local one");
		}
		
		if( !$this->page->get_exists() && @$logger) {
			$logger->warn( "Attempted to download a non-existant file.");
		}
		
		$ii = $this->imageinfo( 1, $width, $height );
		
		if( isset( $ii[ $this->page->get_id() ]['imageinfo'] ) ) {
			$ii = $ii[ $this->page->get_id() ]['imageinfo'][0];
			
			if( $width != -1 ) {
				$url = $ii['thumburl'];
			}
			else {
				$url = $ii['url'];
			}
			
			if( is_null( $localname ) ) {
				$localname = $pgIP . 'Images/' . $this->localname;
			}
			
			Hooks::runHook( 'DownloadImage', array( &$url, &$localname ) );
			
			if (@$logger) {
				$logger->warn( "Downloading {$this->title} to $localname..");
			}
			
			$this->wiki->get_http()->download( $url, $localname );
		}
		else if (@$logger) {
			$logger->error("Error in getting image URL.");
			$logger->error($ii);
		}
	}	
	
	/**
	 * Returns the normalized image name
	 * 
	 * @param bool $namespace Whether or not to include the File: part of the name. Default true.
	 * @return string
	 */
	public function get_title( $namespace = true ) {
		if( $namespace ) {
			return $this->title;
		}
		else {
			return $this->rawtitle;
		}
	}
	
	/**
	 * Returns the sanitized local disk name
	 * 
	 * @return string
	 */
	public function get_localname() {
		return $this->localname;
	}
	
	/**
	 * Whether or not the image is on a shared repository. A true result means that it is stored locally.
	 * 
	 * @return bool
	 */
	public function is_local() {
		return $this->local;
	}
	
	/**
	 * Whether or not the image exists
	 * 
	 * @return bool
	 */
	public function get_exists() {
		return $this->page->get_exists();
	}
	
	/**
	 * Returns a page class for the image
	 * 
	 * @return Page
	 */
	public function &get_page() {
		return $this->page;
	}
	
	/**
	 * Returns the MIME type of the image
	 * 
	 * @return string
	 */
	public function get_mime() {
		return $this->mime;
	}
	
	/**
	 * Returns the bitdepth of the image
	 * 
	 * @return int
	 */
	public function get_bitdepth() {
		return $this->bitdepth;
	}
	
	/**
	 * Returns the SHA1 hash of the image
	 * 
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}
	
	/**
	 * Returns the size of the image, in bytes
	 * 
	 * @return int
	 */
	public function get_size() {
		return $this->size;
	}
	
	/**
	 * Returns the metadata of the image
	 * 
	 * @return array
	 */
	public function get_metadata() {
		return $this->metadata;
	}
	
	/**
	 * Returns the direct URL of the image
	 * 
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}
	
	/**
	 * Returns the timestamp that of the most recent upload
	 * 
	 * @return string
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}
	
	/**
	 * Returns the username of the most recent uploader
	 * 
	 * @return string
	 */
	public function get_user() {
		return $this->user;
	}
	
	/**
	 * Returns the width of the image
	 * 
	 * @return int
	 */
	public function get_width() {
		return $this->width;
	}
	
	/**
	 * Returns the height of the image
	 * 
	 * @return int
	 */
	public function get_height() {
		return $this->height;
	}
	
	private static function trace($message) {
		global $logger;
		if (@$logger) {
			$logger->trace($message);
		}
	}
	
	/**
	 * author Magog
	 *
	 */
	public function __toString() {
		return $this->wiki->get_base_url().':'.$this->title;
	}
	
}
