<?php
/**
 * @package framework.validation.parsers
 */
class validation_NullValueParser implements validation_ValueParser
{
	public static function getValue($valueStr)
	{
		return null;
	}
}