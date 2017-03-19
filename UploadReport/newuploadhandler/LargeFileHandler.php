<?php



//disabling for now: not useful.
// class LargeFileHandler extends NewUploadHandler {

// 	private $fileSizeThresholdInBytes;

// 	public function __construct() {
// 		global $constants;

// 		$this->fileSizeThresholdInBytes = 1024 * 1024 *
// 			intval(array_key_or_exception($constants, "uploadreport.largefilethreshold"));
// 	}

// 	public function getTitleKey() {
// 		return "uploadhandler.largeimages";
// 	}

// 	public function isDisplayUpload($upload, $uploader) {
// 		return $upload['size'] >= $this->fileSizeThresholdInBytes;
// 	}
// }