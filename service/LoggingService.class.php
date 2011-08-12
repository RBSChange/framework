<?php
class Logger
{

	// +-----------------------------------------------------------------------+
	// | CONSTANTS                                                             |
	// +-----------------------------------------------------------------------+

	/**
	 * Debug level.
	 *
	 * @since 0.9.0
	 */
	const DEBUG = 1000;

	/**
	 * Error level.
	 *
	 * @since 0.9.0
	 */
	const ERROR = 4000;

	/**
	 * Information level.
	 *
	 * @since 0.9.0
	 */
	const INFO = 2000;

	/**
	 * Warning level.
	 *
	 * @since 0.9.0
	 */
	const WARN = 3000;

	/**
	 * Fatal level.
	 *
	 * @since 0.9.0
	 */
	const FATAL = 5000;
}
	
class LoggingService
{
	protected $stdLogFilePath;
	 
	protected $errLogFilePath;
	
	protected function __construct()
	{
		$logDir = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME , 'log', 'project'));
		if (!is_dir($logDir)) {@mkdir($logDir, 0777, true);}		
		$this->stdLogFilePath = $logDir . DIRECTORY_SEPARATOR . 'application.log';
		$this->errLogFilePath = $logDir . DIRECTORY_SEPARATOR . 'phperror.log';
	}
	
	/**
	 * the singleton instance
	 * @var LoggingService
	 */
	private static $instance = null;
	
	/**
	 * @return LoggingService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	
	public function log($stringLine, $loggerGroup)
	{
		error_log("\n". gmdate('Y-m-d H:i:s')."\t".$stringLine, 3, $this->stdLogFilePath);
	}
	
	public function errorLog($stringLine, $loggerGroup)
	{
		error_log("\n". gmdate('Y-m-d H:i:s')."\t".$stringLine, 3, $this->errLogFilePath);
	}
}