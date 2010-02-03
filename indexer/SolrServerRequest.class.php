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
		$this->initCurlHandle();
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
	 * @return String
	 */
	public function execute()
	{		
		if (Framework::isDebugEnabled())
		{
			$time = -microtime(true);
		}
		if ($this->getMethod() == self::METHOD_POST)
		{
			curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $this->data);			
		}
		else 
		{
			 curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, "");
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
		$data = curl_exec($this->curlHandle);
		$errno = curl_errno($this->curlHandle);
		curl_close($this->curlHandle);
		if ($errno)
		{	
			throw new IndexException(__METHOD__ . " (URL = " . $this->url . ") failed with error number " . $errno);
		}
		if (Framework::isDebugEnabled())
		{
			$time += microtime(true);
			Framework::debug(__CLASS__ . ': ' . $this->getMethod() . ' Request on ' . $this->url . ' took ' . $time . ' seconds.');
		}
		return $data;
	}
	
	/**
	 * @param String $type
	 */
	public function setContentType($type)
	{
		if ($this->curlHandle !== null)
		{
			curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: $type"));	
		}
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
		if ($this->getMethod() == self::METHOD_GET)
		{ 
			curl_setopt($this->curlHandle, CURLOPT_POST, 0);
		}
		else 
		{
			$this->setContentType("application/x-www-form-urlencoded; charset=UTF-8");
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