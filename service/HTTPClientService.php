<?php

class change_HttpClientService extends BaseService
{
	/**
	 * @var change_HttpClientService
	 */
	private static $instance;
	
	/**
	 * @var array 
	 */
	private $config;

	/**
	 * @return change_HttpClientService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * Return a Zend Framework compatible configuration for HTTP Clients - if you want
	 * a client instance, please call getNewHttpClient directyly
 	 * 
	 * @return array
	 */
	public function getHttpClientConfig()
	{
		if ($this->config === null)
		{
			$this->config = Framework::getHttpClientConfig();
			switch($this->config['adapter'])
			{
				case "Zend_Http_Client_Adapter_Curl":
					$this->config['curloptions'][CURLOPT_RETURNTRANSFER] = true;
					$this->config['curloptions'][CURLOPT_TIMEOUT] = 60;
					$this->config['curloptions'][CURLOPT_CONNECTTIMEOUT] = 5;
					$this->config['curloptions'][CURLOPT_FOLLOWLOCATION] = 1;
					break;
				case "Zend_Http_Client_Adapter_Socket":
				case "Zend_Http_Client_Adapter_Proxy":
					$this->config['curloptions']['timeout'] = 60;
					break;
			}
		}
		return $this->config;
	}
	
	/**
	 * @return Zend_Http_Client
	 */
	public function getNewHttpClient()
	{
		return new Zend_Http_Client(null, $this->getHttpClientConfig());
	}
}