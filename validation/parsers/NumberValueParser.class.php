<?php
/**
 * @package framework.validation.parsers
 */
class validation_NumberValueParser implements validation_ValueParser
{
	public static function getValue($valueStr)
	{
		if ( is_numeric($valueStr) )
		{
			if (intval($valueStr) == $valueStr)
			{
				return intval($valueStr);
			}
			return floatval($valueStr);
		}
		throw new ValidatorConfigurationException('Must be a number');
	}
}