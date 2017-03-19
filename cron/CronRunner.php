<?php

abstract class CronRunner {

	/**
	 * 
	 * @var CronDate
	 */
	protected $cronDate;
	
	/**
	 * 
	 * @var CronConfig
	 */
	protected $runConfig;
	
	/**
	 * 
	 * @var bool
	 */
	private $hasRun;

	public function __construct()  {
		$this->hasRun = false;
	}

	/**
	 * 
	 * @return CronDate
	 */
	public function getCronDate() {
		return $this->cronDate;
	}
	
	/**
	 * 
	 * @return CronConfig
	 */
	public function getRunConfig() {
		return $this->runConfig;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function getHasRun(){
		return $this->hasRun;
	}
	
	/**
	 * 
	 * @param CronDate $cronDate
	 * @return void
	 */
	public function setCronDate(CronDate $cronDate) {
		global $logger;
				
		$this->cronDate = $cronDate;
		$logger->trace($cronDate);
	}
	
	/**
	 * 
	 * @param CronConfig $runConfig
	 * @return void
	 */
	public function setRunConfig(CronConfig $runConfig) {
		global $logger;

		$this->runConfig = $runConfig;
		$logger->trace($runConfig);
	}

	/**
	 * 
	 * @throws IllegalStateException
	 * @return void
	 */
	public function run() {
		global $logger;
		
		$logger->debug($this);
		
		if ($this->hasRun) {
			throw new IllegalStateException("Already ran this cron job once.");
		}
		
		if ($this->cronDate === null) {
			throw new IllegalStateException("CronDate not set.");			
		}
		
		if ($this->runConfig === null) {
			throw new IllegalStateException("RunConfig not set.");
		}
		
		$this->setUpVariables();
		$this->setup();
		
		$command = $this->getExecCommand();
		
		ogrebotExec($command);
		
		$post = $this->runConfig->getPost();
		
		if ($post !== null) {
			ogrebotExec($post);
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString() {
		$cronDateString = $this->cronDate === null? "[date not set]" : $this->cronDate->__toString();
		$configString = $this->runConfig === null? "[config not set]" : $this->runConfig->__toString();
		
		return "$cronDateString $configString";
	}

	/**
	 * @return void
	 */
	private function setUpVariables() {
		global $logger;
		
		$oldArgs = $this->runConfig->getArgs();
		$logger->trace("Old Args: $oldArgs");
		
		$newArgs = replace_named_variables($this->runConfig->getArgs(),
						array("year"=>$this->cronDate->getYear(),
								"month"=>$this->cronDate->getMonth(),
								"day"=>$this->cronDate->getDay(),
								"hour"=>$this->cronDate->getHour()));
		
		$logger->trace("New Args: $newArgs");
		
		$this->runConfig->setArgs(mb_trim($newArgs));
		$this->runConfig->setDirectory(mb_trim($this->runConfig->getDirectory()));
		$this->runConfig->setCommand(mb_trim($this->runConfig->getCommand()));
	}
	
	/**
	 * @return void
	 */
	protected abstract function setup();
	
	/**
	 * @return string
	 */
	protected abstract function getExecCommand();

}