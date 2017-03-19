<?php

class Cleanup_Progress_Indicator implements Progress_Indicator {

	/**
	 *
	 * @var resource
	 */
	private $file_handle;


	/**
	 *
	 * @var int
	 */
	private $total_count;


	/**
	 *
	 * @var int
	 */
	private $current_index = 0;


	/**
	 *
	 * @var bool
	 */
	private $success;
	
	/**
	 * 
	 * @var string
	 */
	private $continue_key;


	/**
	 *
	 * @param resource $file_handle
	 */
	public function __construct($file_handle) {
		global $validator;

		$validator->validate_arg($file_handle, "resource");
		$this->file_handle = $file_handle;
	}

	/**
	 * (non-PHPdoc)
	 * @see Progress_Indicator::setup()
	 */
	public function setup($step_count) {
		$this->total_count = $step_count;
		$this->write_line("|started $step_count");
	}

	/**
	 * (non-PHPdoc)
	 * @see Progress_Indicator::step()
	 */
	public function step($data) {
		list($title, $made_change) = $data;
		$title = substr($title, 5); //remove File: prefix
		$percent = round((++$this->current_index * 100) / $this->total_count, 1);
		$this->write_line("$made_change|$title|$percent");
	}

	/**
	 * 
	 * @param string|null $continue_key
	 * @return void
	 */
	public function set_continue_key($continue_key) {
		$this->continue_key = $continue_key;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Progress_Indicator::complete()
	 */
	public function complete() {
		$line = "complete";
		if ($this->continue_key) {
			$line .= "/$this->continue_key";
		}
		$this->write_line($line);
	}

	/**
	 * (non-PHPdoc)
	 * @see Progress_Indicator::error()
	 */
	public function error($message) {
		ogrebotMail($message);
		$this->write_line("error");
	}
	
	public function close() {
		fclose($this->file_handle);
	}

	/**
	 *
	 * @param string $message
	 * @return void
	 */
	private function write_line($message) {
		fwrite($this->file_handle, "\n$message");
		fflush($this->file_handle);
	}
}