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
		return \Change\Db\Provider::getInstance();
	}
	
	/**
	 * @deprecated
	 */
	protected function getTransactionManager()
	{
		return \Change\Db\Provider::getInstance();
	}
}