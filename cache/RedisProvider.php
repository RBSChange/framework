<?php
class f_RedisProvider
{
	private static $redisInstance = null;
	private static $database = null;
	
	public function __construct($config)
	{
		if (self::$redisInstance === null || self::$database != $config["server"]["database"])
		{
			$redis = new Redis();	
			
			$con = $redis->connect($config["server"]["host"], $config["server"]["port"]);
			if ($con)
			{
				if (isset($config["authentication"]))
				{
					$redis->auth($config["authentication"]["password"]);
				}
				
				$select = $redis->select($config["server"]["database"]);
				if ($select)
				{
					self::$redisInstance = $redis;
					self::$database = $config["server"]["database"];
				}
			}
			
			if (self::$redisInstance === null)
			{
				Framework::debug("RedisProvider : could not obtain redis instance");
			}
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function isAvailable()
	{
		return (self::$redisInstance !== null);
	}
	
	public function close()
	{
		if ($this->isAvailable())
		{
			self::$redisInstance = null;
		}
	}
	
	/**
	 * @return Memcache
	 */
	public function getConnection()
	{
		return self::$redisInstance;	
	}
}