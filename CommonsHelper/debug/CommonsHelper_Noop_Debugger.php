<?php

/**
 *
* @author magog
*
*/
class CommonsHelper_Noop_Debugger implements CommonsHelper_Debugger {

	
	/**
	 *
	 * @param string $type
	 */
	public function __construct($type) {
		//empty
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see CommonsHelper_Debugger::write()
	 */
	public function write($step, $text) {
		//empty
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see CommonsHelper_Debugger::commit()
	 */
	public function commit() {
		//empty
	}
}