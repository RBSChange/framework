<?php
interface indexer_Cache
{
	/**
	 * @param $queryId
	 * @return indexer_CachedQuery
	 */
	function get($queryId);
	
	/**
	 * @param indexer_CachedQuery $cachedQuery
	 */
	function store($cachedQuery);
	
	function clear();
}

class indexer_DataCache implements indexer_Cache
{
	/**
	 * @param $queryId
	 * @return indexer_CachedQuery
	 */
	function get($queryId)
	{
		$cacheItem = f_DataCacheService::getInstance()->readFromCache("indexer_Cache", $queryId, array());
		$cachedQuery = unserialize($cacheItem->getValue("cachedQuery"));
		if ($cachedQuery !== false)
		{
			return $cachedQuery;
		}
		return null;
	}
	
	/**
	 * @param indexer_CachedQuery $cachedQuery
	 */
	function store($cachedQuery)
	{
		$cs = f_DataCacheService::getInstance();
		$cacheItem = $cs->getNewCacheItem("indexer_Cache", $cachedQuery->getId(), array());
		$cacheItem->setValue("cachedQuery", serialize($cachedQuery));
		$cs->writeToCache($cacheItem);
	}
	
	function clear()
	{
		f_DataCacheService::getInstance()->clearCacheByNamespace("indexer_Cache");
	}
}

class indexer_CachedQuery
{
	private $time;
	private $data;
	private $url;
	private $id;
	
	function __construct($url, $data = null)
	{
		$this->time = time();
		$this->data = $data;
		$this->url = $url;
		$this->id = md5($url);
	}
	
	function setData($data)
	{
		$this->data = $data;
	}
	
	function getTime()
	{
		return $this->time;
	}
	
	function getData()
	{
		return $this->data;
	}
	
	function getId()
	{
		return $this->id; 
	}
}