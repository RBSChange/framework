<?php
/**
 * @package framework.service
 */
class StyleService extends BaseService
{
    /**
     * Media name for "screen".
     *
     */
    const MEDIA_SCREEN = 'screen';


    /**
     * Media name for "print".
     *
     */
    const MEDIA_PRINT = 'print';


    /**
     * Specific media name for "XUL".
     *
     */
    const MEDIA_XUL = 'xul';


	/**
     * CSS Cache Location (must be browsable).
     *
     */
	const CACHE_LOCATION = '/cache/www/css/';


	/**
	 * StyleService instance
	 *
	 * @var StyleService
	 */
	private static $instance = null;

	
	/**
	 * @return StyleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * Style registry :
	 */
	public static $styleRegistry = array();


	/**
	 * Registers the given style.
	 *
	 * @param string $styleName Logical style path (ex : "modules.website.frontoffice").
	 * @param string $media Style's media (default = "screen")
	 * @return StyleService
	 */
	public function registerStyle($styleName, $media = self::MEDIA_SCREEN)
	{
		if (!array_key_exists($styleName, self::$styleRegistry))
		{
			self::$styleRegistry[$styleName] = $media;
			
			if ($styleName == 'modules.uixul.backoffice' && (RequestContext::getInstance()->getOperatingSystem() == RequestContext::OS_MAC))
			{
				self::$styleRegistry['modules.uixul.macoffice'] = $media;
			}
		}
		reset(self::$styleRegistry);
		return $this;
	}


	/**
	 * Unregisters the given style.
	 *
	 * @param string $styleName Logical style path (ex : "modules.website.frontoffice").
	 * @return StyleService
	 */
	public function unregisterStyle($styleName)
	{
		if (array_key_exists($styleName, self::$styleRegistry))
		{
			unset(self::$styleRegistry[$styleName]);
			reset(self::$styleRegistry);
		}

		return $this;
	}
	
	/**
	 * @param string[] $styleNames
	 * @param string $contentType
	 * @param f_web_CSSVariables $skin
	 * @return string | null
	 */
	public function getStylePath($styleNames, $contentType = null, $skin = null)
	{
	    if (!is_array($styleNames))
	    {
	        $fileSystemName = $this->getFileSystemName($styleNames, $contentType, $skin);
	        return $fileSystemName;
	    } 
	    else if (count($styleNames) === 0)
		{
			return null;
		}

		$rc = RequestContext::getInstance();
		if ($contentType === null)
		{
			$contentType = $rc->getMimeContentType();
		}
		$engine = $this->getFullEngineName($contentType);
		$skin = $this->normalizeSkin($skin);
		
		$fileSystemNames = array(strtolower($rc->getLang()), $engine, $rc->getProtocol(), 
			$this->getCurrentWebsiteId(), 
			($skin !== null) ? $skin->getIdentifier() : '-1');
		
		foreach ($styleNames as $styleName)
		{
			$fileSystemNames[] = $styleName;
		}
		
		$globalFileSystemName = $this->getFileSystemNameForGlobalStyleSheet(self::MEDIA_SCREEN, $fileSystemNames);
		if (!$this->isFileValid($globalFileSystemName))
		{
			$this->validFile($globalFileSystemName);
			f_util_FileUtils::mkdir(dirname($globalFileSystemName));
			$handle = fopen($globalFileSystemName, 'w');
			foreach ($styleNames as $styleName)
			{
				fwrite($handle, "/* $styleName START */\n");
				$css = $this->getCSS($styleName, $engine, $skin);
				if ($css !== null)
				{
					fwrite($handle, $css);
				}
				fwrite($handle, "\n/* $styleName END */\n");
			}
			fclose($handle);
		}
		return $globalFileSystemName;
	}

	/**
	 * @param String $fileName
	 * @return Boolean
	 */
	private function isFileValid($fileName)
	{
		return is_readable($fileName) && !file_exists($fileName.'.deleted');
	}
	
	private function validFile($fileName)
	{
		if (file_exists($fileName.'.deleted'))
		{
			unlink($fileName.'.deleted');
		}
	}	
	
	/**
	 * Get URL of the given style :
	 * @param mixed $styleNames Logical style paths (ex : "modules.website.frontoffice").
	 * @param string $contentType Required content type
	 * @param mixed $skin
	 * @return string
	 */
	public function getStyleURL($styleNames, $contentType = null, $skin = null)
	{
		$globalFileSystemName = $this->getStylePath($styleNames, $contentType, $skin);
		if ($globalFileSystemName !== null)
		{
			return LinkHelper::getRessourceLink(self::CACHE_LOCATION . basename($globalFileSystemName))->getUrl();
		}
		return null;
	}
	
	/**
	 * Executes (renders) the requires styles.
	 *
	 * @param string $mimeContentType Mimi content type to use
	 * @param f_web_CSSVariables $skin The skin to integrate
	 * @return string Styles URL
	 */
	public function execute($mimeContentType = null, $skin = null)
	{
		$style = array();
		$rc = RequestContext::getInstance();
		if (!is_null($mimeContentType))
		{
		    $rc->setMimeContentType($mimeContentType);
		}
		$skin = $this->normalizeSkin($skin);
		
		if ($mimeContentType == K::XUL)
		{
			foreach (array_keys(self::$styleRegistry) as $styleName)
			{
				$chromeActionLink = LinkHelper::getUIChromeActionLink('uixul', 'GetUICSS')
					->setArgSeparator(f_web_HttpLink::ESCAPE_SEPARATOR)
					->setQueryParameter('stylename', $styleName);
					
				if ($skin !== null)
				{
					$chromeActionLink->setQueryParameter('skinId', $skin->getIdentifier());
				}
				$href = $chromeActionLink->getUrl();
				$style[] = '<?xml-stylesheet href="'.$href.'" type="text/css"?>';
			}
		}
		else
		{
			$styleByMedia = array();
			foreach (self::$styleRegistry as $styleName => $mediaType)
			{
				$styleByMedia[$mediaType][] = $styleName;
			}

			foreach ($styleByMedia as $mediaType => $allStyleNames)
			{
				sort($allStyleNames, SORT_STRING);
				foreach (array_chunk($allStyleNames, 3) as $styleNames) 
				{
					$styleNames[] = $mediaType . '.css';
					$names = implode(',', $styleNames);
					$websiteId = website_WebsiteModuleService::getInstance()->getDefaultWebsite()->getId();	
					if ($websiteId <= 0) {$websiteId = 0;}			
					$pathPart = array('', 'cache', 'www', 'css', $rc->getProtocol(), $websiteId, $rc->getLang(), $rc->getUserAgentType(), $rc->getUserAgentTypeVersion(), $names);				
					$inclusionSrc = LinkHelper::getRessourceLink(implode('/', $pathPart))->getUrl();
					$style[] = '<link rel="stylesheet" href="'.$inclusionSrc.'" type="text/css" media="'.$mediaType.'" />';	
				}
			}
		}
		if (count($style) > 0)
		{
			return implode(K::CRLF, $style);
		}
		return null;
	}

	/**
	 * @example StyleService->getSourceLocation('modules.catalog.frontoffice')
	 * @param String $id
	 * @return String | null the path of the xml source file
	 */
	public function getSourceLocation($id)
	{
		$styleRessource = explode('.', $id);
		$path = null;
		if (count($styleRessource) > 2)
		{
			$path = array_slice($styleRessource, 2, -1);
			if (count($path))
			{
				$path = 'style' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path);
			}
			else
			{
				$path = 'style';
			}

			$file = end($styleRessource);		
			$cssPath = FileResolver::getInstance()
				->setPackageName($styleRessource[0] . '_' . $styleRessource[1])
				->setDirectory($path)->getPath($file . '.css');
			if ($cssPath !== null) 
			{
				return $cssPath;
			}
	
			$path = FileResolver::getInstance()
				->setPackageName($styleRessource[0] . '_' . $styleRessource[1])
				->setDirectory($path)->getPath($file . '.xml');
		}
		return $path;
	}
	
	/**
	 * @param integer|f_web_CSSVariables|null $skin
	 * @return f_web_CSSVariables | null
	 * @throw Exception if $skin is not valid type identifier 
	 */
	private function normalizeSkin($skin)
	{
		if ($skin === null || $skin instanceof f_web_CSSVariables)
		{
			return $skin;
		}
		else if (is_numeric($skin))
		{
			return DocumentHelper::getDocumentInstance($skin);
		}
		throw new Exception('Invalid skin identifier.');
	}
	
	/**
	 * @param string $style
	 * @param string $contentType (html, xul)
	 * @param f_web_CSSVariables $skin
	 * @param string $protocol
	 * @return string
	 */
	private function buildFilePathName($style, $contentType = null, $skin = null, $protocol = null)
	{
		$requestContext = RequestContext::getInstance();
		if (is_null($contentType)) 
		{ 
			$contentType = $requestContext->getMimeContentType();
		}
		$lang = strtolower($requestContext->getLang());
		$engine = $this->getFullEngineName($contentType);
		if ($contentType === 'xul')
		{
			$fileSystemName = $style . '.' . $engine. '.' . $lang . '.';	
		}
		else 
		{
			if ($protocol === null) {$protocol = $requestContext->getProtocol();}
			$fileSystemName = $protocol . '.' . $style . '.' . $engine . '.' . $lang . '.' . $this->getCurrentWebsiteId() . '.';
			if ($skin !== null)
			{
			    $fileSystemName .= $skin->getIdentifier() . '.';
			}
		}
		return f_util_FileUtils::buildWebCachePath('css', $fileSystemName . 'css');		
	}

	/**
	 * Gets the physical path of the given style 
	 * The content of file is generated if needed.
	 * @param string $style Logical path of the style ("modules.website.frontoffice")
	 * @param string $contentType
	 * @param mixed $skin Skin to integrate
	 * @param string $protocol
	 * @return string | null Path 
	 */
	public function getFileSystemName($style, $contentType = null, $skin = null, $protocol = null)
	{
		$engine = $this->getFullEngineName($contentType);
		$skin = $this->normalizeSkin($skin);
		
		$fileLocation = $this->buildFilePathName($style, $contentType, $skin, $protocol);
		if ($this->isFileValid($fileLocation))
		{
			return $fileLocation;
		}
		$this->validFile($fileLocation);
	
		$css = $this->getCSS($style, $engine, $skin);
		if ($css !== null)
		{
			f_util_FileUtils::writeAndCreateContainer($fileLocation, $css, f_util_FileUtils::OVERRIDE);	
			return $fileLocation;
		}
		else if (file_exists($fileLocation))
		{
			unlink($fileLocation);
		}
		return null;
	}
	
	/**
	 * @param string $contentType (html, xul)
	 * @return string
	 * @example 'all.all' 'xul.all' 'gecko.3'
	 */
	public function getFullEngineName($contentType = null)
	{
		if ($contentType === 'xul')
		{
			return 'xul.all';
		}
		else
		{
			$requestContext = RequestContext::getInstance();
			return $requestContext->getUserAgentType() . '.' . $requestContext->getUserAgentTypeVersion();
		}
	}
	
	/**
	 * @param string $style ex "modules.website.frontoffice"
	 * @param string $fullEngine
	 * @param f_web_CSSVariables $skin
	 * @return string | null
	 */
	public function getCSS($style, $fullEngine = 'all.all', $skin = null)
	{
		$sourceLocation = $this->getSourceLocation($style);
		if ($sourceLocation !== null && is_readable($sourceLocation))
		{
			$ss = f_web_CSSStylesheet::getInstanceFromFile($sourceLocation);
			$css = $ss->getAsCSS($fullEngine, $skin);	
			return $css;
		}
		return null;
	}

	/**
	 * Gets the physical path of the GLOBAL stylesheet.
	 *
	 * @param string $mediaType
	 * @param array $fileSystemNames All stylesheets to integrate
	 * @return string Path
	 */
	public function getFileSystemNameForGlobalStyleSheet($mediaType, $fileSystemNames)
	{
		$rc = RequestContext::getInstance();
		$fileParts = array('global', $mediaType, 
			md5(implode('-', $fileSystemNames)), 
			strtolower($rc->getLang()), $rc->getUserAgentType(), $rc->getUserAgentTypeVersion(),
			$this->getCurrentWebsiteId(),
			'css');
			
		return f_util_FileUtils::buildWebCachePath('css', implode('.', $fileParts));
	}

	/**
	 * @return integer
	 */
	private function getCurrentWebsiteId()
	{
		return website_WebsiteModuleService::getInstance()->getCurrentWebsite()->getId();
	}
	
	/**
	 * Convert CSS stylesheet to XML stylesheet.
	 * @param string $css
	 * @return DOMDocument
	 */
	public final function cssToXml($css)
	{
		$ss = new f_web_CSSStylesheet();
		$ss->loadCSS($css);
	    $xml = new DOMDocument('1.0', 'utf-8');
	    $xml->loadXML($ss->getAsXML());
	    return $xml;
	}
	
	/**
	 * @example StyleService->getSourceLocation('modules.catalog.frontoffice')
	 * @param String $id
	 * @return String | null the path of the xml source file
	 */
	private function getImageSourceLocation($id)
	{
		$styleRessource = explode('.', $id);
		$path = null;
		if (count($styleRessource) > 2)
		{
			$path = array_slice($styleRessource, 2, -1);
			if (count($path))
			{
				$path = 'style' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path);
			}
			else
			{
				$path = 'style';
			}

			$file = end($styleRessource);		
			$path = FileResolver::getInstance()
				->setPackageName($styleRessource[0] . '_' . $styleRessource[1])
				->setDirectory($path)->getPath($file . '.image.all.css');
		}
		return $path;
	}
	
	/**
	 * @param String $moduleName
	 */
	public function getImageFormats($stylesheetName)
	{
		$sourceLocation = $this->getImageSourceLocation($stylesheetName);
		if (!$sourceLocation)
		{
			return array();
		}
		$formats = array();
		$css = f_web_CSSStylesheet::getInstanceFromFile($sourceLocation);
		foreach ($css->getCSSRules() as $rule) 
		{
			$formatName = str_replace('.', '', $rule->getSelectorText());
			$formats[$formatName] = array();
			foreach ($rule->getDeclarations() as $declaration) 
			{
				$formats[$formatName][$declaration->getPropertyName()] = $declaration->getPropertyValue();
			}
		}
		return $formats;
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use CacheService::clearCssCache()
	 */
	public function clearCssCache($all = true)
	{
	    CacheService::getInstance()->clearCssCache();
	    return $this;
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use uixul_lib_BindingObject::getUrl($binding)
	 */
	public function getBinding($binding)
	{
		try
		{
			$bindingUrl = uixul_lib_BindingObject::getUrl($binding);
		}
		catch (AutoloadException $e)
		{
		    Framework::exception($e);
		}

		return $bindingUrl;
	}
}