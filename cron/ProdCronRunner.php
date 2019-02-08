<?php

class ProdCronRunner extends CronRunner {

	public function setup() {
		
	}
	
	public function getExecCommand() {
		$directory = $this->runConfig->getDirectory();
		$command   = $this->runConfig->getCommand();
		$args      = $this->runConfig->getArgs();
		$timeout   = $this->runConfig->getTimeout();
		$vm		   = "php"; //$this->runConfig->getHhvm() ? "hhvm" : "php";

		return "timeout $timeout $vm ".BASE_DIRECTORY."/$directory/$command.php $args >> ".LOG_DIRECTORY."/${command}_errors.log";
	}
}