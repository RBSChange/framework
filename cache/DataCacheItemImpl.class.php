<?php
class f_DataCacheItemImpl implements f_DataCacheItem
{
	const MAX_TIME_LIMIT = 86400;
	
	private $namespace;
	private $keyParameters;
	private $patterns;
	private $timeLimit;
	private $data;
		
	/**
	 * @param String $namespace
	 * @param Mixed $keyParameters
	 * @param Array $patterns
	 */
	public function __construct($namespace, $keyParameters, $patterns)
	{
		$this->namespace = str_replace(':', '_', $namespace);
		$this->keyParameters = md5(serialize($keyParameters));
		$this->patterns = $patterns;
		
		$this->setTTL(self::MAX_TIME_LIMIT);
		foreach ($patterns as $pattern)
		{
			if (f_util_StringUtils::beginsWith($pattern, "ttl/"))
			{
				$this->setTTL(intval(substr($pattern, 5)));	
			}
		}
		
		$this->data = array();
	}
	
	/**
	 * @see f_DataCacheItem::getCreationTime()
	 *
	 * @return Integer (timestamp) or null
	 */
	public function getCreationTime()
	{
		if (isset($this->data["creationTime"]) && $this->data["creationTime"] != null)
		{
			return $this->data["creationTime"];
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	/**
	 * @return String
	 */
	public function getKeyParameters()
	{
		return $this->keyParameters;
	}
	
	/**
	 * @return Integer
	 */
	public function getTTL()
	{
		return $this->timeLimit;
	}
	
	/**
	 * @return Array
	 */
	public function getPatterns()
	{
		return $this->patterns;
	}
	
	/**
	 * @see f_DataCacheItem::getValue()
	 *
	 * @param String $key (optional)
	 * @return Mixed
	 */
	public function getValue($key = null)
	{
		if ($key === null)
		{
			return $this->data;
		}
		if (isset($this->data[$key]) && $this->data[$key] != null)
		{
			return $this->data[$key];
		}
		return null;
	}
	
	/**
	 * @see f_DataCacheItem::setTTL()
	 *
	 * @param Integer $seconds
	 */
	public function setTTL($seconds)
	{
		if ($seconds <= self::MAX_TIME_LIMIT)
		{
			$this->timeLimit = $seconds;
		}
		$this->timeLimit = self::MAX_TIME_LIMIT;
	}
	
	/**
	 * @see f_DataCacheItem::setValue()
	 *
	 * @param Mixed $key
	 * @param Mixed $value
	 */
	public function setValue($key, $value = null)
	{
		if ($value === null)
		{
			$this->data = $key;
		}
		$this->data[$key] = $value;
	}
	
	public function setInvalid()
	{
		$this->data["isValid"] = false;
	}
	
	/**
	 * @return Boolean
	 */
	public function isValid()
	{
		return (isset($this->data["isValid"]) && $this->data["isValid"] 
					&& ($this->getCreationTime()+$this->timeLimit > time()));
	}
}
?>