<?php
abstract class Unparsed_Element_Type {

	/**
	 *
	 * @abstract
	 * @param string $text
	 * @param number $start
	 * @return number|false returns the starting tag, or false if not found
	 */
	public abstract function find_next(&$text, $start);

	/**
	 *
	 * @abstract
	 * @param string $text
	 * @param number $start
	 * @return number|false returns the end of the closing tag, or false if not found
	*/
	public abstract function find_close(&$text, $start);

	/**
	 *
	 * @abstract
	 * @return string an all capital alpha string to serve as a marker to cleanup which
	 *         type of change has been made.
	*/
	public abstract function get_replace_string();

	/**
	 * Modify the text before storing it (e.g., <nowiki> multiple spacing is treated as a single space).
	 * To be overridden as needed.
	 *
	 * @param string $text
	 * @return void
	*/
	public function modify_text(&$text) {
	}
}