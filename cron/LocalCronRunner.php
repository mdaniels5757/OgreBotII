<?php

class LocalCronRunner extends CronRunner {

	public function setup() {
		
	}
	
	public function getExecCommand() {
		$directory = $this->runConfig->getDirectory();
		$command   = $this->runConfig->getCommand();
		$args      = $this->runConfig->getArgs();
		$timeout   = $this->runConfig->getTimeout();
		$vm		   = $this->runConfig->getHhvm() ? "hhvm" : "php";

		return "cd ".BASE_DIRECTORY."/$directory; timeout $timeout $vm $command.php $args >> ".LOG_DIRECTORY."/${command}_errors.log";
	}
}