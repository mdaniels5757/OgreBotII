<?php

interface LogFormatter {

	/**
	 *
	 * @param string $level
	 * @param string $file
	 * @param int $line
	 * @return string
	 */
	function doFormat($level, $file, $line, $message);

}
