<?php
/**
 * @package framework.persistentdocument
 */
abstract class f_persistentdocument_CacheService
{
	/**
	 * @var f_persistentdocument_CacheService
	 */
	private static $serviceInstance;

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		if (is_null(self::$serviceInstance))
		{
			if (defined('CHANGE4_CACHE_SERVICE_CLASS'))
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(get_class().'->getInstance() Using '.CHANGE4_CACHE_SERVICE_CLASS);
				}

				self::$serviceInstance = f_util_ClassUtils::callMethod(CHANGE4_CACHE_SERVICE_CLASS, 'getInstance');
			}
			else
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(get_class().'->getInstance() Using default CHANGE4_CACHE_SERVICE_CLASS f_persistentdocument_NoopCacheService');
				}
				self::$serviceInstance = f_persistentdocument_NoopCacheService::getInstance();
			}
		}
		return self::$serviceInstance;
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public abstract function get($key);

	/**
	 * @param integer[] $keys
	 * @return array<mixed, mixed> associative array or false on error
	 */
	public abstract function getMultiple($keys);

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public abstract function set($key, $object);

	/**
	 * @param integer $key
	 * @param mixed $object
	 * @return boolean
	 */
	public abstract function update($key, $object);

	/**
	 * @param $pattern string sql like pattern of cache key
	 * @return boolean
	 */
	public abstract function clear($pattern = null);

	public abstract function beginTransaction();

	public abstract function commit();

	public abstract function rollBack();
}

class f_persistentdocument_NoopCacheService extends f_persistentdocument_CacheService
{
	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		return new f_persistentdocument_NoopCacheService();
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		return null;
	}

	/**
	 * @param integer[] $keys
	 * @return array<mixed, mixed> associative array or false on error
	 */
	public function getMultiple($keys)
	{
		return false;
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		return false;
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		// empty
	}

	public function beginTransaction()
	{
		// empty
	}

	public function commit()
	{
		// empty
	}

	public function rollBack()
	{
		// empty
	}

	public function update($key, $object)
	{
		// empty
	}
}