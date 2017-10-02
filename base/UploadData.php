<?php
class UploadData {
	
	const CATEGORY_PREFIX_LENGTH = 9;
	
	/**
	 *
	 * @var bool
	 */
	private $copyvio;
	
	/**
	 *
	 * @var bool
	 */
	private $dr;
	
	/**
	 *
	 * @var bool
	 */
	private $noPermission;
	
	/**
	 *
	 * @var bool
	 */
	private $noSource;
	
	/**
	 *
	 * @var bool
	 */
	private $noLicense;
	
	/**
	 *
	 * @var bool
	 */
	private $otrsPending;
	
	/**
	 *
	 * @var bool
	 */
	private $mobile;
	
	/**
	 *
	 * @var bool
	 */
	private $self;
	
	/**
	 *
	 * @var bool
	 */
	private $reupload;
	
	/**
	 *
	 * @var string
	 */
	private $title;
	
	/**
	 *
	 * @var string
	 */
	private $uploader;
	
	/**
	 *
	 * @var int
	 */
	private $size;
	
	/**
	 *
	 * @var int
	 */
	private $height;
	
	/**
	 *
	 * @var int
	 */
	private $width;
	
	/**
	 *
	 * @var string[]
	 */
	private $licenses;
	
	/**
	 *
	 * @param array $upload        	
	 * @throws UploadDataException
	 */
	public function __construct($upload) {
		global $logger, $validator;
		
		
		$license_reader = Category_License_Reader::get_singleton();		
		$validator->validate_arg($upload, "array");
		
		
		try {
			// title
			$title_namespace = array_key_or_exception($upload, "title");
			$this->title = substr($title_namespace, mb_stripos($title_namespace, ':') + 1);
			
			// uploader
			$this->uploader = array_key_or_exception($upload, "user");
			
			// size
			$size = @$upload['size'];
			if (!is_int($size)) {
				$logger->warn(
					"Can't find size for file: $upload[filename]. Size report: " .
						 print_r($size, true));
				$size = null;
			}
			$this->size = $size;
			
			// height
			$height = @$upload['height'];
			if (!is_int($height)) {
				$logger->warn(
					"Can't find height for file: $upload[filename] " . print_r($height, true));
				$height = null;
			}
			$this->height = $height;
			
			// width
			$width = @$upload['width'];
			if (!is_int($width)) {
				$logger->warn(
					"Can't find width for file: $upload[filename] " . print_r($width, true));
				$width = null;
			}
			$this->width = $width;
			
			// licensing
			$categories = array_map(function($category) {
				return substr($category, self::CATEGORY_PREFIX_LENGTH);
			}, array_key_or_empty($upload, 'categories'));
			
			$this->licenses = array();
			$this->noPermission = false;
			$this->noSource = false;
			$this->noLicense = false;
			$this->otrsPending = false;
			$this->dr = false;
			$this->copyvio = false;
			$this->self = false;
			$this->mobile = false;
			foreach ($categories as $category) {
				$this->otrsPending |= preg_match("/^OTRS pending as of \d+ [A-Z][a-z]+ \d+$/", 
					$category);
				$this->self |= $category == "Self-published work";
				$this->noLicense |= str_starts_with($category, 
					"Media uploaded without a license as of ");
				$this->noLicense |= str_starts_with($category, "Media without a license as of ");
				$this->noSource |= str_starts_with($category, "Media without a source as of");
				$this->noPermission |= str_starts_with($category, "Media missing permission");
				$this->dr |= str_starts_with($category, "Deletion requests");
				$this->copyvio |= str_starts_with($category, "Copyright violations") 
					|| $category === "Duplicate" || $category === "Other speedy deletions";
				$this->mobile |= str_starts_with($category, "Uploaded with Mobile");
			}
			$this->licenses = $license_reader->get_license_categories($categories);
			
			// no longer supported
			$this->reupload = false; // @$upload['overwrite']?true:false;
		} catch (Exception $e) {
			throw new UploadDataException($e->getMessage(), $e);
		}
	}
	
	/**
	 *
	 * @return string
	 */
	public function __toString() {
		return print_r(this, true);
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getCopyvio() {
		return $this->copyvio;
	}
	
	/**
	 *
	 * @param bool $copyvio        	
	 * @return void
	 */
	public function setCopyvio($copyvio) {
		$this->copyvio = $copyvio;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getDr() {
		return $this->dr;
	}
	
	/**
	 *
	 * @param bool $dr        	
	 * @return void
	 */
	public function setDr($dr) {
		$this->dr = $dr;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getNoPermission() {
		return $this->noPermission;
	}
	
	/**
	 *
	 * @param bool $noPermission        	
	 * @return void
	 */
	public function setNoPermission($noPermission) {
		$this->noPermission = $noPermission;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getNoSource() {
		return $this->noSource;
	}
	
	/**
	 *
	 * @param bool $noSource        	
	 * @return void
	 */
	public function setNoSource($noSource) {
		$this->noSource = $noSource;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getNoLicense() {
		return $this->noLicense;
	}
	
	/**
	 *
	 * @param bool $noLicense        	
	 * @return void
	 */
	public function setNoLicense($noLicense) {
		$this->noLicense = $noLicense;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getOtrsPending() {
		return $this->otrsPending;
	}
	
	/**
	 *
	 * @param bool $otrsPending        	
	 * @return void
	 */
	public function setOtrsPending($otrsPending) {
		$this->otrsPending = $otrsPending;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getMobile() {
		return $this->mobile;
	}
	
	/**
	 *
	 * @param bool $mobile        	
	 * @return void
	 */
	public function setMobile($mobile) {
		$this->mobile = $mobile;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getSelf() {
		return $this->self;
	}
	
	/**
	 *
	 * @param bool $self        	
	 * @return void
	 */
	public function setSelf($self) {
		$this->self = $self;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function getReupload() {
		return $this->reupload;
	}
	
	/**
	 *
	 * @param bool $reupload        	
	 * @return void
	 */
	public function setReupload($reupload) {
		$this->reupload = $reupload;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 *
	 * @param string $title        	
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getUploader() {
		return $this->uploader;
	}
	
	/**
	 *
	 * @param string $uploader        	
	 * @return void
	 */
	public function setUploader($uploader) {
		$this->uploader = $uploader;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}
	
	/**
	 *
	 * @param int $size        	
	 * @return void
	 */
	public function setSize($size) {
		$this->size = $size;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}
	
	/**
	 *
	 * @param int $height        	
	 * @return void
	 */
	public function setHeight($height) {
		$this->height = $height;
	}
	
	/**
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 *
	 * @param int $width        	
	 * @return void
	 */
	public function setWidth($width) {
		$this->width = $width;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getLicenses() {
		return $this->licenses;
	}
	
	/**
	 *
	 * @param string[] $licenses        	
	 * @return void
	 */
	public function setLicenses($licenses) {
		$this->licenses = $licenses;
	}
}