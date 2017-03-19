<?php
class NonImageNewUploaderHandler extends NewUploadHandler {

	private $imageExtensions;
	private $nonImageExtensions;
	private $newUploaderThreshold;

	public function __construct() {
		global $constants;

		$imageExtensionsText = array_key_or_exception($constants, "uploadreport.imageextensions");
		$nonImageExtensionsText = array_key_or_exception($constants, "uploadreport.nonimageextensions");

		$this->imageExtensions    = explode(',', $imageExtensionsText);
		$this->nonImageExtensions = explode(',', $nonImageExtensionsText);
		$this->newUploaderThreshold = intval(
			array_key_or_exception($constants, "uploadreport.newuploaderthreshold"));
	}

	public function getTitleKey() {
		return "uploadhandler.nonimageformat";
	}

	public function isDisplayUpload($upload, $uploader) {
		global $logger;

		if ($uploader->getEditCount() <= $this->newUploaderThreshold) {
				
			$filename = $upload['title'];
			$extension = strtolower(substr($filename, strrpos($filename, '.')+1));
				
			if (in_array($extension, $this->imageExtensions)) {
				return false;
			}

			if (in_array($extension, $this->nonImageExtensions)) {
				return true;
			}

			$logger->warn("Unrecognized file extension: $extension");
			return true;
				
		}
		return false;
	}
}