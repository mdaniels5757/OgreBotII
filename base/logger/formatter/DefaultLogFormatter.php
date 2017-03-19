<?php
class DefaultLogFormatter implements LogFormatter {

	/**
	 * @return int
	 */
	protected function getTime() {
		return time();
	}

	/**
	 * @return string
	 */
	protected function formatTime() {
		$time = $this->getTime();
		return date('Ymd H:i:s', time());
	}

	public function doFormat($level, $file, $line, $message) {
		$time = $this->formatTime();
		return "$level $time $file:$line: $message\n";
	}
}
