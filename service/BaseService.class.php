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
	 * returns an instance of the service or a subclass indicated in injection config section
	 * <pre>
	 * <project>
	 * 	<config>
	 *   <injection>
	 *	   <entry name="$selfClassName">anextensionmodule_aSubClassOfSelfClassName</entry>
	 *   </injection>
	 *  </config>
	 * </project>
	 * </pre>
	 * @param String $selfClassName 
	 * @return mixed an instance of the service or a subclass indicated in injection config section
	 * @throws ConfigurationException if the overriden class is not a subclass of $selfClassName
	 */
	protected static final function getServiceClassInstance($selfClassName)
	{
		$finalClassName = Injection::getFinalClassName($selfClassName);
		return new $finalClassName();
	}
}