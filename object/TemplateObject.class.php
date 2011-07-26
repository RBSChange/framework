<?php
/**
 * @package framework.object
 */
class TemplateObject
{
	
	public static $lastTemplateFileName = null;
	
	/**
	 * @var PHPTAL
	 */
	private $template;

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
	 * @param string $fileName
	 * @param string $mimeContentType
	 */
	public function __construct($fileName, $mimeContentType)
	{
		$this->mimeContentType = $mimeContentType;
		self::$lastTemplateFileName = $fileName;
		
		$this->fileName = $fileName;
		$this->template = new PHPTAL($fileName);
		$this->lang = RequestContext::getInstance()->getLang();
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

	/**
	 * @param boolean $noheader
	 */
	public function execute($noheader = false)
	{
		try
		{
			RequestContext::getInstance()->beginI18nWork($this->lang);
			$this->template->getContext()->noThrow(true);
			$this->template->stripComments(!Framework::inDevelopmentMode());
			$path = PHPTAL_PHP_CODE_DESTINATION . $this->lang;
			f_util_FileUtils::mkdir($path);
			$this->template->setPhpCodeDestination($path);	
					
			if ($this->mimeContentType === K::XUL || $this->mimeContentType === K::XML)
			{
				$this->template->set('HOST',  Framework::getUIBaseUrl());
				$this->template->setOutputMode(PHPTAL::XML);
			}
			else
			{
				$this->template->set('HOST', Framework::getBaseUrl());
				$this->template->setOutputMode(PHPTAL::XHTML);
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
			Framework::warn(__METHOD__ . ' EXCEPTION while executing template ('. $this->fileName . ') :' . $e->getMessage());
			Framework::exception($e);
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
}