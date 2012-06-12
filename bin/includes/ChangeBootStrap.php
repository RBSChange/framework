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
	 * WEBEDIT_HOME
	 * 
	 * @var String
	 */
	private $wd;
	
	/**
	 *
	 * @var String
	 */
	private $name = "change";
	
	/**
	 *
	 * @var String
	 */
	private $descriptor = "change.xml";
	
	/**
	 *
	 * @var String
	 */
	private $descriptorPath;
	
	/**
	 *
	 * @var array<String, Boolean>
	 */
	private $localRepositories;
	
	/**
	 *
	 * @var string[]
	 */
	private $remoteRepositories;
	
	/**
	 *
	 * @var cboot_Properties
	 */
	private $properties;
	
	/**
	 *
	 * @var c_ChangeBootStrap
	 */
	private static $instance;
	
	/**
	 *
	 * @var cboot_Configuration
	 */
	private $configuration;
	
	/**
	 * 
	 * @var string
	 */
	private $autoloadPath;
	
	/**
	 * @var array
	 */
	private $autoloaded = array();
	
	/**
	 * @var boolean
	 */
	private $autoloadRegistered = false;
	
	/**
	 * @var boolean
	 */
	private $refreshAutoload = false;
	
	/**
	 * @var string
	 */	
	private $instanceProjectKey = null;
	
	/**
	 * @var boolean|array
	 */
	private $remoteError = true;
	
	/**
	 *
	 * @param String $path        	
	 */
	public function __construct($path)
	{
		$this->wd = $path;
		self::$instance = $this;
	}
	
	/**
	 *
	 * @return c_ChangeBootStrap
	 */
	static function getInstance()
	{
		return self::$instance;
	}
	
	/**
	 *
	 * @return cboot_Configuration
	 */
	public function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$this->configuration = cboot_Configuration::getInstance($this->getName());
			$this->configuration->addLocation($this->wd);
		}
		return $this->configuration;
	}
	
	public function getName()
	{
		if ($this->name === null)
		{
			$this->name = "change";
		}
		return $this->name;
	}
	
	/**
	 *
	 * @param String $name        	
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 *
	 * @param String $path        	
	 */
	public function addPropertiesLocation($path)
	{
		$this->getConfiguration()->addLocation($path);
	}
	
	/**
	 *
	 * @param String $descriptor        	
	 * @return c_ChangeBootStrap
	 */
	public function setDescriptor($descriptor = "change.xml")
	{
		$this->descriptor = $descriptor;
		return $this;
	}
	
	/**
	 *
	 * @return array
	 */
	public function getComputedDependencies()
	{
		$computedDeps = $this->generateComputedChangeComponents();
		return $computedDeps;
	}
	
	/**
	 * @param string $target        	
	 */
	public function dispatch($target = null)
	{
		// Check change.xml existence
		$descPath = $this->getDescriptorPath();
		
		if (!is_file($descPath))
		{
			throw new Exception("Could not find $descPath");
		}
		
		if ($target != "")
		{
			$scriptPath = null;
			if (f_util_StringUtils::beginsWith($target, "dep:"))
			{
				list (, $component, $relativePath) = explode(":", $target);
				// TODO Component path check
				$scriptPath = $this->wd . "/" . $component . "/" . $relativePath;
			}
			elseif (f_util_StringUtils::beginsWith($target, "func:"))
			{
				list (, $scriptFunction) = explode(":", $target);
				if (!function_exists($scriptFunction))
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
			if ($scriptPath !== null && !file_exists($scriptPath))
			{
				throw new Exception("Unable to find $target (location should be '$scriptPath')");
			}
			
			$computedDeps = $this->getComputedDependencies();
			
			if (!isset($_SERVER["argv"]) || !is_array($_SERVER["argv"]))
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
	
	/**
	 * @return void
	 */
	public function cleanDependenciesCache()
	{
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
	
	/**
	 * @param boolean $refresh
	 */
	public function setRefreshAutoload($refresh)
	{
		$this->refreshAutoload = $refresh;
	}
	
	/**
	 * @param string $componentPath
	 */
	public function appendToAutoload($componentPath)
	{
	
		$autoloadPath = $this->getAutoloadPath();
		$autoloadedFlag = $autoloadPath . "/" . md5($componentPath) . ".autoloaded";
	
		$analyzer = cboot_ClassDirAnalyzer::getInstance();
		if (!$this->autoloadRegistered)
		{
			spl_autoload_register(array($analyzer, "autoload"));
			$this->autoloadRegistered = true;
		}
	
		if (isset($this->autoloaded[$componentPath]) || (!$this->refreshAutoload && file_exists($autoloadedFlag)))
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
	 * @param string $path
	 * @return boolean
	 */
	public function isPathAbsolute($path)
	{
		return $path[0] === '/' || $path[0] === '\\' || (strlen($path) > 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'));
	}
	
	/**
	 * @param string $autoloadPath
	 */
	public function setAutoloadPath($autoloadPath = "cache/autoload")
	{
		if (!$this->isPathAbsolute($autoloadPath))
		{
			$this->autoloadPath = $this->wd . "/" . $autoloadPath;
		}
		else
		{
			$this->autoloadPath = $autoloadPath;
		}
	}	
	
	
	/**
	 * Return the path of project change.xml
	 *
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
	public function getProperties($fileName = null)
	{
		return $this->getConfiguration()->getProperties($fileName);
	}
	
	/**
	 * @return string[]
	 */
	public function getRemoteRepositories()
	{
		if ($this->remoteRepositories === null)
		{
			$this->remoteRepositories = array_unique(explode(",", $this->getProperties()->getProperty("REMOTE_REPOSITORIES", "http://update.rbschange.fr")));
		}
		return $this->remoteRepositories;
	}	
	
	/**
	 * @return string
	 */
	public function getInstanceProjectKey()
	{
		if ($this->instanceProjectKey === null)
		{
			$license = $this->getProperties()->getProperty("PROJECT_LICENSE");
			$mode = ($this->getProperties()->getProperty("DEVELOPMENT_MODE", false) == true) ? "DEV" : "PROD";
			if (empty($license))
			{
				$license = "OS";
			}
			$version = '-';
			$profile = '-';
			$pId = '-';
			$fqdn = '-';
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
				$configPath = $this->wd . '/config/project.' . $profile . '.xml';
				if (is_readable($configPath))
				{
					$changeXMLDoc = f_util_DOMUtils::fromPath($configPath);
					$pIdNode = $changeXMLDoc->findUnique('defines/define[@name="PROJECT_ID"]');
					$pId = $pIdNode ? $pIdNode->textContent : '-';
					$fqdnNode = $changeXMLDoc->findUnique('config/general/entry[@name="server-fqdn"]');
					$fqdn = $fqdnNode ? $fqdnNode->textContent : '-';
				}
			}
			$this->instanceProjectKey = 'Change/' . $version . ';License/' . $license . ';Profile/' . $profile . ';PId/' . $pId . ';DevMode/' . $mode . ';FQDN/' . $fqdn;
		}
		return $this->instanceProjectKey;
	}
	
	/**
	 * @return string
	 */
	public function getCurrentReleaseName()
	{
		$parts = explode(';', $this->getInstanceProjectKey());
		$p2 = explode('/', $parts[0]);
		return $p2[1];
	}	
	
	
	/**
	 *
	 * @return array<String, Boolean> path => writeable
	 */
	public function getLocalRepositories()
	{
		if ($this->localRepositories === null)
		{
			// Local repositories
			$this->localRepositories = array();
			foreach (array_unique(explode(",", $this->getProperties()->getProperty("LOCAL_REPOSITORY", $this->wd . "/repository"))) as $localRepoPath)
			{
				if (trim($localRepoPath) == '') {continue;}
				
				if (!is_dir($localRepoPath)) {continue;}
				
				$writable = true;
				$writableTmpPath = $localRepoPath . DIRECTORY_SEPARATOR . 'tmp';
				if (!is_dir($writableTmpPath) && !mkdir($writableTmpPath, 0777, true))
				{
					$writable = false;
				}
				else
				{
					$destFile = tempnam($writableTmpPath, 'tmp');
					if ($destFile !== false)
					{
						unlink($destFile);
					}
					else
					{
						$writable = false;
					}
				}
				$this->localRepositories[realpath($localRepoPath)] = $writable;
			}
		}
		return $this->localRepositories;
	}	
	
	/**
	 * @param integer $depType
	 * @return string (framework|modules|libs|pearlibs|themes)
	 */
	public function convertToCategory($depType)
	{
		switch ($depType)
		{
			case self::$DEP_FRAMEWORK :
			case "change-lib" :
			case "framework" :
				return 'framework';
					
			case self::$DEP_MODULE :
			case "modules" :
			case "module" :
				return 'modules';
					
			case self::$DEP_LIB :
			case "libs" :
			case "lib" :
				return 'libs';
					
			case self::$DEP_PEAR :
			case "lib-pear" :
			case "pearlibs" :
			case "pearlib" :
			case "pear" :
				return 'pearlibs';
					
			case self::$DEP_THEME :
			case "themes" :
			case "theme" :
				return 'themes';
	
		}
		return "";
	}	
	
	
	/**
	 * @param string $releaseName
	 * @return array
	 */
	public function getReleaseDefinition($releaseName)
	{
		$result = array();
		foreach ($this->getRemoteRepositories() as $repository)
		{
			$url = $repository . "/release-" . $releaseName . ".xml";
			$releaseContentPath = null;
			if ($this->getRemoteFile($url, $releaseContentPath) === true)
			{
				$releaseDom = f_util_DOMUtils::fromPath($releaseContentPath);
				foreach ($releaseDom->documentElement->childNodes as $node)
				{
					/* @var $node DOMElement */
					if ($node->nodeType !== XML_ELEMENT_NODE) {
						continue;
					}
					switch ($node->localName)
					{
						case 'change-lib':
							$e = array('type' => $this->convertToCategory(self::$DEP_FRAMEWORK),
							'name' => 'framework', 'version' => $node->getAttribute('version'));
							$result[$e['type']][$e['name']] = $e;
							break;
						case 'module':
						case 'lib':
						case 'pearlib':
						case 'theme':
							$e = array('type' => $this->convertToCategory($node->localName),
							'name' => $node->getAttribute('name'), 'version' => $node->getAttribute('version'));
							$result[$e['type']][$e['name']] = $e;
					}
				}
				unlink($releaseContentPath);
				break;
			}
		}
		return $result;
	}	
		
	/**
	 * @param string|integer $depType
	 * @param string $name
	 * @param string $version
	 */
	public function getProjectPath($depType, $name, $version)
	{
		return $this->buildProjectPath($this->convertToValidType($depType), $name);
	}
	

	public function loadDependencies()
	{
		$localRepo = $this->getWriteRepository();
		$dependencies = array();
		$changeXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
		$changeXMLDoc->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 'repoRelativePath' => null);
		$dependencies['framework'] = array('framework' => $infos);
		$frameworkElem = $changeXMLDoc->findUnique("c:dependencies/c:framework");
		if ($frameworkElem !== null)
		{
			$infos['version'] = $frameworkElem->textContent;
			$repoRelativePath = '/framework/framework-' . $infos['version'];
			$infos['repoRelativePath'] = $repoRelativePath;
			$infos['path'] = $localRepo . $repoRelativePath;
			$infos['link'] = $this->wd . '/framework';
			
			$infos['localy'] = is_dir($infos['path']);
			$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath($infos['path']) == realpath($infos['link']);
			$dependencies['framework']['framework'] = $infos;
		}
		
		$dependencies['modules'] = array();
		foreach ($changeXMLDoc->find("c:dependencies/c:modules/c:module") as $moduleElem)
		{
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 'repoRelativePath' => null);
			$matches = array();
			if (!preg_match('/^(.*?)-([0-9].*)$/', $moduleElem->textContent, $matches))
			{
				$moduleName = $moduleElem->textContent;
			}
			else
			{
				$moduleName = $matches[1];
				$infos['version'] = $matches[2];
				$repoRelativePath = '/modules/' . $moduleName . '/' . $moduleName . '-' . $infos['version'];
				$infos['repoRelativePath'] = $repoRelativePath;
				$infos['path'] = $localRepo . $repoRelativePath;
				$infos['link'] = $this->wd . '/modules/' . $moduleName;
				
				$infos['localy'] = is_dir($infos['path']);
				$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath($infos['path']) == realpath($infos['link']);
			}
			$dependencies['modules'][$moduleName] = $infos;
		}
		
		$dependencies['libs'] = array();
		foreach ($changeXMLDoc->find("c:dependencies/c:libs/c:lib") as $libElem)
		{
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 'repoRelativePath' => null);
			$matches = array();
			if (!preg_match('/^(.*?)-([0-9].*)$/', $libElem->textContent, $matches))
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
				$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath($infos['path']) == realpath($infos['link']);
			}
			$dependencies['libs'][$libName] = $infos;
		}
		$this->loadImplicitDependencies($dependencies);
		return $dependencies;
	
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
		for ($i = 0; $i < $count; $i++)
		{
			// Componant name
			if (!is_numeric($matches1[$i]))
			{
				continue;
			}
			
			if (intval($matches1[$i]) < intval($matches2[$i]))
			{
				return -1;
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
		return -1;
	}
	
	/**
	 * @param string $type
	 * @param string $name
	 * @param string $version
	 * @return string|false
	 */
	public function dependencyInLocalRepository($type, $name, $version)
	{
		$path = $this->buildLocalRepositoryPath($this->convertToValidType($type), $name, $version);
		return is_dir($path) ? $path : false;
	}
	
	/**
	 * @param string $url
	 * @param string $filePath
	 * @param array $postDataArray
	 * @return true|array<errorNumber, errorMessage>
	 */
	public function downloadRepositoryFile($url, &$filePath, $postDataArray = null)
	{
		return $this->getRemoteFile($url, $filePath, $postDataArray);
	}

	/**
	 * @param string $depType
	 * @param string $name
	 * @param string $version
	 * @param string $url
	 * @throws Exception
	 * @return string repository download path
	 */
	public function downloadDependency($type, $name, &$version, &$url)
	{
		$depType = $this->convertToValidType($type);
		$type = $this->convertToCategory($type);
		
		if ($version === null)
		{
			$relaseInfos = $this->getReleaseDefinition($this->getCurrentReleaseName());
			if (!isset($relaseInfos[$type]) || !isset($relaseInfos[$type][$name]))
			{
				throw new Exception('Unable to find version of: ' . $type .  '/' . $name);
			}
			$version = $relaseInfos[$type][$name]['version'];
		}
		$path = $this->buildRepositoryPath($depType, $name, $version);
		$tmpFile = null;
		if ($url === null)
		{
			foreach ($this->getRemoteRepositories() as $repository)
			{
				$url = $repository . $path . '.zip';
				if ($this->getRemoteFile($url, $tmpFile) === true)
				{
					break;
				}
				$url = null;
				$tmpFile = null;
			}
		}
		else
		{
			if ($this->getRemoteFile($url, $tmpFile) !== true)
			{
				$url = null;
				$tmpFile = null;
			}
		}
		
		if ($tmpFile === null)
		{
			throw new Exception(implode(', ', $this->remoteError));
		}
			
		$this->deleteDependency($depType, $name, $version);
		
		$localPath = $this->buildLocalRepositoryPath($depType, $name, $version);
		echo 'Unzip ', $tmpFile, ' in (', $localPath , ')',  PHP_EOL;
		cboot_Zip::unzip($tmpFile, dirname($localPath));
		unlink($tmpFile);
		
		clearstatcache();
		if (!is_dir($localPath))
		{
			$this->restoreDependency($depType, $name, $version);
			throw new Exception('Invalid Archive Content: ' . dirname($localPath));
		}
		
		if ($depType === self::$DEP_FRAMEWORK)
		{
			$deps = $this->loadDependencies();
			if ($deps['framework']['framework']['version'] == $version)
			{
				$this->linkToProject($depType, $name, $version);
			}
		}
		elseif ($depType === self::$DEP_MODULE)
		{
			$deps = $this->loadDependencies();
			if (isset($deps['modules'][$name]) && $deps['modules'][$name]['version'] == $version)
			{
				$this->linkToProject($depType, $name, $version);
			}
		}
		elseif ($depType === self::$DEP_PEAR)
		{
			$this->linkToProject($depType, $name, $version);
		}
		return $localPath;
	}
	
	/**
	 * @param string $type
	 * @param string $name
	 * @param string $version
	 * @param boolean $backup
	 */
	public function deleteDependency($type, $name, $version, $backup = true)
	{
		$depType = $this->convertToValidType($type);
		$localPath = $this->buildLocalRepositoryPath($depType, $name, $version);
		if (is_dir($localPath))
		{
			if ($backup)
			{
				$count = 0;
				while (is_dir($localPath . '-bak'.$count))
				{
					$count++;
				}
				while ($count > 0)
				{
					rename($localPath . '-bak'.($count -1 ) , $localPath . '-bak'.$count);
					$count--;
				}
				
				echo 'Backup ', $localPath, ' => ', $localPath , '-bak0', PHP_EOL;
				rename($localPath, $localPath . '-bak0');
			}
			else
			{
				f_util_FileUtils::rmdir($localPath);
			}
		}
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
	 * @param string $depType        	
	 * @param string $name        	
	 * @param string $version        	      	
	 * @return boolean;
	 */
	public function linkToProject($depType, $name, $version)
	{
		$depType = $this->convertToValidType($depType);
		$localPath = $this->buildLocalRepositoryPath($depType, $name, $version);
		if (!is_dir($localPath))
		{
			return false;
		}
		
		$projectPath = $this->buildProjectPath($depType, $name);
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
		
		if ($depType == self::$DEP_PEAR)
		{
			$pearInfo = $this->loadPearInfo();
			if ($pearInfo['writeable'])
			{
				$incPath = $pearInfo['include_path'];
				f_util_FileUtils::cp($localPath, $incPath, f_util_FileUtils::OVERRIDE + f_util_FileUtils::APPEND);
				$this->cleanDependenciesCache();
				$this->appendToAutoload($incPath);
				return true;
			}
			return false;
		}
		elseif ($depType == self::$DEP_FRAMEWORK || $depType == self::$DEP_MODULE)
		{
			$this->cleanDependenciesCache();
			$this->appendToAutoload($projectPath);
		}
		return true;
	}
	
	/**
	 * @param string $type
	 * @param string $name
	 * @param string $version
	 */
	public function updateProjectDependencies($type, $name, $version)
	{
		$changeXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
		$changeXMLDoc->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$depType = $this->convertToValidType($type);
		$depNode = null;
		$oldNode = null;
		switch ($depType)
		{
			case self::$DEP_FRAMEWORK :
				$oldNode = $changeXMLDoc->findUnique("c:dependencies/c:framework");
				
				$depNode = $changeXMLDoc->createElement("framework", $version);
				
				if ($oldNode === null)
				{
					$changeXMLDoc->findUnique("c:dependencies")->appendChild($depNode);
				}
				else
				{
					$changeXMLDoc->findUnique("c:dependencies")->removeChild($oldNode, $depNode);
				}
				break;
			
			case self::$DEP_MODULE :
				$modulesNode = $changeXMLDoc->findUnique("c:dependencies/c:modules");
				if ($modulesNode === null)
				{
					$modulesNode = $changeXMLDoc->createElement(':modules');
					$changeXMLDoc->findUnique("c:dependencies")->appendChild($modulesNode);
				}
				
				foreach ($changeXMLDoc->findUnique("c:module", $modulesNode) as $moduleNode)
				{
					list ($nmn, ) = explode('-', $moduleNode->textContent);
					if ($nmn == $name)
					{
						$oldNode = $moduleNode;
						break;
					}
				}
				$depNode = $changeXMLDoc->createElement("module", $name . '-' . $version);
				if ($oldNode !== null)
				{
					$modulesNode->replaceChild($depNode, $oldNode);
				}
				else
				{
					$modulesNode->appendChild($depNode);
				}
				break;
				
			case self::$DEP_LIB :
				$libsNode = $changeXMLDoc->findUnique("c:dependencies/c:libs");
				if ($libsNode === null)
				{
					$libsNode = $changeXMLDoc->createElement('libs');
					$changeXMLDoc->findUnique("c:dependencies")->appendChild($libsNode);
				}
				
				foreach ($changeXMLDoc->findUnique("c:lib", $libsNode) as $libNode)
				{
					list ($nmn, ) = explode('-', $libNode->textContent);
					if ($nmn == $name)
					{
						$oldNode = $libNode;
						break;
					}
				}
				$depNode = $changeXMLDoc->createElement("lib", $name . '-' . $version);
				if ($oldNode !== null)
				{
					$libsNode->replaceChild($depNode, $oldNode);
				}
				else
				{
					$libsNode->appendChild($depNode);
				}
				break;
		}
		
		if (!$depNode)
		{
			return false;
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
	
	// PEAR INSTALLATION
	
	/**
	 *
	 * @var array
	 */
	private $pearInfos;
	
	/**
	 *
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
				// Previous config
				$pearCmd = $pearDir . '/bin/pear';
				$pearConf = $pearDir . '/pear.conf';
				if (!file_exists($pearConf))
				{
					$pearConf = null;
				}
			}
			
			if ($pearCmd !== null)
			{
				if (!file_exists($pearCmd))
				{
					$pearDir = null;
					$pearCmd = null;
					$pearConf = null;
				}
			}
			if ($pearConf !== null && !file_exists($pearConf))
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
			
			if (!file_exists($include_path) && !@mkdir($include_path, 0777, true))
			{
				$writeable = false;
			}
			else
			{
				$writeable = is_writeable($include_path);
			}
			
			$this->pearInfos = array("include_path" => $include_path, "writeable" => $writeable, "path" => $pearDir, "command" => $pearCmd, 
				"conf" => $pearConf);
		}
		return $this->pearInfos;
	}
	

	// PRIVATE
	
	/**
	 * @param integer $depType
	 * @param string $name
	 * @param string $version
	 * @param integer $backupNumber
	 */
	private function restoreDependency($depType, $name, $version, $backupNumber = 0)
	{
		$localPath = $this->buildLocalRepositoryPath($depType, $name, $version);
		$backupPath = $localPath . '-bak'.$backupNumber;
		if (is_dir($backupPath))
		{
			if (is_dir($localPath)) {
				f_util_FileUtils::rmdir($localPath);
			}
			echo "Restore ", $localPath , ' <= ', $backupPath, PHP_EOL;
			rename($backupPath , $localPath);
		}
	}

	/**
	 *
	 * @param string $url
	 * @param string $destFile
	 * @param array $postDataArray
	 * @return true array
	 */
	private function getRemoteFile($url, &$destFile, $postDataArray = null)
	{
		$this->remoteError = true;
		if (!$destFile)
		{
			$wr = $this->getWriteRepository();
			if ($wr === null)
			{
				$this->remoteError = array(-10, 'Invalid LOCAL_REPOSITORY configuration', $this->getProperties()->getProperty("LOCAL_REPOSITORY", $this->wd . "/repository"));
				echo implode(', ', $this->remoteError), PHP_EOL;
				return $this->remoteError;
			}
			$tmpDir = $this->getWriteRepository() . '/tmp';
			if (!file_exists($tmpDir) && !mkdir($tmpDir, 0777, true))
			{
				$this->remoteError = array(-1, 'Can not create tmp directory ' . $tmpDir);
				echo implode(', ', $this->remoteError), PHP_EOL;
				return $this->remoteError;
			}
			$destFile = tempnam($tmpDir, 'tmp');
		}
	
		$fp = fopen($destFile, "wb");
		if ($fp === false)
		{
			$this->remoteError = array(-1, 'Fopen error for filename ', $destFile);
			echo implode(', ', $this->remoteError), PHP_EOL;
			return $this->remoteError;
		}
	
		$ch = curl_init($url);
		if ($ch == false)
		{
			$this->remoteError = array(-2, 'Curl_init error for url ' . $url);
			echo implode(', ', $this->remoteError), PHP_EOL;
			return $this->remoteError;
		}
	
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getInstanceProjectKey());
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if (is_array($postDataArray) && count($postDataArray))
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postDataArray, null , '&'));
			curl_setopt($ch, CURLOPT_POST, true);
		}
		
		$proxy = $this->getProxy();
		if ($proxy !== null)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
	
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		if (curl_exec($ch) === false)
		{
			$this->remoteError = array(curl_errno($ch), curl_error($ch));
			fclose($fp);
			unlink($destFile);
			curl_close($ch);
			echo implode(', ', $this->remoteError), PHP_EOL;
			return $this->remoteError;
		}
	
		fclose($fp);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info["http_code"] != "200")
		{
			unlink($destFile);
			$this->remoteError = array($info["http_code"], "Could not download " . $url . ": bad http status (" . $info["http_code"] . ")");
			echo implode(', ', $this->remoteError), PHP_EOL;
			return $this->remoteError;
		}
	
		return $this->remoteError;
	}
		
	private function loadImplicitDependencies(&$dependencies)
	{
		$localRepo = $this->getWriteRepository();
		foreach ($dependencies as $parentDepTypeKey => $parentDeps)
		{
			foreach ($parentDeps as $parentDepName => $parentInfos)
			{
				if ($parentInfos['localy'] && !isset($parentInfos['implicitdependencies']))
				{
					$dependencies[$parentDepTypeKey][$parentDepName]['implicitdependencies'] = true;
					$filePath = $localRepo . $parentInfos['repoRelativePath'] . '/change.xml';
					if (!is_file($filePath))
					{
						c_warning($filePath . ' not found');
						continue;
					}
						
					$changeXMLDoc = f_util_DOMUtils::fromPath($filePath);
					$decDeps = $this->loadDependenciesFromXML($changeXMLDoc);
					foreach ($decDeps as $depTypeKey => $deps)
					{
						if (!isset($dependencies[$depTypeKey]))
						{
							$dependencies[$depTypeKey] = array();
						}
						foreach ($deps as $depName => $infos)
						{
							if (!isset($dependencies[$depTypeKey][$depName]))
							{
								$infos['depfor'] = $parentDepName;
								$dependencies[$depTypeKey][$depName] = $infos;
							}
						}
					}
				}
			}
		}
	}
	
	
	/**
	 *
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
				
			if (!isset($declaredDeps[$depTypeKey]))
			{
				$declaredDeps[$depTypeKey] = array();
			}
				
			$infos = array('localy' => FALSE, 'linked' => false, 'version' => '', 'repoRelativePath' => null);
				
			foreach ($changeXMLDoc->find("cc:versions/cc:version", $dep) as $versionElem)
			{
				$infos['version'] = $versionElem->textContent;
			}
				
			$repoRelativePath .= $infos['version'];
			$infos['repoRelativePath'] = $repoRelativePath;
			$infos['path'] = $localRepo . $repoRelativePath;
			$infos['link'] = $link;
			$infos['localy'] = is_dir($infos['path']);
			$infos['linked'] = $infos['localy'] && file_exists($infos['link']) && realpath($infos['path']) == realpath($infos['link']);
			$declaredDeps[$depTypeKey][$depName] = $infos;
		}
		return $declaredDeps;
	}
	
	/**
	 *
	 * @return String
	 */
	private function getProxy()
	{
		return $this->getProperties()->getProperty("PROXY");
	}
	
	/**
	 * @param integer $depType
	 * @param string $name
	 * @return string
	 */
	private function buildProjectPath($depType, $name)
	{
	
		$path = $this->wd . DIRECTORY_SEPARATOR;
		switch ($depType)
		{
			case self::$DEP_FRAMEWORK :
				$path .= 'framework';
				break;
			case self::$DEP_MODULE :
				$path .= 'modules' . DIRECTORY_SEPARATOR . $name;
				break;
			case self::$DEP_LIB :
				$path .= 'libs' . DIRECTORY_SEPARATOR . $name;
				break;
			case self::$DEP_PEAR :
				$path .= 'libs' . DIRECTORY_SEPARATOR . 'pearlibs' . DIRECTORY_SEPARATOR . $name;
				break;
			case self::$DEP_THEME :
				$path .= 'themes' . DIRECTORY_SEPARATOR . $name;
				break;
		}
		return $path;
	}
	
	/**
	 *
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
	
	/**
	 * @return boolean
	 */
	private function useChangePearLib()
	{
		$this->loadPearInfo();
		return true;
	}
	
	/**
	 * @return array
	 */
	private function generateComputedChangeComponents()
	{
		$this->loadPearInfo();
		$components = $this->loadDependencies();
		$computedComponents = array();
		$localRepo = $this->getWriteRepository();
		$computedComponents["PEAR_DIR"] = $this->pearInfos['include_path'];
		$computedComponents["USE_CHANGE_PEAR_LIB"] = $this->useChangePearLib();
		$computedComponents["PEAR_WRITEABLE"] = $this->pearInfos['writeable'];
		$computedComponents["LOCAL_REPOSITORY"] = $localRepo;
		$computedComponents["WWW_GROUP"] = $this->getProperties()->getProperty("WWW_GROUP");
		$computedComponents["DEVELOPMENT_MODE"] = $this->getProperties()->getProperty("DEVELOPMENT_MODE") == true;
		$computedComponents["PHP_CLI_PATH"] = $this->getProperties()->getProperty("PHP_CLI_PATH") . "";
	
		$proxy = $this->getProxy();
		if ($proxy !== null)
		{
			$proxyInfo = explode(":", $proxy);
			if (!isset($proxyInfo[1]))
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
	
	
	/**
	 * @return string
	 */
	private function getAutoloadPath()
	{
		if ($this->autoloadPath === null)
		{
			$this->setAutoloadPath();
		}
		return $this->autoloadPath;
	}
	
	/**
	 * @param String $path
	 * @return String
	 */
	private function expandLocalPath($path)
	{
		if ($path !== null)
		{
			if (!strncmp($path, "~/", 2))
			{
				die("Invalid relative local path ($path). Please ubpdate configuration file");
			}
		}
		return $path;
	}
	
	/**
	 * @param mixed $typeStr
	 * @return integer
	 */
	private function convertToValidType($typeStr)
	{
		switch ($typeStr)
		{
			case "modules" :
			case "module" :
				return self::$DEP_MODULE;
					
			case "change-lib" :
			case "framework" :
				return self::$DEP_FRAMEWORK;
					
			case "libs" :
			case "lib" :
				return self::$DEP_LIB;
					
			case "lib-pear" :
			case "pearlibs" :
			case "pear" :
				return self::$DEP_PEAR;
					
			case "themes" :
			case "theme" :
				return self::$DEP_THEME;
					
			case self::$DEP_MODULE :
			case self::$DEP_FRAMEWORK :
			case self::$DEP_LIB :
			case self::$DEP_PEAR :
			case self::$DEP_THEME :
				return intval($typeStr);
		}
		return self::$DEP_UNKNOWN;
	}
	
	/**
	 * @param integer $depType
	 * @param string $name
	 * @param string $version
	 * @return string
	 */
	private function buildRepositoryPath($depType, $name, $version)
	{
		$path = '/';
		switch ($depType)
		{
			case self::$DEP_FRAMEWORK :
				$path .= 'framework/';
				break;
			case self::$DEP_MODULE :
				$path .= 'modules/' . $name . '/';
				break;
			case self::$DEP_LIB :
				$path .= 'libs/' . $name . '/';
				break;
			case self::$DEP_PEAR :
				$path .= 'pearlibs/' . $name . '/';
				break;
			case self::$DEP_THEME :
				$path .= 'themes/' . $name . '/';
				break;
		}
		return $path . $name . '-' . $version;
	}
	
	/**
	 * @param integer $depType
	 * @param string $name
	 * @param string $version
	 * @return string
	 */
	private function buildLocalRepositoryPath($depType, $name, $version)
	{
		return $this->getWriteRepository() . $this->buildRepositoryPath($depType, $name, $version);
	}
	
	// DEPRECATED

	/**
	 * @deprecated
	 */
	public function getRemoteModules($releaseName)
	{
		$modules = array();
		if ($releaseName === null)
		{
			return $modules;
		}
	
		foreach ($this->getRemoteRepositories() as $repository)
		{
			$url = $repository . "/release-" . $releaseName . ".xml";
			$releaseContentPath = null;
			if ($this->getRemoteFile($url, $releaseContentPath) === true)
			{
				$releaseDom = f_util_DOMUtils::fromPath($releaseContentPath);
				unlink($releaseContentPath);
				foreach ($releaseDom->find("module") as $moduleElem)
				{
					$moduleName = $moduleElem->getAttribute("name") . '-' . $moduleElem->getAttribute("version");
					$modules[] = $moduleName;
				}
			}
		}
	
		$modules = array_unique($modules);
		sort($modules);
		return $modules;
	}
	
	/**
	 * @deprecated
	 */
	public function installComponent($componentType, $componentName, $version)
	{
		$depType = $this->convertToValidType($componentType);
		$localPath = $this->buildLocalRepositoryPath($depType, $componentName, $version);
		if (is_dir($localPath))
		{
			return $localPath;
		}
		$path = null;
		$repositoryPath = $this->buildRepositoryPath($depType, $componentName, $version);
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
					if (md5_file($destFile) != $contents[$repositoryPath]["md5"] || sha1_file($destFile) != $contents[$repositoryPath]["sha1"])
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
	 * @deprecated
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
	
	/**
	 * @deprecated
	 */
	public function getHotfixes($releaseName)
	{
		return array();
	}
	
	/**
	 * @deprecated
	 */
	public function explodeRepositoryPath($repositoryPath)
	{
		$result = array(self::$DEP_UNKNOWN, null, null, null);
		$parts = explode('/', $repositoryPath);
		$result[0] = $this->convertToValidType($parts[1]);
		if ($result[0] === self::$DEP_UNKNOWN)
		{
			return $result;
		}
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
}
