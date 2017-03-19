<?php
class NoLicenseOrNldHandler extends NewUploadHandler {

	public function getTitleKey() {
		return "uploadhandler.nolicensenonld";
	}

	public function isDisplayUpload($upload, $uploader) {
		global $logger;

		try {
			$uploadData = new UploadData($upload);
				
			$result = count($uploadData->getLicenses()) == 0 &&
			!$uploadData->getNoLicense() &&
			!$uploadData->getNoSource() && !$uploadData->getNoPermission() &&
			!$uploadData->getOtrsPending();
			
			if ($result) {
				$logger->debug("NoLicenseOrNldHandler::isDisplayUpload -> false");
				unset($upload['metadata']);
				$logger->debug($upload);
				$logger->debug($uploadData);
			}
			return $result;

		} catch (Exception $e) {
			$logger->warn("Caught exception from UploadData; returning false.");
			ogrebotMail($e);
			return false;
		}
	}
}
