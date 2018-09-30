<?php

/**
 * 
 * @author magog
 *
 */
class Command_Line_Cron_Date_Factory extends Cron_Date_Factory {
	

	/**
	 * (non-PHPdoc)
	 * @see Cron_Date_Factory::get_range()
	 */
	protected function get_range() {
		global $env;
		
		$post = $env->load_command_line_args();
		$command_line_range = find_command_line_arg($post, "interval");
		
		if ($command_line_range === null) {
			return null;
		}
		
		preg_match("/^(\d{10})\-(\d{10})$/", $command_line_range, $match);
		
		if (!$match) {
			throw new IllegalArgumentException("Unrecognized range format. Correct format: \d{10}\-\d{10}");
		}
		
		$start = strtotime("$match[1]0000");
		$end = strtotime("$match[2]0000");
		
		if ($start === false || $end === false) {
			throw new IllegalArgumentException("Unrecognized range format. Correct format: \d{10}\-\d{10}");
		}
		
		return [$start, $end];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Cron_Date_Factory::finalize()
	 */
	public function finalize() {
		//do nothing
	}

}