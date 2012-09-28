<?php
interface f_DataCacheItem
{
	/**
	 * @return integer (timestamp) or null
	 */
	public function getCreationTime();

	/**
	 * @param integer (timestamp) $timestamp
	*/
	public function setCreationTime($timestamp);

	/**
	 * @param integer $seconds
	*/
	public function setTTL($seconds);

	/**
	 * @return integer
	*/
	public function getTTL();

	/**
	 * @param Mixed $key
	 * @param Mixed (serializable) $value
	*/
	public function setValue($key, $value);

	/**
	 * @param string $key
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
	 * @param string $key
	*/
	public function setRegistrationPath($key);

	/**
	 * @return string
	*/
	public function getRegistrationPath();

	/**
	 * @param string $key
	*/
	public function setCachePath($key);

	/**
	 * @return string
	*/
	public function getCachePath();

	/**
	 * @return string
	*/
	public function getNamespace();

	/**
	 * @return string
	*/
	public function getKeyParameters();

	/**
	 * @return Array
	*/
	public function getPatterns();

	public function setInvalid();

	/**
	 * @param boolean $isValid
	*/
	public function setValidity($isValid);

	/**
	 * @return boolean
	*/
	public function isValid();
}