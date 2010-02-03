<?php
class LocaleService extends BaseService
{

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

	/**
	 * Regenerate all locales of application
	 */
	public function regenerateLocales()
	{
		// Clear locale table
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$provider->clearTranslationCache();

		// Processing modules :
		$this->processModules();

		// Processing framework :
		$this->processFramework();
	}

	/**
	 * Regenerate locale for a module and save in databases
	 *
	 * @param string $moduleName Example: modules_users
	 */
	public function regenerateLocalesForModule($moduleName)
	{
		// Clear the corresponding entries in databases
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$provider->clearTranslationCache($moduleName);

		// Processing module : $moduleName
		$this->processModule($moduleName);
	}

	/**
	 * Regenerate locale for the framework and save in databases
	 */
	public function regenerateLocalesForFramework()
	{
		// Clear the corresponding entries in databases
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$provider->clearTranslationCache('framework');

		// Processing framework :
		$this->processFramework();
	}

	/**
	 * Insert locale keys for all modules
	 */
	private function processModules()
	{
		$modulesArray = ModuleService::getInstance()->getModules();
		foreach ($modulesArray as $moduleName)
		{
			$this->processModule($moduleName);
		}
	}

	/**
	 * Compile locale for a module
	 *
	 * @param string $moduleName Example: modules_users
	 */
	private function processModule($moduleName)
	{
		$availablePaths = FileResolver::getInstance()->setPackageName($moduleName)->setDirectory('locale')->getPaths('');
		$availablePaths = array_reverse($availablePaths);

		// For all path found for the locale of module insert all localization keys
		foreach ($availablePaths as $path)
		{
			$this->processDir($moduleName, $path);
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
		$availablePaths = array(FRAMEWORK_HOME . DIRECTORY_SEPARATOR."locale".DIRECTORY_SEPARATOR);
		foreach ($availablePaths as $path)
		{
			$this->processDir("/framework", $path);
		}
	}

	/**
	 * This method receive an array '$entities' that contain localized information and insert it in databases.
	 *
	 * @param string $packageKey
	 * @param array $entities
	 */
	private function processDatabase($packageKey, $entities)
	{
		$provider = f_persistentdocument_PersistentProvider::getInstance();

		// Add all entities in databases
		foreach ($entities as $entity => $langs)
		{
			foreach ($langs as $lang => $infos)
			{
				$provider->addTranslate($packageKey . '.' . $entity, $lang, $infos['value'], $packageKey, '0', $infos['overridable'], $infos['useredited']);
			}
		}
	}

	/**
	 * Parse recursively directory and launch the genration of localization for all locale XML file
	 *
	 * @param string $package
	 * @param string $dir
	 */
	private function processDir($package, $dir)
	{
		if (is_dir($dir))
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
					$dirs[$package.'.'.$file] = $absFile;
				}
				elseif (f_util_StringUtils::endsWith($file, '.xml'))
				{
					$entities = null;
					$this->processFile($package, $absFile, $entities);
				}
			}

			foreach ($dirs as $package => $dir)
			{
				$this->processDir($package, $dir);
			}
		}
	}

	/**
	 * Read a file and extract informations of localization
	 *
	 * @param string $package
	 * @param string $file
	 */
	private function processFile($package, $file, &$entities)
	{
		if (strpos($package, '/') !== false)
		{
			$packageKey = strtolower(substr(str_replace('/', '.', $package), 1) . '.' . basename($file, '.xml'));
		}
		else
		{
			$packageKey = strtolower(str_replace('_', '.', $package) . '.' . basename($file, '.xml'));
		}

		// Load the XMl file
		$dom = f_util_DOMUtils::fromPath($file);

		if ($entities === null)
		{
			$entities = array();
		}
		
		// For all entity defined
		foreach ($dom->find("entity") as $entity)
		{
			$entityId = strtolower($entity->getAttribute('id'));

			// If the key not already defined add it in array
			if ( ! array_key_exists($entityId, $entities) )
			{
				$entities[$entityId] = array();

				// For all lang defined in entity create an array
				foreach ($dom->find("locale", $entity) as $locale)
				{
					$lang = strtolower($locale->getAttribute('lang'));

					if ( ! array_key_exists($lang, $entities[$entityId]) )
					{
						$entities[$entityId][$lang] = array();
						// The line below replaces quotes by an equivalent UTF-8 character (that's why it looks like it's not doing anything)
						$content = str_replace('"', '″', $locale->textContent);
						$entities[$entityId][$lang]['value'] = $content;

						// Test if locale can be overridable
						if ($locale->hasAttribute('overridable')
						&& ($locale->getAttribute('overridable') == 'false'))
						{
							$entities[$entityId][$lang]['overridable'] = 0;
						}
						else
						{
							$entities[$entityId][$lang]['overridable'] = 1;
						}

						// Test if locale has been "user edited" (from BO) :
						if ($locale->hasAttribute('useredited')
						&& ($locale->getAttribute('useredited') == 'true'))
						{
							$entities[$entityId][$lang]['useredited'] = 1;
						}
						else
						{
							$entities[$entityId][$lang]['useredited'] = 0;
						}
					}
				}
			}
		}
		
		if ($dom->documentElement->hasAttribute("extend"))
		{
			//$parentFile = WEBEDIT_HOME."/".str_replace(".", "/", );
			$parentInfo = explode(".", $dom->documentElement->getAttribute("extend"));
			if ($parentInfo[0] == "framework")
			{
				unset($parentInfo[0]);
				$baseDir = "framework";
			}
			elseif ($parentInfo[0] == "modules")
			{
				unset($parentInfo[0]);
				$baseDir = "modules/".$parentInfo[1];
				unset($parentInfo[1]);
			}
			$parentFile = $baseDir."/locale/".join("/", $parentInfo).".xml";
			if (file_exists(WEBEDIT_HOME."/webapp/".$parentFile))
			{
				$this->processFile($package, WEBEDIT_HOME."/webapp/".$parentFile, $entities);
			}
			if (file_exists(WEBEDIT_HOME."/".$parentFile))
			{
				$this->processFile($package, WEBEDIT_HOME."/".$parentFile, $entities);
			}
			else
			{
				throw new Exception("Unknown locale parent '".$dom->documentElement->getAttribute("extend")."' in $file");
			}
		}

		// Send the array to the processDatabase to insert into databases
		$this->processDatabase($packageKey, $entities);
	}
}
