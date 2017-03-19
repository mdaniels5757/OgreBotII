<?php
class Logger {
	
	/**
	 *
	 * @var LogFormatter
	 */
	private $logFormatter;
	
	/**
	 * 
	 * @var int[]
	 */
	private $level_stack = [];
	
	/**
	 *
	 * @var int
	 */
	private $debug_level = null;
	
	/**
	 *
	 * @var resource[]
	 */
	private $out = null;
	private function __construct() {
		$this->logFormatter = new DefaultLogFormatter();
	}
	
	/**
	 *
	 * @return Logger
	 */
	public static function construct_from_variables($debug_level, $out) {
		$logger = new Logger();
		$logger->setOut($out);
		$logger->setDebugLevel($debug_level);
		
		return $logger;
	}
	
	/**
	 *
	 * @return Logger
	 */
	public static function construct_from_file($filename, $properties_directory = true) {
		$logger = new Logger();
		
		// read XML file
		$errors = array();
		try {
			$data = XmlParser::xmlFileToStruct($filename, $properties_directory);
		} catch (CantOpenFileException $e) {
			$errors[] = $e->getMessage();
		} catch (XMLParserException $e) {
			$errors[] = $e->getMessage();
		}
		
		$outStreams = array();
		$defaultLevel = null;
		$thisLevel = null;
		
		// verify structure of XML file
		if ($data !== null) {
			if (count(array_key_or_empty($data, 'CONFIGS')) != 1) {
				$errors[] = "Can't find configs element, or multiple configs elements";
			} else {
				if (defined("LOGGER_NAME")) {
					$thisScript = LOGGER_NAME;
				} else {
					$thisScript = preg_replace("/(?:[\S\s]*\\" . DIRECTORY_SEPARATOR . ")?(.+?)\.php$/", "$1",
						$_SERVER['PHP_SELF']);
				}
				
				// parse XML file
				$configs = array_key_or_empty($data, 'CONFIGS', 0, 'elements', 'CONFIG');
				
				foreach ($configs as $config) {
					$scriptName = array_key_or_null($config, 'attributes', 'SCRIPT');
					
					// default
					$parse = false;
					if ($scriptName === '*') {
						$parse = true;
						$defaultLevel = array_key_or_null($config, 'elements', 'LEVEL', 0, 'value');
					} else if ($scriptName === $thisScript) {
						$parse = true;
						$thisLevel = array_key_or_null($config, 'elements', 'LEVEL', 0, 'value');
					}
					
					if ($parse) {
						$outElements = array_key_or_empty($config, 'elements', 'OUT');
						foreach ($outElements as $outElement) {
							$nextStream = @$outElement['value'];
							if ($nextStream !== null) {
								$outStreams[] = $nextStream;
							}
						}
					}
				}
			}
		}
		
		// read OUT variable
		$logger->out = [];
		if (count($outStreams) > 0) {
			foreach ($outStreams as $outStream) {
				$nextStream = Logger::outStringToStream($outStream);
				if ($nextStream === null) {
					$errors[] = "Cannot open file output stream for $outStream";
				} else {
					$logger->out[] = $nextStream;
				}
			}
		} else {
			$errors[] = "OUT not specified; defaulting to standard out.";
			$logger->out = [fopen('php://stdout', 'w')];
		}
		
		// read LEVEL variable
		if ($thisLevel !== null) {
			$level = $thisLevel;
		} else if ($defaultLevel != null) {
			$level = $defaultLevel;
		} else {
			$errors[] = "LEVEL not specified; defaulting to DEBUG.";
			$level = "DEBUG";
		}
		$logger->debug_level = Level::level_to_int($level);
		
		// errors and confirmation to logger
		foreach ($errors as $error) {
			$logger->error($error);
		}
		
		$logger->echoLevel();
		if ($logger->isDebugEnabled()) {
			$streamNames = [];
			foreach ($logger->out as $handle) {
				$metadata = stream_get_meta_data($handle);
				$streamNames[] = $metadata['uri'];
			}
			$logger->trace(
				"Streams added: " . count($logger->out) . ". {" . implode(", ", $streamNames) . "}");
		}
		
		return $logger;
	}
	
	/**
	 *
	 * @param string $match        	
	 * @return resource
	 */
	private static function outStringToStream($match) {
		global $validator;
		$validator->validate_arg($match, "string");
		
		if (strtoupper($match) == "STDERR") {
			return fopen('php://stderr', 'w');
		} else if (strtoupper($match) == "STDOUT") {
			return fopen('php://stdout', 'w');
		} else {
			if (defined("LOGGER_NAME")) {
				$script = LOGGER_NAME;
			} else {
				$script = $_SERVER['PHP_SELF'];
			}
			if ($script == '-') {
				$script = "DEFAULT";
			}
			$match = replace_named_variables_defaults($match, 
				["script" => preg_replace("/(?:.*\\" . DIRECTORY_SEPARATOR . ")?(.+?)\.php$/", "$1", $script), 
					"logdir" => LOG_DIRECTORY]);
			return fopen($match, 'a');
		}
	}
	
	/**
	 *
	 * @return string
	 */
	private function get_time() {
		return date('Ymd H:i:s', time() - date('Z'));
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param int $level        	
	 * @param int $depth        	
	 * @return void
	 */
	public function doLog($message, $level, $depth) {
		if ($level === null || $level <= $this->debug_level) {
			if (is_bool($message)) {
				$message = $message ? "TRUE" : "FALSE";
			} else if ($message instanceof Exception) {
				if ($message instanceof BaseException) {
					if ($message->isLogged()) {
						return;
					} else {
						$message->setLogged();
					}
				}
				$message = exceptionToString($message);
			} else if (!is_string($message)) {
				$message = print_r($message, true);
			}
			
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth)[$depth - 1];
			
			$file = get_short_filename($backtrace['file']);
			$line = $backtrace['line'];
			$level_text = Level::int_to_level($level);
			
			$outString = $this->logFormatter->doFormat($level_text, $file, $line, $message);
			
			$this->writeRaw($outString);
		}
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @return void
	 */
	private function writeRaw($message) {
		foreach ($this->out as $stream) {
			fwrite($stream, $message);
		}
	}
	
	/**
	 *
	 * @return void
	 */
	public function clearOutputStreams() {
		$this->debug("Removing all output streams.");
		$this->out = array();
	}
	
	/**
	 *
	 * @param string $string        	
	 * @throws IllegalStateException
	 * @return void
	 */
	public function addOutputStreams($string) {
		$startCount = count($this->out);
		$stream = Logger::outStringToStream($string);
		
		if ($stream) {
			$this->out[] = $stream;
		} else {
			throw new IllegalStateException("Can't initialize stream: $string");
		}
		$this->trace("Added " . (count($this->out) - $startCount) . " streams");
	}
	
	/**
	 * Log a message regardless of the debug level (including OFF)
	 * 
	 * @param mixed $message        	
	 * @param int $depth
	 *        	(optional)
	 * @return void;
	 */
	public function all($message = "", $depth = 0) {
		$this->doLog($message, LEVEL::ALL, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function insane($message = "", $depth = 0) {
		$this->doLog($message, LEVEL::INSANE, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function trace($message = "", $depth = 0) {
		$this->doLog($message, LEVEL::TRACE, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function debug($message = "", $depth = 0) {
		$this->doLog($message, Level::DEBUG, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function info($message = "", $depth = 0) {
		$this->doLog($message, Level::INFO, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function warn($message = "", $depth = 0) {
		$this->doLog($message, Level::WARN, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function error($message = "", $depth = 0) {
		$this->doLog($message, Level::ERROR, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function fatal($message = "", $depth = 0) {
		$this->doLog($message, LEVEL::FATAL, $depth + 2);
	}
	
	/**
	 *
	 * @param mixed $message        	
	 * @param number $depth
	 *        	(optional)
	 * @return void
	 */
	public function log($level, $message = "", $depth = 0) {
		Level::int_to_level($level); // verify it is a valid level
		$this->doLog($message, $level, $depth + 2);
	}
	
	/**
	 * Write a message to the log without a timestamp.
	 * Use sparingly!
	 * 
	 * @param string $message        	
	 * @param int $level
	 *        	REQUIRED
	 * @return void
	 */
	public function write($message, $level) {
		global $validator;
		
		$validator->validate_arg($message, "string");
		Level::int_to_level($level); // verify it is a valid level
		
		if ($level <= $this->debug_level) {
			$this->writeRaw($message);
		}
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isInsaneEnabled() {
		return $this->debug_level >= Level::INSANE;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isTraceEnabled() {
		return $this->debug_level >= Level::TRACE;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isDebugEnabled() {
		return $this->debug_level >= Level::DEBUG;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isInfoEnabled() {
		return $this->debug_level >= Level::INFO;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isWarnEnabled() {
		return $this->debug_level >= Level::WARN;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isErrorEnabled() {
		return $this->debug_level >= Level::ERROR;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isFatalEnabled() {
		return $this->debug_level >= Level::FATAL;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function isOff() {
		return $this->debug_level == Level::OFF;
	}
	
	/**
	 *
	 * @return Level
	 */
	public function getDebugLevel() {
		return $this->debug_level;
	}
	
	/**
	 *
	 * @param number $debug_level        	
	 * @return void
	 */
	public function setDebugLevel($debug_level) {
		Level::int_to_level($debug_level); // verify it is a valid level
		$this->debug_level = $debug_level;
		$this->echoLevel();
	}
	
	/**
	 *
	 * @return LogFormatter
	 */
	public function getLogFormatter() {
		return $this->logFormatter;
	}
	
	/**
	 *
	 * @param LogFormatter $logFormatter        	
	 * @return void
	 */
	public function setLogFormatter(LogFormatter $logFormatter) {
		global $validator;
		$validator->validate_arg($logFormatter, "LogFormatter");
		$this->logFormatter = $logFormatter;
	}
	
	/**
	 * 
	 * @param int $level
	 * @return void
	 */
	public function pushLevel($level) {
		array_unshift($this->level_stack, $this->debug_level);
		$this->setDebugLevel($level);
	}
	

	/**
	 *
	 * @return int
	 */
	public function popLevel() {
		$level = array_shift($this->level_stack);
		if ($level === null) {
			$this->warn("Can't pop level: stack already empty.");
		} else {
			$this->debug_level = $level;
		}
		return $level;
	}
	
	/**
	 *
	 * @return void
	 */
	private function echoLevel() {
		$level_text = Level::int_to_level($this->debug_level);
		$this->trace("Logger class initialized at level $level_text ($this->debug_level)");
	}
}

?>
