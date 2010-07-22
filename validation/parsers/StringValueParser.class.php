<?php
/**
 * @package framework.validation.parsers
 */
class validation_StringValueParser implements validation_ValueParser
{
	public static function getValue($valueStr)
	{
		if ( is_numeric($valueStr) )
		{
			return strval($valueStr);
		}
		if ( is_string($valueStr) )
		{
			if (f_util_StringUtils::beginsWith($valueStr, '"') && f_util_StringUtils::endsWith($valueStr, '"'))
			{
				$valueStr = substr($valueStr, 1, -1);
			}
			return $valueStr;
		}
		throw new ValidatorConfigurationException('Must be a string');
	}
}