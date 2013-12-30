<?php
class f_MemcachedProvider
{
	private $memcachedInstance = null;
	
	public function __construct($config)
	{
		$this->memcachedInstance = new Memcached('memcachedConnection');

		// Check if memcached instance has no servers in it's pool yet (see http://www.php.net/manual/en/memcached.construct.php#93536)
		if (!count($this->memcachedInstance->getServerList()))
		{
			if ($this->memcachedInstance->addServer($config["server"]["host"], $config["server"]["port"]) === false)
			{
				Framework::error("MemcachedProvider: could not obtain memcache instance");
			}
		}
	}
	
	/**
	 * @return Boolean
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