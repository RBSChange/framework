<?php
/**
 * @package framework.indexer
 */
class indexer_SolrServerRequest
{
	const METHOD_GET = "GET";
	const METHOD_POST = "POST";
	const DEFAULT_CONNECTION_TIMEOUT = 60;
	const DEFAULT_TIMEOUT = 60;

	/**
	 * @var String
	 */
	private $url;

	/**
	 * @var String
	 */
	private $method;
	private $data;
	private $timeout;
	private $contentType;
	
	public function __construct($url)
	{
		$this->url = $url;
		$this->setMethod(self::METHOD_GET);
	}
	/**
	 * @return String
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param String $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @var indexer_Cache
	 */
	private $cache;

	/**
	 * @param indexer_Cache $cache
	 */
	public function setCache($cache)
	{
		$this->cache = $cache;
	}


	/**
	 * @return String
	 */
	public function execute()
	{
		$timeout = $this->getTimeout();
		if ($timeout > 0)
		{
			$config = array('timeout' => $timeout);
		}
		else
		{
			$config = array();
		}
		
		$client = change_HttpClientService::getInstance()->getNewHttpClient($config);
		$client->setUri(trim($this->url));
		if ($this->getMethod() == self::METHOD_POST)
		{
			$client->setMethod(Zend_Http_Client::POST);
			$client->setRawData($this->data, $this->contentType);
			$client->setHeaders('Content-Type: ' . $this->contentType);
		}
		$cachedQuery = null;
		if ($this->cache !== null)
		{
			$cachedQuery = $this->cache->get(md5($this->url));
			if ($cachedQuery !== null)
			{
				$client->setHeaders("If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T', $cachedQuery->getTime()));
			}
		}
		
		$request = $client->request();
		$httpReturnCode = $request->getStatus();
		
		if ($this->cache !== null)
		{
			if ($cachedQuery !== null && $httpReturnCode == 304)
			{
				return $cachedQuery->getData();
			}
			$cachedQuery = new indexer_CachedQuery($this->url, $data);
			$this->cache->store($cachedQuery);
		}
		return $request->getBody();
	}

	/**
	 * @param String $type
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;

	}

	/**
	 * @param String $data
	 */
	public function setPostData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param Integer $secs
	 */
	public function setTimeout($secs)
	{
		$this->timeout = $secs;
	}
	/**
	 * @return Integer
	 */
	public function getTimeout()
	{
		if ($this->timeout === null || !is_int($this->timeout))
		{
			return self::DEFAULT_TIMEOUT;
		}
		return $this->timeout;
	}
}