<?php

/**
 *
* @author magog
*
*/
class CommonsHelper_Debugger_Impl implements CommonsHelper_Debugger {

	/**
	 *
	 * @var string
	 */
	private $file;

	/**
	 *
	 * @var string[]
	 */
	private $data = [];

	/**
	 *
	 * @param string $type
	 */
	public function __construct($type) {
		$this->file = LOG_DIRECTORY . DIRECTORY_SEPARATOR . "chdebug.$type.log";
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see CommonsHelper_Debugger::write()
	 */
	public function write($step, $text) {
		if (isset($this->data[$step])) {
			throw new Exception("Step $step already set!");
		}
		$this->data[$step] = $text;
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see CommonsHelper_Debugger::commit()
	 */
	public function commit() {
		file_put_contents($this->file, serialize($this->data));
	}
}