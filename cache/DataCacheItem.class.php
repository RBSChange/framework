<?php
interface f_DataCacheItem
{
	/**
	 * @return Integer (timestamp) or null
	 */
	public function getCreationTime();
	
	/**
	 * @param Integer (timestamp) $timestamp
	 */
	public function setCreationTime($timestamp);

	/**
	 * @param Integer $seconds
	 */
	public function setTTL($seconds);
	
	/**
	 * @return Integer
	 */
	public function getTTL();

	/**
	 * @param Mixed $key
	 * @param Mixed (serializable) $value
	 */
	public function setValue($key, $value);
	
	/**
	 * @param String $key
	 * @return Mixed
	 */
	public function getValue($key);
	
	/**
	 * @param Array $key
	 */
	public function setValues($key);
	
	/**
	 * @return Array
	 */
	public function getValues();
	
	/**
	 * @param String $key
	 */
	public function setRegistrationPath($key);
	
	/**
	 * @return String
	 */
	public function getRegistrationPath();
	
	/**
	 * @param String $key
	 */
	public function setCachePath($key);
	
	/**
	 * @return String
	 */
	public function getCachePath();
	
	/**
	 * @return String
	 */
	public function getNamespace();
	
	/**
	 * @return String
	 */
	public function getKeyParameters();
	
	/**
	 * @return Array
	 */
	public function getPatterns();
	
	public function setInvalid();
	
	/**
	 * @param Boolean $isValid
	 */
	public function setValidity($isValid);
	
	/**
	 * @return Boolean
	 */
	public function isValid();
}
?>