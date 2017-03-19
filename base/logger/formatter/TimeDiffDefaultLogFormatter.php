<?php
class TimeDiffDefaultLogFormatter extends DefaultLogFormatter {

	private $previousTimeSeconds;
	private $previousTimeMicros;
	private static $microseconds = "μs";

	private function __construct() {
	}

	/**
	 * @return DefaultLogFormatter
	 */
	public static function construct() {
		self::formatMicroTime($seconds, $micros);

		if ($seconds !== null && $micros !== null) {
			return new self();
		}

		ogrebotMail("Can't parse time! Did microtime() change?");

		return new parent();
	}

	/**
	 *
	 * @param int $seconds
	 * @param uint $micros
	 */
	private static function formatMicroTime(&$seconds, &$micros) {
		$microtime = microtime();

		preg_match("/^(\d\.\d+) (\d+)$/", $microtime, $matches);
		$micros  = @$matches[1];
		$seconds = @$matches[2];
	}

	protected function getTime() {
		self::formatMicroTime($this->previousTimeSeconds, $this->previousTimeMicros);

		return $this->previousTimeSeconds;
	}

	protected function formatTime() {
		$previousSeconds = $this->previousTimeSeconds;
		$previousMicros  = $this->previousTimeMicros;

		$formattedTime = parent::formatTime();

		if ($previousSeconds) {
			$diff = $this->previousTimeSeconds - $previousSeconds +
			$this->previousTimeMicros  - $previousMicros;
			$diff = number_format($diff, 6);
			$formattedTime = "$formattedTime ($diff ".self::$microseconds.")";
		}

		return $formattedTime;
	}
}