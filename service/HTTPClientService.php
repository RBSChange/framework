<?php
/**
 * @method change_HttpClientService getInstance()
 */
class change_HttpClientService extends change_BaseService
{	
	/**
	 * @var array 
	 */
	private $config;

	/**
	 * Return a Zend Framework compatible configuration for HTTP Clients - if you want
	 * a client instance, please call getNewHttpClient directyly
 	 * 
	 * @return array
	 */
	public function getHttpClientConfig($params = array())
	{
		if ($this->config === null)
		{
			$this->config = Framework::getHttpClientConfig();
			switch($this->config['adapter'])
			{
				case "Zend_Http_Client_Adapter_Curl":
					$this->config['curloptions'][CURLOPT_TIMEOUT] = isset($params['timeout'])  ? $params['timeout'] : 60;
					$this->config['curloptions'][CURLOPT_CONNECTTIMEOUT] = 5;
					break;
				case "Zend_Http_Client_Adapter_Socket":
				case "Zend_Http_Client_Adapter_Proxy":
					$this->config['timeout'] = isset($params['timeout'])  ? $params['timeout'] : 60;
					break;
			}
		}
		return $this->config;
	}
	
	/**
	 * @return Zend_Http_Client
	 */
	public function getNewHttpClient($params = array())
	{
		$clientInstance = new Zend_Http_Client(null, $this->getHttpClientConfig($params));
		//  avoid dreaded 100-Continue statuses cf. http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
		if ($clientInstance->getAdapter() instanceof Zend_Http_Client_Adapter_Curl)
		{
			$clientInstance->setHeaders('Expect:');
		}
		return $clientInstance;
	}
}