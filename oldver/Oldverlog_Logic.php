<?php
/**
 * 
 * @author magog
 *
 */
class Oldverlog_Logic {

	const RELATIVE_LOG_FILENAME = "../uploadlog";
	
	/**
	 * 
	 * @var string
	 */
	private $from;
	
	/**
	 * 
	 * @var string
	 */
	private $to;
	
	/**
	 * 
	 * @var string
	 */
	private $start_date;

	/**
	 *
	 * @var string
	 */
	private $end_date;
	
	/**
	 * 
	 * @var Oldver_Log_Entry[]
	 */
	private $entries;
	
	/**
	 * 
	 * @var Project_Data[]
	 */
	private $project_datas;
	
	/**
	 * 
	 * @var int
	 */
	private $count;
	
	/**
	 * 
	 * @param string|string[] $name
	 * @return string|string[]
	 */
	private function name_without_namespace($name) {
		return preg_replace("/^.+?\:(.+)$/", "$1", $name);
	}
	
	/**
	 * @return void
	 */
	public function run() {
		global $env;
		
		$POST = $env->get_request_args();
		
		$uploadlog = file_get_contents_ensure(self::get_log_file_name());
		$lines = preg_split("/\r?\n/", $uploadlog);
		$this->count = count($lines);
		
		$first_entry = null;
		for($i = 0; $first_entry === null && $i < $this->count; $i++) {
			$first_entry = $this->parse_line($lines[$i]);
		}
		
		$last_entry = null;
		for($i = $this->count - 1; $last_entry === null && $i > 0; $i--) {
			$last_entry = $this->parse_line($lines[$i]);
		}
		
		$this->start_date = substr($first_entry->datetime, 0, 10);
		$this->end_date = substr($last_entry->datetime, 0, 10);
		
		
		$lines = array_reverse($lines);
		
		$this->project_datas = [
			'commons.wikimedia' => new Project_Data("commons.wikimedia", null, false)];
		
		
		$this->from = $this->parse_date(@$POST["from"]);
		$this->to = $this->parse_date(@$POST["to"]);
		$this->entries = array_map_filter($lines, [$this, "parse_line"]);

		if ($this->from === null && $this->to === null) {
			$this->entries = array_splice($this->entries, 0, 200);
		}
		
		$this->count -= count($lines) - count($this->entries);

		if ($this->from === null && $this->entries) {
			$this->from = end($this->entries)->datetime;
		}
		
		if ($this->end === null) {
			$this->to = $this->end_date;
		}
	}
	
	/**
	 * 
	 * @param string $date
	 * @return null|string
	 */
	private function parse_date($date) {
		return preg_match("/^(\d{4}\-\d{2}\-\d{2})$/", $date) ? $date : null;
	}
	
	private function compare_date($first, $second) {		
		$first_year = substr($first, 0, 4);
		$second_year = substr($second, 0, 4);
		if ($first_year < $second_year) {
			return -1;
		}		
		if ($first_year > $second_year) {
			return 1;
		}

		$first_month = substr($first, 5, 2);
		$second_month = substr($second, 5, 2);
		if ($first_month < $second_month) {
			return -1;
		}		
		if ($first_month > $second_month) {
			return 1;
		}

		$first_day = substr($first, 8, 2);
		$second_day = substr($second, 8, 2);
		if ($first_day < $second_day) {
			return -1;
		}		
		if ($first_day > $second_day) {
			return 1;
		}
		
		return 0;
	}
	
	/**
	 * 
	 * @param string $line
	 * @return void|Oldver_Log_Entry
	 */
	public function parse_line($line) {
		global $logger;
		
		$matched = preg_match(
			"/^\|\*(\d{4}\-\d{2}\-\d{2} \d{2}\:\d{2}\:\d{2}) " .
			"([a-z\-]+\.w[a-z\-]+)\/([^\|]+)\|([^\|]+)\|([^\|]+)$/", $line, $matches);
		if (!$matched) {
			$logger->warn("Unrecognized line: $line");
			return;
		}
		
		$entry = new Oldver_Log_Entry();
		$entry->datetime = $matches[1];
		if ($this->from && $this->compare_date($entry->datetime, $this->from) === -1) {
			return;
		}
		if ($this->to && $this->compare_date($entry->datetime, $this->to) === 1) {
			return;
		}
		
		$entry->user = ucfirst_utf8($matches[5]);
		$entry->project = $matches[2];
		$entry->source = $matches[3];
		$entry->dest = $matches[4];
		$entry->same_name = $this->name_without_namespace($entry->source) ===
		$this->name_without_namespace($entry->dest);
		
		if (!isset($this->project_datas[$entry->project])) {
			$this->project_datas[$entry->project] = new Project_Data($entry->project, null,
				false);
		}
		
		return $entry;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_from() {
		return $this->from;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_to() {
		return $this->to;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_start_date() {
		return $this->start_date;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_end_date() {
		return $this->end_date;
	}
	
	/**
	 *
	 * @return Oldver_Log_Entry[]
	 */
	public function get_entries() {
		return $this->entries;
	}
	
	/**
	 *
	 * @return Project_Data[]
	 */
	public function get_project_datas() {
		return $this->project_datas;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function get_count() {
		return $this->count;
	}
	
	public static function get_log_file_name() {
		return __DIR__ . "/" . self::RELATIVE_LOG_FILENAME;
	}
}