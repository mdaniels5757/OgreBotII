<?php
require_once __DIR__ . "/../base/bootstrap.php";

function get_new_runner() {
	global $environment;
	
	switch ($environment['environment']) {
		case 'local':
			return new LocalCronRunner();
		case 'prod':
			return new ProdCronRunner();
		default:
			throw new InvalidArgumentException("Can't determine environment! $environment[environment]");
	}
}

function cron_run() {
	global $logger, $environment;
	
	$logger->debug("cron_run");
	
	if ($logger->isDebugEnabled()){
		$logger->debug(php_uname());
		$logger->debug(phpversion());
	}

	$cron_date_factory = Cron_Date_Factory::get_handler(); 
	
	$times = $cron_date_factory->get_dates();
	
	$logger->debug("Times:");
	$logger->debug($times);

	//load cron properties
	$processList = XmlParser::xmlFileToStruct("processes-$environment[environment].xml");
	$configs = array_key_or_exception($processList, array('CONFIGS', 0, 'elements', 'CONFIG'));
	
	$runners = array();
	foreach ($times as $time) {
		foreach($configs as $config) {
			$command = "";
			
			$hour = intval(array_key_or_exception($config, array('elements', 'HOUR', 0, 'value')));
			
			if ($hour === intval($time->getHour())) {
				$directory = array_key_or_null($config, 'elements', 'DIRECTORY', 0, 'value');
				$command   = array_key_or_exception($config, 'elements', 'COMMAND', 0, 'value');
				$args  = array_key_or_blank($config, 'elements', 'ARGS', 0, 'value');
				$timeout   = intval(array_key_or_exception($config, 'elements', 'TIMEOUT', 0, 'value'))*60;				
				$runOnce = deep_array_key_exists($config, 'elements', 'RUNONCE');
				$post = array_key_or_null($config, 'elements', 'POST', 0, 'value');
				$hhvm = deep_array_key_exists($config, 'elements', 'HHVM');
				
				
				$cronConfig = new CronConfig();
				$cronConfig->setDirectory($directory);
				$cronConfig->setCommand($command);
				$cronConfig->setArgs($args);
				$cronConfig->setTimeout($timeout);
				$cronConfig->setPost($post);
				$cronConfig->setHhvm($hhvm);
				
				
				//check for previous runs
				$add = true;
				if ($runOnce) {
					foreach ($runners as $runner) {
						if ($runner->getRunConfig() == $cronConfig) {
							$logger->debug("Found duplicate instance of run config: $command. Not running.");
							$add = false;
							break;
						}
					}
				}
				
				if ($add) {
					$runner = get_new_runner();
					$runner->setCronDate($time);
					$runner->setRunConfig($cronConfig);
					
					$logger->debug("Config match for ".$runner);
										
					$runners[] = $runner;
				}				
			}
		}
	}
	
	$cron_date_factory->finalize();
	
	$logger->debug(count($runners)." commands to be run.");
	foreach ($runners as $i => $runner) {		
		$logger->info("**** Starting runner $i ****");
		$runner->run();	
		$logger->info("**** End runner $i ****");
	}
	$logger->debug("Complete.");
}

try {
	cron_run();
} catch (Exception $e) {
	ogrebotMail($e);
}
