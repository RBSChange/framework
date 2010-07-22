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


	public function __construct( $fileName, $mimeContentType )
	{
		$this->mimeContentType = $mimeContentType;
		$this->fileName = $fileName;
		$this->template = new PHPTAL($fileName);
		$this->lang = RequestContext::getInstance()->getLang();

		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ ."($this->lang,$this->fileName");
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
				$header = 'text/html';
				break;

			default:
				$header = null;
				break;
		}
		return $header;
	}

	/**
	 * @deprecated
	 */
	private $signed;

	/**
	 * @param boolean $signed
	 * @deprecated 
	 */
	public function setSigned($signed)
	{
		$this->signed = ($signed === true) ? true : false;
	}
	
	/**
	 * @deprecated
	 */	
	private function isSigned()
	{
		return $this->signed;
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