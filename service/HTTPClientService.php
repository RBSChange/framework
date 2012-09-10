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
	 * a client instance, please call getNewHttpClient directly
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
				case "\Zend\Http\Client\Adapter\Curl":
					$this->config['curloptions'][CURLOPT_TIMEOUT] = isset($params['timeout'])  ? $params['timeout'] : 60;
					$this->config['curloptions'][CURLOPT_CONNECTTIMEOUT] = 5;
					//  avoid dreaded 100-Continue statuses cf. http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
					$this->config['curloptions'][CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
					break;
				case "\Zend\Http\Client\Adapter\Socket":
				case "\Zend\Http\Client\Adapter\Proxy":
					$this->config['timeout'] = isset($params['timeout'])  ? $params['timeout'] : 60;
					break;
			}
		}
		return $this->config;
	}
	
	/**
	 * @return \Zend\Http\Client
	 */
	public function getNewHttpClient($params = array())
	{
		return new \Zend\Http\Client(null, $this->getHttpClientConfig($params));;
	}
}