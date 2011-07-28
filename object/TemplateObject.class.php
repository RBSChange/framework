<?php
/**
 * @package framework.object
 */
class TemplateObject
{
	/**
	 * @var string
	 */	
	public static $lastTemplateFileName = null;
	
	/**
	 * @var string
	 */
	private $mimeContentType;
	
	/**
	 * @var string
	 */	
	private $fileName;
	
	/**
	 * @var string
	 */
	private $lang;
	
	/**
	 * @var string
	 */	
	private $originalPath;

	/**
	 * @var string
	 */
	private $engine = 'PHPTAL';
	
	/**
	 * @var array
	 */
	private $attributes = array();
	
	/**
	 * @param string $fileName
	 * @param string $mimeContentType
	 */
	public function __construct($fileName, $mimeContentType)
	{
		$this->mimeContentType = $mimeContentType;
		$this->fileName = $fileName;
		$this->lang = RequestContext::getInstance()->getLang();
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
		$this->attributes[$name] = $value;
	}
	
	/**
	 * Used for debug purpose : if defined, the result of execute is completed with XML commented "<!-- END $path -->"
	 * @param String $path
	 */
	public function setOriginalPath($path)
	{
		$this->originalPath = $path;
	}

	/**
	 * @param boolean $noheader
	 * @return string
	 */
	public function execute($noheader = false)
	{
		$previousFileName = self::$lastTemplateFileName;
		self::$lastTemplateFileName = $this->fileName;
		
		try
		{
			RequestContext::getInstance()->beginI18nWork($this->lang);
			
			if ($noheader) {$this->sendHeader();}			
			if ($this->engine == 'PHPTAL')
			{
				$result = $this->executePHPTAL();
			}
			else
			{
				$result = null;
			}
			
			RequestContext::getInstance()->endI18nWork();
		}
		catch(Exception $e)
		{
			Framework::error(__METHOD__ . ' EXCEPTION while executing template ('. $this->fileName . ') :' . $e->getMessage());
			RequestContext::getInstance()->endI18nWork($e);
		}
		
		self::$lastTemplateFileName = $previousFileName;
		return $result;
	}
	
	/**
	 * @return string
	 */
	protected function executePHPTAL()
	{
		$fileName = $this->fileName;
		$lang = $this->lang;
		$mimeContentType = $this->mimeContentType;

		$template = new PHPTAL($fileName);
		if (!PHPTAL_Dom_Defs::getInstance()->isHandledNamespace(PHPTAL_Namespace_CHANGE::NAMESPACE_URI))
		{
			spl_autoload_unregister(array('PHPTAL', 'autoload'));
			PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_CHANGE());	
			$registry = PHPTAL_TalesRegistry::getInstance();
			foreach (Framework::getConfigurationValue('tal/prefix') as $prefix => $class)
			{
				$registry->registerPrefix($prefix, array($class, $prefix));
			}
		}
		
		foreach ($this->attributes as $attrName => $attrValue) 
		{
			$template->set($attrName, $attrValue);
		}
		
		$template->getContext()->noThrow(true);
		$template->stripComments(!Framework::inDevelopmentMode());
		$path = f_util_FileUtils::buildCachePath('template', $lang);
		
		f_util_FileUtils::mkdir($path);
		$template->setPhpCodeDestination($path);
		$template->set('LANG', $lang);	
				
		if ($mimeContentType === K::XUL || $mimeContentType === K::XML)
		{
			$template->set('HOST',  Framework::getUIBaseUrl());
			$template->setOutputMode(PHPTAL::XML);
		}
		else
		{
			$template->set('HOST', Framework::getBaseUrl());
			$template->setOutputMode(PHPTAL::XHTML);
		}
		
		$template->set('UIHOST',  Framework::getUIBaseUrl());
		
		$result = $template->execute();
		
		if ($this->originalPath !== null)
		{
			$result .= "<!-- C4TEMPLATE_END ".$this->originalPath." -->";
		}
		return $result;		
	}
		
	protected function sendHeader()
	{
		$mode = Controller::getInstance()->getRenderMode();
		if (($mode != View::RENDER_VAR))
		{
			$header = $this->getHeader();
			if ((is_null($header) === false) && (headers_sent() == false))
			{
				header('Content-type: ' . $header);
			}
		}		
	}

	protected function getHeader()
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
}