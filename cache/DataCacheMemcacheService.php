<?php
class f_DataCacheMemcacheService extends f_DataCacheService
{
	const MEMCACHE_KEY_PREFIX = 'memcacheDataCache-';
	const MEMCACHE_REGISTRATION_KEY_PREFIX = 'memcacheDataCacheRegistration-';
	
	private static $instance;
	private $memcache = null;
	
	protected function __construct()
	{
		$this->memcache = new Memcache();
		
		$config = Framework::getConfiguration("memcache");
		
		if ($this->memcache->connect($config["server"]["host"], $config["server"]["port"]) === false)
		{
			Framework::error("DataCacheMemcacheService: could not obtain memcache instance");
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
		$this->memcache->set(self::MEMCACHE_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), $serialized, null, $item->getTTL());
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 * @param Boolean $dispatch (optional)
	 */
	public final function clearSubCache($item, $subCache, $dispatch = true)
	{
		$this->registerShutdown();
		
		$this->memcache->delete(self::MEMCACHE_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
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
	
	public function clearCommand()
	{
		$this->memcache->flush();
	}
	
	/**
	 * @param String $pattern
	 */
	public function getCacheIdsForPattern($pattern)
	{
		$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$pattern);
		if (f_util_StringUtils::isNotEmpty($object))
		{
			return unserialize($object);
		}
		return array();
	}
	
	protected function commitClear()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("DataCacheMemcacheService->commitClear");
		}
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$this->memcache->flush();
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
				$docIds = array();
				foreach ($this->docIdToClear as $docId => $value)
				{
					$docIds[] = $docId;
				}
				self::commitClearByDocIds($docIds);
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
		foreach ($docIds as $id)
		{
			$keys = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$id);
			if ($keys !== false)
			{
				$keyParameters = unserialize($keys);
				foreach ($keyParameters as $k)
				{
					$this->memcache->delete(self::MEMCACHE_KEY_PREFIX.$k);
				}
			}
		}
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		foreach ($dirsToClear as $id)
		{
			$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$id);
			$object = unserialize($object);
			if ($object === false)
			{
				$object = array();
			}
			foreach ($object as $keyParameters)
			{
				$this->memcache->delete(self::MEMCACHE_KEY_PREFIX.$id.'-'.$keyParameters);
			}
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		if (!$this->isRegistered($item))
		{	
			$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace(), serialize(array()), null, 0);
			
			foreach ($this->optimizeCacheSpecs($item->getPatterns()) as $pattern)
			{
				$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$pattern);
				$object = unserialize($object);
				if ($object === false)
				{
					$object = array();
				}
				array_push($object, $item->getNamespace());
				
				$serialized = serialize($object);
				$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$pattern, $serialized, null, 0);
				
				$object = null;
				$serialized = null;
			}
		}
		
		if (!$this->isRegistered($item, null, $item->getKeyParameters()))
		{	
			$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace());
			
			$object = unserialize($object);
			if ($object === false)
				{
					$object = array();
				}
			array_push($object, $item->getKeyParameters());
				
			$serialized = serialize($object);
			$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace(), $serialized, null, 0);
			
			$object = null;
			$serialized = null;
		}
	
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$spec);
				
				$object = unserialize($object);
				$object[] = $item->getNamespace().'-'.$item->getKeyParameters();
				
				$serialized = serialize($object);
				$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$spec, $serialized, null, 0);
				
				$object = null;
				$serialized = null;
			}
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param unknown_type $spec
	 * @param unknown_type $keyParameters
	 * @return Boolean
	 */
	protected function isRegistered($item, $spec = null, $keyParameters = null)
	{
		if ($spec === null && $keyParameters === null)
		{
			$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace());
		
			return ($object !== false);
		}
		
		if ($spec === null && $keyParameters !== null)
		{
			$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace());
			if ($object === false)
			{
				return false;
			}
			$object = unserialize($object);
			return (in_array($keyParameters, $object));
		}
		
		$object = $this->memcache->get(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$spec);
		
		return ($object !== false);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		$object = $this->memcache->get(self::MEMCACHE_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
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