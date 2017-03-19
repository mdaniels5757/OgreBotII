<?php


class JS_Refactor extends Refactor {

	protected function preProcess($input_string, $command_line_args) {
		$contents = replace_until_no_changes("^( *)\t", "$1    ", $input_string);
		$contents = replace_until_no_changes("function\s*\(", "function (", $contents);
		$contents = replace_until_no_changes("[\t ]+$", "", $contents);

		return $contents;
	}

	protected function get_extension() {
		return "js";
	}
}