<?php
class CSS_Refactor extends Refactor {

	protected function preProcess($input_string, $command_line_args) {
		$contents = preg_replace("/[\t ]+$/m", "", $input_string);
		$contents = preg_replace("/\}\s+([\.\#a-zA-Z])/", "}\n\n$1", $contents);
		//$contents = preg_replace("/^(\s*[a-z][a-z\-]*)\s*\:\s*/m", "$1: ", $contents);
		return $contents;
	}

	protected function get_extension() {
		return "css";
	}

}