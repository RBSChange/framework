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

class f_persistentdocument_MemcachedCacheService extends f_persistentdocument_CacheService
{
	private $memcache;
	private $host, $port;
	private $inTransaction;
	private $deleteTransactionKeys;
	private $updateTransactionKeys;

	protected function __construct()
	{
		// empty
	}

	function __destruct()
	{
		$this->closeMemCache();
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		return new f_persistentdocument_MemcachedCacheService();
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		$object = $this->getMemCache()->get($key);
		if (Framework::isDebugEnabled())
		{
			$end = microtime(true);
			Framework::debug("CacheService : time to get $key : ".($end-$begin)." ms");
		}
		return ($object === false) ? null : $object;
	}

	/**
	 * @param array $key
	 * @return array<mixed> or false on error
	 */
	public function getMultiple($keys)
	{
		return $this->getMemCache()->get($keys);
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		if (!$this->inTransaction)
		{
			if (is_null($object))
			{
				return $this->getMemCache()->delete($key);
			}
			else
			{
				//return $this->getMemCache()->set($key, $object, MEMCACHE_COMPRESSED, 600);
				return $this->getMemCache()->set($key, $object, null, 3600);
			}
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[$key] = true;
			return true;
		}
	}

	/**
	 * @param integer $key
	 * @param mixed $object
	 * @return boolean
	 */
	public function update($key, $object)
	{
		try
		{
			if (!$this->inTransaction)
			{
				$this->getMemCache()->set($key, $object, null, 3600);
			}
			else
			{
				$this->updateTransactionKeys[$key] = $object;
			}
			return true;
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		return $this->getMemCache()->flush();
	}

	// private methods

	/**
	 * @return Memcache
	 */
	private function getMemCache()
	{
		if (is_null($this->memcache))
		{
			$this->memcache = new Memcache();
			$config = Framework::getConfiguration("memcache");
			if ($this->host === null)
			{
				$this->host = $config["serverDataCacheService"]["host"];
			}

			if ($this->port === null)
			{
				$this->port = $config["serverDataCacheService"]["port"];
			}

			if ($this->memcache->connect($this->host, $this->port) === false)
			{
				Framework::error("CacheService: could not obtain memcache instance");
				$this->memcache = new f_persistentdocument_NoopMemcache();
			}
		}
		return $this->memcache;
	}

	private function closeMemCache()
	{
		if (!is_null($this->memcache))
		{
			$this->memcache->close();
		}
	}

	public function beginTransaction()
	{
		$this->inTransaction = true;
		$this->deleteTransactionKeys = array();
		$this->updateTransactionKeys = array();
	}

	public function commit()
	{
		if ($this->inTransaction)
		{
			$memcache = $this->getMemCache();
			if (count($this->deleteTransactionKeys) > 0)
			{
				try
				{
					foreach (array_keys($this->deleteTransactionKeys) as $key)
					{
						$memcache->delete($key);
					}
				}
				catch (Exception $e)
				{
					Framework::exception($e);
				}
			}
			foreach ($this->updateTransactionKeys as $key => $object)
			{
				$this->getMemCache()->set($key, $object, null, 3600);
			}
			$this->deleteTransactionKeys = null;
			$this->updateTransactionKeys = null;
			$this->inTransaction = false;
		}

	}

	public function rollBack()
	{
		$this->deleteTransactionKeys = null;
		$this->updateTransactionKeys = null;
		$this->inTransaction = false;
	}
}

class f_persistentdocument_NoopMemcache
{
	function delete() { }

	function flush() { }

	function close() { }

	function get() { return false; }

	function set() { }

	function replace() { }
}

class f_persistentdocument_MongoCacheService extends f_persistentdocument_CacheService
{
	private static $mongoDB = null;
	private static $mongoCollection = null;
	private $inTransaction = false;
	private $deleteTransactionKeys = array();
	private $updateTransactionKeys = array();

	protected function __construct()
	{
		// empty
	}

	function __destruct()
	{
		$this->closeMongo();
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		return new f_persistentdocument_MongoCacheService();
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		
		try
		{
		    $object = $this->getMongo()->findOne(array("_id" => $key));
		}
		catch (MongoConnectionException $e)
		{
				Framework::exception($e);
				return false;
		}
		
		if (Framework::isDebugEnabled())
		{
			$end = microtime(true);
			Framework::debug("CacheService : time to get $key : ".($end-$begin)." ms");
		}
		return ($object === null) ? null : unserialize($object["object"]);
	}

	/**
	 * @param array $key
	 * @return array<mixed> or false on error
	 */
	public function getMultiple($keys)
	{
		$cursor = $this->getMongo()->find(array("_id" => array('$in' => $keys)));
		$returnArray = array();
		
		foreach ($cursor as $doc)
		{
			$returnArray[$doc["_id"]] = unserialize($doc["object"]);
		}
		return $returnArray;
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		if (!$this->inTransaction)
		{
			try
			{
				if ($object === null)
				{
					$result = $this->getMongo()->remove(array("_id" => $key), array("safe" => true));
				}
				else 
				{
					$serialized = serialize($object);
					$result = $this->getMongo()->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
				}
				return $result["ok"];
			}
			catch (MongoCursorException $e)
			{
				Framework::exception($e);
				return false;
			}
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[$key] = true;
			return true;
		}
		else
		{
			$this->updateTransactionKeys[$key] = $object;
			return true;	
		}
	}

	/**
	 * @param integer $key
	 * @param mixed $object
	 * @return boolean
	 */
	public function update($key, $object)
	{
		if (!$this->inTransaction)
		{
			try
			{
				$serialized = serialize($object);
				$result = $this->getMongo()->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
				return $result["ok"];
			}
			catch (MongoCursorException $e)
			{
				Framework::exception($e);
				return false;
			}
		}
		$this->updateTransactionKeys[$key] = $object;
		return true;
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		return true;
	}

	// private methods

	/**
	 * @return MongoCollection
	 */
	private function getMongo()
	{
		if (self::$mongoDB === null)
		{
			$connectionString = null;
			$config = Framework::getConfiguration("mongoDB");
			
			if (isset($config["authentication"]["username"]) && isset($config["authentication"]["password"]) && 
				$config["authentication"]["username"] !== '' && $config["authentication"]["password"] !== '')
			{
				$connectionString .= $config["authentication"]["username"].':'.$config["authentication"]["password"].'@';
			}
			
			$connectionString .= implode(",", $config["serversCacheService"]);
			
			if ($connectionString != null)
			{
				$connectionString = "mongodb://".$connectionString;
			}
			
			try
			{
				self::$mongoDB = new Mongo($connectionString, array("persistent" => "mongo"));
				self::$mongoCollection = self::$mongoDB->$config["database"]["name"]->documentCache;
			}
			catch (MongoConnnectionException $e)
			{
				Framework::exception($e);
				self::$mongoCollection = new f_persistentdocument_NoopMongo();
			}
		}
		return self::$mongoCollection;
	}

	private function closeMongo()
	{
		if (self::$mongoDB !== null)
		{
			self::$mongoDB->close();
			self::$mongoDB = null;
			self::$mongoCollection = null;
		}
	}

	public function beginTransaction()
	{
		$this->inTransaction = true;
		$this->deleteTransactionKeys = array();
		$this->updateTransactionKeys = array();
	}

	/**
	 * @return boolean
	 */
	public function commit()
	{
		if ($this->inTransaction)
		{
			$mongo = $this->getMongo();
			if (count($this->deleteTransactionKeys) > 0)
			{
				try
				{
					foreach (array_keys($this->deleteTransactionKeys) as $key)
					{
						$result = $mongo->remove(array("_id" => $key), array("safe" => true));
						
						if (!$result["ok"])
						{
							return false;
						}
					}
				}
				catch (MongoCursorException $e)
				{
					Framework::exception($e);
					return false;
				}
			}
			
			try
			{
				foreach ($this->updateTransactionKeys as $key => $object)
				{
					$serialized = serialize($object);
					$result = $mongo->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
					
					if (!$result["ok"])
					{
						return false;
					}
				}
			}
			catch (MongoCursorException $e)
			{
				Framework::exception($e);
				return false;
			}
			
			$this->rollBack();
			
			return true;
		}
		return false;
	}

	public function rollBack()
	{
		$this->deleteTransactionKeys = null;
		$this->updateTransactionKeys = null;
		$this->inTransaction = false;
	}
}

class f_persistentdocument_NoopMongo
{
	function delete() { }

	function flush() { }

	function close() { }

	function get() { return false; }

	function set() { }

	function replace() { }
}

class f_persistentdocument_RedisCacheService extends f_persistentdocument_CacheService
{
	const REDIS_KEY_PREFIX = 'redisCacheService-';
	private static $redis = null;
	private $inTransaction = false;
	private $deleteTransactionKeys = array();
	private $updateTransactionKeys = array();

	protected function __construct()
	{
		// empty
	}

	function __destruct()
	{
		$this->closeRedis();
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		return new f_persistentdocument_RedisCacheService();
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		
		$object = $this->getRedis()->get(self::REDIS_KEY_PREFIX.$key);
		
		if (Framework::isDebugEnabled())
		{
			$end = microtime(true);
			Framework::debug("CacheService : time to get $key : ".($end-$begin)." ms");
		}
		return ($object === false) ? null : unserialize($object);
	}

	/**
	 * @param array $key
	 * @return array<mixed> or false on error
	 */
	public function getMultiple($keys)
	{
		$prefixedKeys = array();
		
		foreach ($keys as $key)
		{
			$prefixedKeys[] = self::REDIS_KEY_PREFIX.$key;
		}
		$cursor = $this->getRedis()->getMultiple($prefixedKeys);
		$returnArray = array();
		
		foreach ($cursor as $doc)
		{
			$returnArray[] = ($doc === false) ? null : unserialize($doc);
		}
		return $returnArray;
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		if (!$this->inTransaction)
		{
			if ($object === null)
			{
				$result = $this->getRedis()->delete(self::REDIS_KEY_PREFIX.$key);
			}
			else 
			{
				$serialized = serialize($object);
				$result = $this->getRedis()->set(self::REDIS_KEY_PREFIX.$key, $serialized);
			}
			return ($result == true) ? true : false;
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[self::REDIS_KEY_PREFIX.$key] = true;
			return true;
		}
		else
		{
			$this->updateTransactionKeys[self::REDIS_KEY_PREFIX.$key] = $object;
			return true;	
		}
	}

	/**
	 * @param integer $key
	 * @param mixed $object
	 * @return boolean
	 */
	public function update($key, $object)
	{
		if (!$this->inTransaction)
		{
			$serialized = serialize($object);
			$result = $this->getRedis()->set(self::REDIS_KEY_PREFIX.$key, $serialized);
			return $result;
		}
		$this->updateTransactionKeys[self::REDIS_KEY_PREFIX.$key] = $object;
		return true;
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		return true;
	}

	// private methods

	/**
	 * @return MongoCollection
	 */
	private function getRedis()
	{
		if (self::$redis === null)
		{
			self::$redis = new Redis();
			
			$config = Framework::getConfiguration("redis");
			
			$con = self::$redis->connect($config["serverDataCacheService"]["host"], $config["serverDataCacheService"]["port"]);
			
			if (isset($config["authentication"]["password"]) && $config["authentication"]["password"] !== '')
			{
				self::$redis->auth($config["authentication"]["password"]);
			}
			
			$select = self::$redis->select($config["serverDataCacheService"]["database"]);
			
			if (!$con && !$select)
			{
				self::$redis = new f_persistentdocument_NoopRedis();
			}
		}
		return self::$redis;
	}

	private function closeRedis()
	{
		if (self::$redis !== null)
		{
			self::$redis->close();
			self::$redis = null;
		}
	}

	public function beginTransaction()
	{
		$this->inTransaction = true;
		$this->deleteTransactionKeys = array();
		$this->updateTransactionKeys = array();
	}

	/**
	 * @return boolean
	 */
	public function commit()
	{
		if ($this->inTransaction)
		{
			if (count($this->deleteTransactionKeys) > 0)
			{
				foreach (array_keys($this->deleteTransactionKeys) as $key)
				{
					if ($this->getRedis()->exists($key))
					{
						$result = $this->getRedis()->delete($key);
						
						if ($result != count($this->deleteTransactionKeys))
						{
							return false;
						}
					}
				}
			}
			
			foreach ($this->updateTransactionKeys as $key => $object)
			{
				$serialized = serialize($object);
				$result = $this->getRedis()->set(self::REDIS_KEY_PREFIX.$key, $serialized);
					
				if (!$result)
				{
					return false;
				}
			}
			
			$this->rollBack();
			
			return true;
		}
		return false;
	}

	public function rollBack()
	{
		$this->deleteTransactionKeys = null;
		$this->updateTransactionKeys = null;
		$this->inTransaction = false;
	}
}

class f_persistentdocument_NoopRedis
{
	function delete() { }

	function flush() { }

	function close() { }

	function get() { return false; }

	function set() { }

	function replace() { }
}

class f_persistentdocument_DatabaseCacheService extends f_persistentdocument_CacheService
{
	private $inTransaction = false;
	private $deleteTransactionKeys;
	private $updateTransactionKeys;

	protected function __construct()
	{
		// empty
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		return new f_persistentdocument_DatabaseCacheService();
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		try
		{
			return $this->getPersistentProvider()->getFromFrameworkCache($key);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return null;
		}
	}

	/**
	 * @param integer[] $keys
	 * @return array<mixed, mixed> associative array or false on error
	 */
	public function getMultiple($keys)
	{
		try
		{
			return $this->getPersistentProvider()->getMultipleFromFrameworkCache($keys);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object if null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		try
		{
			if (!$this->inTransaction)
			{
				$this->getPersistentProvider()->setInFrameworkCache($key, $object);
			}
			else if ($object === null)
			{
				$this->deleteTransactionKeys[$key] = true;
			}
			return true;
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
	}

	/**
	 * @param integer $key
	 * @param mixed $object
	 * @return boolean
	 */
	public function update($key, $object)
	{
		try
		{
			if (!$this->inTransaction)
			{
				$this->getPersistentProvider()->setInFrameworkCache($key, $object);
			}
			else
			{
				$this->updateTransactionKeys[$key] = $object;
			}
			return true;
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
	}
	
	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		try
		{
			$this->getPersistentProvider()->clearFrameworkCache($pattern);
			return true;
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
	}


	public function beginTransaction()
	{
		$this->inTransaction = true;
		$this->deleteTransactionKeys = array();
		$this->updateTransactionKeys = array();
	}

	public function commit()
	{
		if ($this->inTransaction)
		{
			$pp = $this->getPersistentProvider();
			if (count($this->deleteTransactionKeys) > 0)
			{
				try
				{
					$pp->deleteFrameworkCacheKeys(array_keys($this->deleteTransactionKeys));
				}
				catch (Exception $e)
				{
					Framework::exception($e);
				}
			}
			foreach ($this->updateTransactionKeys as $key => $object)
			{
				$pp->setInFrameworkCache($key, $object);
			}
			$this->deleteTransactionKeys = null;
			$this->updateTransactionKeys = null;
			$this->inTransaction = false;
		}
	}

	public function rollBack()
	{
		$this->deleteTransactionKeys = null;
		$this->updateTransactionKeys = null;
		$this->inTransaction = false;
	}


	/**
	 * @return f_persistentdocument_PersistentProvider
	 */
	private function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}
}
