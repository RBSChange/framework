<?php
/**
 * A utility class around arrays
 */
abstract class f_util_ArrayUtils
{
	/**
	 * @param array $array
	 * @return mixed the first element of the array or null
	 */
	public static function firstElement(&$array)
	{
		if (self::isEmpty($array))
		{
			return null;
		}
		return reset($array);
	}

	/**
	 * @param array $array
	 * @return mixed the last element of the array or null
	 */
	public static function lastElement(&$array)
	{
		if (self::isEmpty($array))
		{
			return null;
		}
		$lastElement = end($array);
		reset($array);
		return $lastElement;
	}

	/**
	 * @param array $array
	 * @return boolean True if $array is empty
	 */
	public static function isEmpty(&$array)
	{
		return $array === null || count($array) == 0;
	}

	/**
	 * @param array $array
	 * @return boolean True if $array is not empty
	 */
	public static function isNotEmpty(&$array)
	{
		return !self::isEmpty($array);
	}
	
	/**
	 * @param array $array
	 * @return mixed random element or null
	 */
	public static function randomElement($array)
	{
		if (self::isEmpty($array))
		{
			return null;
		}
		shuffle($array);
		return self::firstElement($array);
	}
}