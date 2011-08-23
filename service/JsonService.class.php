<?php
/**
 * @package framework.service
 * @see Services_JSON ; 100% PHP based implementation for those that do not run json extension
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
			if (function_exists('json_encode'))
			{
				self::$instance = new self();
			}
			else
			{
				self::$instance = new Services_JSON(SERVICES_JSON_IN_ARR);
			}
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