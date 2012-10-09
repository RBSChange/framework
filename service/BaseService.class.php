<?php
/**
 * @deprecated
 */
abstract class change_BaseService extends change_Singleton
{
	/**
	 * @deprecated
	 */
	protected function getPersistentProvider()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
	
	/**
	 * @deprecated
	 */
	protected function getTransactionManager()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
}