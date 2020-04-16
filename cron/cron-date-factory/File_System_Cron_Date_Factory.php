<?php

/**
 *
 * @author magog
 *
 */
class File_System_Cron_Date_Factory extends Cron_Date_Factory {

	/**
	 *
	 * @var string
	 */
	const PROCESS_FILE_DIRECTORY = BASE_DIRECTORY;

	/**
	 *
	 * @var string
	 */
	const PROCESS_FILE_NAME = "process.txt";

	/**
	 *
	 * @var int Unix timestamp
	 */
	private $now;


	/**
	 *
	 * @return string
	 */
	private static function get_process_file_name() {
		return self::PROCESS_FILE_DIRECTORY . "/" . self::PROCESS_FILE_NAME;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Cron_Date_Factory::get_range()
	 */
	protected function get_range() {
		global $logger;

		// get next run time.
		$processRunFileName = self::get_process_file_name();
		$interval = $this->get_interval();
        
		$this->now = time();
		if (!filesize($processRunFileName)) {
			$logger->warn("$processRunFileName doesn't exist. Attempting to create it.");
			$logger->debug("Previous time emulated");
			$previousTime = $this->now - $interval * SECONDS_PER_HOUR - 1;
		} else {
			$processRunFileContents = file_get_contents($processRunFileName);
			$logger->debug("Process file content: $processRunFileContents");
			$previousTime = intval($processRunFileContents);

			$logger->debug(
				"Previous run time: $previousTime: " . date('Y-m-d H:i:s', $previousTime));

			if ($previousTime <= 0) {
				throw new IllegalStateException("Illegal previous run time: $processRunFileContents");
			}
		}

		return [$previousTime + $this->get_interval() * SECONDS_PER_HOUR, $this->now];
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Cron_Date_Factory::finalize()
	 */
	public function finalize() {
		// all done parsing; update process file.
		file_put_contents_ensure(self::get_process_file_name(), $this->now);
	}
}
