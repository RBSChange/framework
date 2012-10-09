<?php
/**
 * @deprecated
 */
abstract class f_persistentdocument_PersistentProvider extends \Change\Db\DbProvider
{
	//DEPRECATED
	
	public static function getInstance()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
	
	/**
	 * @deprecated
	 */
	public function clearFrameworkCacheByTTL($ttl)
	{
	}
	
	/**
	 * @deprecated
	 */
	public static function refresh()
	{
		throw new Exception("Unimplemented");
		//$instance = self::getInstance();
		//$instance->closeConnection();
	}
	
	/**
	 * @deprecated
	 */
	public static function clearInstance()
	{
		throw new Exception("Unimplemented");
		//self::clearInstanceByClassName('\Change\Db\Provider');
	}
}