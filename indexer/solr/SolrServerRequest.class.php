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
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param string $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @return string
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
		$request = $client->getRequest();
		$request->setUri(trim($this->url));
		if ($this->getMethod() == self::METHOD_POST)
		{
			$request->setMethod(\Zend\Http\Request::METHOD_POST);
			$request->getHeaders()->addHeaderLine('Content-Type',  $this->contentType);
			$request->setContent($this->data);
		}

		$response = $client->send();
		$httpReturnCode = $response->getStatusCode();
		return $response->getBody();
	}

	/**
	 * @param string $type
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;

	}

	/**
	 * @param string $data
	 */
	public function setPostData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param integer $secs
	 */
	public function setTimeout($secs)
	{
		$this->timeout = $secs;
	}
	/**
	 * @return integer
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