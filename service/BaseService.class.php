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
		return f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @deprecated
	 */
	protected function getTransactionManager()
	{
		return f_persistentdocument_TransactionManager::getInstance();
	}
}