<?php
/**
 * @package framework.service
 */
class JsonService extends BaseService
{
	/**
	 * the singleton instance
	 * @var JsonService
	 */
	private static $instance = null;

	/**
	 * @return JsonService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @param mixed $value
	 * @return string
	 */
	public function encode($value)
	{
		return json_encode($value);
	}
	
	/**
	 * Objects are always decoded as associative arrays 
	 * @param string $string
	 * @return mixed
	 */
	public function decode($string)
	{
		return json_decode($string, true);
	}
}