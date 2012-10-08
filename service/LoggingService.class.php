<?php
/**
 * @deprecated use \Change\Application\LoggingManager
 */
class change_LoggingService
{
	/**
	 * @deprecated
	 */
	public static function getInstance()
	{
		return \Change\Application\LoggingManager::getInstance();
	}
}