<?php
class RollingCategoryTreeLogger implements CategoryTreeLogger {

	/**
	 * The path to the files
	 * @var string
	 */
	private $output_path;

	/**
	 *
	 * @var number
	 */
	private $date;

	/**
	 *
	 * @var string
	 */
	private $entries;

	/**
	 *
	 * @var ProjectData
	 */
	private $project_data;

	/**
	 *
	 * @param string $output_path
	 * @return void
	 */
	public function __construct($output_path) {
		global $validator;

		$validator->validate_arg($output_path, "string");

		$this->output_path = $output_path;
	}

	/**
	 * (non-PHPdoc)
	 * @see CategoryTreeLogger::init()
	 */
	public function init($date, ProjectData $project_data) {
		global $validator;

		$validator->validate_arg($date, "numeric");
		$this->date = $date;
		$this->entries = "";
		$this->project_data = $project_data;
	}

	/**
	 * (non-PHPdoc)
	 * @see CategoryTreeLogger::log()
	 */
	public function log($gallery_name, $category_name, $pages) {
		global $logger;

		$logger->debug("RollingCategoryTreeLogger::log(string[". count($pages) . "])");

		foreach ($pages as $page) {
			if (str_starts_with($page, "File:")) {
				$this->entries.= "$gallery_name|$category_name|$page\n";
			}
		}
	}


	/**
	 * (non-PHPdoc)
	 * @see CategoryTreeLogger::complete()
	 */
	public function complete() {
		file_put_contents_ensure("$this->output_path/$this->date.log", $this->entries, FILE_APPEND);
	}
}