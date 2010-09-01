<?php
class f_MemcacheProvider
{
	private $memcacheInstance = null;
	
	public function __construct($config)
	{
		$this->memcacheInstance = new Memcache();
		
		if ($this->memcacheInstance->pconnect($config["server"]["host"], $config["server"]["port"]) === false)
		{
			Framework::error("MemcacheProvider: could not obtain memcache instance");
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function isAvailable()
	{
		return ($this->memcacheInstance !== null);
	}
	
	public function close()
	{
		if ($this->isAvailable())
		{
			$this->memcacheInstance = null;
		}
	}
	
	/**
	 * @return Memcache
	 */
	public function getConnection()
	{
		return $this->memcacheInstance;	
	}
}