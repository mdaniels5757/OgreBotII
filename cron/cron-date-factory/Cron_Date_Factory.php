<?php

/**
 * @abstract
 * @author magog
 *
 */
abstract class Cron_Date_Factory {
	
	/**
	 * @abstract
	 * @return int[]|null A two-indexed array with the Unix timestamps
	 *   representing the start and end time, respectively. May return
	 *   null if not applicable.
	 */
	protected abstract function get_range();
	
	/**
	 * @abstract
	 * @return void
	 */
	public abstract function finalize();
	
	
	/**
	 * 
	 * @throws IllegalStateException
	 * @return int
	 */
	protected function get_interval() {
		global $environment;
		
		$processList = XmlParser::xmlFileToStruct("processes-$environment[environment].xml");
		$intervalString = array_key_or_exception($processList, 
			['CONFIGS', 0, 'elements', 'INTERVAL', 0, 'value']);
		$interval = intval($intervalString);
		if ($interval <= 0) {
			throw new IllegalStateException("Illegal interval value: $intervalString");
		}
		if (24 % $interval !== 0) {
			throw new IllegalStateException(
				"Illegal interval value: $interval. Must be divisible by 24.");
		}
		
		return $interval;
	}
	
	/**
	 *
	 * @return Cron_Date_Factory
	 */
	public static final function get_handler() {
		$runner = new Command_Line_Cron_Date_Factory();
		
		if ($runner->get_range()) {
			return $runner;
		}
		
		return new File_System_Cron_Date_Factory();
	}
	
	/**
	 *
	 * @return CronDate[]
	 */
	public final function get_dates() {
		global $logger;
	
		$localInterval = $this->get_interval() * SECONDS_PER_HOUR;
		list($start, $end) = $this->get_range();
		
		$logger->trace("Range returned by cron: $start - $end");
		
		$startTruncated = strtotime(date('Y-m-d H:00:00', $start));
		$endTruncated = strtotime(date('Y-m-d H:00:00', $end));
		
		//not sure how leap seconds will work; proactively programming for it below
		$interval_offset = ($endTruncated - $startTruncated) % $localInterval; 
		if ($interval_offset === $localInterval - 1) {
			$endTruncated++;
		} else if ($interval_offset !== 0 && $interval_offset !== 1) {
			throw new IllegalStateException("Can't truncate dates! \$start = $start, \$end = $end");
		}
		$logger->debug("Start truncated: $startTruncated: ".date('Y-m-d H:i:s', $startTruncated));
		$logger->debug("End truncated: $endTruncated: ".date('Y-m-d H:i:s', $endTruncated));
	
		if ($startTruncated <= $endTruncated) {
			$times_in_range = range($startTruncated, $endTruncated, $localInterval);		
		} else {
			$times_in_range = [];				
		}
		
	
		$logger->trace("Times in range: " . print_r($times_in_range, true));
		
		if ($times_in_range) {
			$times = array_map(
				function ($time) {
					return new CronDate(date("Y", $time), date("m", $time), date("d", $time), 
						date("H", $time));
				}, $times_in_range);
			
			$times = array_unique($times); // just in case (UTC time is off?)
		} else {
			$times = [];
		}
	
		return $times;
	}
}