<?php
class f_persistentdocument_MemcachedExtCacheService extends f_persistentdocument_CacheService
{
	private $memcache;
	private $inTransaction;
	private $deleteTransactionKeys;
	private $updateTransactionKeys;

	protected function __construct()
	{
		$this->memcache = new Memcached();
		$config = Framework::getConfiguration("memcache");

		if ($this->memcache->addServer($config["server"]["host"], $config["server"]["port"]) === false)
		{
			Framework::error("CacheService: could not obtain memcache instance");
			$this->memcache = null;
		}
	}

	function __destruct()
	{
		if ($this->memcache !== null)
		{
			$this->memcache = null;
		}
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		$instance = new f_persistentdocument_MemcachedExtCacheService();
		if ($instance->memcache === null)
		{
			return new f_persistentdocument_NoopCacheService();
		}
		return $instance;
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		$object = $this->memcache->get($key);
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
		return $this->memcache->getMulti($keys);
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object is null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		if (!$this->inTransaction)
		{
			if ($object === null)
			{
				return $this->memcache->delete($key);
			}
			else
			{
				return $this->memcache->set($key, $object, 3600);
			}
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[$key] = true;
		}
		else
		{
			$this->updateTransactionKeys[$key] = $object;
		}
		return true;
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
				$this->memcache->set($key, $object, 3600);
			}
			else
			{
				$this->updateTransactionKeys[$key] = $object;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		if ($pattern === null)
		{
			return $this->memcache->flush();
		}
		return $this->memcache->delete($pattern);
	}

	// private methods

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
			$memcache = $this->memcache;
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
				$this->memcache->set($key, $object, 3600);
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

class f_persistentdocument_MemcachedCacheService extends f_persistentdocument_CacheService
{
	private $memcache;
	private $inTransaction;
	private $deleteTransactionKeys;
	private $updateTransactionKeys;

	protected function __construct()
	{
		$this->memcache = new Memcache();
		$config = Framework::getConfiguration("memcache");

		if ($this->memcache->connect($config["server"]["host"], $config["server"]["port"]) === false)
		{
			Framework::error("CacheService: could not obtain memcache instance");
			$this->memcache = null;
		}
	}

	function __destruct()
	{
		if ($this->memcache !== null)
		{
			$this->memcache->close();
			$this->memcache = null;
		}
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		$instance = new f_persistentdocument_MemcachedCacheService();
		if ($instance->memcache === null)
		{
			return new f_persistentdocument_NoopCacheService();
		}
		return $instance;
	}

	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		$object = $this->memcache->get($key);
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
		return $this->memcache->get($keys);
	}

	/**
	 * @param integer $key
	 * @param mixed $object if object is null, perform a delete
	 * @return boolean
	 */
	public function set($key, $object)
	{
		if (!$this->inTransaction)
		{
			if ($object === null)
			{
				return $this->memcache->delete($key);
			}
			else
			{
				return $this->memcache->set($key, $object, null, 3600);
			}
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[$key] = true;
		}
		else
		{
			$this->updateTransactionKeys[$key] = $object;
		}
		return true;
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
				$this->memcache->set($key, $object, null, 3600);
			}
			else
			{
				$this->updateTransactionKeys[$key] = $object;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		if ($pattern === null)
		{
			return $this->memcache->flush();
		}
		return $this->memcache->delete($pattern);
	}

	// private methods

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
			$memcache = $this->memcache;
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
				$this->memcache->set($key, $object, null, 3600);
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

class f_persistentdocument_MongoCacheService extends f_persistentdocument_CacheService
{
	private $provider = null;
	private $mongoCollection = null;
	private $writeMode = false;
	private $inTransaction = false;
	private $deleteTransactionKeys = array();
	private $updateTransactionKeys = array();

	protected function __construct()
	{
		if ($this->mongoCollection === null)
		{
			$provider = new f_MongoProvider(Framework::getConfiguration('mongoDB'));
			if ($provider->isAvailable())
			{
				$this->provider = $provider;
				$this->mongoCollection = $provider->getCollection('documentCache');
			}
		}
	}

	function __destruct()
	{
		//$empty
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		$instance = new f_persistentdocument_MongoCacheService();
		if ($instance->mongoCollection === null)
		{
			return new f_persistentdocument_NoopCacheService();
		}
		return $instance;
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
			$object = $this->mongoCollection->findOne(array("_id" => $key));
		}
		catch (MongoCursorException $e)
		{
			Framework::info(var_export($this->provider, true));
			Framework::exception($e);
			return null;
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
		$cursor = $this->mongoCollection->find(array("_id" => array('$in' => $keys)));
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
				$this->writeMode();
				if ($object === null)
				{
					$result = $this->mongoCollection->remove(array("_id" => $key), array("safe" => true));
				}
				else 
				{
					$serialized = serialize($object);
					$result = $this->mongoCollection->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
				}
				return $result["ok"];
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				return false;
			}
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[$key] = true;
		}
		else
		{
			$this->updateTransactionKeys[$key] = $object;
		}
		return true;	
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
				$this->writeMode();
				$serialized = serialize($object);
				$result = $this->mongoCollection->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
				return $result["ok"];
			}
			catch (Exception $e)
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
		if ($pattern === null)
		{
			$result = $this->mongoCollection->drop();
			return $result["ok"] ? true : false;
		}
		return $this->mongoCollection->remove(array("_id" => $pattern));
	}

	// private methods
	
	private function writeMode()
	{
		if (!$this->writeMode)
		{
			$this->writeMode = true;
			$this->mongoCollection = $this->provider->getCollection('documentCache', true);			
		}
	}

	public function beginTransaction()
	{
		if (!$this->inTransaction)
		{
			$this->inTransaction = true;
			$this->deleteTransactionKeys = array();
			$this->updateTransactionKeys = array();
		}
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
				try
				{
					$this->writeMode();
					foreach (array_keys($this->deleteTransactionKeys) as $key)
					{
						$result = $this->mongoCollection->remove(array("_id" => $key), array("safe" => true));
						
						if (!$result["ok"])
						{
							return false;
						}
					}
				}
				catch (Exception $e)
				{
					Framework::exception($e);
					return false;
				}
			}
			if (count($this->updateTransactionKeys) > 0)
			{
				try
				{
					$this->writeMode();
					foreach ($this->updateTransactionKeys as $key => $object)
					{
						$serialized = serialize($object);
						$result = $this->mongoCollection->save(array("_id" => $key, "object" => $serialized), array("safe" => true));
						
						if (!$result["ok"])
						{
							return false;
						}
					}
				}
				catch (Exception $e)
				{
					Framework::exception($e);
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

class f_persistentdocument_RedisCacheService extends f_persistentdocument_CacheService
{
	const REDIS_KEY_PREFIX = 'redisCacheService-';
	private $redis = null;
	private $inTransaction = false;
	private $deleteTransactionKeys = array();
	private $updateTransactionKeys = array();

	protected function __construct()
	{
		$redis = new Redis();	
		$config = Framework::getConfiguration("redis");
		$con = $redis->connect($config["server"]["host"], $config["server"]["port"]);
		if ($con)
		{
			if (isset($config["authentication"]))
			{
				$redis->auth($config["authentication"]["password"]);
			}
			
			$select = $redis->select($config["server"]["database"]);
			if ($select)
			{
				$this->redis = $redis;
			}
		}
	}

	function __destruct()
	{
		if ($this->redis !== null)
		{
			$this->redis->close();
			$this->redis = null;
		}
	}

	/**
	 * @return f_persistentdocument_CacheService
	 */
	public static function getInstance()
	{
		$instance = new f_persistentdocument_RedisCacheService();
		if ($instance->redis === null)
		{
			Framework::debug("CacheService : could not obtain redis instance");
			return new f_persistentdocument_NoopCacheService();
		}
		return $instance;
	}
	/**
	 * @param integer $key
	 * @return mixed or null if not exists or on error
	 */
	public function get($key)
	{
		$begin = microtime(true);
		
		$object = $this->redis->get(self::REDIS_KEY_PREFIX.$key);
		
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
		$cursor = $this->redis->getMultiple($prefixedKeys);
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
				$result = $this->redis->delete(self::REDIS_KEY_PREFIX.$key);
			}
			else 
			{
				$serialized = serialize($object);
				$result = $this->redis->set(self::REDIS_KEY_PREFIX.$key, $serialized);
			}
			return ($result == true) ? true : false;
		}
		else if ($object === null)
		{
			$this->deleteTransactionKeys[self::REDIS_KEY_PREFIX.$key] = true;
		}
		else
		{
			$this->updateTransactionKeys[self::REDIS_KEY_PREFIX.$key] = $object;
		}
		return true;
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
			$result = $this->redis->set(self::REDIS_KEY_PREFIX.$key, $serialized);
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
		if ($pattern === null)
		{
			return $this->redis->flushDB();
		}
		return $this->redis->delete(self::REDIS_KEY_PREFIX.$pattern);
	}

	// private methods

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
					if ($this->redis->exists($key))
					{
						$result = $this->redis->delete($key);
					}
				}
			}
			
			foreach ($this->updateTransactionKeys as $key => $object)
			{
				$serialized = serialize($object);
				$result = $this->redis->set($key, $serialized);
					
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
			else
			{
				$this->updateTransactionKeys[$key] = $object;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
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
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
	}
	
	/**
	 * @return boolean
	 */
	public function clear($pattern = null)
	{
		try
		{
			$this->getPersistentProvider()->clearFrameworkCache($pattern);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
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
