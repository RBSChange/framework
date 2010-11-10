<?php
class f_Locale
{
	private static $cache = array();
	private static $prefixes = array('modules', 'framework', 'themes');

	const LOCALE_KEY_REGEXP = '&([a-zA-Z_-]+\.[a-zA-Z0-9_.-]*);';
	const LOOSE_LOCALE_KEY_REGEXP = '&?([a-zA-Z_-]+\.[a-zA-Z0-9_.-]*);?';
	const CLEAN_KEY_REGEXP = '&([^;]*);';

	const LOCALE_MANDATORY_MARKER = ' <em>*</em>';

	const MODIFIER_NONE = 0;
	const MODIFIER_UCFIRST = 1;
	const MODIFIER_UCALL = 2;
	const MODIFIER_LABEL = 4;
	const MODIFIER_SPACED = 8;
	const MODIFIER_ELLIPSIS = 16;
	const MODIFIER_MANDATORY = 32;

	private function __construct()
	{
		// empty
	}
	
	/**
	 * @deprecated
	 */
	public static function getPrefixes()
	{
		return self::$prefixes;	
	}
	
	/**
	 * @deprecated
	 */
	public static function isLocaleKey($key)
	{
		return (preg_match('/^' . self::LOCALE_KEY_REGEXP . '$/', $key) == true);
	}

	/**
	 * @deprecated use LocaleService::getInstance()->transBO()
	 */
	public static function translateUI($key, $substitution = null)
	{
		return self::translate($key, $substitution, RequestContext::getInstance()->getUILang());
	}

	/**
	 * @deprecated use LocaleService::getInstance()->transFO()
	 */
	public static function translate($key, $substitution = null, $lang = null, $makeDefaultTranslation = true)
	{
		if (!self::isLocaleKey($key))
		{
			return self::getCleanKey($key);
		}

		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		$cacheKey = $key . ',' . $lang;

		if (!array_key_exists($cacheKey, self::$cache))
		{
			$keyModifier = self::getKeyModifier($key);
			$cleanKey = strtolower(self::getCleanKey($key));
			$content = LocaleService::getInstance()->getFullKeyContent($lang, $cleanKey);
			if ($content !== null)
			{
				$keyModifier = $keyModifier & (self::MODIFIER_UCFIRST + self::MODIFIER_UCALL);
				self::$cache[$cacheKey] = array($keyModifier, $content);
			}
			else
			{
				$modifiedKey = $cleanKey;

				if ($keyModifier & self::MODIFIER_SPACED)
				{
					$modifiedKey = preg_replace('/spaced$/i', '', $modifiedKey);
				}
				if ($keyModifier & self::MODIFIER_LABEL)
				{
					$modifiedKey = preg_replace('/label$/i', '', $modifiedKey);
				}
				if ($keyModifier & self::MODIFIER_MANDATORY)
				{
					$modifiedKey = preg_replace('/mandatory$/i', '', $modifiedKey);
				}

				if ($keyModifier & self::MODIFIER_ELLIPSIS)
				{
					$modifiedKey = preg_replace('/ellipsis$/i', '', $modifiedKey);
				}

				if ($modifiedKey  !== $cleanKey)
				{
					$content = LocaleService::getInstance()->getFullKeyContent($lang, $modifiedKey);
				}

				if ($content !== null)
				{
					self::$cache[$cacheKey] = array($keyModifier, $content);
				}
				else if ($makeDefaultTranslation)
				{
					if (Framework::isDebugEnabled())
					{
						self::$cache[$cacheKey] = array(0, $cleanKey);
					}
					else
					{
						$keyModifier = $keyModifier & (self::MODIFIER_UCFIRST + self::MODIFIER_UCALL);
						self::$cache[$cacheKey] = array($keyModifier, substr($modifiedKey, strrpos($modifiedKey, '.') + 1));
					}
				}
				else
				{
					self::$cache[$cacheKey] = null;
				}
			}
		}

		if (self::$cache[$cacheKey] === null)
		{
			return null;
		}

		list($keyModifier, $translated) = self::$cache[$cacheKey];

		if (is_null($substitution) || (is_array($substitution) == false))
		{
			$substitution = array();
		}

		if (empty($substitution) == false)
		{
			foreach ($substitution as $key => $value)
			{
				$translated = str_replace('{' . $key  . '}', $value, $translated);
			}
		}

		if ($keyModifier & self::MODIFIER_UCFIRST)
		{
			$translated = f_util_StringUtils::ucfirst($translated);
		}
		if ($keyModifier & self::MODIFIER_UCALL)
		{
			$translated = f_util_StringUtils::strtoupper($translated);
		}
		if ($keyModifier & self::MODIFIER_MANDATORY)
		{
			$translated .= self::LOCALE_MANDATORY_MARKER;
		}
		if ($keyModifier & self::MODIFIER_LABEL)
		{
			$translated .= self::getLabelSuffix($lang);
		}
		if ($keyModifier & self::MODIFIER_ELLIPSIS)
		{
			$translated .= '...';
		}
		if ($keyModifier & self::MODIFIER_SPACED)
		{
			$translated = ' ' . $translated . ' ';
		}
		return $translated;
	}

	private static function getLabelSuffix($lang = null)
	{
		if (!$lang)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		switch (strtolower($lang))
		{
			case 'fr':
				return ' :';
				break;

			default:
				return ':';
				break;
		}
	}
	
	// private methods

	private static function getCleanKey($key)
	{
		return preg_replace('/^' . self::CLEAN_KEY_REGEXP . '$/', '$1', $key);
	}

	private static function getKeyModifier($key)
	{
		$cleanKey = self::getCleanKey($key);
		if (strrpos($cleanKey, '.') > 0)
		{
			$lastTerm = substr($cleanKey, strrpos($cleanKey, '.') + 1);
		}
		else
		{
			$lastTerm = $cleanKey;
		}
		$modifier = self::MODIFIER_NONE;
		if (preg_match('/^[A-Z][a-z-]+/', $lastTerm))
		{
			$modifier += self::MODIFIER_UCFIRST;
		}
		if (preg_match('/^[A-Z][A-Z]+/', $lastTerm))
		{
			$modifier += self::MODIFIER_UCALL;
		}
		if (preg_match('/[a-z0-9]+spaced$/i', $lastTerm))
		{
			$modifier += self::MODIFIER_SPACED;
		}
		if (preg_match('/[a-z0-9]+mandatory$/i', $lastTerm)
		|| preg_match('/[a-z0-9]+mandatorylabel$/i', $lastTerm))
		{
			$modifier += self::MODIFIER_MANDATORY;
		}
		if (preg_match('/[a-z0-9]+label$/i', $lastTerm))
		{
			$modifier += self::MODIFIER_LABEL;
		}
		if (preg_match('/[a-z0-9]+ellipsis$/i', $lastTerm))
		{
			$modifier += self::MODIFIER_ELLIPSIS;
		}
		return $modifier;
	}
}