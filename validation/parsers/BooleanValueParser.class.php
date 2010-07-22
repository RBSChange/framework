<?php
/**
 * @package framework.validation.parsers
 */
class validation_BooleanValueParser implements validation_ValueParser
{
	public static function getValue($valueStr)
	{
		if ( is_bool($valueStr) )
		{
			return $valueStr;
		}
		else if ( ! strcasecmp($valueStr, 'true') )
		{
			return true;
		}
		else if ( ! strcasecmp($valueStr, 'false') )
		{
			return false;
		}
		throw new ValidatorConfigurationException('Must be a boolean');
	}
}
