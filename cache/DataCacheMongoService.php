<?php
class f_DataCacheMongoService extends f_DataCacheService
{
	private static $instance;
	
	/**
	 * @var f_MongoProvider
	 */
	private $provider = null;
	
	/**
	 * @var MongoCollection
	 */	
	private $mongoCollection = null;
	/**
	 * @var MongoCollection
	 */	
	private $mongoRegistration = null;
	
	private $writeMode = false;
	
	protected function __construct()
	{
		if ($this->mongoCollection === null)
		{
			$provider = new f_MongoProvider(Framework::getConfiguration('mongoDB'));
			if ($provider->isAvailable())
			{
				$this->provider = $provider;
				$this->mongoCollection = $provider->getCollection('dataCache');
				$this->mongoRegistration = $provider->getCollection('dataCacheRegistration');
			}
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
		$this->writeMode();
		$this->register($item);
		$data = array();
		foreach ($item->getValues() as $key => $value)
		{
			if (is_string($value))
			{
				$data[$key] = utf8_encode($value);
			}
			else 
			{
				$data[$key] = $value;
			}
		}
		$data["timestamp"] = time();
		$data["isValid"] = true;
		$data["ttl"] = $item->getTTL();
		unset($data["_id"]);
		
		try
		{
			$this->mongoCollection->update(array("_id" => $item->getNamespace().'-'.$item->getKeyParameters()), 
				array('$set' => $data),	array("upsert" => true, "safe" => true));
		}
		catch (MongoCursorException $e)
		{
			Framework::exception($e);
		}
	}
	
	public function cleanExpiredCache()
	{
		$this->writeMode();
		try
		{
			$this->mongoCollection->remove(array("isValid" => false), array("safe" => true));
			$this->mongoCollection->remove(array("timestamp" => array('$lt' => time() - self::MAX_TIME_LIMIT)), array("safe" => true));
		}
		catch (MongoCursorException $e)
		{
			Framework::exception($e);
			return false;
		}
		return true;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 */
	public final function clearSubCache($item, $subCache)
	{
		$this->writeMode();
		$this->registerShutdown();
		
		try
		{
			$this->mongoCollection->remove(array("_id" => $item->getNamespace().'-'.$item->getKeyParameters()), array("safe" => true));
		}
		catch (MongoCursorException $e)
		{
			Framework::exception($e);
		}
		
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
		$this->writeMode();
		$this->mongoCollection->drop();
		$this->mongoRegistration->drop();
	}
	
	/**
	 * @param String $pattern
	 */
	public function getCacheIdsForPattern($pattern)
	{
		$cursor = $this->mongoRegistration->find(array("pattern" => $pattern), array("_id" => true));
		$cacheids = array();
		foreach ($cursor as $result)
		{
			$cacheids[] = $result['_id'];
		}
		return $cacheids;
	}
	
	protected function writeMode()
	{
		if (!$this->writeMode)
		{
			$this->writeMode = true;
			$this->mongoCollection = $this->provider->getCollection('dataCache', true);	
			$this->mongoRegistration = $this->provider->getCollection('dataCacheRegistration', true);
		}
	}
	
	protected function commitClear()
	{
		$this->writeMode();
		if (Framework::isDebugEnabled())
		{
			Framework::debug("DataCacheMongoService->commitClear");
		}
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$this->mongoCollection->update(array(), array('$set' => array("isValid" => false)), array("multiple" => true, "safe" => true));	
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
		$this->writeMode();
		try
		{
			$keyParameters = array();
			foreach ($docIds as $id)
			{
				$cursor = $this->mongoRegistration->find(array("_id" => $id));
				foreach ($cursor as $k)
				{
					$keyParameters = array_merge($keyParameters, $k["keyParameters"]);
				}
			}
			
			$this->mongoCollection->update(array("_id" => array('$in' => $keyParameters)), 
				array('$set' => array("isValid" => false)), array("multiple" => true, "safe" => true));
		}
		catch (MongoCursorException $e)
		{
			Framework::exception($e);
		}
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		$this->writeMode();
		try
		{
			$regex =  implode("|", $dirsToClear);
			$this->mongoCollection->update(array("_id" => new MongoRegex("/^($regex)-/") ), 
				array('$set' => array("isValid" => false)), 
				array("multiple" => true, "safe" => true));				
		}
		catch (MongoCursorException $e)
		{
			Framework::exception($e);
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		$this->writeMode();
		if (!$this->isRegistered($item))
		{	
			try
			{
				$this->mongoRegistration->update(array("_id" => $item->getNamespace()), 
					array('$set' => array("pattern" => $this->optimizeCacheSpecs($item->getPatterns()))), 
					array("upsert" => true, "safe" => true));
			}
			catch (MongoCursorException $e)
			{
				Framework::exception($e);
			}
		}
		
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				try
				{
					$this->mongoRegistration->update(array("_id" => $spec), 
						array('$addToSet' => array("keyParameters" => $item->getNamespace().'-'.$item->getKeyParameters())), 
						array("upsert" => true, "safe" => true));
				}
				catch (MongoCursorException $e)
				{
					Framework::exception($e);
				}
			}
		}
	}
	
	protected function isRegistered($item, $id = null, $keyParameters = null)
	{
		if ($id === null && $keyParameters === null)
		{
			try
			{
				$object = $this->mongoRegistration->findOne(array("_id" => $item->getNamespace()));
			}
			catch (MongoConnnectionException $e)
			{
				Framework::exception($e);
			}
		
			return ($object !== null);
		}
		
		try
		{
			$object = $this->mongoRegistration->findOne(array("keyParameters" => $id.'-'.$keyParameters));
		}
		catch (MongoConnnectionException $e)
		{
			Framework::exception($e);
		}
		
		return ($object !== null);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		try
		{
			$object = $this->mongoCollection->findOne(array("_id" => $item->getNamespace().'-'.$item->getKeyParameters()));
		}
		catch (MongoConnnectionException $e)
		{
			Framework::exception($e);
		}
		
		if ($object !== null)
		{
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
				if (is_string($v))
				{
					$item->setValue($k, utf8_decode($v));
					continue;
				}
				$item->setValue($k, $v);
			}
		}
		return $item;
	}
}