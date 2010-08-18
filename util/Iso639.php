<?php
class f_util_Iso639
{
	private static $codesByLang = array();
	
	static function getAll($lang = null, $exclude = null)
	{
		if ($lang === null)
		{
			$lang = RequestContext::getInstance()->getLang();
		}
		
		if (isset(self::$codesByLang[$lang]))
		{
			$codes = self::$codesByLang[$lang];
		}
		else
		{
			$isoFile = f_util_FileUtils::buildWebeditPath("framework/util/iso639/iso-639-".$lang.".txt");
			if (!file_exists($isoFile))
			{
				$isoFile = f_util_FileUtils::buildWebeditPath("framework/util/iso639/iso-639-".Framework::getConfigurationValue("framework/util/iso639-defaultlang", "fr").".txt");
			}
			$lines = f_util_FileUtils::readArray($isoFile);
			$codes = array();
			foreach ($lines as $line)
			{
				if ($line[0] === '#')
				{
					continue;
				}
				$lineInfo = explode(":", $line);
				$codes[$lineInfo[0]] = $lineInfo[1];
			}
			self::$codesByLang[$lang] = $codes;
		}
		
		if ($exclude !== null)
		{
			foreach ($exclude as $code)
			{
				if (isset($codes[$code]))
				{
					unset($codes[$code]);
				}
			}
		}
		
		return $codes;
	}
}