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
			$fileResolver = FileResolver::getInstance()->setDirectory("util/iso639")->setPackageName("framework");
			$isoFile = $fileResolver->getPath("iso-639-".$lang.".txt");
			if ($isoFile === null)
			{
				$file = "iso-639-".Framework::getConfigurationValue("framework/util/iso639-defaultlang", "fr").".txt";
				$isoFile = $fileResolver->getPath($file);
				if ($isoFile === null)
				{
					throw new Exception("Could not find ".$file." file");
				}
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