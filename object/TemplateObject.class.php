<?php
/**
 * Auto-generated doc comment
 * @package framework.object
 */

class TemplateObject
{
	private $template;
	private $mimeContentType;
	private $fileName;
	private $lang;
	private $originalPath;
	private static $prefixregistered;

	/**
	 * @param string $fileName
	 * @param string $mimeContentType
	 */
	public function __construct($fileName, $mimeContentType)
	{
		$this->mimeContentType = $mimeContentType;
		$this->fileName = $fileName;
		if ($mimeContentType == K::TWIG) {
			$this->template = new Twig($fileName);
		} else {
			$this->template = new PHPTAL($fileName);
		}
		$this->lang = RequestContext::getInstance()->getLang();
		$registry = PHPTAL_TalesRegistry::getInstance();
		if (!self::$prefixregistered)
		{
			self::$prefixregistered = true;
			foreach (Framework::getConfigurationValue('tal/prefix') as $prefix => $class)
			{
				$registry->registerPrefix($prefix, array($class, $prefix));
			}			
		}
	}

	/**
	 * @param array $attributes
	 */
	public function importAttributes($attributes)
	{
		if (is_array($attributes) && !empty($attributes))
		{
			foreach ($attributes as $name => &$value)
			{
				$this->setAttribute($name, $value);
			}
		}
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setAttribute($name, $value)
	{
		$this->template->set($name, $value);
	}

	public function execute($noheader = false)
	{
		try
		{
			RequestContext::getInstance()->beginI18nWork($this->lang);

			$this->template->setTranslator(new LocaleTranslator($this->lang));
			$this->template->getContext()->noThrow(true);

			if ($this->mimeContentType === K::XUL || $this->mimeContentType === K::XML)
			{
				$this->template->set('HOST',  Framework::getUIBaseUrl());
				$this->template->setOutputMode(PHPTAL_XML);
			}
			else
			{
				$this->template->set('HOST', Framework::getBaseUrl());
			}
			
			$this->template->set('UIHOST',  Framework::getUIBaseUrl());

			$mode = Controller::getInstance()->getRenderMode();
			if (($mode != View::RENDER_VAR) && ($noheader === false))
			{
				$header = $this->getHeader();
				if ((is_null($header) === false) && (headers_sent() == false))
				{
					header('Content-type: ' . $header);
				}
			}
			$result = $this->template->execute();
			RequestContext::getInstance()->endI18nWork();
			if ($this->originalPath !== null)
			{
				$result .= "<!-- C4TEMPLATE_END ".$this->originalPath." -->";
			}
			return $result;
		}
		catch(Exception $e)
		{
			Framework::warn(__METHOD__ . ' EXCEPTION while executing template: ' . $e->getMessage());
			RequestContext::getInstance()->endI18nWork($e);
		}
	}
	
	/**
	 * Used for debug purpose : if defined, the result of execute is completed with XML commented "<!-- END $path -->"
	 * @param String $path
	 */
	public function setOriginalPath($path)
	{
		$this->originalPath = $path;
	}

	private function getHeader()
	{
		switch($this->mimeContentType)
		{
		    case K::XUL :
				$header = 'application/vnd.mozilla.xul+xml';
				break;

			case K::XML :
				$header = 'text/xml';
				break;

			case K::HTML :
			case K::TWIG :
				$header = 'text/html';
				break;

			default:
				$header = null;
				break;
		}
		return $header;
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) 
	 */
	public function setSigned($signed)
	{
	}
}

class LocaleTranslator implements PHPTAL_TranslationService
{
	function __construct($lang)
	{
		$this->lang = $lang;
	}

	function setLanguage()
	{
		// nothing
	}

	/**
	 * Set the domain to use for translations.
	 */
	function useDomain($domain)
	{
		// nothing
	}

	/**
	 * Set an interpolation var.
	 */
	function setVar($key, $value)
	{
		// nothing
	}

	/**
	 * Translate a gettext key and interpolate variables.
	 */
	function translate($key)
	{
		return f_Locale::translate($key, null, $this->lang);
	}
}

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
		return self::translate($src, RequestContext::getInstance()->getLang());
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
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static private function translate($src, $lang)
	{
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
				$replacementsStr[] = var_export($name , true). ' => ' . phptal_tales($value);
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

class f_TalDate implements PHPTAL_Tales
{
	/**
	 * trans: modifier.
	 *
	 * Returns the code required to localize a key
	 * <?php echo phptal_escape(RETURN_VALUE, ENT_QUOTES, 'UTF-8');?>
	 */
	static public function date($src, $nothrow)
	{
		return self::renderDate($src, 'date');
	}
		
	/**
	 * transui: modifier.
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