<?php
/**
 * Note: sub classes must implement static function getInstance() wich usually 
 * returns the singleton instance of the service
 * @package framework.service
 */
abstract class BaseService
{
	protected function __construct()
	{
	}
	
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
	
	/**
	 * @deprecated
	 */
	protected static final function getServiceClassInstance($selfClassName)
	{
		return new $selfClassName();
	}
}