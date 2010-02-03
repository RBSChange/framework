<?php
/**
 * @package framework.validation.parsers
 */
class validation_RangeValueParser implements validation_ValueParser
{
	const REGEXP_INT_INT = '/^(\-?[0-9]+)\.\.(\-?[0-9]+)$/';
	const REGEXP_FLOAT_FLOAT = '/^(\-?[0-9]+\.[0-9]+)\.\.(\-?[0-9]+\.[0-9]+)$/';
	const REGEXP_INT_FLOAT = '/^(\-?[0-9]+)\.\.(\-?[0-9]+\.[0-9]+)$/';
	const REGEXP_FLOAT_INT = '/^(\-?[0-9]+\.[0-9]+)\.\.(\-?[0-9]+)$/';
	
	public static function getValue($valueStr)
	{
		if ($valueStr instanceof validation_Range)
		{
			return $valueStr;
		}
		// range with integers
		else if (preg_match(self::REGEXP_INT_INT, $valueStr, $matches))
		{
			$value = new validation_Range(intval($matches[1]), intval($matches[2]));
		}
		// range with floats
		else if (preg_match(self::REGEXP_FLOAT_FLOAT, $valueStr, $matches))
		{
			$value = new validation_Range(floatval($matches[1]), floatval($matches[2]));
		}
		// range with integer and float
		else if (preg_match(self::REGEXP_INT_FLOAT, $valueStr, $matches))
		{
			$value = new validation_Range(intval($matches[1]), floatval($matches[2]));
		}
		// range with float and integer
		else if (preg_match(self::REGEXP_FLOAT_INT, $valueStr, $matches))
		{
			$value = new validation_Range(floatval($matches[1]), intval($matches[2]));
		}
		else 
		{
			throw new ValidatorConfigurationException('Must be a valid range: number..number');
		}
		return $value;
	}
}