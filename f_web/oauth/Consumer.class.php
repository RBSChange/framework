<?php
class f_web_oauth_Consumer
{
	/**
	 * @var String
	 */
	private $key;
	
	/**
	 * @var String
	 */
	private $secret;
	
	public function __construct($key, $secret)
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
}