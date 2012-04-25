<?php
/**
 * @deprecated
 */
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
	
	/**
	 * @var String
	 */
	private $callback;
	
	public function __construct($key, $secret = null)
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