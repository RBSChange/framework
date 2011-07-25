<?php

class f_TalesDate implements PHPTAL_Tales
{
	/**
	 * date: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function date($src, $nothrow)
	{
		return self::renderDate($src, 'date');
	}
		
	/**
	 * datetime: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function datetime($src, $nothrow)
	{
		return self::renderDate($src, 'datetime');
	}
	
	/**
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 * @param string $mode date|datetime
	 */
	static private function renderDate($src, $mode)
	{
		$params = explode(',', $src);

		$dateExpr = trim(array_shift($params));
		if ($dateExpr == '')
		{
			$dateValue = 'date_Calendar::getUIInstance()';
		}
		else
		{
			$dateValue = self::evalExpr($dateExpr);
		}	
		
		if (count($params) > 0)
		{
			$format = self::evalExpr(implode(',', $params));
		}
		else if ($mode == 'date')
		{
			$format = var_export(date_Formatter::getDefaultDateFormat(RequestContext::getInstance()->getLang()), true);
		}
		else
		{
			$format = var_export(date_Formatter::getDefaultDateTimeFormat(RequestContext::getInstance()->getLang()), true);
		}
		
		return "date_Formatter::format($dateValue, $format)";
	}
	
	/**
	 * @param string $value
	 * @return mixed
	 */
	private static function evalExpr($value)
	{
		$value = trim($value);
		$l = strlen($value);
		if ($l > 0 && !is_numeric($value) && $value[0] != '\'')
		{
			return phptal_tales($value);
		}
		else if ($l > 1 && $value[0] == '\'' && $value[$l-1] == '\'')
		{
			$value = htmlspecialchars_decode(substr($value, 1, $l - 2));
			return var_export($value , true);
		}
		else
		{
			return var_export($value , true);
		}
	}
}