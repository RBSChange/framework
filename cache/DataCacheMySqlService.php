<?php
class f_DataCacheMySqlService extends f_DataCacheService
{
	private static $instance;
	private static $pdo = null;
	
	protected function __construct()
	{
		self::$pdo = $this->getPersistentProvider()->getDriver();
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
		$serialized = serialize($data);
		if ($item->isNew())
		{
			$query = 'INSERT INTO `f_data_cache` (`cache_key`, `text_value`, `creation_time`, `ttl`, `is_valid`) VALUES (:id, :content, :time, :ttl, :valid)';
		}
		else 
		{
			$query = 'UPDATE `f_data_cache` SET `text_value` = :content, `ttl` = :ttl, `is_valid` = :valid, `creation_time` = :time WHERE `cache_key` = :id';
		}
		$stmt = self::$pdo->prepare($query);
		
		$stmt->bindValue(':id', $item->getNamespace().'-'.$item->getKeyParameters(), PDO::PARAM_STR);
		$stmt->bindValue(':content', $serialized, PDO::PARAM_STR);
		$stmt->bindValue(':ttl', $item->getTTL(), PDO::PARAM_INT);
		$stmt->bindValue(':time', time(), PDO::PARAM_INT);
		$stmt->bindValue(':valid', true, PDO::PARAM_BOOL);
		
		$stmt->execute();
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 * @param Boolean $dispatch (optional)
	 */
	public final function clearSubCache($item, $subCache, $dispatch = true)
	{
		$this->registerShutdown();
		
		$query = 'DELETE FROM `f_data_cache` WHERE `cache_key` = :id';
		$stmt = self::$pdo->prepare($query);
		$stmt->bindValue(':id', $item->getNamespace().'-'.$item->getKeyParameters(), PDO::PARAM_STR);
		$stmt->execute();
		
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
		$query = 'DELETE FROM `f_data_cache`';
		$stmt = self::$pdo->query($query);
	}
	
	/**
	 * @param String $pattern
	 */
	public function clearCacheByPattern($pattern)
	{
		$cacheIds = $this->getPersistentProvider()->getCacheIdsByPattern($pattern);
		foreach ($cacheIds as $cacheId)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[". __CLASS__ . "]: clear $cacheId cache");
			}
			$this->clear($cacheId);
		}
	}
	
	protected function commitClear()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("DataCacheMySqlService->commitClear");
		}
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$this->clearCommand();
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
		$query = 'SELECT `key_parameters` FROM `f_data_cache_doc_id_registration` WHERE `document_id` = :id';
		$keyParameters = array();
		
		foreach ($docIds as $docId)
		{
			$stmt = self::$pdo->prepare($query);
			$stmt->bindValue(':id', $docId, PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if (f_util_ArrayUtils::isEmpty($result))
			{
				continue;
			}
			$keyParameters = array_merge($keyParameters, unserialize($result[0]["key_parameters"]));
		}
		
		$query = 'DELETE FROM `f_data_cache` WHERE `cache_key` = :id';
		
		foreach ($keyParameters as $id)
		{
			$stmt = self::$pdo->prepare($query);
			$stmt->bindValue(':id', $id, PDO::PARAM_STR);
			$stmt->execute();
		}
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		$query = 'DELETE FROM `f_data_cache` WHERE `cache_key` LIKE :id';
		
		foreach ($dirsToClear as $id)
		{
			$stmt = self::$pdo->prepare($query);
			$stmt->bindValue(':id', $id.'-%', PDO::PARAM_STR);
			$stmt->execute();
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
			$pp = f_persistentdocument_PersistentProvider::getInstance();
			$pp->registerSimpleCache($item->getNamespace(), $this->optimizeCacheSpecs($item->getPatterns()));
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
		
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$query = 'SELECT `key_parameters` FROM `f_data_cache_doc_id_registration` WHERE `document_id` = :id';
				$stmt = self::$pdo->prepare($query);
				$stmt->bindValue(':id', $spec, PDO::PARAM_INT);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if (f_util_ArrayUtils::isNotEmpty($result))
				{
					$obj = unserialize($result[0]["key_parameters"]);
					$obj[] = $item->getNamespace().'-'.$item->getKeyParameters();
					$serialized = serialize($obj);
					$query2 = 'UPDATE `f_data_cache_doc_id_registration` SET `key_parameters` = :content WHERE `document_id` = :id';
					$stmt2 = self::$pdo->prepare($query2);
					$stmt2->bindValue(':id', $spec, PDO::PARAM_INT);
					$stmt2->bindValue(':content', $serialized, PDO::PARAM_STR);
					$stmt2->execute();
				}
				else 
				{
					$obj = array($item->getNamespace().'-'.$item->getKeyParameters());
					$serialized = serialize($obj);
					$query2 = 'INSERT INTO `f_data_cache_doc_id_registration` (`document_id`, `key_parameters`) VALUES (:id, :content)';
					$stmt2 = self::$pdo->prepare($query2);
					$stmt2->bindValue(':id', $spec, PDO::PARAM_INT);
					$stmt2->bindValue(':content', $serialized, PDO::PARAM_STR);
					$stmt2->execute();
				}
			}
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		$query = 'SELECT `is_valid`, `creation_time`, `ttl`, `text_value` FROM `f_data_cache` WHERE `cache_key` = :id';
		$stmt = self::$pdo->prepare($query);
		$stmt->bindValue(':id', $item->getNamespace().'-'.$item->getKeyParameters(), PDO::PARAM_STR);
		$stmt->execute();
		
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (f_util_ArrayUtils::isNotEmpty($result))
		{
			$isValid = (intval($result[0]["is_valid"]) === 1) ? true : false;
			$item->setValidity($isValid);
			$item->setCreationTime($result[0]["creation_time"]);
			$item->setTTL($result[0]["ttl"]);
			$values = unserialize($result[0]["text_value"]);
			foreach ($values as $k => $v)
			{
				$item->setValue($k, $v);
			}
		}
		else 
		{
			$item->markAsNew();
		}
		return $item;
	}
}