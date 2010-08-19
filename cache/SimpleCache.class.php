<?php
/**
 * @deprecated use f_DataCacheService
 *
 */
class f_SimpleCache
{
	/**
	 * @var f_DataCacheItem
	 */
	private $cacheItem = null;

	public function __construct($id, $keyParameters, $cacheSpecs)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info('Deprecated usage of f_SimpleCache');
		}
		$this->cacheItem = f_DataCacheFileService::getInstance()->readFromCache($id, $keyParameters, $cacheSpecs);
	}

	static function isEnabled()
	{
		return f_DataCacheFileService::getInstance()->isEnabled();
	}

	public function exists($subCache)
	{
		return f_DataCacheFileService::getInstance()->exists($this->cacheItem, $subCache);
	}

	public function setInvalid()
	{
		$this->cacheItem->setInvalid();
	}

	public function readFromCache($subCache)
	{
		return $this->cacheItem->getValue($subCache);
	}

	public function writeToCache($subCache, $content)
	{
		$this->cacheItem->setValue($subCache, $content);
		f_DataCacheFileService::getInstance()->writeToCache($this->cacheItem);
	}

	public function getCachePath($subCache)
	{
		$path = f_DataCacheFileService::getInstance()->getCachePath($this->cacheItem, $subCache);
		return  $path;
	}

	public static function clearCacheById($id)
	{
		f_DataCacheFileService::getInstance()->clearCacheByNamespace($id);
	}

	static function commitClearByDocIds($docIds)
	{
		foreach ($docIds as $id)
		{
			f_DataCacheFileService::getInstance()->clearCacheByDocId($id);
		}
	}

	/**
	 * @param String $id
	 */
	public static function clear($id = null, $dispatch = true)
	{
		if ($id === null)
		{
			f_DataCacheFileService::getInstance()->clearAll();
		}
		else 
		{
			f_DataCacheFileService::getInstance()->clearCacheByNamespace($id);
		}
	}


	public final function clearSubCache($subCache, $dispatch = true)
	{
		f_DataCacheFileService::getInstance()->clearSubCache($this->cacheItem, $subCache, $dispatch);
	}

	/**
	 * This is the same as BlockCache::commitClear()
	 * but designed for the context of <code>register_shutdown_function()</code>,
	 * to be sure the correct umask is used.
	 */
	public static function shutdownCommitClear()
	{
		f_DataCacheFileService::getInstance()->shutdownCommitClear();
	}

	public static function commitClearDispatched($ids = null)
	{
		return true;
	}

	/**
	 */
	public static function commitClear()
	{
		return true;
	}

	public static function cleanExpiredCache()
	{
		f_DataCacheFileService::getInstance()->cleanExpiredCache();
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public static function clearCacheByModel($model)
	{
		f_DataCacheFileService::getInstance()->clearCacheByModel($model);
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public static function clearCacheByTag($tag)
	{
		f_DataCacheFileService::getInstance()->clearCacheByTag($tag);
	}
}