<?php
class f_DataCacheMemcachedService extends f_DataCacheService
{
	const MEMCACHE_KEY_PREFIX = 'memcacheDataCache-';
	const MEMCACHE_REGISTRATION_KEY_PREFIX = 'memcacheDataCacheRegistration-';
	
	private static $instance;
	private $memcache = null;
	
	protected function __construct()
	{
		$provider = new f_MemcachedProvider(Framework::getConfiguration('memcache'));
		if ($provider->isAvailable())
		{
			$this->memcache = $provider->getConnection();
		}
		else
		{
			Framework::info("DataCacheMemcachedService : could not obtain memcache instance");
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
		
		$data = array("v" => $item->getValues(),
			"c" => time(), "t" => $item->getTTL());
		
		$this->memcache->set(self::MEMCACHE_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters(), serialize($data), $item->getTTL());
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
		if ($object !== false)
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
					$docIds[] = $docId;
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
			$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace(), serialize(array()), 0);
			
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
				$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$pattern, $serialized, 0);
				
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
			$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$item->getNamespace(), $serialized, 0);
			
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
				$this->memcache->set(self::MEMCACHE_REGISTRATION_KEY_PREFIX.$spec, $serialized, 0);
				
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
		$dataSer = $this->memcache->get(self::MEMCACHE_KEY_PREFIX.$item->getNamespace().'-'.$item->getKeyParameters());
		
		if ($dataSer !== false)
		{
			$data = unserialize($dataSer);
			if ($data !== false)
			{
				$item->setCreationTime($data["c"]);
				$item->setValues($data["v"]);
				$item->setTTL($data["t"]);
				$item->setValidity(true);
			}
		}
		
		return $item;
	}
}