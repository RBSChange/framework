<?php
class JsService extends BaseService
{
	/**
	 * JS Cache Location (must be browsable).
	 *
	 */
	const CACHE_LOCATION = '/cache/www/js/';

	/**
	 * XHTML template for NO script integration.
	 *
	 */
	const TEMPLATE_NOSCRIPT = '<noscript>%s</noscript>%s';


	/**
	 * Scripts registry.
	 * TODO: should be private and not static !
	 * @var array
	 */
	public static $scriptRegistry = array();
	
	private $registery = array();


	/**
	 * JsService instance.
	 *
	 * @var JsService
	 */
	private static $instance = null;


	/**
	 * Gets the JsService instance.
	 *
	 * @return JsService
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
	 * Gets a JsService instance.
	 *
	 * @return JsService
	 */
	public static function newInstance()
	{
		return self::getServiceClassInstance(get_class());
	}

	/**
	 * Registers the given script.
	 *
	 * @param string $scriptPath Logical script path (ex : "modules.website.lib.frontoffice").
	 * @return JsService
	 */
	public function registerScript($scriptPath, $skin = null)
	{
		if ($this === self::getInstance())
		{	
			if (array_key_exists($scriptPath, self::$scriptRegistry) === false)
			{
				self::$scriptRegistry[$scriptPath] = $skin;
				$this->computedRegisteredScripts = null;
			}
		}
		else
		{
			if (array_key_exists($scriptPath, $this->registery) === false)
			{
				$this->registery[$scriptPath] = $skin;
				$this->computedRegisteredScripts = null;
			}
		}

		return $this;
	}

	/**
	 * Unregisters the given script.
	 *
	 * @param string $scriptPath Logical script path (ex : "modules.website.lib.frontoffice").
	 * @return JsService
	 */
	public function unregisterScript($scriptPath)
	{
		
		if ($this === self::getInstance())
		{
			if (array_key_exists($scriptPath, self::$scriptRegistry) === true)
			{
				unset(self::$scriptRegistry[$scriptPath]);
				$this->computedRegisteredScripts = null;
			}
		}
		else
		{
			if (array_key_exists($scriptPath, $this->registery) === true)
			{
				unset($this->registery[$scriptPath]);
				$this->computedRegisteredScripts = null;
			}
		}

		return $this;
	}


	/**
	 * Clear JS cache files
	 *
	 * @param boolean $all
	 * @return JsService
	 */
	public function clearJsCache($all = true)
	{
		$baseJSCacheDir = f_util_FileUtils::buildWebCachePath('js');
		f_util_FileUtils::mkdir($baseJSCacheDir);
		
		if ($all)
		{
			// Clear ALL JS :
			f_util_FileUtils::clearDir($baseJSCacheDir);
		}
		else
		{
			// Only the Frontoffice ones :
			$dir = $baseJSCacheDir;		
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if (($file != '.') && ($file != '..') && is_file($dir . $file) && !is_dir($dir . $file) && is_writable($dir . $file) && (strpos($file, '.uixul.') === false) && (strpos($file, '.backoffice-') === false))
					{
						@unlink($dir . $file);
					}
				}
				closedir($dh);
			}
		}

		return $this;
	}

	/**
	 * Executes (renders) the requires scritps.
	 *
	 * @param string $mimeContentType Mimi content type to use
	 * @param boolean $compact Compact generated content
	 * @return string Scripts URL
	 */
	public function execute($mimeContentType = null, $compact = false, $excluded = null)
	{
		$rc = RequestContext::getInstance();
		if (is_null($mimeContentType))
		{
			$mimeContentType = $rc->getMimeContentType();
		}
		else
		{
			$rc->setMimeContentType($mimeContentType);
		}
		
		if ($this === self::getInstance())
		{
			$scriptNames = array_keys(self::$scriptRegistry);
		}
		else
		{
			$scriptNames = array_keys($this->registery);
		}
		if (count($scriptNames) === 0)
		{
			return null;
		}		
		$scriptNames[] = 'block.js';
		$names = implode('/', $scriptNames);
		$websiteId = website_WebsiteModuleService::getInstance()->getDefaultWebsite()->getId();	
		if ($websiteId <= 0) {$websiteId = 0;}
		$protocol = website_WebsiteModuleService::getInstance()->getDefaultWebsite()->getProtocol();
		$pathPart = array('', 'cache', 'www', 'js', $protocol, $websiteId, $rc->getLang(), 0, $names);				
		$inclusionSrc = LinkHelper::getRessourceLink(implode('/', $pathPart))->getUrl();
		return '<script src="' . $inclusionSrc . '" type="text/javascript"></script>';
	}
	
	public function executeInline($mimeContentType = null, $compact = false)
	{
		$rc = RequestContext::getInstance();
		if ($mimeContentType === null)
		{
			$mimeContentType = $rc->getMimeContentType();
		}
		else
		{
			$rc->setMimeContentType($mimeContentType);
		}	
		
		$scriptRegistryOrdered = $this->getComputedRegisteredScripts();
		$script = array();		
		if ($mimeContentType == K::HTML)
		{
			$script[] = '<script type="text/javascript">';
		}
		else
		{
			$script[] = '<script type="text/javascript"><![CDATA[';
		}

		$script[] = "// **** Global Context ****";
		$script[] = $this->getJS('init');
		foreach ($scriptRegistryOrdered as $scriptPath => $skin)
		{
			$text = $this->getJS($scriptPath);
			if (!empty($text))
			{
				$script[] = "// **** $scriptPath ****";
				$script[] = str_replace(array('<![CDATA[', ']]>'), '', $text);
			}
		}
						
		if ($mimeContentType == K::HTML)
		{
			$script[] = '</script>';
		}
		else
		{
			$script[] = ']]></script>';
		}
		return implode(K::CRLF, $script);
	}

	private function readJsDependenciesFile($path, &$declaredDependencies, &$noReplacementScripts)
	{
		echo "Reading $path... ";
		$doc = new DOMDocument();
		if ($doc->load($path) === false)
		{
			throw new Exception(__METHOD__ . ": could not load $path as a valid XML file");
		}
		$xpath = new DOMXPath($doc);
		$appLogLevel = AG_LOGGING_LEVEL;
		foreach ($xpath->query("script") as $scriptNode)
		{
				
			$scriptName = $scriptNode->getAttribute("name");
			if ($scriptNode->getAttribute("noreplacement") === "true")
			{
				$noReplacementScripts[$scriptName] = true;
			}
			$deps = array();
			foreach ($xpath->query("dependencies/dependency", $scriptNode) as $depNode)
			{
				if ($depNode->hasAttribute("logLevel"))
				{
					$logLevel = $depNode->getAttribute("logLevel");
					if ((f_util_StringUtils::beginsWith($logLevel, "!") && $appLogLevel != substr($logLevel, 1)) || $appLogLevel == $logLevel)
					{
						$deps[] = $depNode->getAttribute("name");
					}
				}
				else
				{
					$deps[] = $depNode->getAttribute("name");
				}
			}

			if (!isset($declaredDependencies[$scriptName]))
			{
				$declaredDependencies[$scriptName] = $deps;
			}
			else
			{
				$declaredDependencies[$scriptName] = array_merge($declaredDependencies[$scriptName], $deps);
			}
		}
		echo "OK\n";
	}

	/**
	 * Find and read any "config/jsDependencies.xml" file (framework, module or webapp/module level).
	 * The declared scripts are then ordered by dependency and the result is written into
	 * build/$profile/jsDependencies.php for further loading by JsService::loadOrderedScripts().
	 * As a dependency can depend to the log level, any log level update (project.xml edition and
	 * "change compile-config") must be followed by "change compile-js-dependencies"
	 */
	function compileScriptDependencies()
	{
		$appLogLevel = AG_LOGGING_LEVEL;
		$moduleService = ModuleService::getInstance();
		$fileResolver = FileResolver::getInstance();
		$declaredDependencies = array();
		$noReplacementScripts = array();

		$frameworkJsFilePath = f_util_FileUtils::buildFrameworkPath('config/jsDependencies.xml');
		if (file_exists($frameworkJsFilePath))
		{
			$this->readJsDependenciesFile($frameworkJsFilePath, $declaredDependencies, $noReplacementScripts);
		}

		foreach ($moduleService->getModules() as $packageName)
		{
			$fileResolver->setPackageName($packageName)->setDirectory('config');
			$jsDepsFilePath = $fileResolver->getPath("jsDependencies.xml");
			if ($jsDepsFilePath === null)
			{
				continue;
			}
			$this->readJsDependenciesFile($jsDepsFilePath, $declaredDependencies, $noReplacementScripts);
		}

		foreach ($declaredDependencies as $scriptName => $deps)
		{
			foreach ($deps as $dep)
			{
				if (!isset($declaredDependencies[$dep]))
				{
					echo "WARNING: undeclared script $dep in $scriptName dependencies declaration\n";
				}
			}
		}

		$resolver = new f_util_DependencyResolver($declaredDependencies);
		try
		{
			$ordered = $resolver->solve();
		}
		catch (Exception $e)
		{
			Framework::error($e->getMessage());
			throw new Exception(__METHOD__ . " unable to order script dependencies");
		}
		$orderedScriptDeps = array();
		foreach ($ordered as $script)
		{
			$orderedScriptDeps[$script] = $declaredDependencies[$script];
		}
		$jsDepsPath = f_util_FileUtils::buildChangeBuildPath('jsDependencies.php');
		f_util_FileUtils::writeAndCreateContainer($jsDepsPath, '<?php $orderedScripts = unserialize(' . var_export(serialize($orderedScriptDeps), true) . '); $noReplacementScripts = unserialize(' . var_export(serialize($noReplacementScripts), true) . '); ', f_util_FileUtils::OVERRIDE);
	}

	/**
	 * Initialized by loadOrderedScripts
	 * @var array<String, String[]>
	 */
	private static $orderedScripts;
	
	/**
	 * Initialized by loadOrderedScripts
	 */
	private static $noReplacementScripts;

	/**
	 * Load ordered scripts (by dependencies).
	 * @see compileScriptDependencies
	 */
	private function loadOrderedScripts()
	{
		if (self::$orderedScripts === null)
		{
			$jsDepsPath = f_util_FileUtils::buildChangeBuildPath('jsDependencies.php');
			if (!file_exists($jsDepsPath))
			{
				throw new Exception("$jsDepsPath does not exists, please run change compile-js-dependencies");
			}
			include_once ($jsDepsPath);
			self::$orderedScripts = $orderedScripts;
			self::$noReplacementScripts = $noReplacementScripts;
		}
	}


	/**
	 * @return Boolean
	 */
	private function isSignedMode()
	{
		$request = Controller::getInstance()->getContext()->getRequest();
		$signed = false;
		if ($request->getParameter('signedView') == 1)
		{
			$signed = true;
		}
		return $signed;
	}

	private function addDependencies($scriptName, &$depArray)
	{
		if (array_key_exists($scriptName, $depArray))
		{
			return;
		}

		if (isset(self::$orderedScripts[$scriptName]))
		{
			foreach (self::$orderedScripts[$scriptName] as $value)
			{
				if ($this === self::getInstance())
				{
					$this->addDependencies($value, $depArray);
				}
				else
				{
					$this->addDependencies($value, self::$scriptRegistry);
				}
			}
		}
		$depArray[$scriptName] = null;
	}

	private $computedRegisteredScripts;
	public function getComputedRegisteredScripts()
	{
		if ($this->computedRegisteredScripts === null)
		{
			$this->loadOrderedScripts();
			$scriptRegistry = array();
			if ($this === self::getInstance())
			{
				$registery = self::$scriptRegistry;
			}
			else
			{
				$registery = $this->registery;
			}
			foreach ($registery as $scriptName => $value )
			{
				$this->addDependencies($scriptName, $scriptRegistry);
			}

			$scriptRegistryOrdered = array();
			foreach (self::$orderedScripts as $scriptName => $value)
			{
				if (array_key_exists($scriptName, $scriptRegistry))
				{
					$scriptRegistryOrdered[$scriptName] = $scriptRegistry[$scriptName];
					unset($scriptRegistry[$scriptName]);
				}
			}

			$scriptRegistryOrdered = array_merge($scriptRegistryOrdered, $scriptRegistry);
			$this->computedRegisteredScripts = $scriptRegistryOrdered;
		}
		return $this->computedRegisteredScripts;
	}

	public function generateXulLibrary()
	{
		$rc = RequestContext::getInstance();
		$rc->setMimeContentType(K::XUL);
		$scriptRegistryOrdered = $this->getComputedRegisteredScripts();
		foreach ($scriptRegistryOrdered as $scriptPath => $skin)
		{
			$fileSystemName = $this->getFileSystemName($scriptPath);
			if ($fileSystemName !== null)
			{
				ob_start();
				readfile($fileSystemName);
				$content = ob_get_contents();
				ob_end_clean();

				$tagReplacer = new f_util_TagReplacer();
				$content = $tagReplacer->run($content, true);
				echo $content;
			}
		}
	}

	/**
	 * @param $scriptName
	 * @param f_web_CSSVariables $skin
	 * @return string | null
	 */
	public function getJS($scriptName, $skin = null)
	{
		if ($scriptName === 'init')
		{
			return  $this->getScriptInit();
		}
		$fileSystemName = $this->getFileSystemName($scriptName);
		if ($fileSystemName === null)
		{
			Framework::warn(__METHOD__ . ' Script not found: ' . $scriptName);
			return '';
		}
		$this->loadOrderedScripts();
		$jsScript = file_get_contents($fileSystemName);
		$this->loadOrderedScripts();
		if (!array_key_exists($scriptName, self::$noReplacementScripts))
		{
			$tagReplacer = new f_util_TagReplacer();
			$tagReplacer->setReplacement('W_HOST', Framework::getBaseUrl());
			$tagReplacer->setReplacement('UIHOST', Framework::getUIBaseUrl());
			$jsScript = $tagReplacer->run($jsScript, true);
		}
		return $jsScript;
	}
	
	/**
	 * Gets the physical path of the given script.
	 *
	 * @param string $script Logical path of the script ("modules.website.lib.frontoffice")
	 * @return string Path
	 */
	public function getFileSystemName($script)
	{		
		// Case #1 - a cached JS file matches the requirement :
		$fileLocation = f_util_FileUtils::buildWebCachePath('js', $script . '.js');
		if (is_readable($fileLocation))
		{
			return $fileLocation;
		}

		// Case #2 - a module-based JS file matches the requirement :
		$match = null;
		if (preg_match('/^modules\.(\w+)\.(.*)/i', $script, $match))
		{
			$package = 'modules_' . $match[1];
			$path = Package::makeSystemPath($match[2]) . '.js';
				
			$fileLocation = FileResolver::getInstance()->setPackageName($package)->setDirectory(dirname($path))->getPath(basename($path));
				
			if (is_readable($fileLocation))
			{
				return $fileLocation;
			}
		}
		else if (preg_match('/^themes\.(\w+)\.(.*)/i', $script, $match))
		{
			$package = 'themes_' . $match[1];
			$path = Package::makeSystemPath($match[2]) . '.js';
				
			$fileLocation = FileResolver::getInstance()->setPackageName($package)->setDirectory(dirname($path))->getPath(basename($path));
				
			if (is_readable($fileLocation))
			{
				return $fileLocation;
			}
		}

		// Case #3 - a framework JS file matches the requirement :
		$fileLocation = FRAMEWORK_HOME . "/". Package::makeSystemPath($script) . '.js';
		if (is_readable($fileLocation))
		{
			return $fileLocation;
		}

		// Case #4 - everything that could be done has failed :
		return null;
	}


	/**
	 * Compacts the given JS content (NOT SAFE - DISABLED BY DEFAULT).
	 *
	 * @param string $content JS script content
	 * @return string Compacted script
	 */
	static public function compact($content)
	{
		$content = preg_replace('/\/\*(.*)\*\//sU', '', $content);
		$content = preg_replace('/^\/\/(.*)/', '', $content);
		$content = preg_replace('/[^:]\/\/(.*)/', '', $content);
		$content = preg_replace('/\s{2,}[+-]\s{2,}/', '+', $content);
		$content = preg_replace('/[\r\n]+/', "\n", $content);
		$content = preg_replace('/[\n]+/', ' ', $content);
		$content = preg_replace('/[ \t]+/', ' ', $content);
		$content = str_replace('} function', "};function", $content);
		$content = str_replace('} var', "};var", $content);
		$content = preg_replace('/\s([{};()<>=|+-])/', '$1', $content);
		$content = preg_replace('/([{};()<>=|+-])\s/', '$1', $content);
		return trim($content);
	}


	/**
	 * Returns the content of the initialization script (script with predefined PHP constants, etc.).
	 *
	 * @param Context $context Agavi context
	 * @return string Init script content
	 */
	static public function getScriptInit($context = null)
	{
		if (null === $context)
		{
			$context = Controller::getInstance()->getContext();
		}

		$attributes = array();
		$attributes['W_HOST_PREFIX'] = Framework::getUIProtocol() . '://';
		$attributes['W_HOST'] = Framework::getUIDefaultHost();

		$attributes['UIHOST_PREFIX'] = Framework::getUIProtocol() . '://';
		$attributes['UIHOST'] = Framework::getUIDefaultHost();
		$attributes['UIBASEURL'] = Framework::getUIBaseUrl();
		$attributes['LOG_LEVEL'] = Framework::$logLevel;
		$attributes['DEV_MODE'] = (defined('AG_DEVELOPMENT_MODE') && AG_DEVELOPMENT_MODE);
		$attributes['inDragSession'] = false;
		$attributes['RICHTEXT_PRESERVE_H1_TAGS'] = (defined('RICHTEXT_PRESERVE_H1_TAGS') && RICHTEXT_PRESERVE_H1_TAGS == 'true');
		$attributes['CHROME_BASEURL'] = isset($_SESSION['ChromeBaseUri']) ? 'xchrome://' . $_SESSION['ChromeBaseUri'] : false;
		$attributes['CONTROLLER'] = $attributes['UIBASEURL'] . '/xul_controller.php';

		$langs = array();
		$rc = RequestContext::getInstance();
		foreach ($rc->getSupportedLanguages() as $lang)
		{
			$langs[$lang] = array('label' => f_Locale::translateUI('&modules.uixul.bo.languages.'.ucfirst($lang).';'));
		}
		$attributes['LANGS'] = $langs;
		$attributes['W_LANG'] = $rc->getLang();
		$attributes['W_UILANG'] = $rc->getUILang();

		$js = K::CRLF . 'var Context = ' . JsonService::getInstance()->encode($attributes) . ';' . K::CRLF;

		$constants = array();
		$constants['WEBEDIT_MODULE_ACCESSOR'] = K::WEBEDIT_MODULE_ACCESSOR;
		$constants['COMPONENT_ACCESSOR'] = K::COMPONENT_ACCESSOR;
		$constants['COMPONENT_ID_ACCESSOR'] = K::COMPONENT_ID_ACCESSOR;
		$constants['COMPONENT_LANG_ACCESSOR'] = K::COMPONENT_LANG_ACCESSOR;
		$constants['GENERIC_MODULE_NAME'] = K::GENERIC_MODULE_NAME;
		$constants['PARENT_ID_ACCESSOR'] = K::PARENT_ID_ACCESSOR;
		$constants['VALUE_ACCESSOR'] = K::VALUE_ACCESSOR;
		$constants['LABEL_ACCESSOR'] = K::LABEL_ACCESSOR;
		$constants['TREE_FILTER'] = K::TREE_FILTER;
		$constants['LANG_ACCESSOR'] = K::LANG_ACCESSOR;
		$constants['FULL_TREE_CONTENT_ACCESSOR'] = K::FULL_TREE_CONTENT_ACCESSOR;
		$constants['LINKED_COMPONENT_ACCESSOR'] = K::LINKED_COMPONENT_ACCESSOR;
		$js .= K::CRLF . 'var K = ' . JsonService::getInstance()->encode($constants) . ';' . K::CRLF;

		return $js;
	}
}
