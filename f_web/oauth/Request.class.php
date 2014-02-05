<?php
class f_web_oauth_Util
{
	/**
	 * @param string $input
	 * @return string
	 */
	static function encode($input)
	{
		return str_replace('%7E', '~', rawurlencode($input));
	}
	
	/**
	 * @param string $input
	 * @return string
	 */
	static function decode($input)
	{
		return rawurldecode($input);
	}
	
	/**
	 * @param string $signName
	 * @return string
	 */
	static function getSignatureClassNameFromSignatureName($signName)
	{
		$signatureClassName = 'f_web_oauth_Signature';
		foreach (explode('-', $signName) as $parts)
		{
			$signatureClassName .= ucfirst(strtolower($parts));
		}
		return $signatureClassName;
	}
	
	/**
	 * @return array
	 */
	static function parseOauthAutorizationHeader()
	{
		if (!isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			return array();
		}
		$rawHeader = $_SERVER['HTTP_AUTHORIZATION'];
		if (strpos($rawHeader, 'OAuth') !== 0)
		{
			return array();
		}
		$headers = array();
		foreach (explode(',', trim(substr($rawHeader, 5))) as $part)
		{
			$firstEqual = strpos($part, '=');
			$name = self::decode(substr($part, 0, $firstEqual));
			if (strpos($name, 'oauth_') === 0)
			{
				$value = substr($part, $firstEqual+1);
				if (strlen($value) > 1 && $value[0] == '"' && $value[strlen($value)-1] == '"')
				{
					$value = substr($value, 1, strlen($value)-2);
				}
				$headers[$name] = self::decode($value);
			}
		}
		return $headers;
	}
	
	/**
	 * @param f_web_oauth_Request $request
	 * @param array $parameters
	 */
	static function setParametersFromArray($request, $parameters)
	{
		foreach ($parameters as $name => $value)
		{
			$request->setParameter($name, $value);
		}
	}
}
/**
 * @deprecated
 */
class f_web_oauth_Request
{
	
	const METHOD_POST = "POST";
	const METHOD_GET = "GET";
	const HMAC_SHA1 = "HMAC-SHA1";
	const RSA_SHA1 = "rsa";
	
	private $mGetParameters = array();
	private $mPostParameters = array();
	private $mOauthParameters = array();
	private $mIncomingHeaders = array();
	/**
	 * @var unknown_type
	 */
	private $mMethod;
	/**
	 * @var unknown_type
	 */
	private $mSignatureClassInstance;
	/**
	 * @var unknown_type
	 */
	private $mSignatureRequestUrl;
	/**
	 * @var f_web_oauth_Consumer
	 */
	private $mConsumer;
	private $mUrlParts;
	/**
	 * @var f_web_oauth_Token
	 */
	private $mToken = null;
	private $isSigned = false;
	
	private $mBaseSignature = "";
	
	private $mSignature;
	/**
	 * @param String $url
	 * @param f_web_oauth_Consumer $consumer
	 */
	public function __construct($url, f_web_oauth_Consumer $consumer, $method = self::METHOD_GET, $signatureClassName = 'f_web_oauth_SignatureHmacSha1')
	{
		$this->mMethod = $method;
		$this->mSignatureClassInstance = new $signatureClassName();
		if (!($this->mSignatureClassInstance instanceof f_web_oauth_Signature))
		{
			throw new Exception("Signature class must implement f_web_oauth_Signature");
		}
		$this->mConsumer = $consumer;
		
		$parts = parse_url($url);
		$this->mUrlParts = $parts;
		if (!isset($parts['scheme']))
		{
			throw new Exception("Invalid url : $url");
		}
		if (!isset($parts['host']))
		{
			throw new Exception("Invalid url : $url");
		}
		if (!isset($parts['path']))
		{
			throw new Exception("Invalid url : $url");
		}
		$parts['scheme'] = strtolower($parts['scheme']);
		$parts['host'] = strtolower($parts['host']);
		if (isset($parts['query']))
		{
			parse_str($parts['query'], $this->mGetParameters);
			unset($parts['query']);
		}
		if (isset($parts['fragment']))
		{
			unset($parts['fragment']);
		}
		if (isset($parts['port']))
		{
			if (($parts['scheme'] == 'http' && $parts['port'] == "80") || ($parts['scheme'] == 'https' && $parts['port'] == "443"))
			{
				unset($parts['port']);
			}
		}
		$this->mSignatureRequestUrl = f_web_HttpLink::http_build_url($parts);
	}
	/**
	 * @return String
	 */
	public function getBaseSignature()
	{
		return $this->mBaseSignature;
	}
	
	/**
	 * @return f_web_oauth_Consumer
	 */
	public function getConsumer()
	{
		return $this->mConsumer;
	}
	
	/**
	 * @param f_web_oauth_Consumer $mConsumer
	 */
	public function setConsumer($mConsumer)
	{
		$this->mConsumer = $mConsumer;
	}
	
	/**
	 * @return f_web_oauth_Token
	 */
	public function getToken()
	{
		return $this->mToken;
	}
	
	/**
	 * @param f_web_oauth_Token $mToken
	 */
	public function setToken($mToken)
	{
		$this->mToken = $mToken;
	}
	
	public function setParameter($name, $value)
	{
		if (self::METHOD_POST == $this->mMethod)
		{
			$this->mPostParameters[$name] = $value;
		}
		else
		{
			$this->mGetParameters[$name] = $value;
		}
	}
	
	private function buildOauthParameters()
	{
		$timestamp = time();
		$nonce = sha1(f_util_StringUtils::randomString(16, false) . $timestamp);
		$this->mOauthParameters['oauth_timestamp'] = $timestamp;
		$this->mOauthParameters['oauth_nonce'] = $nonce;
		$this->mOauthParameters['oauth_consumer_key'] = $this->mConsumer->getKey();
		$this->mOauthParameters['oauth_version'] = "1.0";
		$this->mOauthParameters['oauth_signature_method'] = $this->mSignatureClassInstance->getName();
		if ($this->mToken)
		{
			$this->mOauthParameters['oauth_token'] = $this->mToken->getKey();
		}
		
	}
	

	public function sign()
	{
		$this->buildOauthParameters();
		$signature = $this->getSignature();
		$this->mOauthParameters['oauth_signature'] = $signature;
	}
	
	public function getSignature()
	{
		$mergedRequest = array();	
		foreach ($this->mGetParameters as $name => $value)
		{
			if ($name == "oauth_signature")
			{
				continue;
			}
			$mergedRequest[$name] = array($value);
		}
		
		foreach ($this->mPostParameters as $name => $value)
		{
			if ($name == "oauth_signature")
			{
				continue;
			}
			if (!isset($mergedRequest[$name]))
			{
				$mergedRequest[$name] = array();
			}
			$mergedRequest[$name][] = $value;
		}
		
		foreach ($this->mOauthParameters as $name => $value)
		{
			if ($name == "oauth_signature")
			{
				continue;
			}
			if (!isset($mergedRequest[$name]))
			{
				$mergedRequest[$name] = array();
			}
			$mergedRequest[$name][] = $value;
		}
		
		ksort($mergedRequest);
		parse_str(http_build_query($mergedRequest, "", "&"), $mergedRequest);
		foreach ($mergedRequest as $name => $value)
		{
			sort($value);
		}
		$parts = array();
		foreach ($mergedRequest as $name => $values)
		{
			foreach ($values as $value)
			{
				$parts[] = $this->encodeValue($name, $value);
			}
		}
		$this->mBaseSignature = $this->mMethod . '&' . f_web_oauth_Util::encode($this->mSignatureRequestUrl) . '&' . f_web_oauth_Util::encode(implode('&', $parts));
		return $this->mSignatureClassInstance->buildSignatureFromRequest($this);
	}
	
	private function encodeValue($name, $value)
	{	
		if (!is_array($value))
		{
			return f_web_oauth_Util::encode($name) . '=' . f_web_oauth_Util::encode($value);
		}
		$subvalues = array();
		foreach ($value as $key => $val)
		{
			$subvalues[] = $this->encodeValue($name.'['.$key.']', $val);
		}
		return implode('&', $subvalues);
	}
	
	public function getUrl($includeOauthParamsInGet = false)
	{
		$parts = $this->mUrlParts;
		if ($includeOauthParamsInGet)
		{
			$getParameters = array_merge($this->mGetParameters, $this->mOauthParameters);
		}
		else
		{
			$getParameters = $this->mGetParameters;
		}
		$parts["query"] = str_replace('+', '%20', http_build_query($getParameters, '', '&'));
		return f_web_HttpLink::http_build_url($parts);
	}
	
	public function getAuthorizationHeader()
	{
		$params = $this->mOauthParameters;
		$parts = array();
		$parts[] = 'oauth_token="' . f_web_oauth_Util::encode($this->mToken ? $this->mToken->getKey() : '') . '"';
		$parts[] = 'oauth_signature_method="' . f_web_oauth_Util::encode($this->mSignatureClassInstance->getName()) . '"';
		$parts[] = 'oauth_signature="' . f_web_oauth_Util::encode($params['oauth_signature']) . '"';
		$parts[] = 'oauth_consumer_key="' . f_web_oauth_Util::encode($params['oauth_consumer_key']) . '"';
		$parts[] = 'oauth_timestamp="' . f_web_oauth_Util::encode($params['oauth_timestamp']) . '"';
		$parts[] = 'oauth_nonce="' . f_web_oauth_Util::encode($params['oauth_nonce']) . '"';
		$parts[] = 'oauth_version="' . f_web_oauth_Util::encode($params['oauth_version']) . '"';
		return 'Authorization: OAuth ' . implode(',', $parts);
	}
	
	public function getMethod()
	{
		return $this->mMethod;
	}
	
	public function getPostParameters()
	{
		return $this->mPostParameters;
	}
}
