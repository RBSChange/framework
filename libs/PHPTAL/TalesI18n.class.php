<?php
class f_TalesI18n implements PHPTAL_Tales
{
	/**
	 * trans: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function trans($src, $nothrow)
	{
		return self::translate($src, RequestContext::getInstance()->getUILang());
	}
		
	/**
	 * transui: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function transui($src, $nothrow)
	{
		return self::translate($src, RequestContext::getInstance()->getUILang());
	}
	
	/**
	 * transdata: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function transdata($src, $nothrow)
	{
		return self::translate($src, RequestContext::getInstance()->getLang());
	}

	/**
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static private function translate($src, $lang)
	{
		PHPTAL_Php_State::setStructuredTales(true);
		list($key, $formatters, $replacements) = LocaleService::getInstance()->parseTransString($src);
		
		$formattersStr = array();
		$addHTMLFormatter = true;
		foreach ($formatters as $formatter) 
		{
			if ($formatter === 'html' || $formatter === 'js' || $formatter === 'attr' || $formatter === 'raw')
			{
				$addHTMLFormatter = false;
			}
			$formattersStr[] = var_export($formatter , true);
		}
		if ($addHTMLFormatter) 
		{
			$formatter = 'html'; 
			$formatters[] = $formatter;
			$formattersStr[] = var_export($formatter , true);	
		}
		
		if (count($replacements) === 0)
		{
			return var_export(LocaleService::getInstance()->formatKey($lang, $key, $formatters), true);
		}
		
		$replacementsStr = array();
		foreach ($replacements as $name => $value) 
		{
			$l = strlen($value);
			if ($l > 0 && !is_numeric($value) && $value[0] != '\'')
			{
				$replacementsStr[] = var_export($name , true). ' => ' . phptal_tales($value, true);
			}
			else if ($l > 1 && $value[0] == '\'' && $value[$l-1] == '\'')
			{
				$value = htmlspecialchars_decode(substr($value, 1, $l - 2));
				$replacementsStr[] = var_export($name , true). ' => ' . var_export($value , true);
			}
			else
			{
				$replacementsStr[] = var_export($name , true). ' => ' . var_export($value , true);
			}
		}
		return "LocaleService::getInstance()->formatKey('$lang', '$key', array(".implode(', ', $formattersStr)."), array(".implode(', ', $replacementsStr)."))";
	}
}