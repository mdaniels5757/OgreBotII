<?php
class FacebookImageHandler extends NewUploadHandler {

	public function getTitleKey() {
		return "uploadhandler.facebookimage";
	}

	public function isDisplayUpload($upload, $uploader) {
		$filename = $upload['title'];

		return preg_match("/^(?:\w+)(?# File namespace)\:\d{6,}( \d{7,}){2,} (a|b|n|o|s)\.jpg$/",
			$filename);
	}
}