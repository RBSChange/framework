<?php

class f_web_oauth_Util
{
	static function encode($input)
	{
		return str_replace('%7E', '~', rawurlencode($input));
	}

}

class f_web_oauth_Request
{
	
	const METHOD_POST = "POST";
	const METHOD_GET = "GET";
	const HMAC_SHA1 = "HMAC-SHA1";
	const RSA_SHA1 = "rsa";
	
	private $mGetParameters = array();
	private $mPostParameters = array();
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
	
	public function sign()
	{
		$mergedRequest = array();
		$timestamp = time();
		$nonce = sha1(f_util_StringUtils::randomString(16, false) . $timestamp);
		if ($this->mMethod == self::METHOD_GET)
		{
			$this->mGetParameters['oauth_timestamp'] = $timestamp;
			$this->mGetParameters['oauth_nonce'] = $nonce;
			$this->mGetParameters['oauth_consumer_key'] = $this->mConsumer->getKey();
			$this->mGetParameters['oauth_version'] = "1.0";
			$this->mGetParameters['oauth_signature_method'] = $this->mSignatureClassInstance->getName();
			if ($this->mToken)
			{
				$this->mGetParameters['oauth_token'] = $this->mToken->getKey();
			}
		}
		else
		{
			$this->mPostParameters['oauth_timestamp'] = $timestamp;
			$this->mPostParameters['oauth_nonce'] = $nonce;
			$this->mPostParameters['oauth_consumer_key'] = $this->mConsumer->getKey();
			$this->mPostParameters['oauth_version'] = "1.0";
			$this->mPostParameters['oauth_signature_method'] = $this->mSignatureClassInstance->getName();
			if ($this->mToken)
			{
				$this->mPostParameters['oauth_token'] = $this->mToken->getKey();
			}
		}
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
		ksort($mergedRequest);
		foreach ($mergedRequest as $name => $value)
		{
			sort($value);
		}
		$parts = array();
		foreach ($mergedRequest as $name => $values)
		{
			foreach ($values as $value)
			{
				$parts[] = f_web_oauth_Util::encode($name) . '=' . f_web_oauth_Util::encode($value);
			}
		}
		$this->mBaseSignature = $this->mMethod . '&' . f_web_oauth_Util::encode($this->mSignatureRequestUrl) . '&' . f_web_oauth_Util::encode(implode('&', $parts));
		$finalSignature = $this->mSignatureClassInstance->buildSignatureFromRequest($this);
		if ($this->mMethod == self::METHOD_GET)
		{
			$this->mGetParameters['oauth_signature'] = $finalSignature;
		}
		else
		{
			$this->mPostParameters['oauth_signature'] = $finalSignature;
		}
	}
	
	public function getUrl()
	{
		$parts = $this->mUrlParts;
		$parts["query"] = str_replace('+', '%20', http_build_query($this->mGetParameters, '', '&'));
		return f_web_HttpLink::http_build_url($parts);
	}
	
	public function getAuthorizationHeader()
	{
		$params = ($this->mMethod == self::METHOD_GET ? $this->mGetParameters : $this->mPostParameters);
		$parts = array();
		$parts[] = 'oauth_token="' . f_web_oauth_Util::encode($this->mToken ? $this->mToken->getKey() : '') . '"';
		$parts[] = 'oauth_signature_method="' . f_web_oauth_Util::encode($this->mSignatureMethod) . '"';
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