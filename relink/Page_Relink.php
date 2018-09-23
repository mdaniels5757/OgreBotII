<?php

class Page_Relink extends Relink {
	
	/**
	 * 
	 * @var string[][]
	 */
	private $pages;
	
	protected function post_delink(string $from, bool $same) {
		//do nothing
	}
	
	protected function get_files_same_name(): array {
		//only delinking different name
		return array();
	}
	
	protected function get_files_different_name(): array {
		return $this->pages;
	}
	
	/**
	 * 
	 * @param string[][] $pages
	 * @return void
	 */	
	public function set_pages(array $pages) {
		$this->pages = $pages;
	}
	
	/**
	 * 
	 * @param string[] $lines
	 * @param bool $ignore_first_parameter DEFAULT true
	 * @return void
	 */
	public function set_pages_from_unparsed_command_line(array $lines, bool $ignore_first_parameter = true) {
		if ($ignore_first_parameter) {
			array_shift($lines);
		}
		
		$this->pages = map_array_function_keys($lines, function($arg) {
			list($from, $to) = explode("|", $arg);
				
			return [$this->decode_pagename($from), $this->decode_pagename($to)];
		});
	}
	
	private function decode_pagename(string $page): string {
		return urldecode($page);
	}
}