<?php
/**
 * @method change_LoggingService getInstance()
 */
class change_LoggingService extends change_BaseService
{
	/**
	 * @return string (DEBUG, INFO, NOTICE, WARN, ERR, ALERT, EMERG)
	 */
	public function getLogLevelName()
	{
		return LOGGING_LEVEL;
	}
	
	/**
	 * @return integer
	 */
	public function getLogPriority()
	{
		return LOGGING_PRIORITY;
	}
	
	/**
	 * @var Zend_Log[]
	 */
	protected $loggers = array();
	
	/**
	 * @param string $name
	 * @return Zend_Log
	 */
	protected function getZendLogByName($name = 'application')
	{
		if (!isset($this->loggers[$name]))
		{			
			$this->loggers[$name] = $this->createFileLog($name);
		}
		return $this->loggers[$name];
	}
	
	/**
	 * @param string $name
	 * @return Zend_Log
	 */
	protected function createFileLog($name)
	{
		$logger = new Zend_Log();
		$directory = ($name == 'application' || $name == 'phperror') ? 'project' : 'other';
		$filePath = f_util_FileUtils::buildProjectPath('log', $directory, $name . '.log');
		if (!file_exists($filePath))
		{
			f_util_FileUtils::mkdir(dirname($filePath));
		}
		
		$writer = new Zend_Log_Writer_Stream($filePath);		
		$filter = new Zend_Log_Filter_Priority(LOGGING_PRIORITY);
		if ($name == 'application')
		{
			$writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp% [%priorityName%] %sessionId%: %message% (in %file% at line %line%)' . PHP_EOL));
			$logger->setEventItem('sessionId' , '');
			$logger->setEventItem('file' , '?');
			$logger->setEventItem('line' , '?');
		}
		else
		{
			$writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp%: %message%' . PHP_EOL));
		}
		$logger->addWriter($writer);
		$logger->addFilter($filter);
		$logger->setTimestampFormat('Y-m-d H:i:s');
		return $logger;
	}
	
	/**
	 * @param integer $id
	 */
	public function registerSessionId($id)
	{
		$this->getZendLogByName('application')->setEventItem('sessionId' , '(' . $id . ')');
	}
	
	/**
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->getZendLogByName('application')->log($message, Zend_Log::DEBUG);
	}
	
	/**
	 * @param string $message
	 */
	public function info($message)
	{
		$this->getZendLogByName('application')->log($message, Zend_Log::INFO);
	}
	
	/**
	 * @param string $message
	 */
	public function warn($message)
	{
		$this->getZendLogByName('application')->log($message, Zend_Log::WARN);
	}
	
	/**
	 * @param string $message
	 */
	public function error($message)
	{
		$this->getZendLogByName('application')->log($message, Zend_Log::ERR);
	}
	
	/**
	 * @param Exception $e
	 */
	public function exception($e)
	{
		$this->getZendLogByName('application')->log(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString(), Zend_Log::ALERT);
	}
	
	/**
	 * @param string $message
	 */
	public function fatal($message)
	{
		$this->getZendLogByName('application')->log($message, Zend_Log::EMERG);
	}
	
	/**
	 * @var array
	 */
	protected $errortype;
	
	/**
	 * @return void
	 */
	public function registerErrorHandler()
	{
		ini_set('display_errors', 1);
	
		$this->errortype = array (
			E_ERROR			  => 'E_ERROR',
			E_WARNING			=> 'E_WARNING',
			E_PARSE			  => 'E_PARSE',
			E_NOTICE			 => 'E_NOTICE',
			E_CORE_ERROR		 => 'E_CORE_ERROR',
			E_CORE_WARNING	   => 'E_CORE_WARNING',
			E_COMPILE_ERROR	  => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING	=> 'E_COMPILE_WARNING',
			E_USER_ERROR		 => 'E_USER_ERROR',
			E_USER_WARNING	   => 'E_USER_WARNING',
			E_USER_NOTICE		=> 'E_USER_NOTICE',
			E_STRICT			 => 'E_STRICT',
			E_RECOVERABLE_ERROR  => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED  		 => 'E_DEPRECATED',
			E_USER_DEPRECATED	 => 'E_USER_DEPRECATED'
		);
		// Configuration du gestionnaire d'erreurs
		set_error_handler(array($this, 'defaultErrorHandler'));
		set_exception_handler(array($this, 'defaultExceptionHandler'));
		// Make sure that the Zend classes used for logging are loaded
		$this->getZendLogByName('phperror');
	}
	
	/**
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 * @throws Exception
	 */
	public function defaultErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$message = '[' .$this->errortype[$errno] . '] ' . $errstr . ' in file (' . $errfile . ') line ' .$errline;
		switch ($errno)
		{
			case E_USER_ERROR:
			case E_USER_WARNING:
				$this->phperror($message);
				die($message . PHP_EOL);
				break;
			default:
				if (Framework::inDevelopmentMode())
				{
					if ($errno === E_USER_DEPRECATED)
					{
						$this->phperror('[E_USER_DEPRECATED] ' . $errstr);
						$this->phperror(f_util_ProcessUtils::getBackTrace(false, 5));
					}
					else if ($errno & error_reporting())
					{
						$this->phperror($message);
					}
				}
				break;
		}
		return true;
	}
	
	/**
	 * @param Exception $exception
	 */
	public function defaultExceptionHandler($exception)
	{
		$errfile = $exception->getFile();
		$errline = $exception->getLine();
		$message = '['.get_class($exception).'] ' . $exception->getMessage() . ' in file (' . $errfile . ') line ' . $errline . "\n".$exception->getTraceAsString();
		$this->phperror($message);
		echo $message . PHP_EOL;
	}
	
	/**
	 * @param string $message
	 */
	protected function phperror($message)
	{
		$this->getZendLogByName('phperror')->log($message, $this->getLogPriority());
	}
	
	/**
	 * @param string $stringLine
	 * @param string $logName
	 */
	public function namedLog($stringLine, $logName)
	{
		$logger = $this->getZendLogByName($logName);
		$logFilePath = f_util_FileUtils::buildProjectPath('log', $logName, $logName.'.log');
		$logger->log($stringLine, $this->getLogPriority());
	}
}