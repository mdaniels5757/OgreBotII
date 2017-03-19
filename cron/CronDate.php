<?php
class CronDate {
	
	/**
	 *
	 * @var number
	 */
	private $year;
	
	/**
	 *
	 * @var number
	 */
	private $month;
	
	/**
	 *
	 * @var number
	 */
	private $day;
	
	/**
	 *
	 * @var number
	 */
	private $hour;
	
	/**
	 *
	 * @param number $year        	
	 * @param number $month        	
	 * @param number $day        	
	 * @param number $hour        	
	 */
	function __construct($year, $month, $day, $hour) {
		$this->setYear($year);
		$this->setMonth($month);
		$this->setDay($day);
		$this->setHour($hour);
	}
	
	/**
	 *
	 * @return number
	 */
	function getYear() {
		return $this->year;
	}
	
	/**
	 *
	 * @param number $year        	
	 * @return void
	 */
	function setYear($year) {
		$this->year = $year;
	}
	
	/**
	 *
	 * @return number
	 */
	function getMonth() {
		return $this->month;
	}
	
	/**
	 *
	 * @param number $year        	
	 * @return void
	 */
	function setMonth($month) {
		$this->month = $month;
	}
	
	/**
	 *
	 * @return number
	 */
	function getDay() {
		return $this->day;
	}
	/**
	 *
	 * @param number $year        	
	 * @return void
	 */
	function setDay($day) {
		$this->day = $day;
	}
	
	/**
	 *
	 * @return number
	 */
	function getHour() {
		return $this->hour;
	}
	/**
	 *
	 * @param number $year        	
	 * @return void
	 */
	function setHour($hour) {
		$this->hour = $hour;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString() {
		return "$this->year-$this->month-$this->day-$this->hour";
	}
}