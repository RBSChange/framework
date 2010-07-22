<?php
interface f_DataCacheItem
{
	/**
	 * @return Integer (timestamp) or null
	 */
	public function getCreationTime();

	/**
	 * @param Integer $seconds
	 */
	public function setTTL($seconds);

	/**
	 * @param Mixed $key
	 * @param Mixed (serializable) $value
	 */
	public function setValue($key, $value);
	
	/**
	 * @param String $key (optional)
	 * @return Mixed
	 */
	public function getValue($key);
	
	/**
	 * @return String
	 */
	public function getNamespace();
	
	/**
	 * @return String
	 */
	public function getKeyParameters();
	
	/**
	 * @return Integer
	 */
	public function getTTL();
	
	/**
	 * @return Array
	 */
	public function getPatterns();
}
?>