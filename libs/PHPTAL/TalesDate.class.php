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
		$params = array();
		$from = 'local';
		$dateValue = null;
		foreach (explode(',', $src) as $value) 
		{
			$tv = trim($value);
			if ($dateValue === null)
			{
				$dateValue = ($tv == '') ? 'date_Calendar::getUIInstance()' : self::evalExpr($tv);
			}
			else
			{
				$convertPart = explode('=', $tv);
				if (count($convertPart) === 2)
				{
					$or = trim($convertPart[0]);
					$ty = trim($convertPart[1]);
					if ($or === 'from' && ($ty === 'gmt' || $ty === 'local'))
					{
						$from = $ty;
						continue;
					}
				}
				$params[] = $value;
			}
		}
		if ($from === 'gmt')
		{
			$dateValue = 'date_Converter::convertDateToLocal('. $dateValue . ')';
		}
				
		if (count($params) > 0)
		{
			$format = self::evalExpr(implode(',', $params));
			return "date_Formatter::format($dateValue, $format)";
		}
		else if ($mode == 'date')
		{
			return "date_Formatter::toDefaultDate($dateValue)";
		}
		else
		{
			return "date_Formatter::toDefaultDateTime($dateValue)";
		}
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
			return phptal_tales($value, true);
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