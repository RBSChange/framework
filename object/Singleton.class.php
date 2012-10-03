<?php
/**
 * @deprecated use \Change\AbstractSingleton 
 */
abstract class change_Singleton
{
	/**
	 * @deprecated
	 */
	private static $instances = array();

	/**
	 * @deprecated
	 */
	protected final static function getInstanceByClassName($className)
	{
		if (!isset(self::$instances[$className]))
		{
			self::$instances[$className] = new $className();
		}
		return self::$instances[$className];
	}
	
	/**
	 * @deprecated
	 */
	protected final static function clearInstanceByClassName($className)
	{
		if (isset(self::$instances[$className]))
		{
			unset(self::$instances[$className]);
		}
	}

	/**
	 * @deprecated
	 */
	public static function getInstance()
	{
		return self::getInstanceByClassName(get_called_class());
	}

	/**
	 * @deprecated
	 */
	protected function __construct()
	{

	}
}