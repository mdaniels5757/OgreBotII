<?php

/**
 *
* @author magog
*
*/
interface CommonsHelper_Debugger {

	/**
	 *
	 * @param int $step
	 * @param string $text
	 */
	public function write($step, $text);

	public function commit();
}