<?php
/**
 * @method ModuleService getInstance()
 */
class ModuleService extends change_BaseService
{
	const SETTING_ROOT_FOLDER_ID = 'root_folder_id';
	const SETTING_SYSTEM_FOLDER_ID = 'system_folder_id';
	const SETTING_PREFERENCES_DOCUMENT_ID = 'preferences_document_id';
	const SETTING_PREFERENCES_DOCUMENT_TYPE = 'preferences';
	
	const NAME_PATTERN = '[a-z][a-z0-9]*';
	
	private $actionStack;
	
	/**
	 * Array of installed package
	 * @var String[]
	 */
	private $packages = null;
	
	/**
	 * @var c_Module[]
	 */
	private $modules;
	

	private $rootNodeIds = null;
	

	public static function clearInstance()
	{
		self::clearInstanceByClassName(get_called_class());
		self::getInstance()->initialize();
	}
	
	/**
	 * @return void
	 */
	public function clearCache()
	{
		$this->rootNodeIds = null;
	}
	
	/**
	 * Initialize the cache file for the module list used by module service
	 *
	 */
	public final function initialize()
	{
		// Unset the static variable of packages list
		$this->loadCacheFile();
	}
	
	public function loadCacheFile()
	{
		$this->packages = Framework::getConfiguration('packageversion');
	}

	private function initializeIfNeeded()
	{
		if ($this->packages === null)
		{
			$this->initialize();
		}
	}
	
	/**
	 * Returns the module absolute paths.
	 *
	 * @param string $moduleName The module name.
	 * @param string $relativePath The path to append to the module path.
	 * @param string $mode If $mode="all", returns all the paths defined for
	 *        the given module (overridden resources).
	 *
	 * @return array The absolute paths of the module $moduleName.
	 *
	 * @see FileResolver::getPath()
	 */
	public function getModulePath($moduleName, $relativePath = '/', $mode = null)
	{
		if (!$this->moduleExists($moduleName))
		{
			throw new UnavailableModuleException($moduleName);
		}
		
		if ($mode == 'all')
		{
			$method = 'getPaths';
		}
		else
		{
			$method = 'getPath';
		}
		return FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->setDirectory($relativePath)->$method('');
	}
	
	/**
	 * @param String $shortModuleName
	 * @return c_Module
	 */
	public function getModule($shortModuleName)
	{
		$modules = $this->getModulesObj();
		if (isset($modules[$shortModuleName]))
		{
			return $modules[$shortModuleName];
		}
		return new c_Module($shortModuleName);
	}
	

	/**
	 * Indicates whether a module exists or not.
	 *
	 * @param string $moduleName
	 * @return boolean
	 */
	public final function moduleExists($moduleName)
	{
		$this->initializeIfNeeded();
		if (substr($moduleName, 0, 8) !== 'modules_')
		{
			$moduleName = 'modules_' . $moduleName;
		}
		
		return array_key_exists($moduleName, $this->packages);
	}
	
	
	/**
	 * Test if a module is installed
	 *
	 * @param string $moduleName
	 * @return boolean
	 */
	public function isInstalled($moduleName)
	{
		return $this->moduleExists($moduleName) && $this->getImportInitDataDate($moduleName) !== null;
	}
	
	/**
	 * Get the list of modules visible (enabled) for the $user.
	 *
	 * @param users_persistentdocument_user $user
	 * @return array<c_Module>
	 */
	public function getVisibleModulesForUser($user)
	{
		$modulesList = array();
		$permissionService = change_PermissionService::getInstance();
		foreach ($this->getModulesObj() as $module)
		{
			if ($module->isVisible())
			{
				$enabledPermissionName = $module->getFullName() . '.Enabled';
				$rootFolderId = $this->getRootFolderId($module->getName());
				if ($user->getIsroot() || $permissionService->hasPermission($user, $enabledPermissionName, $rootFolderId))
				{
					$modulesList[] = $module;
				
				}
				else
				{
					continue;
				}
			}
		}
		return $modulesList;
	}
	
	/**
	 * Get the list of modules visible (enabled) for the current user.
	 *
	 * @param users_persistentdocument_user $user
	 * @return array<c_Module>
	 */
	public function getVisibleModulesForCurrentUser()
	{
		return $this->getVisibleModulesForUser(users_UserService::getInstance()->getAutenticatedUser());
	}
		
	/**
	 * @return string[]
	 */
	public final function getPackageNames()
	{
		$this->initializeIfNeeded();
		return array_keys($this->packages);
	}
	
	/**
	 * @todo this should be named getModules()
	 * @return c_Module[]
	 */
	public function getModulesObj()
	{
		if ($this->modules === null)
		{
			$this->initializeIfNeeded();
			$modules = array();
			foreach ($this->packages as $fullModuleName => $infos)
			{
				$shortName = substr($fullModuleName, 8);
				$modules[$shortName] = new c_Module($shortName, $infos);
			}
			$this->modules = $modules;
		}
		return $this->modules;
	}
	
	/**
	 * Return the short name for the module
	 *
	 * @param string $moduleName Example: module_users
	 * @return string
	 */
	public final function getShortModuleName($moduleName)
	{
		$modInfo = explode('_', $moduleName);
		if (isset($modInfo[1]))
		{
			return $modInfo[1];
		}
		return $modInfo[0];
	}
	
	/**
	 * Return the version of module
	 *
	 * @param string $moduleName Example: module_users
	 * @return string
	 */
	public final function getModuleVersion($moduleName)
	{
		$this->initializeIfNeeded();
		if (substr($moduleName, 0, 8) !== 'modules_')
		{
			$moduleName = 'modules_' . $moduleName;
		}
		if (isset($this->packages[$moduleName]) && isset($this->packages[$moduleName]["VERSION"]))
		{
			return $this->packages[$moduleName]["VERSION"];
		}
		return null;
	}

	/**
	 * Return the list of version of packages.
	 *
	 * @return array
	 */
	public final function getPackageVersionList()
	{
		$this->initializeIfNeeded();
		return $this->packages;
	}


	
	/**
	 * Return an associative array of the versions for the packages.
	 * The key is the package label (localized).
	 *
	 * @return array<packageLabel=>packageVersion>
	 */
	public final function getPackageVersionInfos()
	{
		$packageVersion = array();
		$packageVersionArray = $this->getPackageVersionList();
		$ls = LocaleService::getInstance();
		foreach ($packageVersionArray as $packageName => $version)
		{
			$packageVersion[$ls->trans('m.' . substr($packageName, 8) . '.bo.general.module-name', array('ucf'))] = $version;
		}
		krsort($packageVersion);
		$packageVersion['Framework'] = FRAMEWORK_VERSION;
		$packageVersion = array_reverse($packageVersion);
		return $packageVersion;
	}
	
	/**
	 * Returns the defined document name in the module $moduleName.
	 *
	 * @param string $moduleName The module name.
	 * @return array<string> The names of the defined documents.
	 */
	public function getDefinedDocumentModelNames($moduleName)
	{
		$modelsByModules = f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules();
		if (isset($modelsByModules[$moduleName]))
		{
			return $modelsByModules[$moduleName];
		}
		return array();
	}
	
	/**
	 * Returns the defined document name in the module $moduleName.
	 *
	 * @param string $moduleName The module name.
	 * @return array<string> The names of the defined documents.
	 */
	public function getDefinedDocumentNames($moduleName)
	{
		$documentNames = array();
		foreach ($this->getDefinedDocumentModels($moduleName) as $model)
		{
			$documentNames[] = $model->getDocumentName();
		}
		return $documentNames;
	}
	
	/**
	 * @param string $moduleName The module name.
	 * @return array<f_persistentdocument_PersistentDocumentModel> The models of the defined documents
	 */
	public function getDefinedDocumentModels($moduleName)
	{
		$moduleModels = array();
		foreach ($this->getDefinedDocumentModelNames($moduleName) as $modelName)
		{
			$moduleModels[] = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName);
		}
		return $moduleModels;
	}
	

	/**
	 * Returns the root folder ID of the specified module.
	 *
	 * @param string $moduleName The name of the module.
	 *
	 * @return integer The root folder ID (or null for the generic module).
	 */
	public function getRootFolderId($moduleName)
	{
		if ($this->rootNodeIds !== null && array_key_exists($moduleName, $this->rootNodeIds))
		{
			return $this->rootNodeIds[$moduleName];
		}
		
		if ($this->rootNodeIds === null)
		{
			$this->rootNodeIds = array();
		}
		
		$rootNodeId = null;
		$tm = f_persistentdocument_TransactionManager::getInstance();
		$pp = $tm->getPersistentProvider();
		
		if ($moduleName !== 'generic')
		{
			$package = 'modules_' . $moduleName;
			
			$rootNodeId = $pp->getSettingValue($package, self::SETTING_ROOT_FOLDER_ID);
			if (is_null($rootNodeId))
			{
				// setting not found: create root folder and save setting information
				try
				{
					$tm->beginTransaction();
					
					// create root folder
					$rootFolder = $pp->getNewDocumentInstance('modules_generic/rootfolder');
					$rootFolder->save();
					
					// set as root folder in the tree
					TreeService::getInstance()->setRootNode($rootFolder->getId());
					
					// save root folder id in f_settings table
					$rootNodeId = $rootFolder->getId();
					$pp->setSettingValue($package, self::SETTING_ROOT_FOLDER_ID, $rootNodeId);
					
					$tm->commit();
				}
				catch (Exception $e)
				{
					$tm->rollBack();
					Framework::exception($e);
					$rootNodeId = null;
				}
			}
		}
		
		$this->rootNodeIds[$moduleName] = $rootNodeId;
		return $rootNodeId;
	}
	
	/**
	 * 
	 * @param String $moduleName the module name (eg : youpi)
	 * @return String or null the package's import-init-data date
	 */
	public function getImportInitDataDate($moduleName)
	{
		if (substr($moduleName, 0, 8) !== 'modules_')
		{
			$moduleName = 'modules_' . $moduleName;
		}
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		return $pp->getSettingValue($moduleName, 'init-data');
	}
	

	/**
	 * Returns the system folder ID of the specified "owner" module, related to the optional "related" module.
	 *
	 * @param string $ownerModuleName The name of the owner module.
	 * @param string $relatedModuleName The name of the related module (if null, the name of the owner module).
	 *
	 * @return integer The system folder ID (or null for the generic module).
	 */
	public function getSystemFolderId($ownerModuleName, $relatedModuleName = null)
	{
		if (empty($ownerModuleName))
		{
			throw new BaseException('invalid-empty-module-name', 'framework.exception.errors.Invalid-empty-module-name');
		}
		
		if (empty($relatedModuleName))
		{
			$relatedModuleName = $ownerModuleName;
		}
		
		$systemNodeId = null;
		
		$tm = f_persistentdocument_TransactionManager::getInstance();
		
		if ($ownerModuleName !== 'generic')
		{
			$package = 'modules_' . $ownerModuleName . '/modules_' . $relatedModuleName;
			
			$systemNodeId = $this->getPersistentProvider()->getSettingValue($package, self::SETTING_SYSTEM_FOLDER_ID);
			
			if (is_null($systemNodeId))
			{
				// setting not found: create system folder and save setting information
				try
				{
					$tm->beginTransaction();
					
					// create system folder
					$systemFolder = $tm->getPersistentProvider()->getNewDocumentInstance('modules_generic/systemfolder');
					$systemFolder->setLabel('m.' . $relatedModuleName . '.bo.general.system-folder-name');
					
					$systemFolder->save(ModuleService::getInstance()->getRootFolderId($ownerModuleName));
					
					// save system folder id in f_settings table
					$systemNodeId = $systemFolder->getId();
					$this->getPersistentProvider()->setSettingValue($package, self::SETTING_SYSTEM_FOLDER_ID, $systemNodeId);
					
					$tm->commit();
				}
				catch (Exception $e)
				{
					$tm->rollBack();
					Framework::exception($e);
					$systemNodeId = null;
				}
			}
		}
		
		return $systemNodeId;
	}
	
	/**
	 * Returns the preferences document of the specified module.
	 *
	 * @param string $moduleName The name of the module.
	 *
	 * @return object The preferences document .
	 */
	public static function getPreferencesDocument($moduleName)
	{
		if (empty($moduleName))
		{
			throw new BaseException('invalid-empty-module-name', 'framework.exception.errors.Invalid-empty-module-name');
		}
		
		$preferenceModel = f_persistentdocument_PersistentDocumentModel::getInstance($moduleName, 'preferences');
		$realModelName = $preferenceModel->getName();
		// Check if User exist in database
		$persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
		$query = $persistentProvider->createQuery($realModelName)->add(Restrictions::eq('model', $realModelName));
		
		return $persistentProvider->findUnique($query);
	}
	
	/**
	 * Returns the value of the property $fieldName of the preferences document
	 * of module $moduleName.
	 *
	 * @param String $moduleName
	 * @param String $fieldName
	 * @return String
	 */
	public static function getPreferenceValue($moduleName, $fieldName)
	{
		$pref = self::getPreferencesDocument($moduleName);
		if ($pref !== null)
		{
			$property = $pref->getPersistentModel()->getProperty($fieldName);
			if ($property === null)
			{
				$property = $pref->getPersistentModel()->getSerializedProperty($fieldName);
			}
			if ($property === null)
			{
				throw new Exception("Unknown property \"$fieldName\" in module \"$moduleName\"'s preferences.");
			}
			if ($property->isArray())
			{
				return $pref->{'get' . ucfirst($fieldName) . 'Array'}();
			}
			return $pref->{'get' . ucfirst($fieldName)}();
		}
		return null;
	}
	

	/**
	 * Sets the preferences document ID for the specified module.
	 *
	 * @param string $moduleName The name of the module.
	 * @param integer $preferencesDocumentId The preferences document ID.
	 *
	 */
	public function setPreferencesDocumentId($moduleName, $preferencesDocumentId)
	{
		if (empty($moduleName))
		{
			throw new BaseException('invalid-empty-module-name', 'framework.exception.errors.Invalid-empty-module-name');
		}
		
		$package = 'modules_' . $moduleName;
		$this->getPersistentProvider()->setSettingValue($package, self::SETTING_PREFERENCES_DOCUMENT_ID, $preferencesDocumentId);
	}
	

	/**
	 * Indicates whether the modue $moduleName has preferences or not.
	 *
	 * @param string $moduleName Name of the module.
	 * @return boolean
	 */
	public function hasPreferences($moduleName)
	{
		return in_array('preferences', $this->getDefinedDocumentNames($moduleName));
	}
		
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	private function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}
	
	/**
	 * Returns the localized label of the module $moduleName.
	 *
	 * @param string $moduleName
	 * @return string
	 */
	public function getLocalizedModuleLabel($moduleName)
	{
		return LocaleService::getInstance()->trans('m.'.$moduleName.'.bo.general.module-name', array('ucf'));
	}
	
	/**
	 * Returns the localized label of the module $moduleName.
	 *
	 * @param string $moduleName
	 * @return string
	 */
	public function getUILocalizedModuleLabel($moduleName)
	{
		return LocaleService::getInstance()->trans('m.'.$moduleName.'.bo.general.module-name', array('ucf'));
	}
}

class c_Module
{
	/**
	 * @var String
	 */
	private $name;
	
	/**
	 * @var array
	 */
	private $infos;
	
	/**
	 * @param string $name
	 * @param array $infos
	 */
	function __construct($name, $infos = array())
	{
		$this->name = $name;
		$this->infos = $infos;
	}
	
	/**
	 * String[]
	 */
	function getTemplatePaths()
	{
		$templatePaths = array();
		$files = f_util_FileUtils::getDirFiles($this->getPath() . "/templates");
		foreach ($files as $file)
		{
			if (!f_util_StringUtils::contains($file, ".svn/"))
			{
				$templatePaths[] = $file;
			}
		}
		return $templatePaths;
	}
	
	/**
	 * @return String
	 */
	function getLabel()
	{
		return ModuleService::getInstance()->getLocalizedModuleLabel($this->name);
	}
	
	/**
	 * @return String
	 */
	function getUILabel()
	{
		return ModuleService::getInstance()->getUILocalizedModuleLabel($this->name);
	}
	
	/**
	 * @return String
	 */
	function getName()
	{
		return $this->name;
	}
	
	/**
	 * (Une belle absurdit‚àö¬© qu'on va se d‚àö¬©p‚àö¬©cher d'enlever...)
	 * @return String "modules_<Name>"
	 */
	function getFullName()
	{
		return "modules_" . $this->name;
	}
	
	
	/**
	 * @return String
	 */
	function getPath()
	{
		return realpath(f_util_FileUtils::buildModulesPath($this->name));
	}
	
	/**
	 * @return Boolean
	 */
	function isVisible()
	{
		return isset($this->infos['VISIBLE']) ? $this->infos['VISIBLE'] : false;
	}
		
	/**
	 * @return Boolean
	 */
	function isFolderBased()
	{
		return !$this->isTopicBased();
	}
	
	/**
	 * Module based on new perspective (3.0.0)
	 * @return Boolean
	 */
	function hasPerspectiveConfigFile()
	{
		$path = f_util_FileUtils::buildAbsolutePath($this->getPath(), 'config', 'perspective.xml');
		return file_exists($path);
	}
	
	/**
	 * @return String
	 *
	 */
	function getVersion()
	{
		return isset($this->infos['VERSION']) ? $this->infos['VERSION'] : null;
	}
	
	/**
	 * @return String
	 */
	function getIconName()
	{
		return isset($this->infos['ICON']) ? $this->infos['ICON'] : 'package';
	}
	
	/**
	 * @return String
	 */
	function getCategory()
	{
		return isset($this->infos['CATEGORY']) ? $this->infos['CATEGORY'] : 'modules';
	}
	
	/**
	 * @return Integer
	 */
	function getRootFolderId()
	{
		return ModuleService::getInstance()->getRootFolderId($this->name);
	}
	
	/**
	 * @return true
	 * @deprecated
	 */
	function isEnabled()
	{
		return true;
	}
	
	/**
	 * @return Boolean
	 * @deprecated
	 */
	function isTopicBased()
	{
		return isset($this->infos['USETOPIC']) ? $this->infos['USETOPIC'] : false;
	}
}