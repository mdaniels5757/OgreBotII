<?php
/**
 * 
 * @author magog
 *
 */
class Cleanup_Mass_Tester {
	
	/**
	 * 
	 * @var int
	 */
	const CLEAN = 0;
	
	/**
	 * 
	 * @var int
	 */
	const NO_REFRESH_TMP = 1;
	
	/**
	 * 
	 * @var int
	 */
	const REFRESH_TEMP = 2;
	
	/**
	 *
	 * @var string
	 */
	const TMP_FILE = "mass-tester";
	
	/**
	 * 
	 * @var bool
	 */
	private $changes;
	
	
	/**
	 * 
	 * @var int
	 */
	private $clean;
	
	/**
	 *
	 * @var string
	 */
	private $tmp_dir;
	
	/**
	 *
	 * @var string
	 */
	private $filename;
	
	/**
	 *
	 * @param string $filename        	
	 * @param int $clean        	
	 */
	public function __construct($filename, $clean = self::REFRESH_TEMP) {
		global $logger;
		
		$this->filename = $filename;
		$this->tmp_dir = TMP_DIRECTORY . DIRECTORY_SEPARATOR . self::TMP_FILE;
		
		if ($clean !== self::NO_REFRESH_TMP) {
			$logger->info("Cleaning tmp directory");
			$this->clean_dir();
			
			if ($clean === self::CLEAN) {
				$gz = "$filename.tar.gz";
				if (file_exists($gz)) {
					$logger->info("Extracting tarball to tmp directory.");
					$phar_data = new PharData($gz);
					$phar_data->extractTo($this->tmp_dir);
				}
			}
			
			$logger->info("Extraction done.");
		}
	}
	
	/**
	 *
	 * @param int[] $dates        	
	 * @param int $count        	
	 * @return void
	 * @throws IllegalStateException
	 */
	public function download(array $dates, $count) {
		global $logger, $wiki_interface;
		
		$this->changes = true;
		$co = (new ProjectData("commons.wikimedia"))->getWiki();
		
		$all_uploads = array_merge_all(
			array_map(
				function ($date) use($co, $logger, $wiki_interface) {
					$logger->debug("Downloading file names for $date");
					
					return array_keys(
						$wiki_interface->get_recent_uploads_single_array($co, "${date}000000", 
							"${date}235959"));
				}, $dates));
		
		if ($count < count($all_uploads)) {
			shuffle($all_uploads);
			$all_uploads = array_slice($all_uploads, 0, $count);
		}
		
		$all_content = $wiki_interface->get_text($co, $all_uploads);
		
		array_walk($all_content, 
			function (Page_Text_Response $text_response, $filename) {
				if ($text_response->exists) {
					file_put_contents_ensure("$this->tmp_dir/" . sha1($filename) . ".in", 
						"$filename\n$text_response->text");
					// $this->archive->addFromString(substr($filename, 5) . ".in",
					// $text_response->text);
				}
			});
	}
	
	/**
	 *
	 * @return void
	 * @throws IllegalStateException
	 */
	public function create_base() {
		$this->changes = true;
		$ar = array_values(
			array_filter(glob("$this->tmp_dir/*"), 
				function ($file) {
					return substr($file, -3) === ".in";
				}));
		$num_files = count($ar);
		$cleanup = new Cleanup_Base();
		$time = date('Ymd235959', time() - SECONDS_PER_DAY);
		
		array_walk($ar, 
			function ($file, $i) use($num_files, $cleanup, $time) {
				global $logger;
				
				if ($i % 100 === 0) {
					$logger->info("Check " . ($i + 1) . " of $num_files");
				}
				
				list($filename, $contents) = preg_split("/\r?\n/", file_get_contents_ensure($file), 2);
				$response = $cleanup->super_cleanup($contents, $time, true);
				
				$out_string = json_encode($response->get_warnings()) . "\n" .
					 json_encode($response->get_duplicate_authors()) . "\n" . $response->get_text();
				
				$new_file_name = substr($file, 0, -3) . ".out";
				file_put_contents_ensure($new_file_name, $out_string);
			});
	}
	
	/**
	 * @return void
	 */
	public function test() {
		$ar = array_values(
			array_filter(glob("$this->tmp_dir/*"), 
				function ($file) {
					return substr($file, -3) === ".in";
				}));
		$num_files = count($ar);
		$cleanup = new Cleanup_Base();
		$time = date('Ymd235959', time() - SECONDS_PER_DAY);
		array_walk($ar, 
			function ($file, $i) use($num_files, $cleanup, $time) {
				global $logger;
				
				if ($i % 100 === 0) {
					$logger->info("Check " . ($i + 1) . " of $num_files");
				}
				
				list($filename, $in) = preg_split("/\r?\n/", file_get_contents_ensure($file), 2);
				$out_expected = file_get_contents_ensure(substr($file, 0, -3) . ".out");
				
				$logger->pushLevel(Level::WARN);
				$response = $cleanup->super_cleanup($in, $time, true);
				$logger->popLevel();
				
				$out = json_encode($response->get_warnings()) . "\n" .
					 json_encode($response->get_duplicate_authors()) . "\n" . $response->get_text();
				
				if ($out_expected !== $out) {
					$this->echo_fail($filename, $out_expected, $out);
				}
			});
	}
	
	/**
	 * @return void
	 */
	public function save() {
		global $logger;
		
		// archive
		if ($this->changes) {
			$tar = "$this->filename-tmp.tar";
			$gz = "$tar.gz";
			
			if (file_exists($gz)) {
				$logger->trace("Deleting gz: $gz");
				unlink($gz);
			}
			
			$phar_data = new PharData($tar);
			$phar_data->buildFromDirectory($this->tmp_dir);
			$phar_data->compress(Phar::GZ);
			unlink($tar);
			rename($gz, "$this->filename.tar.gz");
		}
	}
	
	/**
	 * @return void
	 */
	public function clean_dir() {
		if (!file_exists($this->tmp_dir)) {
			mkdir($this->tmp_dir);
		} else {
			$files = glob("$this->tmp_dir/*");
			array_walk($files, function ($file) {
				unlink($file);
			});
		}
	}
	
	/**
	 * 
	 * @param string $filename
	 * @param string $expected
	 * @param string $actual
	 * @return void
	 */
	private function echo_fail($filename, $expected, $actual) {
		global $logger;
		
		
		$sha = sha1($filename);
		$out_expected = TMP_DIRECTORY_SLASH. "expected.tmp";
		$out_actual =  TMP_DIRECTORY_SLASH. "actual.tmp";
		try {
			$pass_fail = "FAIL on $filename ($sha). Diff below:";
			file_put_contents_ensure($out_expected, $expected);
			file_put_contents_ensure($out_actual, $actual);
			if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
				exec("fc \"$out_expected\" \"$out_actual\"", $output, $returned);
			} else {
				exec("diff \"$out_expected\" \"$out_actual\" -y --suppress-common-lines", $output, $returned);
			}
			
			$pass_fail .= "\n  " . join("\n", $output);
			$logger->info($pass_fail);
		} finally {
			unlink($out_expected);
			unlink($out_actual);
		}
	}
}