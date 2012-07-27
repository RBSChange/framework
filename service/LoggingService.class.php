<?php
class Logger
{
	/**
	 * Debug level.
	 */
	const DEBUG = 1000;

	/**
	 * Error level.
	 */
	const ERROR = 4000;

	/**
	 * Information level.
	 */
	const INFO = 2000;

	/**
	 * Warning level.
	 */
	const WARN = 3000;

	/**
	 * Fatal level.
	 */
	const FATAL = 5000;
}
	

class LoggingService extends BaseService
{
	/**
	 * @var LoggingService
	 */
	protected static $instance = null;
	
	/**
	 * @return LoggingService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
		
	/**
	 * @var string
	 */
	protected $stdLogFilePath;

	/**
	 * @var string
	 */
	protected $errLogFilePath;
	
	
	/**
	 * @var array
	 */
	protected $errortype;
	
	
	protected function __construct()
	{
		$this->stdLogFilePath = CHANGE_LOG_DIR . DIRECTORY_SEPARATOR . 'application.log';
		$this->errLogFilePath = CHANGE_LOG_DIR . DIRECTORY_SEPARATOR . 'phperror.log';
	}
	
	/**
	 * @return void
	 */
	public function registerErrorHandler()
	{
		ini_set('display_errors', 1);
		
		$this->errortype = array (
			E_ERROR              => 'E_ERROR',
			E_WARNING            => 'E_WARNING',
			E_PARSE              => 'E_PARSE',
			E_NOTICE             => 'E_NOTICE',
			E_CORE_ERROR         => 'E_CORE_ERROR',
			E_CORE_WARNING       => 'E_CORE_WARNING',
			E_COMPILE_ERROR      => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING    => 'E_COMPILE_WARNING',
			E_USER_ERROR         => 'E_USER_ERROR',
			E_USER_WARNING       => 'E_USER_WARNING',
			E_USER_NOTICE        => 'E_USER_NOTICE',
			E_STRICT             => 'E_STRICT',
			E_RECOVERABLE_ERROR  => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED  		 => 'E_DEPRECATED',
			E_USER_DEPRECATED	 => 'E_USER_DEPRECATED'
		);
		// Configuration du gestionnaire d'erreurs
		set_error_handler(array($this, "defaultErrorHandler"));
		set_exception_handler(array($this, "defaultExceptionHandler"));
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
				$this->errorLog($message, 'self');
				die($message . PHP_EOL);
				break;
			default:
				if (Framework::inDevelopmentMode())
				{
					if ($errno === E_USER_DEPRECATED)
					{
						$this->errorLog('[E_USER_DEPRECATED] ' . $errstr, 'self');
						$this->errorLog(f_util_ProcessUtils::getBackTrace(false, 5), 'self');
					}
					else
					{
						$this->errorLog($message, 'self');
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
		$this->errorLog($message, 'self');
		echo $message . PHP_EOL;
	}
	
	/**
	 * @param string $stringLine
	 * @param string $loggerGroup
	 */
	public function log($stringLine, $loggerGroup)
	{
		error_log(gmdate('Y-m-d H:i:s')."\t".$stringLine . PHP_EOL, 3, $this->stdLogFilePath);
	}
	
	/**
	 * @param string $stringLine
	 * @param string $loggerGroup
	 */
	public function errorLog($stringLine, $loggerGroup)
	{
		error_log(gmdate('Y-m-d H:i:s')."\t".$stringLine . PHP_EOL, 3, $this->errLogFilePath);
	}
	
	/**
	 * @param string $stringLine
	 * @param string $logName
	 */
	public function namedLog($stringLine, $logName)
	{
		$logFilePath = f_util_FileUtils::buildWebeditPath('log', $logName , $logName.'.log');
		try
		{
			f_util_FileUtils::mkdir(dirname($logFilePath));
			error_log(gmdate('Y-m-d H:i:s')."\t".$stringLine . PHP_EOL, 3, $logFilePath);
		} 
		catch (Exception $e) 
		{
			$this->defaultExceptionHandler($e);
		}
	}
}