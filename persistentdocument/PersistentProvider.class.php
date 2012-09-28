<?php
/**
 * @deprecated
 */
abstract class f_persistentdocument_PersistentProvider extends \Change\Db\Provider
{
	//DEPRECATED
	
	public static function getInstance()
	{
		return \Change\Db\Provider::getInstance();
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
		$instance = self::getInstance();
		$instance->closeConnection();
	}
	
	/**
	 * @deprecated
	 */
	public static function clearInstance()
	{
		self::clearInstanceByClassName('\Change\Db\Provider');
	}
}