<?php
abstract class change_Singleton
{
	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @param string $className
	 * @return change_Singleton
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
	 * @param string $className
	 * @return void
	 */
	protected final static function clearInstanceByClassName($className)
	{
		if (isset(self::$instances[$className]))
		{
			unset(self::$instances[$className]);
		}
	}

	/**
	 * @return change_Singleton
	 */
	public static function getInstance()
	{
		return self::getInstanceByClassName(get_called_class());
	}

	/**
	 * Protected constructor use getInstance()
	 */
	protected function __construct()
	{

	}
}