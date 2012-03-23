<?php
class LocaleService extends BaseService
{
	private $LCID_BY_LANG = null;
	
	private $ignoreTransform;
	
	protected $transformers;
	
	/**
	 * The singleton instance
	 * @var LocaleService
	 */
	private static $instance = null;
	
	/**
	 * @return LocaleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	protected function __construct()
	{
		$this->ignoreTransform = array('TEXT' => 'raw', 'HTML' => 'html');
		$this->transformers = array(
			'lab' => 'transformLab', 'uc' => 'transformUc', 'ucf' => 'transformUcf', 'lc' => 'transformLc', 
			'js' => 'transformJs', 'html' => 'transformHtml', 'text' => 'transformText', 
			'attr' => 'transformAttr', 'space' => 'transformSpace', 'etc' => 'transformEtc', 'ucw' => 'transformUcw');
	}
	
	/**
	 * @param string $langCode
	 * @return string
	 */
	public function getLCID($langCode)
	{
		if ($this->LCID_BY_LANG === null)
		{
			$this->LCID_BY_LANG = Framework::getConfiguration('i18n');
		}
		if (! isset($this->LCID_BY_LANG[$langCode]))
		{
			if (strlen($langCode) === 2)
			{
				$this->LCID_BY_LANG[$langCode] = strtolower($langCode) . '_' . strtoupper($langCode);
			}
			else
			{
				$this->LCID_BY_LANG[$langCode] = strtolower($langCode);
			}
		}
		return $this->LCID_BY_LANG[$langCode];
	}
	
	
	public function importOverride($name = null)
	{
		if ($name === null || $name === 'framework')
		{
			$i18nPath = f_util_FileUtils::buildOverridePath('framework', 'i18n');
			if (is_dir($i18nPath))
			{
				$this->importOverrideDir($i18nPath, 'f');
			}
			
			if ($name === 'framework')
			{
				return true;
			}
		}
		if ($name === null)
		{
			$pathPattern = f_util_FileUtils::buildOverridePath('themes', '*', 'i18n');
			$paths = glob($pathPattern, GLOB_ONLYDIR);
			if (is_array($paths))
			{
				foreach ($paths as $i18nPath)
				{
					$name = basename(dirname($i18nPath));
					$basekey = 't.' . $name;
					$this->importOverrideDir($i18nPath, $basekey);
				}	
			}

			$pathPattern = f_util_FileUtils::buildOverridePath('modules', '*', 'i18n');
			$paths = glob($pathPattern, GLOB_ONLYDIR);
			if (is_array($paths))
			{
				foreach ($paths as $i18nPath)
				{
					$name = basename(dirname($i18nPath));
					$basekey = 'm.' . $name;
					$this->importOverrideDir($i18nPath, $basekey);
				}
			}
		}
		else
		{
			$parts = explode('/', $name);
			if (count($parts) != 2)
			{
				return false;
			}
			if ($parts[0] === 'modules')
			{
				$basekey = 'm.' . $parts[1];
			}
			else if ($parts[0] === 'themes')
			{
				$basekey = 't.' . $parts[1];
			}
			else
			{
				return false;
			}
			$i18nPath = f_util_FileUtils::buildOverridePath($parts[0], $parts[1], 'i18n');
			if (is_dir($i18nPath))
			{
				$this->importOverrideDir($i18nPath, $basekey);
			}
		}
		return true;
	}
	
	private function importOverrideDir($dir, $baseKey)
	{
		foreach (scandir($dir) as $file)
		{
			if ($file[0] == ".")
			{
				continue;
			}
			$absFile = $dir . DIRECTORY_SEPARATOR . $file;
			if (is_dir($absFile))
			{
				$this->importOverrideDir($absFile, $baseKey . '.' . $file);
			}
			elseif (f_util_StringUtils::endsWith($file, '.xml'))
			{
				$entities = array();
				//$entities[$lcid][$id] = array($content, $format);
				$this->processFile($absFile, $entities, false);
				if (count($entities))
				{
					echo "Import $baseKey\n";
					$this->updatePackage($baseKey, $entities);
				}
				echo "Remove file $absFile\n";
				unlink($absFile);
			}
		}	
		f_util_FileUtils::rmdir($dir);	
	}
	
	
	/**
	 * @param string $name [null] | framework | modules_NAME | themes_NAME
	 * @param boolean $removeLocalFolder
	 * @param boolean $overrideI18Folder
	 * @return boolean
	 */
	public function convertLocales($name = null, $removeLocalFolder = false, $overrideI18Folder = false)
	{
		if ($name === null || $name === 'framework')
		{
			$path = f_util_FileUtils::buildWebeditPath('framework', 'locale');
			if (is_dir($path))
			{
				echo "Convert framework...\n";
				$i18nPath = f_util_FileUtils::buildWebeditPath('framework', 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('f', $path, false);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			
			$path = f_util_FileUtils::buildOverridePath('framework', 'locale');
			if (is_dir($path))
			{
				echo "Convert framework override...\n";
				$i18nPath = f_util_FileUtils::buildOverridePath('framework', 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('f', $path, true);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			
			if ($name === 'framework')
			{
				return true;
			}
		}
		
		if ($name === null)
		{
			//Themes locales conversion
			$pathPattern = f_util_FileUtils::buildWebeditPath('themes', '*', 'locale');
			foreach (glob($pathPattern, GLOB_ONLYDIR) as $path)
			{
				$name = basename(dirname($path));
				echo "Convert theme $name ...\n";
				$i18nPath = f_util_FileUtils::buildWebeditPath('themes', $name, 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('t.' . $name, $path, false);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			
			$pathPattern = f_util_FileUtils::buildOverridePath('themes', '*', 'locale');
			foreach (glob($pathPattern, GLOB_ONLYDIR) as $path)
			{
				$name = basename(dirname($path));
				echo "Convert theme $name override...\n";
				$i18nPath = f_util_FileUtils::buildOverridePath('themes', $name, 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('t.' . $name, $path, true);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			
			//Modules locales conversion
			$pathPattern = f_util_FileUtils::buildWebeditPath('modules', '*', 'locale');
			foreach (glob($pathPattern, GLOB_ONLYDIR) as $path)
			{
				$name = basename(dirname($path));
				echo "Convert module $name ...\n";
				$i18nPath = f_util_FileUtils::buildWebeditPath('modules', $name, 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('m.' . $name, $path, false);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			
			$pathPattern = f_util_FileUtils::buildOverridePath('modules', '*', 'locale');
			foreach (glob($pathPattern, GLOB_ONLYDIR) as $path)
			{
				$name = basename(dirname($path));
				echo "Convert module $name override...\n";
				$i18nPath = f_util_FileUtils::buildOverridePath('modules', $name, 'i18n');
				if ($overrideI18Folder && is_dir($i18nPath))
				{
					f_util_FileUtils::rmdir($i18nPath);
				}
				$this->convertDir('m.' . $name, $path, true);
				if ($removeLocalFolder)
				{
					f_util_FileUtils::rmdir($path);
				}
			}
			return true;
		}
		
		$parts = explode('/', $name);
		if (count($parts) != 2)
		{
			return false;
		}
		if ($parts[0] === 'modules')
		{
			$basekey = 'm.' . $parts[1];
		}
		else if ($parts[0] === 'themes')
		{
			$basekey = 't.' . $parts[1];
		}
		else
		{
			return false;
		}
		
		$path = f_util_FileUtils::buildWebeditPath($parts[0], $parts[1], 'locale');
		if (is_dir($path))
		{
			$i18nPath = f_util_FileUtils::buildWebeditPath($parts[0], $parts[1], 'i18n');
			if ($overrideI18Folder && is_dir($i18nPath))
			{
				f_util_FileUtils::rmdir($i18nPath);
			}
			$this->convertDir($basekey, $path, false);
			if ($removeLocalFolder)
			{
				f_util_FileUtils::rmdir($path);
			}
		}
		
		$path = f_util_FileUtils::buildOverridePath($parts[0], $parts[1], 'locale');
		if (is_dir($path))
		{
			$i18nPath = f_util_FileUtils::buildOverridePath($parts[0], $parts[1], 'i18n');
			if ($overrideI18Folder && is_dir($i18nPath))
			{
				f_util_FileUtils::rmdir($i18nPath);
			}
			$this->convertDir($basekey, $path, true);
			if ($removeLocalFolder)
			{
				f_util_FileUtils::rmdir($path);
			}
		}
		return true;
	}
	
	/**
	 * @param string $baseKey
	 * @param array $keysInfos [lcid => [id => [text, format]]
	 * @param boolean $override
	 * @param boolean $addOnly
	 * @param string $includes
	 */
	public function updatePackage($baseKey, $keysInfos, $override = false, $addOnly = false, $includes = '')
	{
		if (is_array($keysInfos))
		{
			foreach ($keysInfos as $lcid => $values)
			{
				if (strlen($lcid) === 5)
				{
					$this->updateI18nFile($baseKey, $lcid, $values, $includes, $override, $addOnly);
				}
			}
		}
	}
	
	/**
	 * Parse recursively directory and launch the genration of localization for all locale XML file
	 *
	 * @param string $package
	 * @param string $dir
	 * @param boolean $override
	 */
	private function convertDir($package, $dir, $override)
	{
		$dirs = array();
		foreach (scandir($dir) as $file)
		{
			if ($file[0] == ".")
			{
				continue;
			}
			$absFile = $dir . DIRECTORY_SEPARATOR . $file;
			if (is_dir($absFile))
			{
				$dirs[$package . '.' . $file] = $absFile;
			}
			elseif (f_util_StringUtils::endsWith($file, '.xml'))
			{
				$entities = array();
				$includes = '';
				$this->readOldFile($absFile, $entities, $includes);
				$baseKey = $package . '.' . basename($absFile, '.xml');
				$keys = $this->convertEntities($entities, $baseKey);
				foreach ($keys as $lcid => $data)
				{
					foreach ($data as $baseKey => $values)
					{
						$this->updateI18nFile($baseKey, $lcid, $values, $includes, $override, false);
					}
				}
			}
		}
		foreach ($dirs as $package => $dir)
		{
			$this->convertDir($package, $dir, $override);
		}
	}
	
	private function readOldFile($file, &$entities, &$extend)
	{
		// Load the XMl file
		$dom = f_util_DOMUtils::fromPath($file);
		if ($dom->documentElement->hasAttribute("extend"))
		{
			$parentInfo = explode(".", $dom->documentElement->getAttribute("extend"));
			if ($parentInfo[0] == "framework")
			{
				$parentInfo[0] = 'f';
				$extend = implode('.', $parentInfo);
			}
			elseif ($parentInfo[0] == "modules")
			{
				$parentInfo[0] = "m";
				$extend = implode('.', $parentInfo);
			}
			elseif ($parentInfo[0] == "themes")
			{
				$parentInfo[0] = "t";
				$extend = implode('.', $parentInfo);
			}
		}
		
		foreach ($dom->find("entity") as $entity)
		{
			$entityId = strtolower($entity->getAttribute('id'));
			if (! array_key_exists($entityId, $entities))
			{
				$entities[$entityId] = array();
				foreach ($dom->find("locale", $entity) as $locale)
				{
					$lang = strtolower($locale->getAttribute('lang'));
					if (! array_key_exists($lang, $entities[$entityId]))
					{
						$entities[$entityId][$lang] = array();
						$content = str_replace('"', '″', $locale->textContent);
						$entities[$entityId][$lang]['value'] = $content;
					}
				}
			}
		}
	}
	
	private function convertEntities($entities, $baseKey)
	{
		$keys = array();
		foreach ($entities as $subKey => $langsInfos)
		{
			$id = strtolower($baseKey . '.' . $subKey);
			foreach ($langsInfos as $lang => $data)
			{
				$key = $this->getKeyId($id);
				$path = $this->getBaseKey($id);
				$keys[$this->getLCID($lang)][$path][$key] = $data['value'];
			}
		}
		return $keys;
	}
	
	private function getBaseKey($key)
	{
		return substr($key, 0, strrpos($key, '.'));
	}
	
	private function getKeyId($key)
	{
		return end(explode('.', $key));
	}
	
	private function getI18nFilePath($baseKey, $lcid, $override = false)
	{
		$parts = explode('.', $baseKey);
		$parts[] = $lcid . '.xml';
		switch ($parts[0])
		{
			case 'f' :
			case 'framework' :
				$parts[0] = '/framework/i18n';
				break;
			case 'm' :
			case 'modules' :
				$parts[0] = '/modules';
				$parts[1] .= '/i18n';
				break;
			case 't' :
			case 'themes' :
				$parts[0] = '/themes';
				$parts[1] .= '/i18n';
				break;
		}
		if ($override)
		{
			return PROJECT_OVERRIDE . implode('/', $parts);
		}
		
		return WEBEDIT_HOME . implode('/', $parts);
	}
	
	/**
	 * @param string $baseKey
	 * @param string $lcid
	 * @param array $values
	 * @param string $include
	 * @param boolean $override
	 * @param boolean $addOnly
	 */
	private function updateI18nFile($baseKey, $lcid, $values, $includes, $override, $addOnly)
	{
		$path = $this->getI18nFilePath($baseKey, $lcid, $override);
		
		if (is_readable($path))
		{
			$i18nDoc = f_util_DOMUtils::fromPath($path);
		}
		else
		{
			$i18nDoc = f_util_DOMUtils::fromString('<?xml version="1.0" encoding="utf-8"?><i18n/>');
		}
		$i18nNode = $i18nDoc->documentElement;
		$i18nNode->setAttribute('baseKey', $baseKey);
		$i18nNode->setAttribute('lcid', $lcid);
		if ($includes !== '')
		{
			foreach (explode(',', $includes) as $include)
			{
				$include = strtolower(trim($include));
				if ($include == '')
				{
					continue;
				}
				
				$includeNode = $i18nDoc->findUnique('include[@id="' . $include . '"]', $i18nNode);
				if ($includeNode === null)
				{
					$includeNode = $i18nDoc->createElement('include');
					$includeNode->setAttribute('id', $includes);
					if ($i18nNode->firstChild)
					{
						$i18nNode->insertBefore($includeNode, $i18nNode->firstChild);
					}
					else
					{
						$i18nNode->appendChild($includeNode);
					}
				}
			}
		}
		
		foreach ($values as $id => $value)
		{
			$id = strtolower($id);
			$keyNode = $i18nDoc->findUnique('key[@id="' . $id . '"]', $i18nNode);
			if ($keyNode !== null)
			{
				if ($addOnly)
				{
					continue;
				}
				$newNode = $i18nDoc->createElement('key');
				$i18nNode->replaceChild($newNode, $keyNode);
			}
			else
			{
				$newNode = $i18nNode->appendChild($i18nDoc->createElement('key'));
			}
			$newNode->setAttribute('id', $id);
			if (is_array($value))
			{
				list($content, $format) = $value;
				$format = strtolower($format);
				if ($format != 'text')
				{
					$newNode->setAttribute('format', $format);
					$newNode->appendChild($i18nDoc->createCDATASection($content));
				}
				else 
				{
					$newNode->appendChild($i18nDoc->createTextNode($content));
				}
			}
			else
			{
				$newNode->appendChild($i18nDoc->createTextNode($value));
			}
		}
		f_util_DOMUtils::save($i18nDoc, $path);
	}
	
	/**
	 * Regenerate all locales of application
	 */
	public function regenerateLocales()
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
			$this->getPersistentProvider()->clearTranslationCache();
			$this->processModules();
			$this->processFramework();
			$this->processThemes();
				
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * Regenerate locale for a module and save in databases
	 *
	 * @param string $moduleName Example: users
	 */
	public function regenerateLocalesForModule($moduleName)
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			$this->getPersistentProvider()->clearTranslationCache('m.' . $moduleName);		
			// Processing module : $moduleName
			$this->processModule($moduleName);
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * Regenerate locale for a theme and save in databases
	 *
	 * @param string $themeName Example: webfactory
	 */
	public function regenerateLocalesForTheme($themeName)
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			$this->getPersistentProvider()->clearTranslationCache('t.' . $themeName);
			$this->processTheme($themeName);
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * Regenerate locale for the framework and save in databases
	 */
	public function regenerateLocalesForFramework()
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			$this->getPersistentProvider()->clearTranslationCache('f');
			$this->processFramework();
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * Insert locale keys for all modules
	 */
	private function processModules()
	{
		$paths = glob(WEBEDIT_HOME . "/modules/*/i18n", GLOB_ONLYDIR);
		if (! is_array($paths))
		{
			return;
		}
		foreach ($paths as $path)
		{
			$moduleName = basename(dirname($path));
			$this->processModule($moduleName);
		}
	}
	
	private function processThemes()
	{
		$paths = glob(WEBEDIT_HOME . "/themes/*/i18n", GLOB_ONLYDIR);
		foreach ($paths as $path)
		{
			$themeName = basename(dirname($path));
			$this->processTheme($themeName);
		}
	}
	
	/**
	 * Compile locale for a module
	 * @param string $moduleName Example: users
	 */
	private function processModule($moduleName)
	{
		$availablePaths = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory(
				'i18n')->getPaths('');
		if ($availablePaths === null)
		{
			return;
		}
		
		$availablePaths = array_reverse($availablePaths);
		// For all path found for the locale of module insert all localization keys
		foreach ($availablePaths as $path)
		{
			$this->processDir('m.' . $moduleName, $path);
		}
	}
	
	/**
	 * Compile locale for a theme
	 * @param string $themeName Example: webfactory
	 */
	private function processTheme($themeName)
	{
		$availablePaths = FileResolver::getInstance()->setPackageName('themes_' . $themeName)->setDirectory(
				'i18n')->getPaths('');
		if ($availablePaths === null)
		{
			return;
		}
		$availablePaths = array_reverse($availablePaths);
		
		// For all path found for the locale of module insert all localization keys
		foreach ($availablePaths as $path)
		{
			$this->processDir('t.' . $themeName, $path);
		}
	}
	
	/**
	 * Generate the framework localization
	 *
	 * @param string $dir
	 * @param string $basedir
	 */
	private function processFramework()
	{
		
		try
		{
			$this->getTransactionManager()->beginTransaction();
			
			$availablePaths = array(f_util_FileUtils::buildFrameworkPath('i18n'), 
					f_util_FileUtils::buildOverridePath('framework', 'i18n'));
			foreach ($availablePaths as $path)
			{
				if (is_dir($path))
				{
					$this->processDir("f", $path);
				}
			}
			
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
			throw $e;
		}
	
	}
	
	/**
	 * Parse recursively directory and launch the genration of localization for all locale XML file
	 *
	 * @param string $baseKey
	 * @param string $dir
	 */
	private function processDir($baseKey, $dir)
	{
		if (substr($dir, - 1) === DIRECTORY_SEPARATOR)
		{
			$dir = substr($dir, 0, strlen($dir) - 1);
		}
		
		if (is_dir($dir))
		{
			$dirs = array();
			$entities = array();
			foreach (scandir($dir) as $file)
			{
				if ($file[0] == ".")
				{
					continue;
				}
				$absFile = $dir . DIRECTORY_SEPARATOR . $file;
				if (is_dir($absFile))
				{
					$dirs[$baseKey . '.' . $file] = $absFile;
				}
				elseif (f_util_StringUtils::endsWith($file, '.xml'))
				{
					$this->processFile($absFile, $entities);

				}
			}
			
			if (count($entities))
			{
				$this->applyEntitiesI18nSyncho($entities);
				$this->processDatabase($baseKey, $entities);
			}
			
			foreach ($dirs as $baseKey => $dir)
			{
				$this->processDir($baseKey, $dir);
			}
		}
	}
	
	protected function applyEntitiesI18nSyncho(&$entities)
	{
		$syncConf = RequestContext::getInstance()->getI18nSyncho();
		if (count($syncConf) === 0) {return;}
		foreach ($syncConf as $to => $froms)
		{
			$toLCID = $this->getLCID($to);
			foreach ($froms as $from)
			{
				$fromLCID = $this->getLCID($from);
				if (isset($entities[$fromLCID]))
				{
					if (!isset($entities[$toLCID]))
					{
						$entities[$toLCID] = array();
					}
					foreach ($entities[$fromLCID] as $id => $data)
					{
						if (!isset($entities[$toLCID][$id]))
						{
							$entities[$toLCID][$id] = $data;
						}		
					}
				}
			}
		}
	}
	
	/**
	 * Read a file and extract informations of localization
	 * @param string $file
	 * @param array $entities
	 * @param boolean $processInclude
	 */
	private function processFile($file, &$entities, $processInclude = true)
	{
		$lcid = basename($file, '.xml');
		$dom = f_util_DOMUtils::fromPath($file);
		foreach ($dom->documentElement->childNodes as $node)
		{
			if ($node->nodeType == XML_ELEMENT_NODE)
			{
				if ($node->nodeName == 'include' && $processInclude)
				{
					$id = $node->getAttribute('id');
					$subPath = $this->getI18nFilePath($id, $lcid);
					$ok = false;
					if (file_exists($subPath))
					{
						$ok = true;
						$this->processFile($subPath, $entities);
					}
					$subPath = $this->getI18nFilePath($id, $lcid, true);
					if (file_exists($subPath))
					{
						$ok = true;
						$this->processFile($subPath, $entities);
					}
					if (! $ok && Framework::isWarnEnabled())
					{
						Framework::warn("Include ($id) not found in file $file");
					}
				}
				else if ($node->nodeName == 'key')
				{
					$id = $node->getAttribute('id');
					$content = $node->textContent;
					$format = $node->getAttribute('format') === 'html' ? 'HTML' : 'TEXT';
					$entities[$lcid][$id] = array($content, $format);
				}
			}
		}
	}
	
	/**
	 * @return array [baseKey => nbLocales]
	 */
	public function getPackageNames()
	{
		return $this->getPersistentProvider()->getPackageNames();
	}

	/**
	 * @return array [baseKey => nbLocales]
	 */
	public function getUserEditedPackageNames()
	{
		return $this->getPersistentProvider()->getUserEditedPackageNames();
	}
	
	/**
	 * 
	 * @param string $keyPath
	 * @return array[id => [lcid => ['content' => string, 'useredited' => integer, 'format' => string]]]
	 */
	public function getPackageContent($keyPath)
	{
		$result = $this->getPersistentProvider()->getPackageData($keyPath);
		$contents = array();
		foreach ($result as $row)
		{
			$contents[$row['id']][$row['lang']] = array('content' => $row['content'], 
					'useredited' => $row['useredited'] == "1", 'format' => $row['format']);
		}
		return $contents;
	}

	/**
	 * @param string $keyPath
	 * @return array[id => [lcid => ['content' => string, 'useredited' => integer, 'format' => string]]]
	 */
	public function getPackageContentFromFile($keyPath)
	{
		$entities = array();
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang) 
		{
			$lcid = $this->getLCID($lang);
			$filePath = $this->getI18nFilePath($keyPath, $lcid);
			if (file_exists($filePath))
			{
				$this->processFile($filePath, $entities);
			}
			$filePath = $this->getI18nFilePath($keyPath, $lcid, true);
			if (file_exists($filePath))
			{
				$this->processFile($filePath, $entities);
			}
		}
		$results  = array();
		if (count($entities))
		{
			foreach ($entities as $lcid => $infos) 
			{
				foreach ($infos as $id => $entityInfos)
				{
					list($content, $format) = $entityInfos;
					$results[$id][$lcid] = array('content' => $content, 'useredited' => false, 'format' => $format);
				}
			}
		}
		return $results;
	}
	
	/**
	 * @param string $keyPath
	 * @param array $entities
	 */
	private function processDatabase($keyPath, $entities)
	{
		$keyPath = strtolower($keyPath);
		
		$provider = $this->getPersistentProvider();
		$lcids = array();
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang)
		{
			$lcids[$this->getLCID($lang)] = $lang;
		}
		foreach ($entities as $lcid => $infos)
		{
			if (! isset($lcids[$lcid]))
			{
				continue;
			}
			foreach ($infos as $id => $entityInfos)
			{
				list($content, $format) = $entityInfos;
				$provider->addTranslate($lcid, strtolower($id), $keyPath, $content, 0, $format, false);
			}
		}
	}
	
	/**
	 * 
	 * @param string $lcid exemple fr_FR
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param string $format TEXT | HTML
	 */
	public function updateUserEditedKey($lcid, $id, $keyPath, $content, $format)
	{
		$this->updateKey($lcid, $id, $keyPath, $content, $format, true);
	}
	
	public function deleteUserEditedKey($lcid, $id, $keyPath)
	{
		$provider = $this->getPersistentProvider();
		$provider->deleteI18nKey($keyPath, $id, $lcid);
	}
	
	/**
	 * @param string $lcid exemple fr_FR
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param string $format TEXT | HTML
	 * @param boolean $userEdited
	 */
	public function updateKey($lcid, $id, $keyPath, $content, $format, $userEdited = false)
	{
		$provider = $this->getPersistentProvider();
		$provider->addTranslate($lcid, $id, $keyPath, $content, $userEdited ? 1 : 0, $format, true);
	}
	
	/**
	 * @param string $cleanKey
	 * @return array(keyPath, id) || array(false, false);
	 */
	public function explodeKey($cleanKey)
	{
		$parts = explode('.', strtolower($cleanKey));
		if (count($parts) < 3) {return array(false, false);}
		
		$id = end($parts);
		$keyPathParts = array_slice($parts, 0, - 1);
		switch ($keyPathParts[0])
		{
			case 'f' :
			case 'm' :
			case 't' :
				break;
			case 'framework' :
				$keyPathParts[0] = 'f';
				break;
			case 'modules' :
				$keyPathParts[0] = 'm';
				break;
			case 'themes' :
				$keyPathParts[0] = 't';
				break;
			default :
				return array(false, false);
		}
		return array(implode('.', $keyPathParts), $id);
	}
	
	/**
	 * @param string $lang
	 * @param string $cleanKey
	 * @return string | null
	 */
	public function getFullKeyContent($lang, $cleanKey)
	{
		list ($keyPath, $id) = $this->explodeKey($cleanKey);
		if ($keyPath !== false)
		{
			$lcid = $this->getLCID($lang);
			list($content, ) = f_persistentdocument_PersistentProvider::getInstance()->translate($lcid, $id, $keyPath);
			
			if ($content === null)
			{
				Framework::info("No translation for $keyPath, $id, $lcid");
			}
			return $content;
		}
		
		Framework::warn("Invalid Key $cleanKey");
		return null;
	}
	
	/**
	 * @param string $oldKey
	 * @return string | false
	 */
	public function cleanOldKey($oldKey)
	{
		$l = strlen($oldKey);
		if ($l > 2 && $oldKey[0] === '&' & $oldKey[$l-1] === ';')
		{
			return str_replace(array('&modules.', '&framework.', '&themes.', ';', '&'), array('m.', 'f.', 't.', '', ''), $oldKey);
		}
		return false;
	}
	
	/**
	 * @param string $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, html, attr
	 * @param array $replacements
	 * @return string | $cleanKey
	 */
	public function transFO($cleanKey, $formatters = array(), $replacements = array())
	{
		return $this->formatKey(RequestContext::getInstance()->getLang(), $cleanKey, $formatters, 
				$replacements);
	}
	
	/**
	 * @param string $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, html, attr
	 * @param array $replacements
	 * @return string | $cleanKey
	 */
	public function transBO($cleanKey, $formatters = array(), $replacements = array())
	{
		return $this->formatKey(RequestContext::getInstance()->getUILang(), $cleanKey, $formatters, 
				$replacements);
	}
	
	/**
	 * @param string $text
	 * @return string
	 */
	public function translateText($text)
	{
		if (f_util_StringUtils::isEmpty($text)) {return $text;}
		if (preg_match_all('/\$\{(trans|transui):([^}]*)\}/', $text, $matches, PREG_SET_ORDER))
		{
			$search = array();
			$replace = array();
			$rc = RequestContext::getInstance();
			foreach ($matches as $infos) 
			{
				$search[] = $infos[0];
				$lang = ($infos[1] === 'transui') ? $rc->getUILang() : $rc->getLang();
				list($key, $formatters, $replacements) = $this->parseTransString($infos[2]);
				$replace[] = $this->formatKey($lang, $key, $formatters, $replacements);
			}
			$text = str_replace($search, $replace, $text);
		}
		return $text;
	}
	
	
	/**
	 * @param string $lang
	 * @param string $cleanKey
	 * @param array $formatters value in array lab, lc, uc, ucf, js, attr, raw, text, html
	 * @param array $replacements
	 */
	public function formatKey($lang, $cleanKey, $formatters = array(), $replacements = array())
	{
		list ($keyPath, $id) = $this->explodeKey($cleanKey);
		if ($keyPath === false)
		{
			return $cleanKey;
		}
		$lcid = $this->getLCID($lang);
		list($content, $format) = f_persistentdocument_PersistentProvider::getInstance()->translate($lcid, $id, $keyPath);
		if ($content === null)
		{
			return $cleanKey;
		}
		
		if (count($replacements))
		{
			$search = array();
			$replace = array();
			foreach ($replacements as $key => $value)
			{
				$search[] = '{' . $key . '}';
				$replace[] = $value;
			}
			$content = str_replace($search, $replace, $content);
		}
		
		if (count($formatters))
		{
			foreach ($formatters as $formatter)
			{
				if ($formatter === 'raw' || $formatter === $this->ignoreTransform[$format]) 
				{
					continue;
				}	
				if (isset($this->transformers[$formatter]))
				{
					$content = $this->{$this->transformers[$formatter]}($content, $lang);
				}
				else 
				{
					Framework::warn(__METHOD__ . ' Invalid formatter '. $formatter);
				}
			}
		}
		return $content;
	}
		
	public function transformLab($text, $lang)
	{
		return $text . ($lang == 'fr' ? ' :' : ':');
	}
	
	public function transformUc($text, $lang)
	{
		return f_util_StringUtils::strtoupper($text);
	}
	
	public function transformUcf($text, $lang)
	{
		return f_util_StringUtils::ucfirst($text);
	}
	
	public function transformUcw($text, $lang)
	{
		return mb_convert_case($text, MB_CASE_TITLE, "UTF-8"); 
	}
	
	public function transformLc($text, $lang)
	{
		return f_util_StringUtils::strtolower($text);
	}
	
	public function transformJs($text, $lang)
	{
		return str_replace(array("\\", "\t", "\n", "\"", "'"), 
				array("\\\\", "\\t", "\\n", "\\\"", "\\'"), $text);
	}
	
	public function transformHtml($text, $lang)
	{
		return nl2br(htmlspecialchars($text, ENT_COMPAT, 'UTF-8'));
	}
	
	public function transformText($text, $lang)
	{
		return f_util_StringUtils::htmlToText($text);
	}
	
	public function transformAttr($text, $lang)
	{
		return f_util_HtmlUtils::textToAttribute($text);
	}
	
	public function transformSpace($text, $lang)
	{
		return ' ' . $text . ' ';
	}
	
	public function transformEtc($text, $lang)
	{
		return $text . '...';
	}
	
	/**
	 * @param string $transString
	 * @return array[$key, $formatters, $replacements]
	 */
	public function parseTransString($transString)
	{
		$formatters = array();
		$replacements = array();
		$key = null;
		$parts = explode(',' , $transString);
		$key = strtolower(trim($parts[0]));		
		$count = count($parts);
		for ($i = 1; $i < $count; $i++)
		{
			$data = trim($parts[$i]);
			if (strlen($data) == 0) {continue;}
			if (strpos($data, '='))
			{
				$subParts = explode('=' , $data);
				if (count($subParts) == 2)
				{
					list($name, $value) = $subParts;
					$name = trim($name);
					$value = trim($value);
					$l = strlen($value);
					if ($l === 0)
					{
						$replacements[$name] = '';
					}
					else
					{
						$replacements[$name] = $value;
					}
				}
			}
			else
			{
				$data = strtolower($data);
				$formatters[] = $data;
			}
		}
		return array($key, $formatters, $replacements);
	}
	

	
	const SYNCHRO_MODIFIED = 'MODIFIED';
	const SYNCHRO_VALID = 'VALID';
	const SYNCHRO_SYNCHRONIZED = 'SYNCHRONIZED';
		
	/**
	 * @param integer $documentId
	 */
	public function resetSynchroForDocumentId($documentId)
	{
		if (RequestContext::getInstance()->hasI18nSyncho())
		{
			$d = DocumentHelper::getDocumentInstanceIfExists($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				$this->getPersistentProvider()->setI18nSynchroStatus($d->getId(), $d->getLang(), self::SYNCHRO_MODIFIED, null);
			}
		}
	}
	

	/**
	 * @param integer $documentId
	 */
	public function initSynchroForDocumentId($documentId)
	{
		if (RequestContext::getInstance()->hasI18nSyncho())
		{
			$d = DocumentHelper::getDocumentInstanceIfExists($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				foreach ($d->getI18nInfo()->getLangs() as $lang)
				{
					$this->getPersistentProvider()->setI18nSynchroStatus($d->getId(), $lang, self::SYNCHRO_MODIFIED, null);
				}
			}
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function getDocumentIdsToSynchronize()
	{
		if (RequestContext::getInstance()->hasI18nSyncho())
		{
			return $this->getPersistentProvider()->getI18nSynchroIds();
		}
		return array();
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return array
	 * 		- isLocalized : boolean
	 * 		- action : 'none'|'generate'|'synchronize'
	 * 		- config : array
	 * 			- 'fr'|'??' : string[]
	 * 			- ...
	 *		- states : array
	 * 			- 'fr'|'??' : array
	 * 				- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * 				- from : fr'|'en'|'??'|null
	 * 			- ...
	 */
	public function getI18nSynchoForDocument($document)
	{
		$result = array('isLocalized' => false, 'action' => 'none', 'config' => array());
		$pm = $document->getPersistentModel();
		if ($pm->isLocalized())
		{
			$result['isLocalized'] = true;
			$rc = RequestContext::getInstance();
			if ($rc->hasI18nSyncho())
			{
				$result['config'] = $rc->getI18nSyncho();
				$data = $this->getPersistentProvider()->getI18nSynchroStatus($document->getId());
				$result['states'] = $data;
				foreach ($document->getI18nInfo()->getLangs() as $lang)
				{
					if (!isset($data[$lang]))
					{
						$result['action'] = 'generate';
						break;
					}
					elseif ($data[$lang]['status'] === self::SYNCHRO_MODIFIED)
					{
						$result['action'] = 'synchronize';
					}
				}
			}
		}
		return $result;	
	}
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public function synchronizeDocumentId($documentId)
	{
		$rc = RequestContext::getInstance();
		if (!$rc->hasI18nSyncho())
		{
			//No synchro configured
			return false;
		}
		$d = DocumentHelper::getDocumentInstanceIfExists($documentId);
		if ($d === null)
		{
			//Invalid document
			return false;
		}
		
		$pm = $d->getPersistentModel();
		if (!$pm->isLocalized())
		{
			//Not applicable on this document
			return false;
		}
		
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();	
			$ds = $d->getDocumentService();
			
			$synchroConfig = $ds->getI18nSynchroConfig($d, $rc->getI18nSyncho());			
			if (count($synchroConfig))
			{			
				$dcs = f_DataCacheService::getInstance();
				$datas = $tm->getPersistentProvider()->getI18nSynchroStatus($d->getId());
				if (count($datas) === 0)
				{
					foreach ($d->getI18nInfo()->getLangs() as $lang)
					{
						$datas[$lang] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
					}
				}
				else
				{
					$datas[$d->getLang()] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
				}
				
				foreach ($synchroConfig as $lang => $fromLangs)
				{
					if (!isset($datas[$lang]) || $datas[$lang]['status'] === self::SYNCHRO_SYNCHRONIZED)
					{
						foreach ($fromLangs as $fromLang)
						{
							if (isset($datas[$fromLang]) && $datas[$fromLang]['status'] !== self::SYNCHRO_SYNCHRONIZED)
							{
								list($from, $to) = $tm->getPersistentProvider()->prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
								try
								{
									$rc->beginI18nWork($fromLang);
									
									if ($ds->synchronizeI18nProperties($d, $from, $to))
									{
										$tm->getPersistentProvider()->setI18nSynchro($pm, $to);
										$tm->getPersistentProvider()->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_SYNCHRONIZED, $fromLang);
										$dcs->clearCacheByPattern(f_DataCachePatternHelper::getModelPattern($d->getDocumentModelName()));
										$dcs->clearCacheByDocId(f_DataCachePatternHelper::getIdPattern($documentId));
									}
									elseif (isset($datas[$lang]))
									{
										$this->getPersistentProvider()->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
									}
										
									$rc->endI18nWork();
								} 
								catch (Exception $e) 
								{
									$rc->endI18nWork($e);
								}	
								break;
							}
						}
					}
				}
				
				foreach ($datas as $lang => $synchroInfos)
				{
					if ($synchroInfos['status'] === self::SYNCHRO_MODIFIED)
					{
						$this->getPersistentProvider()->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
					elseif ($synchroInfos['status'] === self::SYNCHRO_SYNCHRONIZED && !isset($synchroConfig[$lang]))
					{
						$this->getPersistentProvider()->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
				}
			}
			else
			{
				Framework::fatal('NO Synchro config file');
				$tm->getPersistentProvider()->deleteI18nSynchroStatus($documentId);
			}
			$tm->commit();
		} 
		catch (Exception $e) 
		{
			$tm->rollback($e);
			return false;
		}		
		return true;
	}
}
