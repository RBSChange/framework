<?php
abstract class f_util_Convert
{
	/**
	 * Converts the given $value into a boolean.
	 * Positive numbers greater than 0 return true, 0 or negative numbers return false.
	 * The string 'true' returns true (case INsensitive).
	 * The string 'false' returns false (case INsensitive).
	 * If a value cannot be converted, this method returns false.
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public static function toBoolean($value)
	{
		if (is_bool($value))
		{
			return $value;
		}
		else if (strcasecmp($value, 'true') === 0)
		{
			return true;
		}
		else if (strcasecmp($value, 'false') === 0)
		{
			return false;
		}
		else if (is_numeric($value))
		{
			return $value > 0;
		}
		return false;
	}

	/**
	 * @param mixed $value
	 * @return f_persistentdocument_PersistentDocument | null
	 */
	static function toDocument($value)
	{
		if (is_numeric($value))
		{
			return DocumentHelper::getDocumentInstance($value);
		}
		if ($value instanceof f_persistentdocument_PersistentDocument)
		{
			return $value;
		}
		return null;
	}

	/**
	 * Converts the given $value into an integer.
	 * If a value cannot be converted, this method returns 0.
	 *
	 * @param mixed $value
	 * @return integer
	 */
	public static function toInteger($value)
	{
		if (null === $value)
		{
			return null;
		}
		if (is_int($value))
		{
			return $value;
		}
		if (is_numeric($value))
		{
			$value = preg_replace('/^0*/', '', $value);
			$intVal = intval($value);
			if (is_string($value) && strval($intVal) === $value)
			{
				return $intVal;
			}
		}
		return 0;
	}

	/**
	 * Converts the given $value into an floating point number.
	 * If a value cannot be converted, this method returns 0.0.
	 *
	 * @param mixed $value
	 * @return float
	 */
	public static function toFloat($value)
	{
		if (null === $value)
		{
			return null;
		}
		if (is_float($value))
		{
			return $value;
		}
		else if (is_int($value))
		{
			return floatval($value);
		}
		else if (is_string($value))
		{
			$value = str_replace(",", ".", $value);
			if (is_numeric($value))
			{
				return floatval($value);
			}
		}
		return floatval(0);
	}

	/**
	 * Converts the given $value into a double precision floating point number.
	 * If a value cannot be converted, this method returns 0.0.
	 *
	 * @param mixed $value
	 * @return double
	 */
	public static function toDouble($value)
	{
		// In PHP, double and float are equivalent.
		return self::toFloat($value);
	}

	/**
	 * Converts the given $value into a string.
	 * The boolean true returns 'true'.
	 * The boolean false returns 'false'.
	 * For other values, this method returns strval($value).
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function toString($value)
	{
		if ($value === true)
		{
			return 'true';
		}
		else if ($value === false)
		{
			return 'false';
		}
		else if (is_object($value) && f_util_ClassUtils::methodExists($value, 'toString'))
		{
			return $value->toString();
		}
		else if (is_object($value) && f_util_ClassUtils::methodExists($value, '__toString'))
		{
			return $value->__toString();
		}
		else
		{
			return strval($value);
		}
	}

	/**
	 * Fixes the data type of the given $value.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function fixDataType($value)
	{
		if ( ! is_int($value) && strval(intval($value)) === $value)
		{
			$value = intval($value);
		}
		else if ( ! is_int($value) && is_numeric($value) && strpos($value, '.') !== false )
		{
			$value = floatval($value);
		}
		else if (!strcasecmp('true', $value))
		{
			return true;
		}
		else if (!strcasecmp('false', $value))
		{
			return false;
		}
		return $value;
	}

	/**
	 * @param string $value
	 * @return float
	 */
	public static function parseUIDouble($value)
	{
		if (empty($value))
		{
			return null;
		}
		$matches = array();
		if (preg_match('/^([\\-+]?)(\\d{0,8})?[\\.,]?(\\d{0,8})?$/', strval($value), $matches))
		{
			$s = $matches[1] == '-' ? '-' : '';
			$a = intval($matches[2]);
			$b = $matches[3];
			return floatval($s.$a.'.'.$b);
		}
		return floatval(0.0);
	}

	/**
	 * @param float $value
	 */
	public static function toUIDouble($value)
	{
		if ($value !== null)
		{
			$separator = f_Locale::translateUI('&framework.validation.validator.Float.decimalseperator;');
			if ($separator == 'decimalseperator') {$separator = '.';}
			return str_replace('.', $separator, strval($value));
		}
		return null;
	}

}
