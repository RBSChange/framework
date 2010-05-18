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
	private $curlHandle;
	private $data;
	private $timeout;

	public function __construct($url)
	{
		$this->url = $url;
		$this->setMethod(self::METHOD_GET);
		$this->initCurlHandle();
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

	private $headers = array();

	/**
	 * @return String
	 */
	public function execute()
	{
		$time = -microtime(true);
		$cachedQuery = null;
		if ($this->cache !== null)
		{
			$cachedQuery = $this->cache->get(md5($this->url));
			if ($cachedQuery !== null)
			{
				//curl_setopt($this->curlHandle, CURLOPT_HEADER, true);
				/*curl_setopt($this->curlHandle, CURLOPT_TIMEVALUE, $cachedQuery->getTime());
				curl_setopt($this->curlHandle, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);*/
				$this->headers[] = "If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T', $cachedQuery->getTime());
				//$this->headers[] = "If-Modified-Since: Thu, 06 May 2010 17:50:09 GMT";
			}
		}
		if ($this->getMethod() == self::METHOD_POST)
		{
			curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $this->data);
		}
			
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . " : " . $this->url . " data: " . $this->data);
		}
		$timeout = $this->getTimeout();
		if ($timeout > 0)
		{
			curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $this->getTimeout());
		}
			
		curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $this->headers);
		$data = curl_exec($this->curlHandle);
		$errno = curl_errno($this->curlHandle);
		$httpReturnCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
		curl_close($this->curlHandle);
		if ($errno)
		{
			throw new IndexException(__METHOD__ . " (URL = " . $this->url . ") failed with error number " . $errno);
		}

		$time += microtime(true);
		if ($this->cache !== null)
		{
			
			if ($cachedQuery !== null && $httpReturnCode == 304)
			{
				return $cachedQuery->getData();
			}
			// TODO: cache only if time > ...
			$cachedQuery = new indexer_CachedQuery($this->url, $data);
			$this->cache->store($cachedQuery);
		}
		return $data;
	}

	/**
	 * @param String $type
	 */
	public function setContentType($type)
	{
		$this->headers[] = "Content-Type: $type";

	}

	/**
	 * @param String $data
	 */
	public function setPostData($data)
	{
		$this->data = $data;
	}

	private function initCurlHandle()
	{
		$this->curlHandle = curl_init();
		$this->headers = array();
		if ($this->getMethod() == self::METHOD_GET)
		{
			curl_setopt($this->curlHandle, CURLOPT_POST, 0);
		}
		else
		{
			$this->headers[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
			curl_setopt($this->curlHandle, CURLOPT_POST, 1);
		}
		curl_setopt($this->curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT, self::DEFAULT_TIMEOUT);
		curl_setopt($this->curlHandle, CURLOPT_DNS_USE_GLOBAL_CACHE, 1);
		curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, 1);
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