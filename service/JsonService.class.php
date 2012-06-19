<?php
/**
 * @method JsonService getInstance()
 */
class JsonService extends change_BaseService
{
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