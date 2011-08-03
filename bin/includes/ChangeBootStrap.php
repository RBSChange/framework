<?php
class c_ChangeBootStrap
{
	static $DEP_FRAMEWORK = 7;
	static $DEP_LIB = 2;
	static $DEP_MODULE = 3;
	static $DEP_PEAR = 5;
	static $DEP_UNKNOWN = 0;
	static $DEP_THEME = 20;
	
	/**
	 * PROJECT_HOME
	 * @var String
	 */
	private $wd;
	
	/**
	 * @var String
	 */
	private $name = "change";
	
	/**
	 * @var String
	 */
	private $descriptor = "change.xml";
	
	/**
	 * @var String
	 */
	private $descriptorPath;
	
	/**
	 * @var array<String, Boolean>
	 */
	private $localRepositories;
	
	/**
	 * @var String[]
	 */
	private $remoteRepositories;
	
	/**
	 * @var cboot_Properties
	 */
	private $properties;
	
	/**
	 * @var c_ChangeBootStrap
	 */
	private static $instance;
	
	/**
	 * @var cboot_Configuration
	 */
	private $configuration;
	
	/**
	 * @param String $path
	 */
	function __construct($path)
	{
		$this->wd = $path;
		self::$instance = $this;
	}
	
	/**
	 * @return c_ChangeBootStrap
	 */
	static function getInstance()
	{
		return self::$instance;
	}
	
	/**
	 * @return cboot_Configuration
	 */
	function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$this->configuration = cboot_Configuration::getInstance($this->getName());
			$this->configuration->addLocation($this->wd);
		}
		return $this->configuration;
	}
	
	function getName()
	{
		if ($this->name === null)
		{
			$this->name = "change";
		}
		return $this->name;
	}
	
	/**
	 * @param String $name
	 */
	function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @param String $path
	 */
	function addPropertiesLocation($path)
	{
		$this->getConfiguration()->addLocation($path);
	}
	
	/**
	 * @param String $descriptor
	 * @return c_ChangeBootStrap
	 */
	function setDescriptor($descriptor = "change.xml")
	{
		$this->descriptor = $descriptor;
		return $this;
	}
	
	/**
	 * @return array unserialized content of .computedChangeComponents.ser
	 */
	public function getComputedDependencies()
	{
		$descComputedPath = $this->wd . "/.computedChangeComponents.ser";
		if (is_file($descComputedPath))
		{
			$computedDeps = unserialize(file_get_contents($descComputedPath));
		}
		else
		{
			$computedDeps = $this->generateComputedChangeComponents(null);
			file_put_contents($descComputedPath, serialize($computedDeps));
		}	
		return $computedDeps;
	}
	
	/**
	 * @param string $target
	 */
	function dispatch($target = null)
	{
		// Check change.xml existence
		$descPath = $this->getDescriptorPath();
		
		if (! is_file($descPath))
		{
			throw new Exception("Could not find $descPath");
		}
		
		if ($target != "")
		{
			$scriptPath = null;
			if (f_util_StringUtils::beginsWith($target, "dep:"))
			{
				list (, $component, $relativePath) = explode(":", $target);
				//TODO Component path check
				$scriptPath = $this->wd . "/" . $component . "/" . $relativePath;
			}
			elseif (f_util_StringUtils::beginsWith($target, "func:"))
			{
				list (, $scriptFunction) = explode(":", $target);
				if (! function_exists($scriptFunction))
				{
					throw new Exception("Function $scriptFunction does not exists");
				}
			}
			else
			{
				$scriptPath = $target;
			}
			
			if ($scriptPath === null && $scriptFunction === null)
			{
				throw new Exception("Unable to find $target");
			}
			if ($scriptPath !== null && ! file_exists($scriptPath))
			{
				throw new Exception("Unable to find $target (location should be '$scriptPath')");
			}
			
			$computedDeps = $this->getComputedDependencies();
			
			if (! isset($_SERVER["argv"]) || ! is_array($_SERVER["argv"]))
			{
				$_SERVER["argv"] = array();
			}
			else
			{
				array_shift($_SERVER["argv"]);
			}
			
			if ($scriptPath !== null)
			{
				array_walk($_SERVER["argv"], array($this, "escapeArg"));
				$returnCode = 0;
				$cmd = "php $scriptPath " . join(" ", $_SERVER["argv"]);
				passthru($cmd, $returnCode);
				exit($returnCode);
			}
			else
			{
				$scriptFunction($_SERVER["argv"], $computedDeps);
			}
		}
	}
	
	public function cleanDependenciesCache()
	{
		$descComputedPath = $this->wd . "/.computedChangeComponents.ser";
		if (file_exists($descComputedPath))
		{
			unlink($descComputedPath);
		}
		$autoloadPath = $this->getAutoloadPath();
		$cacheKey = $autoloadPath . "/*.autoloaded";
		$files = glob($cacheKey);
		if (is_array($files))
		{
			foreach ($files as $file)
			{
				unlink($file);
			}
		}
	}
	
	private function generateComputedChangeComponents($components)
	{
		$this->loadPearInfo();
		if ($components === null)
		{
			$components = $this->loadDependencies();
		}
		$computedComponents = array();
		$localRepo = $this->getWriteRepository();
		$computedComponents["PEAR_DIR"] = $this->pearInfos['include_path'];
		$computedComponents["USE_CHANGE_PEAR_LIB"] = $this->useChangePearLib();
		$computedComponents["PEAR_WRITEABLE"] = $this->pearInfos['writeable'];
		$computedComponents["LOCAL_REPOSITORY"] = $localRepo;
		$computedComponents["WWW_GROUP"] = $this->getProperties()->getProperty('WWW_GROUP');
		$computedComponents["DEVELOPMENT_MODE"] = $this->getProperties()->getProperty('DEVELOPMENT_MODE', false) == true;
		$computedComponents["PHP_CLI_PATH"] = $this->getProperties()->getProperty('PHP_CLI_PATH', '');
		
		$computedComponents["TMP_PATH"] = $this->getProperties()->getProperty('TMP_PATH');
		
		$computedComponents["CHANGE_COMMAND"] = $this->getProperties()->getProperty('CHANGE_COMMAND', 'framework/bin/change.php');
		$computedComponents["PROJECT_HOME"] = $this->getProperties()->getProperty('PROJECT_HOME', $this->wd);
		$computedComponents["DOCUMENT_ROOT"] = $this->getProperties()->getProperty('DOCUMENT_ROOT', $this->wd);
		$computedComponents["PROJECT_LICENSE"] = $this->getProperties()->getProperty('PROJECT_LICENSE', 'OS');
		$computedComponents["FAKE_EMAIL"] = $this->getProperties()->getProperty('FAKE_EMAIL');
		
		$proxy = $this->getProxy();
		if ($proxy !== null)
		{
			$proxyInfo = explode(":", $proxy);
			if (! isset($proxyInfo[1]))
			{
				$proxyInfo[1] = "80";
			}
			$computedComponents["OUTGOING_HTTP_PROXY_HOST"] = $proxyInfo[0];
			$computedComponents["OUTGOING_HTTP_PROXY_PORT"] = $proxyInfo[1];
		}
		
		$computedComponents['change-lib'] = $components['framework'];
		$computedComponents['lib-pear'] = isset($components['pearlibs']) ? $components['pearlibs'] : array();
		$computedComponents['extension'] = array();
		$computedComponents['lib'] = $components['libs'];
		$computedComponents['module'] = $components['modules'];
		foreach (array('change-lib', 'lib-pear', 'extension', 'lib', 'module') as $depType)
		{
			foreach ($computedComponents[$depType] as $depname => $infos)
			{
				$computedComponents[$depType][$depname]['path'] = $localRepo . $infos["repoRelativePath"];
			}
		}
		
		return $computedComponents;
	}
	
	private $autoloadPath;
	private $autoloaded = array();
	private $autoloadRegistered = false;
	private $refreshAutoload = false;
	
	function isPathAbsolute($path)
	{
		return $path[0] === '/' || $path[0] === '\\' || (strlen($path) > 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'));
	}
	
	function setAutoloadPath($autoloadPath = "cache/autoload")
	{
		if (! $this->isPathAbsolute($autoloadPath))
		{
			$this->autoloadPath = $this->wd . "/" . $autoloadPath;
		}
		else
		{
			$this->autoloadPath = $autoloadPath;
		}
	}
	
	private function getAutoloadPath()
	{
		if ($this->autoloadPath === null)
		{
			$this->setAutoloadPath();
		}
		return $this->autoloadPath;
	}
	
	function setRefreshAutoload($refresh)
	{
		$this->refreshAutoload = $refresh;
	}
	
	function appendToAutoload($componentPath)
	{
		
		$autoloadPath = $this->getAutoloadPath();
		$autoloadedFlag = $autoloadPath . "/" . md5($componentPath) . ".autoloaded";
		
		$analyzer = cboot_ClassDirAnalyzer::getInstance();
		if (! $this->autoloadRegistered)
		{
			spl_autoload_register(array($analyzer, "autoload"));
			$this->autoloadRegistered = true;
		}
		
		if (isset($this->autoloaded[$componentPath]) || (! $this->refreshAutoload && file_exists(
				$autoloadedFlag)))
		{
			$this->autoloaded[$componentPath] = true;
			return;
		}
		
		if (file_exists($autoloadedFlag))
		{
			unlink($autoloadedFlag);
		}
		
		$analyzer->appendRealDir($componentPath);
		$this->autoloaded[$componentPath] = true;
		touch($autoloadedFlag);
	}
	
	/**
	 * Return the path of project change.xml
	 * @return String
	 */
	public function getDescriptorPath()
	{
		if ($this->descriptorPath === null)
		{
			if ($this->isPathAbsolute($this->descriptor))
			{
				$this->descriptorPath = $this->descriptor;
			}
			else
			{
				$this->descriptorPath = $this->wd . "/" . $this->descriptor;
			}
		}
		return $this->descriptorPath;
	}
	
	/**
	 * @return cboot_Properties
	 */
	function getProperties($fileName = null)
	{
		return $this->getConfiguration()->getProperties($fileName);
	}
	
	/**
	 * @return String[]
	 */
	public function getRemoteRepositories()
	{
		if ($this->remoteRepositories === null)
		{
			$this->remoteRepositories = array_unique(
					explode(",", $this->getProperties()->getProperty("REMOTE_REPOSITORIES", "http://osrepo.rbschange.fr")));
		}
		return $this->remoteRepositories;
	}
	
	private $instanceProjectKey = null;
	
	/**
	 * @return string
	 */
	public function getInstanceProjectKey()
	{
		if ($this->instanceProjectKey === null)
		{
			$license = $this->getProperties()->getProperty("PROJECT_LICENSE");
			if (empty($license)) {$license = "OS";}
			$version = '-'; $profile = '-'; $pId = '-';  $fqdn='-';
			$versionPath = $this->wd . '/framework/change.xml';
			if (is_readable($versionPath))
			{
				$changeXMLDoc = f_util_DOMUtils::fromPath($versionPath);
				$changeXMLDoc->registerNamespace("cc", "http://www.rbs.fr/schema/change-component/1.0");
				$node = $changeXMLDoc->findUnique("cc:version");
				$version = $node ? $node->textContent : '-';
			}
			
			$profilePath = $this->wd . '/profile';
			if (is_readable($profilePath))
			{
				$profile = trim(file_get_contents($profilePath));
				$configPath = $this->wd . '/config/project.'. $profile .'.xml';
				if (is_readable($configPath))
				{
					$changeXMLDoc = f_util_DOMUtils::fromPath($configPath);
					$pIdNode = $changeXMLDoc->findUnique('defines/define[@name="PROJECT_ID"]');
					$pId = $pIdNode ? $pIdNode->textContent : '-';
					$fqdnNode = $changeXMLDoc->findUnique('config/general/entry[@name="server-fqdn"]');
					$fqdn = $fqdnNode ? $fqdnNode->textContent : '-';
				}
			}
			$this->instanceProjectKey = 'Change/' . $version . ';License/' . $license. ';Profile/' . $profile . ';PId/' . $pId. ';FQDN/'. $fqdn;
		}
		return $this->instanceProjectKey;
	}
	
	/**
	 * @param String $path
	 * @return String
	 */
	private function expandLocalPath($path)
	{
		if ($path !== null)
		{
			if (! strncmp($path, "~/", 2))
			{
				die("Invalid relative local path ($path). Please ubpdate configuration file");
			}
		}
		return $path;
	}
	
	/**
	 * @return array<String, Boolean> path => writeable
	 */
	public function getLocalRepositories()
	{
		if ($this->localRepositories === null)
		{
			// Local repositories
			$this->localRepositories = array();
			foreach (array_unique(explode(",", $this->getProperties()->getProperty("LOCAL_REPOSITORY", $this->wd . "/repository"))) 
				as $localRepoPath)
			{
				$localRepoPath = $this->expandLocalPath($localRepoPath);
				if (! file_exists($localRepoPath) && ! is_writable(dirname($localRepoPath)))
				{
					continue;
				}
				if (is_file($localRepoPath))
				{
					c_warning("$localRepoPath exists and is not a directory");
					continue;
				}
				if (! is_dir($localRepoPath) && ! @mkdir($localRepoPath, 0777, true))
				{
					throw new Exception("Could not create $localRepoPath");
				}
				$this->localRepositories[realpath($localRepoPath)] = is_writeable($localRepoPath);
			}
		}
		return $this->localRepositories;
	}

	/**
	 * @param integer $depType
	 * @return string
	 */
	public function convertToCategory($depType)
	{
		switch ($depType)
		{
			case self::$DEP_FRAMEWORK:
			case "change-lib":
			case "framework":
				return 'framework';
				
			case self::$DEP_MODULE;
			case "modules":
			case "module":
				return 'modules';

			case self::$DEP_LIB:
			case "libs":
			case "lib":
				return 'libs';
				
			case self::$DEP_PEAR:
			case "lib-pear":
			case "pearlibs":
			case "pear":
				return 'pearlibs';

			case self::$DEP_THEME:
			case "themes":
			case "theme":
				return 'themes';
				
		}
		return "";		
	}
	
	/**
	 * @param mixed $typeStr
	 * @return integer
	 */
	private function convertToValidType($typeStr)
	{
		switch ($typeStr)
		{
			case "modules":
			case "module":
				return self::$DEP_MODULE;
				
			case "change-lib":
			case "framework":
				return self::$DEP_FRAMEWORK;
				
			case "libs":
			case "lib":
				return self::$DEP_LIB;
				
			case "lib-pear":
			case "pearlibs":
			case "pear":
				return self::$DEP_PEAR;
				
			case "themes":
			case "theme":
				return self::$DEP_THEME;
							
			case self::$DEP_MODULE:
			case self::$DEP_FRAMEWORK:
			case self::$DEP_LIB:
			case self::$DEP_PEAR:	
				return intval($typeStr);
		}
		return self::$DEP_UNKNOWN;
	}
	
	/**
	 * @param integer $depType
	 * @param string $componentName
	 * @param string $version
	 * @param integer $hotFix
	 * @return string
	 */
	private function buildLocalRepositoryPath($depType, $componentName, $version, $hotFix = null)
	{
		$path = '/';
		switch ($depType) {
			case self::$DEP_FRAMEWORK:
				$path .= 'framework/';
				break;
			case self::$DEP_MODULE:
				$path .= 'modules/' . $componentName . '/';
				break;
			case self::$DEP_LIB:
				$path .= 'libs/' . $componentName . '/';
				break;
			case self::$DEP_PEAR:
				$path .= 'pearlibs/' . $componentName . '/';
				break;
			case self::$DEP_THEME:
				$path .= 'themes/' . $componentName . '/';
				break;
		}
		return $path . $componentName . '-' . $version . ($hotFix ? '-' . $hotFix : '');
	}
	
	/**
	 * @param integer $depType
	 * @param string $componentName
	 * @return string
	 */
	private function buildProjectPath($depType, $componentName)
	{
		$path = $this->wd . '/';
		switch ($depType) {
			case self::$DEP_FRAMEWORK:
				$path .= 'framework';
				break;
			case self::$DEP_MODULE:
				$path .= 'modules/' . $componentName;
				break;
			case self::$DEP_LIB:
				$path .= 'libs/' . $componentName;
				break;
			case self::$DEP_PEAR:
				$path .= 'libs/pearlibs/' . $componentName;
				break;
			case self::$DEP_THEME:
				$path .= 'themes/' . $componentName;
				break;
		}
		return $path;
	}	
	
	/**
	 * @param string $repositoryPath
	 * @return array[$depType, $componentName, $version, $hotFix]
	 */
	public function explodeRepositoryPath($repositoryPath)
	{
		$result = array(self::$DEP_UNKNOWN, null, null, null);
		$parts = explode('/', $repositoryPath);
		$result[0] = $this->convertToValidType($parts[1]);
		if ($result[0] === self::$DEP_UNKNOWN) {return $result;}
		if ($result[0] == self::$DEP_FRAMEWORK)
		{
			$result[1] = 'framework';
			$versionParts = explode('-', $parts[2]);
		}
		else
		{
			$result[1] = $parts[2];
			$versionParts = explode('-', $parts[3]);
		}
		$result[2] = $versionParts[1];
		if (count($versionParts) == 3)
		{
			$result[3] = $versionParts[2];
		}
		return $result;
	}
	
	/**
	 * @return string
	 */
	private function getWriteRepository()
	{
		foreach ($this->getLocalRepositories() as $localRepoPath => $writable)
		{
			if ($writable)
			{
				return $localRepoPath;
			}
		}
		return null;
	}
	
	
	private function escapeArg(&$value, $key)
	{
		$value = '"' . str_replace('"', '\"', $value) . '"';
	}
	
	public function loadDependencies()
	{
		$localRepo = $this->getWriteRepository();
		$dependencies = array();
		$changeXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
		$changeXMLDoc->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 'hotfix' => array(), 
				'repoRelativePath' => null);
		$dependencies['framework'] = array('framework' => $infos);
		$frameworkElem = $changeXMLDoc->findUnique("c:dependencies/c:framework");
		if ($frameworkElem !== null)
		{
			$infos['version'] = $frameworkElem->textContent;
			$infos['hotfix'] = $this->extractHotFix($frameworkElem->getAttribute("hotfixes"));
			$repoRelativePath = '/framework/framework-' . $infos['version'];
			if (count($infos['hotfix']))
			{
				$repoRelativePath .= '-' . max($infos['hotfix']);
			}
			$infos['repoRelativePath'] = $repoRelativePath;
			$infos['path'] = $localRepo . $repoRelativePath;
			$infos['link'] = $this->wd . '/framework';
			
			$infos['localy'] = is_dir($infos['path']);
			$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath(
					$infos['path']) == realpath($infos['link']);
			$dependencies['framework']['framework'] = $infos;
		}
		$dependencies['modules'] = array();
		foreach ($changeXMLDoc->find("c:dependencies/c:modules/c:module") as $moduleElem)
		{
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 
					'hotfix' => array(), 'repoRelativePath' => null);
			$matches = array();
			if (! preg_match('/^(.*?)-([0-9].*)$/', $moduleElem->textContent, $matches))
			{
				$moduleName = $moduleElem->textContent;
			}
			else
			{
				$moduleName = $matches[1];
				$infos['version'] = $matches[2];
				$infos['hotfix'] = $this->extractHotFix($moduleElem->getAttribute("hotfixes"));
				
				$repoRelativePath = '/modules/' . $moduleName . '/' . $moduleName . '-' . $infos['version'];
				if (count($infos['hotfix']))
				{
					$repoRelativePath .= '-' . max($infos['hotfix']);
				}
				$infos['repoRelativePath'] = $repoRelativePath;
				$infos['path'] = $localRepo . $repoRelativePath;
				$infos['link'] = $this->wd . '/modules/' . $moduleName;
				
				$infos['localy'] = is_dir($infos['path']);
				$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath(
						$infos['path']) == realpath($infos['link']);
			}
			$dependencies['modules'][$moduleName] = $infos;
		}
		
		$dependencies['libs'] = array();
		foreach ($changeXMLDoc->find("c:dependencies/c:libs/c:lib") as $libElem)
		{
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 
					'hotfix' => array(), 'repoRelativePath' => null);
			$matches = array();
			if (! preg_match('/^(.*?)-([0-9].*)$/', $libElem->textContent, $matches))
			{
				$libName = $libElem->textContent;
			}
			else
			{
				$libName = $matches[1];
				$infos['version'] = $matches[2];
				$repoRelativePath = '/libs/' . $libName . '/' . $libName . '-' . $infos['version'];
				$infos['repoRelativePath'] = $repoRelativePath;
				$infos['path'] = $localRepo . $repoRelativePath;
				$infos['link'] = $this->wd . '/libs/' . $libName;
				
				$infos['localy'] = is_dir($infos['path']);
				$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath(
						$infos['path']) == realpath($infos['link']);
			}
			$dependencies['libs'][$libName] = $infos;
		}
		$this->loadImplicitDependencies($dependencies);
		return $dependencies;
	
	}
	
	private function loadImplicitDependencies(&$dependencies)
	{
		$localRepo = $this->getWriteRepository();
		foreach ($dependencies as $parentDepTypeKey => $parentDeps)
		{
			foreach ($parentDeps as $parentDepName => $parentInfos)
			{
				if ($parentInfos['localy'] && ! isset($parentInfos['implicitdependencies']))
				{
					$dependencies[$parentDepTypeKey][$parentDepName]['implicitdependencies'] = true;
					$filePath = $localRepo . $parentInfos['repoRelativePath'] . '/change.xml';
					if (! is_file($filePath))
					{
						c_warning($filePath . ' not found');
						continue;
					}
					
					$changeXMLDoc = f_util_DOMUtils::fromPath($filePath);
					$decDeps = $this->loadDependenciesFromXML($changeXMLDoc);
					foreach ($decDeps as $depTypeKey => $deps)
					{
						if (! isset($dependencies[$depTypeKey]))
						{
							$dependencies[$depTypeKey] = array();
						}
						foreach ($deps as $depName => $infos)
						{
							if (! isset($dependencies[$depTypeKey][$depName]))
							{
								$infos['depfor'] = $parentDepName;
								$dependencies[$depTypeKey][$depName] = $infos;
							}
							else if ($infos['version'] != $dependencies[$depTypeKey][$depName]['version'])
							{
								c_warning(
										$parentDepName . ' Invalid ' . $depName . '-' . $infos['version'] . ' version expected : ' . $dependencies[$depTypeKey][$depName]['version']);
							}
						}
					}
				}
			}
		}
	}
	
	private function extractHotFix($string)
	{
		$hotFix = array();
		if (empty($string))
		{
			return $hotFix;
		}
		$strHotfixes = explode(",", str_replace(" ", "", $string));
		foreach ($strHotfixes as $value)
		{
			if (intval($value) > 0)
			{
				$hotFix[] = intval($value);
			}
		}
		sort($hotFix, SORT_NUMERIC);
		return $hotFix;
	}
	
	/**
	 * @param f_util_DOMDocument $changeXMLDoc
	 * @return array
	 */
	private function loadDependenciesFromXML($changeXMLDoc)
	{
		$localRepo = $this->getWriteRepository();
		$declaredDeps = array();
		$changeXMLDoc->registerNamespace("cc", "http://www.rbs.fr/schema/change-component/1.0");
		foreach ($changeXMLDoc->find("cc:dependencies/cc:dependency") as $dep)
		{
			if ($dep->hasAttribute("optionnal") && $dep->getAttribute("optionnal") == "true")
			{
				continue;
			}
			$name = $changeXMLDoc->findUnique("cc:name", $dep);
			if ($name == null)
			{
				continue;
			}
			if ($name->textContent == 'framework')
			{
				$depType = 'framework';
				$depName = 'framework';
			}
			else
			{
				$matches = null;
				if (!preg_match('/^([^\/]*)\/(.*)$/', $name->textContent, $matches))
				{
					c_warning("Invalid component name " . $name->textContent);
					continue;
				}
				$depType = $matches[1];
				$depName = $matches[2];
			}
			
			$depTypeKey = null;
			$repoRelativePath = null;
			$link = null;
			switch ($depType)
			{
				case "change-lib" :
				case "framework" :
					if ($depName == "framework")
					{
						$depTypeKey = 'framework';
						$repoRelativePath = '/framework/framework-';
						$link = $this->wd . '/framework';
					}
					break;
				case "lib" :
					$depTypeKey = 'libs';
					$repoRelativePath = '/libs/' . $depName . '/' . $depName . '-';
					$link = $this->wd . '/libs/' . $depName;
					break;
				case "module" :
				case "change-module" :
					$depTypeKey = 'modules';
					$repoRelativePath = '/modules/' . $depName . '/' . $depName . '-';
					$link = $this->wd . '/modules/' . $depName;
					break;
				case "pear" :
				case "lib-pear" :
					$depTypeKey = 'pearlibs';
					$repoRelativePath = '/pearlibs/' . $depName . '/' . $depName . '-';
					$link = $this->wd . '/libs/pearlibs/' . $depName;
					break;
				case "theme" :
				case "themes" :
					$depTypeKey = 'themes';
					$repoRelativePath = '/themes/' . $depName . '/' . $depName . '-';
					$link = $this->wd . '/themes/' . $depName;
					break;
			}
			
			if ($depTypeKey === null)
			{
				continue;
			}
			
			if (! isset($declaredDeps[$depTypeKey]))
			{
				$declaredDeps[$depTypeKey] = array();
			}
			
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 
					'hotfix' => array(), 'repoRelativePath' => null);
			
			foreach ($changeXMLDoc->find("cc:versions/cc:version", $dep) as $versionElem)
			{
				$infos['version'] = $versionElem->textContent;
			}
			
			$repoRelativePath .= $infos['version'];
			$infos['repoRelativePath'] = $repoRelativePath;
			$infos['path'] = $localRepo . $repoRelativePath;
			$infos['link'] = $link;
			$infos['localy'] = is_dir($infos['path']);
			$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath(
					$infos['path']) == realpath($infos['link']);
			$declaredDeps[$depTypeKey][$depName] = $infos;
		}
		return $declaredDeps;
	}
	
	/**
	 * @return String
	 */
	private function getProxy()
	{
		return $this->getProperties()->getProperty("PROXY");
	}
	
	/**
	 * 
	 * @param string $url
	 * @param string $destFile
	 */
	private function downloadFile($url, &$destFile)
	{
		if (!$destFile)
		{
			$tmpDir = $this->getWriteRepository() . '/tmp';
			if (!file_exists($tmpDir) && !mkdir($tmpDir, 0777, true))
			{
				return "Can not create tmp directory $tmpDir";
			}
			$destFile = tempnam($tmpDir, 'tmp');
		}
		
		if (($ch = curl_init($url)) == false)
		{
			return "curl_init error for url $url.";
		}
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getInstanceProjectKey());
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		$proxy = $this->getProxy();
		if ($proxy !== null)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		
		if (($fp = fopen($destFile, "wb")) === false)
		{
			curl_close($ch);
			return "fopen error for filename $destFile";
		}
		
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		if (curl_exec($ch) === false)
		{
			fclose($fp);
			unlink($destFile);
			curl_close($ch);
			return "curl_exec error for url $url in $destFile";
		}
		fclose($fp);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info["http_code"] != "200")
		{
			unlink($destFile);
			return "Could not download $url: bad http status (" . $info["http_code"] . ")";
		}
		return true;
	}
	
	public function compareVersion($version1, $version2)
	{
		if ($version1 == $version2)
		{
			return 0;
		}
		
		$matches1 = explode('.', str_replace('-', '.', $version1));
		$matches2 = explode('.', str_replace('-', '.', $version2));	
			
		$matches1Count = count($matches1);
		$matches2Count = count($matches2);
		
		$count = min($matches1Count, $matches2Count);
		for($i = 0; $i < $count; $i ++)
		{
			//Componant name
			if (!is_numeric($matches1[$i]))
			{
				continue;
			}
			
			if (intval($matches1[$i]) < intval($matches2[$i]))
			{
				return - 1;
			}
			elseif (intval($matches2[$i]) < intval($matches1[$i]))
			{
				return 1;
			}
		}
		if ($matches1Count > $matches2Count)
		{
			return 1;
		}
		return - 1;
	}
	
    public function getRemoteModules($releaseName)
	{
		$modules = array();	
		if ($releaseName === null)
		{
			return $modules;
		}
		
		foreach ($this->getRemoteRepositories() as $repository)
		{
			$url =  $repository . "/release-index.xml";
			$relaseIndexPath = null;
			$result = $this->downloadFile($url, $relaseIndexPath);
			if ($result !== true)
			{
				continue;
			}
			$doc = f_util_DOMUtils::fromPath($relaseIndexPath);
			unlink($relaseIndexPath);
			$releaseElem = $doc->findUnique("release[@name = '$releaseName']");
			if ($releaseElem === null)
			{
				continue;	
			}
			$releaseFile = ($releaseElem->getAttribute("pending") == "true" ? "pendingrelease" : "release") . "-" . $releaseElem->getAttribute(
							"name") . ".xml";
			$url = $repository . "/" . $releaseFile;
			$releaseFilePath = null;
			$result = $this->downloadFile($url, $releaseFilePath);
			if ($result !== true)
			{
				continue;
			}
			$releaseDom = f_util_DOMUtils::fromPath($releaseFilePath);
			unlink($releaseFilePath);
			foreach ($releaseDom->find("module[@available='true']") as $moduleElem) 
			{
				$moduleName = $moduleElem->getAttribute("name") .'-'. $moduleElem->getAttribute("version");
				$hotFix = null;
				foreach ($releaseDom->find("hotfix[not(@pending)]", $moduleElem) as $hotFixNode) 
				{
					$hfn = intval($hotFixNode->getAttribute('number'));
					if ($hotFix === null || $hfn > $hotFix)
					{
						$hotFix = $hfn;
					}
				}
				$modules[] = $moduleName . ($hotFix ? '-' . $hotFix : '');
			}
		}		
		$modules = array_unique($modules);
		sort($modules);
		return $modules;
	}
	
	/**
	 * @param integer $depType
	 * @param string $componentName
	 * @param string $version
	 * @param string $hotfix
	 * @return array
	 */
	private function getLocalComponentPaths($depType, $componentName, $version)
	{
		$basePath = $this->getWriteRepository();
		switch ($depType) 
		{
			case self::$DEP_MODULE:
				$basePath .= "/modules/$componentName";
				break;
			case self::$DEP_FRAMEWORK:
				$basePath .= "/framework";
				break;
			case self::$DEP_LIB:
				$basePath .= "/libs/$componentName";
				break;
			case self::$DEP_PEAR:
				$basePath .= "/pearlibs/$componentName";
				break;
			case self::$DEP_THEME:
				$basePath .= "/themes/$componentName";
				break;
		}
		$result = array();
		var_export($basePath);
		$chekDir = $componentName.'-'.$version;
		foreach (scandir($basePath) as $dir) 
		{
			if (strpos($dir, $chekDir) === 0 && is_dir($basePath . '/'. $dir))
			{
				$result[$basePath . '/'. $dir] = $dir;
			}
		}
		uasort($result, array($this, 'compareVersion'));
		return array_keys($result);
	}
	
	private function getLocalComponentPath($depType, $componentName, $version, $hotfix = null)
	{
		$basePath = $this->getWriteRepository();
		switch ($depType) 
		{
			case self::$DEP_MODULE:
				$basePath .= "/modules/$componentName";
				break;
			case self::$DEP_FRAMEWORK:
				$basePath .= "/framework";
				break;
			case self::$DEP_LIB:
				$basePath .= "/libs/$componentName";
				break;
			case self::$DEP_PEAR:
				$basePath .= "/pearlibs/$componentName";
				break;
			case self::$DEP_THEME:
				$basePath .= "/themes/$componentName";
				break;
		}
		$basePath .= '/'.$componentName.'-'.$version . ($hotfix ? '-' . $hotfix : '');
		if (is_dir($basePath))
		{
			return $basePath;
		}
		return null;	
	}
	
	private $repositoryContents = array();
	
	private function getRemoteRepositoryContent($repository)
	{
		if (!isset($this->repositoryContents[$repository]))
		{
			$this->repositoryContents[$repository] = array();
			$url =  $repository . "/repository.xml";
			$destFile = null;
			$result = $this->downloadFile($url, $destFile);	
			if ($result === true)
			{
				$components = array();
				$doc = f_util_DOMUtils::fromPath($destFile);
				unlink($destFile);
				foreach ($doc->find("//element[@url]") as $elementNode) 
				{
					$depType = self::$DEP_UNKNOWN;
					$elementName = $elementNode->getAttribute("url");
					if ($elementName == "framework")
					{
						$depType = $this->convertToValidType($elementName);
						$componentName = $elementName;
						$repositoryPath = "/framework/framework";
					}
					else
					{
						$elementInfo = explode("/", $elementName);
						if (count($elementInfo) == 2)
						{
							$categorie = $elementInfo[0];
							$depType =  $this->convertToValidType($categorie);
							$componentName = $elementInfo[1];
							$repositoryPath = "/".$categorie . "/" . $componentName . "/" . $componentName;
						}
						else if ($elementNode->parentNode->nodeName == 'elements')
						{
							$categorie = $elementNode->parentNode->getAttribute('type');
							$depType =  $this->convertToValidType($categorie);
							$componentName = $elementName;
							$repositoryPath = "/". $categorie . "/" .$componentName. "/" . $componentName;
						}
					}
					
					if ($depType != self::$DEP_UNKNOWN)
					{
						foreach ($doc->find("versions/version", $elementNode) as $versionNode) 
						{
							$fullVersion = $versionNode->textContent;
							$components[$repositoryPath . '-' . $fullVersion] = array(
								"md5" => $versionNode->getAttribute("md5"), 
								"sha1" => $versionNode->getAttribute("sha1"));
						}
					}
				}
				$this->repositoryContents[$repository] = $components;
			}	
		}
		return $this->repositoryContents[$repository];
	}
	
	public function getHotfixes($releaseName)
	{
		$hotfixes = array();
		foreach ($this->getRemoteRepositories() as $repository)
		{
			$url =  $repository . "/release-index.xml";
			$relaseIndexPath = null;
			$result = $this->downloadFile($url, $relaseIndexPath);
			if ($result !== true)
			{
				continue;
			}
			$doc = f_util_DOMUtils::fromPath($relaseIndexPath);
			unlink($relaseIndexPath);
			$releaseElem = $doc->findUnique("release[@name = '$releaseName']");
			if ($releaseElem === null)
			{
				continue;	
			}
			$releaseFile = ($releaseElem->getAttribute("pending") == "true" ? "pendingrelease" : "release") . "-" . $releaseElem->getAttribute(
							"name") . ".xml";
			$url = $repository . "/" . $releaseFile;
			$releaseFilePath = null;
			$result = $this->downloadFile($url, $releaseFilePath);
			if ($result !== true)
			{
				continue;
			}
			$releaseDom = f_util_DOMUtils::fromPath($releaseFilePath);
			unlink($releaseFilePath);
			foreach ($releaseDom->find("//hotfix[not(@pending)]") as $hotfixElem) 
			{
				$moduleElem = $hotfixElem->parentNode;
				$category = $this->convertToCategory($moduleElem->tagName);			
				$componentName = $moduleElem->getAttribute("name");
				$version = $moduleElem->getAttribute("version");
				$hotFix = $hotfixElem->getAttribute("number");
				$repositoryPath = $this->buildLocalRepositoryPath($this->convertToValidType($category), $componentName, $version, $hotFix);
				$hotfixes[$repositoryPath] = true;
			}
		}
		return array_keys($hotfixes);
	}
	
	/**
	 * @param mixed $componentType
	 * @param string $componentName
	 * @param string $version
	 * @return string 
	 */
	public function installComponent($componentType, $componentName, $version, $hotfix = null)
	{
		$depType = $this->convertToValidType($componentType);
		if (!$hotfix && strpos($version, '-'))
		{
			list ($version, $hotfix) = explode('-', $version);
		}		
		$localPath = $this->getLocalComponentPath($depType, $componentName, $version, $hotfix);
		if ($localPath)
		{
			return $localPath;
		}
		$path = null;
		$repositoryPath = $this->buildLocalRepositoryPath($depType, $componentName, $version, $hotfix);
		foreach ($this->getRemoteRepositories() as $repository) 
		{
			$contents = $this->getRemoteRepositoryContent($repository);
			if (isset($contents[$repositoryPath]))
			{
				$url = $repository . $repositoryPath . '.zip';
				$destFile = null;
				$result = $this->downloadFile($url, $destFile);
				if ($result === true)
				{
					if (md5_file($destFile) != $contents[$repositoryPath]["md5"] ||
							sha1_file($destFile) != $contents[$repositoryPath]["sha1"])
					{
						unlink($destFile);
						c_warning("Checksum of $destFile failed");
					}
					else
					{
						$path = $this->getWriteRepository() . $repositoryPath;
						cboot_Zip::unzip($destFile, dirname($path));
						unlink($destFile);
						return $path;
					}
				}
				else
				{
					c_warning("downloadFile ->  $result");
				}
			}
		}
		return $path;
	}
	
	
	
	/**
	 * @param String $dir
	 * @return multitype:NULL
	 */
	public function getDependencies($localPath)
	{
		$filePath = $localPath . '/change.xml';
		var_export($filePath);	
		if (!is_file($filePath))
		{
			return array();
		}
		
		$changeXMLDoc = f_util_DOMUtils::fromPath($filePath);
		$decDeps = $this->loadDependenciesFromXML($changeXMLDoc);
		return $decDeps;
	}
	
	/** 
	 * @param string $componentType
	 * @param string $componentName
	 * @param string $version
	 * @param integer $hotfix
	 * @return boolean;
	 */
	public function linkToProject($componentType, $componentName, $version, $hotfix = null)
	{
		$depType = $this->convertToValidType($componentType);
		$localPath = $this->getLocalComponentPath($depType, $componentName, $version, $hotfix);
		if ($localPath === null)
		{
			return false;
		}
		
		$projectPath = $this->buildProjectPath($depType, $componentName);
		if (!is_dir(dirname($projectPath)) && !@mkdir(dirname($projectPath), 0777, true))
		{
			return false;
		}
		
		if (file_exists($projectPath) && !@unlink($projectPath))
		{
			return false;
		}
		if (!@symlink($localPath, $projectPath))
		{
			return false;
		}
		
		$this->cleanDependenciesCache();
		if ($depType == self::$DEP_PEAR)
		{
			$pearInfo = $this->loadPearInfo();
			if ($pearInfo['writeable'])
			{
				$incPath = $pearInfo['include_path'];
				$projectPath = $incPath .'/'. basename($localPath);
				
				f_util_FileUtils::cp($localPath, $incPath, f_util_FileUtils::OVERRIDE + f_util_FileUtils::APPEND);
				
				$analyzer = cboot_ClassDirAnalyzer::getInstance();
				foreach (scandir($localPath) as $newPearFile)
				{
					if ($newPearFile == "." || $newPearFile == "..")
					{
						continue;
					}
					
					$toAutoload = $incPath.'/'.$newPearFile;
					if (is_file($toAutoload))
					{
						$analyzer->appendFile($toAutoload);
					}
					else if (is_dir($toAutoload))
					{
						$analyzer->appendRealDir($toAutoload);
					}
				}
				return true;
			}
			return false;
		}
		$this->appendToAutoload($projectPath);
		return true;
	}
	
	public function updateProjectDependencies($componentType, $componentName, $version, $hotfix = null)
	{
		$changeXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
		$changeXMLDoc->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$depType = $this->convertToValidType($componentType);
		$depNode = null;
		switch ($depType) 
		{
			case self::$DEP_FRAMEWORK:
				$depNode = $changeXMLDoc->findUnique("c:dependencies/c:framework");
				if ($depNode === null)
				{
					$depNode = $changeXMLDoc->createElement("framework", $version);
					$changeXMLDoc->findUnique("c:dependencies")->appendChild($depNode);
				}
				else
				{
					while ($depNode->hasChildNodes()){$depNode->removeChild($depNode->lastChild);}
					$depNode->appendChild($changeXMLDoc->createTextNode($version));
				}
				break;
				
			case self::$DEP_MODULE:
				$modulesNode = $changeXMLDoc->findUnique("c:dependencies/c:modules");
				foreach ($changeXMLDoc->findUnique("c:module", $modulesNode) as $moduleNode) 
				{
					list($nmn, ) = explode('-', $moduleNode->textContent);
					if ($nmn == $componentName)
					{
						$depNode = $moduleNode;
						while ($depNode->hasChildNodes()){$depNode->removeChild($depNode->lastChild);}
						$depNode->appendChild($changeXMLDoc->createTextNode($componentName .'-'.$version));
						break;
					}
				}
				if ($depNode === null)
				{
					$depNode = $changeXMLDoc->createElement("module", $componentName .'-'.$version);
					$modulesNode->appendChild($depNode);
				}		
				break;	
			case self::$DEP_LIB:
				$libsNode = $changeXMLDoc->findUnique("c:dependencies/c:libs");
				if ($libsNode === null)
				{
					$libsNode = $changeXMLDoc->createElement('libs');
					$changeXMLDoc->findUnique("c:dependencies")->appendChild($libsNode);
				}
				
				foreach ($changeXMLDoc->findUnique("c:lib", $libsNode) as $libNode) 
				{
					list($nmn, ) = explode('-', $libNode->textContent);
					if ($nmn == $componentName)
					{
						$depNode = $libNode;
						while ($depNode->hasChildNodes()){$depNode->removeChild($depNode->lastChild);}
						$depNode->appendChild($changeXMLDoc->createTextNode($componentName .'-'.$version));
						break;
					}
				}
				if ($depNode === null)
				{
					$depNode = $changeXMLDoc->createElement("lib", $componentName .'-'.$version);
					$libsNode->appendChild($depNode);
				}		
				break;
		}
		
		if (!$depNode)
		{
			return false;
		}
		
		if ($hotfix)
		{
			$hotfixAttr = array();
			if ($depNode->hasAttribute("hotfixes"))
			{
				foreach (explode(',', $depNode->getAttribute("hotfixes")) as $str) 
				{
					$hotfixNumber = intval(trim($str));
					if ($hotfixNumber) {$hotfixAttr[] = $hotfixNumber;}
				}
			}
			$hotfixNumber = intval($hotfix);
			if (!in_array($hotfixNumber, $hotfixAttr))
			{
				$hotfixAttr[] = $hotfixNumber;
			}
			sort($hotfixAttr, SORT_NUMERIC);
			$depNode->setAttribute("hotfixes", join(",", $hotfixAttr));
		}
		
		try 
		{
			f_util_DOMUtils::save($changeXMLDoc, $this->getDescriptorPath());
		}
		catch (Exception $e)
		{
			return false;
		}
		
		return true;
	}

	//PEAR INSTALLATION

	/**
	 * @var array
	 */
	private $pearInfos;
	
	/**
	 * @return array
	 */
	public function loadPearInfo()
	{
		if ($this->pearInfos === null)
		{
			$pearDir = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_DIR"));
			$pearCmd = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_CMD"));
			$pearConf = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_CONF"));
			$include_path = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_INCLUDE_PATH"));
			
			if ($pearDir !== null && $pearCmd === null && $pearConf === null && $include_path === null)
			{
				//Previous config
				$pearCmd = $pearDir . '/bin/pear';
				$pearConf = $pearDir . '/pear.conf';
				if (! file_exists($pearConf))
				{
					$pearConf = null;
				}
			}
			
			if ($pearCmd !== null)
			{
				if (! file_exists($pearCmd))
				{
					$pearDir = null;
					$pearCmd = null;
					$pearConf = null;
				}
			}
			if ($pearConf !== null && ! file_exists($pearConf))
			{
				$pearConf = null;
			}
			
			if ($include_path === null && $pearDir === null)
			{
				$include_path = $this->wd . "/pear";
			}
			
			if ($include_path === null)
			{
				$include_path = $pearDir . '/PEAR';
			}
			
			if (! file_exists($include_path) && ! @mkdir($include_path, 0777, true))
			{
				$writeable = false;
			}
			else
			{
				$writeable = is_writeable($include_path);
			}
			
			$this->pearInfos = array("include_path" => $include_path, "writeable" => $writeable, 
					"path" => $pearDir, "command" => $pearCmd, "conf" => $pearConf);
		}
		return $this->pearInfos;
	}
	
	/**
	 * @return boolean
	 */
	private function useChangePearLib()
	{
		$this->loadPearInfo();
		return true;
	}
}
