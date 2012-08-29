<?php
class f_MemcachedProvider
{
	private $memcachedInstance = null;
	
	public function __construct($config)
	{
		$this->memcachedInstance = new Memcached('memcachedConnection');
		
		if ($this->memcachedInstance->addServer($config["server"]["host"], $config["server"]["port"]) === false)
		{
			Framework::error("MemcachedProvider: could not obtain memcache instance");
		}
	}
	
	/**
	 * @return boolean
	 */
	public function isAvailable()
	{
		return ($this->memcachedInstance !== null);
	}
	
	public function close()
	{
		if ($this->isAvailable())
		{
			$this->memcachedInstance = null;
		}
	}
	
	/**
	 * @return Memcache
	 */
	public function getConnection()
	{
		return $this->memcachedInstance;	
	}
}