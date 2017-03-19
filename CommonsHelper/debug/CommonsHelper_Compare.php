<?php

/**
 * 
 * @author magog
 *
 */
class CommonsHelper_Compare {
	
	/**
	 * 
	 * @param string $first
	 * @param string $second
	 * @return void
	 */
	public static function compare($first, $second) {
		global $logger;
		
		ini_set('xdebug.var_display_max_data', -1);
		
		$dfirst = unserialize(
			file_get_contents_ensure(LOG_DIRECTORY . DIRECTORY_SEPARATOR . "chdebug.$first.log"));
		$dsecond = unserialize(
			file_get_contents_ensure(LOG_DIRECTORY . DIRECTORY_SEPARATOR . "chdebug.$second.log"));
		
		foreach ($dfirst as $key => $string1) {
			$string2 = @$dsecond[$key];
			unset($dsecond[$key]);
			
			$string1 = self::modify($string1);
			$string2 = self::modify($string2);
			if ($string1 !== $string2) {
				$logger->error("$first !== $second at key $key");
				self::find_diff($string1, $string2);
				return;
			}
		}
		
		foreach ($dsecond as $key => $string2) {
			if ($string1 !== $string2) {
				$logger->error("$first !== $second at key $key");
				self::find_diff("", $string2);
				return;
			}
		}
		$logger->info("Passed.");
	}
	
	private static function find_diff($string1, $string2) {
		global $logger;
		
		$string1_array = explode("\n", $string1);
		$string2_array = explode("\n", $string2);
		foreach ([&$string1_array, &$string2_array] as $a => &$string_array) {
			foreach ($string_array as $i => $line) {
				$logger->warn(str_pad(($a + 1) . " $i: ", 9) . $line);	
			}
		}
		for ($i = 0; $i < count($string1_array); $i++) {
			if ($string1_array[$i] !== $string2_array[$i]) {
				break;				
			}
		}

		$logger->error("First diff found at line $i\n$string1_array[$i]\n$string2_array[$i]");;
	}
	
	private static function modify($string) {
		$string = str_replace("\t", "    ", $string);
		$string = preg_replace("/(\=\= \{\{Original upload log\}\} \=\=
)(\{\{original description)(?: page)?(\|[\-\,\%\+\.\|\w]+\}\}
)(\{\|)/", "$1    $2$3    $4", $string);
		$string = preg_replace("/ +$/m", "", $string);
		return $string;
		
	}
}