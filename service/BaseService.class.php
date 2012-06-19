<?php
/**
 * @package framework.service
 */
abstract class change_BaseService extends change_Singleton
{
	/**
	 * @return f_persistentdocument_PersistentProvider
	 */
	protected function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @return f_persistentdocument_TransactionManager
	 */
	protected function getTransactionManager()
	{
		return f_persistentdocument_TransactionManager::getInstance();
	}
}