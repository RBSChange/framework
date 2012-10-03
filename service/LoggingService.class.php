<?php
class change_LoggingService extends \Change\Application\LoggingManager
{
	/**
	 * @deprecated use \Change\Application\LoggingManager::getLevel()
	 */
	public function getLogLevelName()
	{
		return parent::getLevel();
	}
	
	/**
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public function getLogPriority()
	{
		return parent::getPriority();
	}
}