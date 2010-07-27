<?php
class f_DataCacheRedisService extends f_DataCacheService
{
	const REDIS_KEY_PREFIX = 'redisDataCache-';
	const REDIS_REGISTRATION_KEY_PREFIX = 'redisDataCacheRegistration-';
	
	private static $instance;
	private static $redis = null;
	
	protected function __construct()
	{
		self::$redis = new Redis();
			
		$config = Framework::getConfiguration("redis");
		
		self::$redis->connect($config["serverDataCacheService"]["host"], $config["serverDataCacheService"]["port"]);
		
		if (isset($config["authentication"]["password"]) && $config["authentication"]["password"] !== '')
		{
			self::$redis->auth($config["authentication"]["password"]);
		}
		
		self::$redis->select($config["serverDataCacheService"]["database"]);
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
		self::$redis->set(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), $serialized);
		
		self::$redis->setTimeout(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), $item->getTTL());
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 * @param Boolean $dispatch (optional)
	 */
	public final function clearSubCache($item, $subCache, $dispatch = true)
	{
		$this->registerShutdown();
		
		self::$redis->delete(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
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

		$this->dispatch = $dispatch || $this->dispatch;
	}
	
	protected function commitClear()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("SimpleCache->commitClear");
		}
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$keys = self::$redis->getKeys(self::REDIS_KEY_PREFIX.'*');
			if (!is_array($keys))
			{
				$keys = array();
			}
			self::$redis->delete($keys);	
			if ($this->dispatch)
			{
				f_event_EventManager::dispatchEvent('simpleCacheCleared', null);
			}
		}
		else
		{
			if (!empty($this->idToClear))
			{
				$ids = array();
				foreach ($this->idToClear as $id => $value)
				{
					$ids[] = $id;
				}
				self::buildInvalidCacheList($ids);
			}
			if (!empty($this->docIdToClear))
			{
				self::commitClearByDocIds($this->docIdToClear);
			}
			
			if ($this->dispatch)
			{
				if ($this->idToClear === null)
				{
					$this->idToClear = array();
				}
				if ($this->docIdToClear === null)
				{
					$this->docIdToClear = array();
				}
				f_event_EventManager::dispatchEvent('simpleCacheCleared', null, array_merge($this->idToClear, $this->docIdToClear));
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
		$keys = self::$redis->getMultiple($docIds);
		$keyParameters = array();
		foreach ($keys as $k)
		{
			$a = unserialize($k);
			$keyParameters[] = self::REDIS_KEY_PREFIX.$a["keyParameters"];
		}
		self::$redis->delete($keyParameters);
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		$keys = array();
		
		foreach ($dirsToClear as $id)
		{
			$keys = array_merge($keys, self::$redis->getKeys(self::REDIS_KEY_PREFIX.$id.'-*'));
		}
		self::$redis->delete($keys);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		if (!$this->isRegistered($item))
		{	
			$object = self::$redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace());
			if ($object !== false)
			{
				$object = unserialize($object);
			}
			$object["pattern"] = $this->optimizeCacheSpecs($item->getPatterns());
			
			$serialized = serialize($object);
			self::$redis->set(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace(), $serialized);
			
			$object = null;
			$serialized = null;
		}
	
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$object = self::$redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$spec);
				if ($object !== false)
				{
					$object = unserialize($object);
				}
				$object["keyParameters"] = $item->getNamespace().'-'.$item->getKeyParameters();
				
				$serialized = serialize($object);
				self::$redis->set(self::REDIS_REGISTRATION_KEY_PREFIX.$spec, $serialized);
				
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
			$object = self::$redis->get(self::REDIS_REGISTRATION_KEY_PREFIX.$item->getNamespace());
		
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
		$object = self::$redis->get(self::REDIS_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
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
?>