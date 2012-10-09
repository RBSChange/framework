<?php
/**
 * @deprecated
 */
class change_LoggingService
{
	protected static $instance;
	
	/**
	 * 
	 * @var \Change\Logging\Logging
	 */
	protected $wrappedLogging;
	
	/**
	 * @deprecated use \Change\Logging\Logging
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new static();
			self::$instance->wrappedLogging = \Change\Application::getInstance()->getApplicationServices()->getLogging();
		}
		return self::$instance;
	}
	
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->wrappedLogging, $name), $arguments);
	}
	
	/**
	 * @deprecated use \Change\Logging\Logging::getLevel()
	 */
	public function getLogLevelName()
	{
		return $this->wrappedLogging->getLevel();
	}
	
	/**
	 * @deprecated use \Change\Logging\Logging::getPriority()
	 */
	public function getLogPriority()
	{
		return $this->wrappedLogging->getPriority();
	}
}