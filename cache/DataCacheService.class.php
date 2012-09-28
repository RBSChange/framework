<?php
/**
 * @method f_DataCacheService getInstance()
 */
class f_DataCacheService extends change_BaseService
{
	const MAX_TIME_LIMIT = 86400;
	
	protected $clearAll = false;
	protected $idToClear = array();
	protected $docIdToClear = array();
	protected $dispatch = true;
	protected $shutdownRegistered = false;

	/**
	 * @param string $namespace
	 * @param Mixed $keyParameters
	 * @param Array $patterns
	 * @return f_DataCacheItem
	 */
	public function getNewCacheItem($namespace, $keyParameters, $patterns)
	{
		return new f_DataCacheItemImpl($namespace, $keyParameters, $patterns);
	}
	
	/**
	 * @return boolean
	 */
	public function isEnabled()
	{
		return DISABLE_DATACACHE !== true;
	}
	
	/**
	 * @param string $namespace
	 * @param Mixed $keyParameters
	 * @param string $subCache (optional)
	 * @param Array	$newPatterns
	 * @return f_DataCacheItem or null or String
	 */
	public function readFromCache($namespace, $keyParameters, $newPatterns = null)
	{
		if ($newPatterns !== null)
		{
			$returnItem = true;
		}
		else 
		{
			$returnItem = false;
			$newPatterns = array();
		}
		
		$item = $this->getData($this->getNewCacheItem($namespace, $keyParameters, $newPatterns));
		if ($returnItem || $this->exists($item))
		{
			return $item;
		}
		
		return null;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function writeToCache($item)
	{
		$item->setValidity(false);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function markAsBeingRegenerated($item)
	{
		// nothing
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param string $subCache
	 * @return boolean
	 */
	public function exists($item, $subCache = null)
	{
		$result = $item->isValid();
		if ($subCache !== null)
		{
			return $result && $item->getValue($subCache) !== null;
		}
		return $result;
	}
	
	/**
	 * @param string $pattern
	 */
	public function clearCacheByPattern($pattern)
	{
		$cacheIds = $this->getCacheIdsForPattern($pattern);
		foreach ($cacheIds as $cacheId)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[". __CLASS__ . "]: clear $cacheId cache");
			}
			$this->clear($cacheId);
		}
	}
	
	/**
	 * Has to be implemeted by sub class
	 * @param string $pattern
	 * @return array
	 */
	public function getCacheIdsForPattern($pattern)
	{
		return array();
	}
	
	/**
	 * @param string $namespace
	 */
	public function clearCacheByNamespace($namespace)
	{
		$this->clear($namespace);
	}
	
	/**
	 * @param string $id
	 */
	public function clearCacheByDocId($id)
	{
		$this->registerShutdown();
		$this->docIdToClear[$id] = true;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public function clearCacheByModel($model)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[". __CLASS__ . "]: clear cache by model:".$model->getName());
		}
		$this->clearCacheByPattern($model->getName());
	}

	/**
	 * @param string $tag
	 */
	public function clearCacheByTag($tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[". __CLASS__ . "]: clear cache by tag:$tag");
		}
		$this->clearCacheByPattern(f_DataCachePatternHelper::getTagPattern($tag));
	}

	public function clearAll()
	{
		$this->clear();
	}
	
	public function cleanExpiredCache()
	{
		return true;
	}
	
	/**
	 * This is the same as BlockCache::commitClear()
	 * but designed for the context of <code>register_shutdown_function()</code>,
	 * to be sure the correct umask is used.
	 */
	public function shutdownCommitClear()
	{
		$this->commitClear();
	}
	
	protected function commitClear()
	{
		return true;
	}
	
	public function clearCommand()
	{
		return true;
	}
	
	/**
	 * @param Array $cacheSpecs
	 * @return Array
	 */
	protected function optimizeCacheSpecs($cacheSpecs)
	{
		if (f_util_ArrayUtils::isNotEmpty($cacheSpecs))
		{
			$finalCacheSpecs = array();
			foreach (array_unique($cacheSpecs) as $spec)
			{
				if (preg_match('/^modules_\w+\/\w+$/', $spec))
				{
					try
					{
						$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($spec);
						$finalCacheSpecs[] = $model->getName();
					}
					catch (Exception $e)
					{
						Framework::exception($e);
					}
				}
				else if (strpos($spec, 'tags/') === 0 && strpos($spec, '*') !== false)
				{
					try
					{
						$tags = TagService::getInstance()->getAvailableTagsByPattern(substr($spec,5));
						foreach ($tags as $tag)
						{
							$finalCacheSpecs[] = 'tags/' . $tag;
						}
					}
					catch (Exception $e)
					{
						Framework::exception($e);
					}
				}
				elseif (!is_numeric($spec))
				{
					$finalCacheSpecs[] = $spec;
				}
			}

			return $finalCacheSpecs;
		}
		return array();
	}
	
	protected function registerShutdown()
	{
		if (!$this->shutdownRegistered)
		{
			register_shutdown_function(array($this,'shutdownCommitClear'));
			$this->shutdownRegistered = true;
		}
	}
	
	/**
	 * @param string $id
	 */
	protected function clear($id = null)
	{
		$this->registerShutdown();
		if ($id === null)
		{
			$this->clearAll = true;
		}
		else
		{
			$this->idToClear[$id] = true;
		}
	}
	
	/**
	 * @param array $patternArray
	 * @param array $idArray
	 */
	public function commitClearDispatched($patternArray,  $idArray)
	{
		$this->idToClear = $patternArray;
		$this->docIdToClear = $idArray;
		$this->commitClear();
	}
	
	/**
	 */
	public function clearAllDispatched()
	{
		$this->clearAll = true;
		$this->commitClear();
	}

	/**
	 * Has to be implemeted by sub class
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		$item->setValidity(false);
		return $item;
	}
	/**
	 * @param boolean $dispatch
	 */
	public function setDispatch($dispatch = true)
	{
		$this->dispatch = $dispatch;
	}
}