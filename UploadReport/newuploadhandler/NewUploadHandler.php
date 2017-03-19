<?php

abstract class NewUploadHandler {
	
	/**
	 * 
	 * @var NewUploadHandler[]
	 */
	private static $allHandlers = null;
	
	/**
	 * 
	 * @return NewUploadHandler[]
	 */
	public static function getHandlers() {
		if (self::$allHandlers === null) {
			self::$allHandlers = [new FacebookImageHandler(), new NonImageNewUploaderHandler(), 
				// new LargeFileHandler()
				new NoLicenseOrNldHandler(), new SuspiciousFilenameHandler()];
		}
		return self::$allHandlers;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString() {
		return get_class($this);
	}
	
	
	/**
	 * returns the message key
	 * @return string
	 */
	public abstract function getTitleKey();
	
	/**
	 * @param array $upload
	 * @return boolean True if to include it, false if not
	 */
	public abstract function isDisplayUpload($upload, $uploaderData);
	
}