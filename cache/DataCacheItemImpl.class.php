<?php
class f_DataCacheItemImpl implements f_DataCacheItem
{
	const MAX_TIME_LIMIT = 86400;
	
	private $namespace;
	private $keyParameters;
	private $patterns;
	private $timeLimit;
	private $creationTime = null;
	private $isValid = false;
	private $registrationPath = null;
	private $cachePath = null;
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
		if ($this->creationTime !== null)
		{
			return $this->creationTime;
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
	 * @param String $key
	 * @return Mixed
	 */
	public function getValue($key)
	{
		if (isset($this->data[$key]) && $this->data[$key] != null)
		{
			return $this->data[$key];
		}
		return null;
	}
	
	/**
	 * @see f_DataCacheItem::getValues()
	 *
	 * @return Array
	 */
	public function getValues()
	{
		return $this->data;
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
	 * @see f_DataCacheItem::setCreationTime()
	 *
	 * @param Integer $timestamp
	 */
	public function setCreationTime($timestamp)
	{
		$this->creationTime = $timestamp;
	}
	
	public function setRegistrationPath($path)
	{
		$this->registrationPath = $path;
	}
	
	public function getRegistrationPath()
	{
		return $this->registrationPath;
	}
	
	public function setCachePath($path)
	{
		$this->cachePath = $path;
	}
	
	public function getCachePath()
	{
		return $this->cachePath;
	}
	
	/**
	 * @see f_DataCacheItem::setValue()
	 *
	 * @param String $key
	 * @param Mixed $value
	 */
	public function setValue($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function setValues($values)
	{
		$this->data = $values;
	}
	
	public function setInvalid()
	{
		$this->isValid = false;
	}
	
	/**
	 * @param Boolean $isValid
	 */
	public function setValidity($isValid)
	{
		$this->isValid = $isValid;
	}
	
	/**
	 * @return Boolean
	 */
	public function isValid()
	{
		return $this->isValid && $this->getCreationTime() !== null && ($this->getCreationTime()+$this->timeLimit > time());
	}
}
?>