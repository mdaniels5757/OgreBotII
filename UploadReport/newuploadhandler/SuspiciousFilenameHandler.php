<?php
class SuspiciousFilenameHandler extends NewUploadHandler {

	public function getTitleKey() {
		return "uploadhandler.suspiciousfilename";
	}

	public function isDisplayUpload($upload, $uploader) {
		global $logger;

		try {
			return preg_match("/^\s*(?=\d*[a-z]+\d+[a-z]+)(?=[a-z]*\d+[a-z]+\d+)[a-z\d]{25,}( \(\d|\))?\.\w+/ui", $upload['title']);
		} catch (Exception $e) {
			$logger->warn("Caught exception from UploadData; returning false.");
			ogrebotMail($e);
			return false;
		}
	}
}