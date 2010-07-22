<?php
/**
 * @package framework.validation.parsers
 */
class validation_InListValueParser implements validation_ValueParser
{
	public static function getValue($valueStr)
	{
		if ( ! f_util_StringUtils::beginsWith($valueStr, '[') || ! f_util_StringUtils::endsWith($valueStr, ']'))
		{
			throw new ValidatorConfigurationException('Must be a string like ["value1","value2",...]');
		}
		$valueStr = substr($valueStr, 1, -1);
		$len = strlen($valueStr);
		$quoted = -1;
		$values = array();
		$value = '';
		$escaped = false;
		for ($i = 0 ; $i<$len ; $i++)
		{
			$c = $valueStr{$i};
			if ($c == '\\')
			{
				if ($escaped)
				{
					$value .= '\\';
				}
				$escaped = ! $escaped;
			}
			else
			{
				if ($escaped)
				{
					if ($c == '"' || $c == "'")
					{
						$value .= $c;
					}
					else
					{
						$value .= '\\' . $c;
					}
					$escaped = false;
				}
				else if ($c == '"')
				{
					if ($quoted == -1)
					{
						$quoted = $i;
					}
					else
					{
						$quoted = -1;
						$values[] = $value;
						$value = '';
					}
				}
				else if ($c == ' ')
				{
					if ($quoted != -1) $value .= $c;
				}
				else if ($c != ',' || $quoted != -1)
				{
					$value .= $c;
				}
			}
		}

		return $values;
	}
}