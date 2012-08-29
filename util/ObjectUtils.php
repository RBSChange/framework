<?php
abstract class f_util_ObjectUtils
{
	/**
	 * @param mixed $value1
	 * @param mixed $value2
	 * @return boolean
	 */
	static function equals($value1, $value2)
	{
		if ($value1 == $value2)
		{
			return true;
		}
		if (is_array($value1) && is_array($value2))
		{
			return f_util_ArrayUtils::equals($value, $value2);	
		}
		if (is_object($value1) && is_object($value2))
		{
			return get_class($value1) == get_class($value2) && method_exists($value1, "getId")
				&& $value1->getId() == $value2->getId();
		}
		return false;
	}
	
	/**
	 * @param mixed $value
	 * @return boolean
	 */
	static function isEmpty($value)
	{
		if ($value === null)
		{
			return true;
		}
		if (is_string($value))
		{
			return f_util_StringUtils::isEmpty($value);
		}
		if (is_array($value))
		{
			return f_util_ArrayUtils::isEmpty($value);
		}
		return false;
	}
}