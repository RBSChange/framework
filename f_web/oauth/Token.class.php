<?php
/**
 * @deprecated
 */
class f_web_oauth_Token
{
	const TOKEN_NOT_AUTHORIZED = 0;
	const TOKEN_AUTHORIZED = 1;
	const TOKEN_ACCESS = 2;
	/**
	 * @var String
	 */
	private $key;
	
	/**
	 * @var String
	 */
	private $secret;
	
	/**
	 * @var String
	 */
	private $verificationCode;
	
	/**
	 * @var Int
	 */
	private $status = self::TOKEN_NOT_AUTHORIZED;
	
	/**
	 * @var String
	 */
	private $callback;
	

	/**
	 * @return String
	 */
	public function getVerificationCode()
	{
		return $this->verificationCode;
	}
	
	/**
	 * @param String $verificationCode
	 */
	public function setVerificationCode($verificationCode)
	{
		$this->verificationCode = $verificationCode;
	}
	
	public function __construct($key = null, $secret = null)
	{
		$this->setSecret($secret);
		$this->setKey($key);
	}
	/**
	 * @return String
	 */
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * @param String $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}
	
	/**
	 * @return String
	 */
	public function getSecret()
	{
		return $this->secret;
	}
	
	/**
	 * @param String $secret
	 */
	public function setSecret($secret)
	{
		$this->secret = $secret;
	}
	/**
	 * @return Int
	 */
	public function getStatus()
	{
		return $this->status;
	}
	
	/**
	 * @param Int $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	/**
	 * @return String
	 */
	public function getCallback()
	{
		return $this->callback;
	}
	
	/**
	 * @param String $callback
	 */
	public function setCallback($callback)
	{
		$this->callback = $callback;
	}
}