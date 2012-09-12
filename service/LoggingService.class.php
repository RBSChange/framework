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
	 * @var \Zend\Log\Logger[]
	 */
	protected $loggers = array();
	
	/**
	 * @param string $name
	 * @return \Zend\Log\Logger
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
	 * @return \Zend\Log\Logger
	 */
	protected function createFileLog($name)
	{
		$logger = new \Zend\Log\Logger();
		$directory = ($name == 'application' || $name == 'phperror') ? 'project' : 'other';
		$filePath = f_util_FileUtils::buildProjectPath('log', $directory, $name . '.log');
		if (!file_exists($filePath))
		{
			f_util_FileUtils::mkdir(dirname($filePath));
		}
		
		
		if ($name == 'phperror')
		{
			$writer = new \Zend\Log\Writer\Stream($filePath);
			//$writer = new \Zend\Log\Writer\Syslog(array('application' => 'RBS Change'));
			$formatter = new \Zend\Log\Formatter\ErrorHandler();
		}
		else
		{
			$writer = new \Zend\Log\Writer\Stream($filePath);
			$filter = new \Zend\Log\Filter\Priority(LOGGING_PRIORITY);
			$writer->addFilter($filter);
		}
		$logger->addWriter($writer);
		return $logger;
	}
	
	/**
	 * @param integer $id
	 */
	public function registerSessionId($id)
	{
		//$this->getZendLogByName('application')->setEventItem('sessionId' , '(' . $id . ')');
	}
	
	/**
	 * @param string $message
	 */
	public function debug($message)
	{
		$this->getZendLogByName('application')->debug($message);
	}
	
	/**
	 * @param string $message
	 */
	public function info($message)
	{
		$this->getZendLogByName('application')->info($message);
	}
	
	/**
	 * @param string $message
	 */
	public function warn($message)
	{
		$this->getZendLogByName('application')->warn($message);
	}
	
	/**
	 * @param string $message
	 */
	public function error($message)
	{
		$this->getZendLogByName('application')->err($message);
	}
	
	/**
	 * @param Exception $e
	 */
	public function exception($e)
	{
		$this->getZendLogByName('application')->alert(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
	}
	
	/**
	 * @param string $message
	 */
	public function fatal($message)
	{
		$this->getZendLogByName('application')->emerg($message);
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
		$this->getZendLogByName('phperror')->log($this->getLogPriority(), $message);
	}
	
	/**
	 * @param string $stringLine
	 * @param string $logName
	 */
	public function namedLog($stringLine, $logName)
	{
		$logger = $this->getZendLogByName($logName);
		$logger->log($this->getLogPriority(), $stringLine);
	}
}