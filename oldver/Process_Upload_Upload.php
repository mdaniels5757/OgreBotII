<?php
/**
 * 
 * @author magog
 *
 */
class Process_Upload_Upload implements JsonSerializable {

	/**
	 * 
	 * @var bool
	 */
	public $download_attempted;
	
	/**
	 *
	 * @var Remote_Response
	 */
	public $download_response;
	
	/**
	 *
	 * @var int
	 */
	public $download_time;

	/**
	 *
	 * @var bool
	 */
	public $upload_attempted;
	/**
	 * 
	 * @var int
	 */
	public $upload_time;
	
	
	/**
	 * A "truthy" boolean (might be null for false)
	 * @var bool
	 */
	public $upload_error;
	
	/**
	 * 
	 * @var string
	 */
	public $edit_summary;
	
	/**
	 * 
	 * @var int
	 */
	public $size;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 */
	public function jsonSerialize() {
		return [
			'download_attempted' => !!$this->download_attempted,
			'download_time' => $this->download_time,
			'edit_summary' => $this->edit_summary,
			'size' => $this->size,
			'upload_attempted' => $this->upload_attempted,
			'upload_error' => $this->upload_error,
			'upload_time' => $this->upload_time
		];
	}
}