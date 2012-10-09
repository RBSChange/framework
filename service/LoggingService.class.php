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
	 * @deprecated use \Change\Logging\Logging::getLevel()
	 */
	public function getLogLevelName()
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
		return call_user_method($name, $this->wrappedLogging, $arguments);
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