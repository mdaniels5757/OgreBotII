<?php

class License_Cache implements Serializable {

	/**
	 * Unix timestamp for when licenses are loaded
	 * @var int
	 */
	public $load_time;

	/**
	 * @var string[]
	 */
	public $license_regexes = [];

	/**
	 * (non-PHPdoc)
	 * @see Serializable::serialize()
	*/
	public function serialize() {
		return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * (non-PHPdoc)
	 * @see Serializable::unserialize()
	 */
	public function unserialize($data_string) {
		$data = json_decode($data_string, true);

		$this->load_time = $data['load_time'];
		$this->license_regexes = $data['license_regexes'];
	}
}
