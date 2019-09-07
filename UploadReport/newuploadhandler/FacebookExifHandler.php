<?php
class FacebookExifHandler extends NewUploadHandler {

	public function getTitleKey() {
		return "uploadhandler.facebookexif";
	}

	public function isDisplayUpload($upload, $uploader) {
		foreach ($upload[METADATA]??[] as ["name" => $name, "value" => $value]) {
			if ($name === SPECIAL_INSTRUCTIONS) {
				return !!preg_match("/^FBMD[\da-f]+$/", $value[0]["value"]??"");
			}
		}
		return false;
	}
}