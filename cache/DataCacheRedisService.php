<?php
class f_DataCacheRedisService extends f_DataCacheService
{
	const REDIS_KEY_PREFIX = 'redisDataCache-';
	const REDIS_REGISTRATION_KEY_PREFIX = 'redisDataCacheRegistration-';
	
	private static $instance;
	private $redis = null;
	
	protected function __construct()
	{
		$provider = new f_RedisProvider(Framework::getConfiguration('redis'));
		if ($provider->isAvailable())
		{
			$this->redis = $provider->getConnection();
		}
		else
		{
			Framework::info("DataCacheRedisService : could not obtain redis instance");
		}
	}

	/**
	 * @return f_DataCacheService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function writeToCache($item)
	{	
		$this->register($item);
		$data = $item->getValues();
		
		$data["timestamp"] = time();
		$data["isValid"] = true;
		$data["ttl"] = $item->getTTL();
		
		$serialized = serialize($data);
		$this->redis->set(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), $serialized);
		
		$this->redis->setTimeout(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), $item->getTTL());
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 */
	public final function clearSubCache($item, $subCache)
	{
		$this->registerShutdown();
		
		$this->redis->delete(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' ' . $item->getNamespace().'-'.$item->getKeyParameters().' : '.$subCache);
		}
		
		if (!array_key_exists($item->getNamespace(), $this->idToClear))
		{
			$this->idToClear[$item->getNamespace()] = array($item->getKeyParameters() => $subCache);
		}
		else if (is_array($this->idToClear[$item->getNamespace()]))
		{
			$this->idToClear[$item->getNamespace()][$item->getKeyParameters()] = $subCache;
		}
	}
	
	public function clearCommand()
	{
		$keys = $this->redis->getKeys(self::REDIS_KEY_PREFIX.'*');
		if (!is_array($keys))
		{
			$keys = array();
		}
		$registrationKeys = $this->redis->getKeys(self::REDIS_REGISTRATION_KEY_PREFIX.'*');
		if (!is_array($registrationKeys))
		{
			$registrationKeys = array();
		}
		
		$allKeys = array_merge($keys, $registrationKeys);
		
		$this->redis->delete($allKeys);
	}
	
	/**
	 * @param String $pattern
	 */
	public function getCacheIdsForPattern($pattern)
	{
		$keys = $this->redis->getKeys(self::REDIS_REGISTRATION_KEY_PREFIX.'*');
		$objects = $this->redis->getMultiple($keys);
		
		$docs = array();
		
		for ($i = 0; $i < count($keys); $i++)
		{
			$object = unserialize($objects[$i]);
			
			if (isset($object["pattern"]))
			{
				foreach ($object["pattern"] as $p)
				{
					if ($p == $pattern)
					{
						$docs[] = substr($keys[$i], strlen(self::REDIS_REGISTRATION_KEY_PREFIX));
					}
				}
			}
		}
		
		return $docs;
	}
	
	protected function commitClear()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("DataCacheRedisService->commitClear");
		}
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$keys = $this->redis->getKeys(self::REDIS_KEY_PREFIX.'*');
			if (!is_array($keys))
			{
				$keys = array();
			}
			$this->redis->delete($keys);	
		}
		else
		{
			if (!empty($this->idToClear))
			{
				$ids = array();
				foreach (array_keys($this->idToClear) as $id)
				{
					$ids[] = $id;
				}
				self::buildInvalidCacheList($ids);
			}
			if (!empty($this->docIdToClear))
			{
				$docIds = array();
				foreach (array_keys($this->docIdToClear) as $docId)
				{
					$docIds[] = self::REDIS_REGISTRATION_KEY_PREFIX.$docId;
				}
				self::commitClearByDocIds($docIds);
			}
		}
		
		$this->clearAll = false;
		$this->idToClear = null;
		$this->docIdToClear = null;
	}

	/**
	 * @param Array $docIds
	 */
	protected function commitClearByDocIds($docIds)
	{
		$keys = $this->redis->getMultiple($docIds);
		$keyParameters = array();
		
		foreach ($keys as $k)
		{
			if ($k === false)
			{
				continue;
			}
			$a = unserialize($k);
			$keyParameters[] = self::REDIS_KEY_PREFIX.$a["keyParameters"];
		}
		
		$this->redis->delete($keyParameters);
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		$keys = array();
		
		foreach ($dirsToClear as $id)
		{
			$keys = array_merge($keys, $this->redis->getKeys(self::REDIS_KEY_PREFIX.$id.'-*'));
		}
		$this->redis->delete($keys);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		if (!$this->isRegistered($item))
		{	
			$object = $this->redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace());
			if ($object !== false)
			{
				$object = unserialize($object);
			}
			$object["pattern"] = $this->optimizeCacheSpecs($item->getPatterns());
			
			$serialized = serialize($object);
			$this->redis->set(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace(), $serialized);
			
			$object = null;
			$serialized = null;
		}
	
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$object = $this->redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$spec);
				if ($object !== false)
				{
					$object = unserialize($object);
				}
				$object["keyParameters"] = $item->getNamespace().'-'.$item->getKeyParameters();
				
				$serialized = serialize($object);
				$this->redis->set(self::REDIS_REGISTRATION_KEY_PREFIX.$spec, $serialized);
				
				$object = null;
				$serialized = null;
			}
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param unknown_type $id
	 * @param unknown_type $keyParameters
	 * @return Boolean
	 */
	protected function isRegistered($item, $id = null, $keyParameters = null)
	{
		if ($id === null && $keyParameters === null)
		{
			$object = $this->redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace());
		
			return ($object !== false);
		}
		return false;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		$object = $this->redis->get(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
		if ($object !== false)
		{
			$object = unserialize($object);
			
			foreach ($object as $k => $v)
			{
				if ($k == "isValid")
				{
					$item->setValidity($v);
					continue;
				}
				if ($k == "timestamp")
				{
					$item->setCreationTime($v);
					continue;
				}
				if ($k == "ttl")
				{
					$item->setTTL($v);
					continue;
				}
				$item->setValue($k, $v);
			}
		}
		return $item;
	}
}