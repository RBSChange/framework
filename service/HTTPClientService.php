<?php
/**
 * @package framework.service
 */
class HTTPClientService extends BaseService
{	
	/**
	 * @var HTTPClientService
	 */
	private static $instance;

	/**
	 * @return HTTPClientService
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
	 * @return HTTPClient
	 */
	public function getNewHTTPClient($acceptCookies = true)
	{
		return new HTTPClient($acceptCookies);
	}
}

class HTTPClient
{
	/**
	 * @var Integer
	 */
	private $httpReturnCode = -1;
	
	/**
	 * @var Integer
	 */
	private $httpHeaders = array();
	/**
	 * @var resource
	 */
	private $curlResource;
	
	private $followRedirects = true;
	private $timeOut = 60;
	private $referer = '';
	
	private $proxyHost;
	private $proxyPort;
	
	private $acceptCookies;
	private $cookieName;
		
	public function __construct($acceptCookies = true)
	{
		$this->curlResource = curl_init();
		$this->setOption(CURLOPT_RETURNTRANSFER, true);
		
		// Cookies handling.
		$this->acceptCookies = $acceptCookies;
		if ($this->acceptCookies)
		{
			$this->cookieName = uniqid('./.');
			$this->setOptions(array(
				CURLOPT_COOKIEJAR => $this->cookieName,
				CURLOPT_COOKIEFILE => $this->cookieName,
			));
		}
		
		// Set default proxy.
		if (defined('OUTGOING_HTTP_PROXY_HOST') && OUTGOING_HTTP_PROXY_HOST
			 && defined('OUTGOING_HTTP_PROXY_PORT') && OUTGOING_HTTP_PROXY_PORT)
		{
			$this->setProxy(OUTGOING_HTTP_PROXY_HOST, OUTGOING_HTTP_PROXY_PORT);
		}
	}
	
	public function __destruct()
	{
		if ($this->curlResource !== null)
		{
			$this->close();
		}
	}
	
	/**
	 * @return void
	 */
	public function close()
	{
		curl_close($this->curlResource);
		if ($this->acceptCookies && file_exists($this->cookieName))
		{
			@unlink($this->cookieName);
		}
		$this->curlResource = null;
	}
	
	/**
	 * @param String $url
	 * @return String
	 */
	public function get($url)
	{
		$this->setOption(CURLOPT_POSTFIELDS, null);
		$this->setOption(CURLOPT_POST, false);
		return $this->execute($url);
	}
	
	/**
	 * @param String $url
	 * @param Array<String, String> $params
	 * @return String
	 */
	public function post($url, $params)
	{
		$query = http_build_query($params, null , '&');		
		$this->setOption(CURLOPT_POSTFIELDS, $query);
		$this->setOption(CURLOPT_POST, true);
		return $this->execute($url);
	}
	
	/**
	 * @param String $host
	 * @param Integer $port
	 */
	public function setProxy($host, $port)
	{
		$this->proxyHost = $host;
		$this->proxyPort = $port;
	}
	
	/**
	 * @param Boolean $value
	 */
	public function setFollowRedirects($value)
	{
		$this->followRedirects = $value;
	}
	
	/**
	 * @param Integer $value
	 */
	public function setTimeOut($value)
	{
		$this->timeOut = $value;
	}
	
	/**
	 * @param string $referer
	 */
	public function setReferer($referer)
	{
		$this->referer = $referer;
	}
		
	/**
	 * @param Integer $option
	 * @param mixed $value
	 * @see curl_setopt() for available options.
	 */
	public function setOption($option, $value)
	{
		curl_setopt($this->curlResource, $option, $value);
	}
	
	/**
	 * @param Array<Integer, mixed> $options
	 * @see curl_setopt() for available options.
	 * @see curl_setopt_array()
	 */
	public function setOptions($options)
	{
		curl_setopt_array($this->curlResource, $options);
	}
	
	/**
	 * @param String $url
	 * @param String $postFields
	 * @return String
	 */
	private function execute($url = null)
	{
		if ($this->proxyHost && $this->proxyPort)
		{
			$this->setOption(CURLOPT_PROXY, $this->proxyHost.':'.$this->proxyPort);
		}
		
		$this->setOptions(array(
			CURLOPT_REFERER => $this->referer,
			CURLOPT_TIMEOUT => $this->timeOut,
			CURLOPT_FOLLOWLOCATION => $this->followRedirects,
			CURLOPT_URL => $url,
			CURLOPT_HEADERFUNCTION => array($this, 'readHeaders')
		));
		
		$data = curl_exec($this->curlResource);
		$errno = curl_errno($this->curlResource);
		if ($errno)
		{	
			Framework::error(__METHOD__ . ': curl_errno : ' . $errno);
		}
		$this->httpReturnCode = curl_getinfo($this->curlResource, CURLINFO_HTTP_CODE);
		return $data;
	}
	
	private function readHeaders($ch, $header)
	{
		$trimmedHeader = trim($header);
		if (f_util_StringUtils::isNotEmpty($trimmedHeader))
		{
			$this->httpHeaders[] = $trimmedHeader;
		}
		return strlen($header);
	}
	
	/**
	 * @return Integer
	 */
	public function getHTTPReturnCode()
	{
		return $this->httpReturnCode;
	}
	
	/**
	 * @return Array
	 */
	public function getHTTPHeaders()
	{
		return $this->httpHeaders;
	}
}