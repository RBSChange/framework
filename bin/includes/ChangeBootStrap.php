<?php
class c_ChangeBootStrap
{
	static $DEP_UNKNOWN = 0;
	static $DEP_FRAMEWORK = 7;
	static $DEP_LIB = 2;
	static $DEP_MODULE = 3;
	static $DEP_THEME = 20;
	
	/**
	 * PROJECT_HOME
	 * @var String
	 */
	private $projectHomePath;
	
	
	private $tmpPath;
	
	/**
	 * @var String
	 */
	private $name = "change";
		
	/**
	 * @var cboot_Properties
	 */
	private $properties;
		
	/**
	 * @var cboot_Configuration
	 */
	private $configuration;
	
	/**
	 * @var string
	 */
	private $instanceProjectKey = null;
	
	/**
	 * @var c_Package
	 */	
	private $frameworkPackage;
	
	/**
	 * @var c_Package[]
	 */
	private $projectDependencies;
		
	/**
	 * @var string
	 */
	private $autoloadPath;

	/**
	 * @var boolean
	 */
	private $autoloadRegistered = false;
		
	/**
	 * @var XMLDocument[]	
	 */
	private $releaseDocuments = array();
	
	/**
	 * @param String $path
	 */
	function __construct($path)
	{
		$this->projectHomePath = $path;
	}
	
	/**
	 * Return the path of project install.xml
	 * @return String
	 */
	public function getDescriptorPath()
	{
		return $this->projectHomePath . '/install.xml';
	}
	
	/**
	 * @return string
	 */
	public function getReleaseRepository()
	{
		$releaseRepository = $this->getProperties()->getProperty('REMOTE_REPOSITORY');
		if (empty($releaseRepository))
		{
			return 'http://update.rbschange.fr';
		}
		return $releaseRepository;
	}
	
	/**
	 * @return boolean
	 */
	public function	inReleaseDevelopement()
	{
	 	return ($this->getProperties()->getProperty('RELEASE_DEVELOPEMENT', false) == true);
	}
	
	/**
	 * @return boolean
	 */
	public function	inDevelopement()
	{
		return ($this->getProperties()->getProperty('DEVELOPMENT_MODE', false) == true);
	}
	
	/**
	 * @return string|null
	 */
	public function	getArchivePath()
	{
		$ap = $this->getProperties()->getProperty('REPOSITORY_ARCHIVE');
		if (is_dir($ap) && is_writable($ap))
		{
			return $ap;
		}
		return null;
	}
	
	
	// CONFIGURATION
	
	/**
	 * @return cboot_Configuration
	 */
	function getConfiguration()
	{
		if ($this->configuration === null)
		{
			$this->configuration = cboot_Configuration::getInstance($this->getName());
			$this->configuration->addLocation($this->projectHomePath);
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
	
	// AUTOLOAD	
	private function getAutoloadPath()
	{
		if ($this->autoloadPath === null)
		{
			$this->autoloadPath = $this->projectHomePath . '/cache/autoload';
			if (!is_dir($this->autoloadPath))
			{
				mkdir($this->autoloadPath, 0777, true);
			}
		}
		return $this->autoloadPath;
	}
	
	/**
	 * @param string $className
	 * @return boolean
	 */
	function autoload($className)
	{
		$path = change_AutoloadBuilder::getInstance()->buildLinkPathByClass($className);
		if ($path !== false && is_readable($path))
		{
			require_once $path;
		}
	}
	
	/**
	 * @param string $componentPath
	 */
	function appendToAutoload($componentPath)
	{	
		if (!$this->autoloadRegistered)
		{
			spl_autoload_register(array($this, "autoload"));
			$this->autoloadRegistered = true;
		}
		$autoloadPath = $this->getAutoloadPath();
		$autoloadedFlag = $autoloadPath . "/" . md5($componentPath) . ".autoloaded";
		if (file_exists($autoloadedFlag)) {return;}
		change_AutoloadBuilder::getInstance()->appendDir($componentPath);
		touch($autoloadedFlag);
	}
	
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
			clearstatcache();
		}
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
			if (empty($license)) {$license = "OS";}			
			$pId = '-';  
			$fqdn='-';
			$release = $this->getRelease();				
			$profilePath = $this->projectHomePath . '/profile';
			$profile = (is_readable($profilePath)) ? trim(file_get_contents($profilePath)) : 'default';
		
			$configPath = $this->projectHomePath . '/config/project.'. $profile .'.xml';
			if (is_readable($configPath))
			{
				$projectXMLDoc = f_util_DOMUtils::fromPath($configPath);
				$pIdNode = $projectXMLDoc->findUnique('defines/define[@name="PROJECT_ID"]');
				$pId = $pIdNode ? $pIdNode->textContent : '-';
				$fqdnNode = $projectXMLDoc->findUnique('config/general/entry[@name="server-fqdn"]');
				$fqdn = $fqdnNode ? $fqdnNode->textContent : '-';
			}
			$this->instanceProjectKey = 'Change/' . $release . ';License/' . $license. ';Profile/' . $profile . ';PId/' . $pId. ';DevMode/' . $mode . ';FQDN/' . $fqdn;
		}
		return $this->instanceProjectKey;
	}
	
	/**
	 * @return c_Package
	 */
	public function getFrameworkPackage()
	{
		if ($this->frameworkPackage === null)
		{
			$frameworkPackagePath = $this->projectHomePath . '/framework/install.xml';
			$installXMLDoc = f_util_DOMUtils::fromPath($frameworkPackagePath);		
			$this->frameworkPackage = c_Package::getInstanceFromPackageElement($installXMLDoc->documentElement, 
				$this->projectHomePath);
		}
		return $this->frameworkPackage;
	}
	
	/**
	 * @return string
	 */
	public function getRelease()
	{
		return $this->getFrameworkPackage()->getVersion();
	}
	
	/**
	 * @return array
	 */
	public function getComputedDependencies()
	{
		$computedDeps = $this->generateComputedChangeComponents();
		return $computedDeps;
	}
		
	private function generateComputedChangeComponents()
	{
		$computedComponents = array('dependencies' => $this->getProjectDependencies());
		$computedComponents["ZEND_FRAMEWORK_PATH"] = $this->getProperties()->getProperty('ZEND_FRAMEWORK_PATH');
		$computedComponents["INCLUDE_PATH"] = $this->getProperties()->getProperty('INCLUDE_PATH');

		$computedComponents["WWW_GROUP"] = $this->getProperties()->getProperty('WWW_GROUP');
		$computedComponents["DEVELOPMENT_MODE"] = $this->getProperties()->getProperty('DEVELOPMENT_MODE', false) == true;
		$computedComponents["PHP_CLI_PATH"] = $this->getProperties()->getProperty('PHP_CLI_PATH', '');
		
		$computedComponents["TMP_PATH"] = $this->getProperties()->getProperty('TMP_PATH');
		
		$computedComponents["CHANGE_COMMAND"] = $this->getProperties()->getProperty('CHANGE_COMMAND', 'framework/bin/change.php');
		$computedComponents["PROJECT_HOME"] = $this->getProperties()->getProperty('PROJECT_HOME', $this->projectHomePath);
		$computedComponents["DOCUMENT_ROOT"] = $this->getProperties()->getProperty('DOCUMENT_ROOT', $this->projectHomePath);
		$computedComponents["PROJECT_LICENSE"] = $this->getProperties()->getProperty('PROJECT_LICENSE', 'OS');
		$computedComponents["FAKE_EMAIL"] = $this->getProperties()->getProperty('FAKE_EMAIL');
		
		$proxy = $this->getProxy();
		if ($proxy)
		{
			$proxyInfo = explode(":", $proxy);
			if (!isset($proxyInfo[1]))
			{
				$proxyInfo[1] = "8080";
			}
			$computedComponents["OUTGOING_HTTP_PROXY_HOST"] = $proxyInfo[0];
			$computedComponents["OUTGOING_HTTP_PROXY_PORT"] = $proxyInfo[1];
		}
		return $computedComponents;
	}
		
	/**
	 * @return cboot_Properties
	 */
	function getProperties($fileName = null)
	{
		return $this->getConfiguration()->getProperties($fileName);
	}
	

	
	/**
	 * @return c_Package[]
	 */
	public function getProjectDependencies()
	{ 
		if ($this->projectDependencies === null)
		{
			$this->projectDependencies = array();	
			if (is_readable($this->getDescriptorPath()))
			{	
				$installXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
				foreach ($installXMLDoc->getElementsByTagName('package') as $package) 
				{
					/* @var $package DOMElement */
					$infos = c_Package::getInstanceFromPackageElement($package, $this->projectHomePath);
					$this->projectDependencies[$infos->getKey()] = $infos;
				}
			}
			else
			{
				
				//Build Temporary dependencies List for framework and modules
				$p = $this->getFrameworkPackage();
				$this->projectDependencies[$p->getKey()] = $p;
				$paths = glob($this->projectHomePath . '/modules/*/install.xml');
				if (is_array($paths));
				{
					foreach ($paths as $path) 
					{
						$installXMLDoc = f_util_DOMUtils::fromPath($path);
						$p = c_Package::getInstanceFromPackageElement($installXMLDoc->documentElement, $this->projectHomePath);
						$this->projectDependencies[$p->getKey()] = $p;
					}
				}
			}
		}
		return $this->projectDependencies;
	}
	
	/**
	 * @param c_Package $updatedPackage
	 */
	public function updateProjectPackage($updatedPackage)
	{
		$isModified = false;
		$toAdd = true;
		$installXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());

		foreach ($installXMLDoc->getElementsByTagName('package') as $node) 
		{
			/* @var $node DOMElement */
			$infos = c_Package::getInstanceFromPackageElement($node, $this->projectHomePath);
			if ($infos->getKey() == $updatedPackage->getKey())
			{
				$toAdd = false;
				if ($infos->getVersion() != $updatedPackage->getVersion())
				{
					$isModified = true;
					$updatedPackage->populateNode($node);
				}
				break;
			}
		}
		
		if ($toAdd)
		{
			$isModified = true;
			$node = $installXMLDoc->documentElement->appendChild($installXMLDoc->createElement('package'));
			$updatedPackage->populateNode($node);
		}
		
		if ($isModified)
		{
			$installXMLDoc->save($this->getDescriptorPath());
			$this->projectDependencies = null;
		}
	}
		
	/**
	 * @param c_Package $removedPackage
	 */
	public function removeProjectDependency($removedPackage)
	{
		$isModified = false;
		$installXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());

		foreach ($installXMLDoc->getElementsByTagName('package') as $node) 
		{
			/* @var $node DOMElement */
			$infos = c_Package::getInstanceFromPackageElement($node, $this->projectHomePath);
			if ($infos->getKey() == $removedPackage->getKey())
			{
				$isModified = true;
				$installXMLDoc->documentElement->removeChild($node);
				break;
			}
		}
		if ($isModified)
		{
			$installXMLDoc->save($this->getDescriptorPath());
			$this->projectDependencies = null;
		}
	}
			
	/**
	 * @param f_util_DOMDocument $installXMLDoc
	 * @return c_Package[]
	 */
	public function getDependenciesFromXML($installXMLDoc)
	{
		$declaredDeps = array();
		if ($installXMLDoc && $installXMLDoc->documentElement)
		{
			foreach ($installXMLDoc->getElementsByTagName('package') as $package) 
			{
				/* @var $package DOMElement */
				$infos = c_Package::getInstanceFromPackageElement($package, $this->projectHomePath);
				$declaredDeps[$infos->getKey()] = $infos;
			}
		}
		return $declaredDeps;
	}
	
	/**
	 * @param f_util_DOMDocument $installXMLDoc
	 * @return c_Package
	 */
	public function getPackageFromXML($installXMLDoc)
	{
		if ($installXMLDoc && $installXMLDoc->documentElement)
		{
			return c_Package::getInstanceFromPackageElement($installXMLDoc->documentElement, $this->projectHomePath);
		}
		return null;
	}
	
	/**
	 * @param string $releaseURL
	 */
	public function getReleasePackages($releaseURL)
	{
		if (!array_key_exists($releaseURL, $this->releaseDocuments))
		{
			$this->releaseDocuments[$releaseURL] = null;
			$url = $releaseURL . '/release-'.$this->getRelease().'.xml';
			$archivePath = $this->getArchivePath();
			$destFile = $archivePath ? $archivePath. '/remoteRelease-'.$this->getRelease().'.xml' : null;
			$result = $this->getRemoteFile($url, $destFile);
			if ($result === true)
			{
				$doc = f_util_DOMUtils::fromPath($destFile);
				if ($doc && $doc->documentElement)
				{
					$this->releaseDocuments[$releaseURL] = array();
					foreach ($doc->documentElement->childNodes as $node) 
					{
						if ($node->nodeType == XML_ELEMENT_NODE && in_array($node->nodeName, array('change-lib', 'lib', 'theme', 'module')))
						{
							$package = c_Package::getInstanceFromRepositoryElement($node, $this->projectHomePath);
							if ($package->getTypeAsInt() != self::$DEP_UNKNOWN)
							{
								if ($package->getDownloadURL() == null)
								{
									$package->setReleaseURL($releaseURL);
									$package->populateDefaultDownloadUrl();
								}
								$this->releaseDocuments[$releaseURL][$package->getKey()] = $package;
							}
						}
					}
				}
				else
				{
					echo 'Invlid XML document : ', $destFile, ' ', $url, PHP_EOL;
				}
				if (!$archivePath) {unlink($destFile);}
			}
			else
			{
				echo $result, PHP_EOL;
			}
		}
		return $this->releaseDocuments[$releaseURL];
	}
		
	/**
	 * @return String
	 */
	private function getProxy()
	{
		return $this->getProperties()->getProperty("OUTGOING_HTTP_PROXY");
	}
	
	public function getTmpPath()
	{
		if ($this->tmpPath === null)
		{
			$tmpPath = $this->getProperties()->getProperty('TMP_PATH');
			if (empty($tmpPath))
			{
				throw new Exception('Invalid TMP_PATH configuration value');
			}
			$testTempFile = @tempnam($tmpPath, 'tmp');
			if ($testTempFile === false)
			{
				throw new Exception('Invalid TMP_PATH configuration value not writable');
			}
			unlink($testTempFile);
			$this->tmpPath = $tmpPath;
		}
		return $this->tmpPath;
	}
		
	/**
	 * @var boolean|array
	 */
	private $remoteError = false;
	
	/**
	 * @param string $url
	 * @param string $destFile
	 * @param array $postDataArray
	 * @return true array
	 */
	private function getRemoteFile($url, &$destFile, $postDataArray = null)
	{
		$this->remoteError = false;
		if (!$destFile)
		{
			$destFile = tempnam($this->getTmpPath(), 'tmp');
		}
		
		$fp = fopen($destFile, "wb");
		if ($fp === false)
		{
			$this->remoteError = array(-1, 'Fopen error for filename ', $destFile);
			return $this->remoteError;
		}
	
		$ch = curl_init($url);
		if ($ch == false)
		{
			$this->remoteError = array(-2, 'Curl_init error for url ' . $url);
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
			return $this->remoteError;
		}
	
		fclose($fp);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info["http_code"] != "200")
		{
			unlink($destFile);
			$this->remoteError = array($info["http_code"], "Could not download " . $url . ": bad http status (" . $info["http_code"] . ")");
			return $this->remoteError;
		}
	
		return $this->remoteError === false;
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
	 * @param string $zipPath
	 * @param string $tmpPath
	 * @return c_Package or null
	 */
	public function unzipPackage($zipPath, $tmpPath)
	{
		$package = null;
		$zip = new ZipArchive();		
		if ($zip->open($zipPath) === true) 
		{		
			if (is_dir($tmpPath)) {f_util_FileUtils::rmdir($tmpPath);}		
		    for($i = 0; $i < $zip->numFiles; $i++) 
		    {
				$name = $zip->getNameIndex($i);
				$zip->extractTo($tmpPath, array($name));
				if ($name === 'install.xml')
				{
					$xmlDoc = f_util_DOMUtils::fromPath($tmpPath . '/' . $name);
					$package = $this->getPackageFromXML($xmlDoc);
					if ($package) {$package->setTemporaryPath($tmpPath);}
				}          
		    }             
		    $zip->close();
		}
		return $package;
	}
		
	//COMMAND Manager
	/**
	 * @var String[]
	 */
	private $commands;

	/**
	 * @var array<String, String[]>
	 */
	private $commandSections = array('PROD' => array(), 'DEV' => array());
	
	
	public function initCommands()
	{
		$addDevCmds = $this->getProperties()->getProperty('DEVELOPMENT_MODE') == true;
		$dependencies = $this->getProjectDependencies();
		foreach ($dependencies as $dependency) 
		{
			/* @var $dependency c_Package */
			if ($dependency->isFramework() || $dependency->isModule())
			{
				$section = $dependency->getName();
				$cmdPath  = $dependency->getPath() . '/commands';
				if (is_dir($cmdPath))
				{
					$this->appendToAutoload($cmdPath);
					$this->addCommandDir($cmdPath, $section, false);
				}
				
				$devPath = $dependency->getPath() .'/commands/dev';
				if ($addDevCmds && is_dir($devPath))
				{
					$this->appendToAutoload($devPath);
					$this->addCommandDir($devPath, $section, true);
				}
			}
		}	
	}
	
	/**
	 * @param String $path
	 * @param String $sectionName
	 * @param Boolean $devCommand
	 */
	private function addCommandDir($path, $sectionName, $devCommand)
	{
		$part = $devCommand ? 'DEV' : 'PROD';
		if (!isset($this->commandSections[$part][$sectionName]))
		{
			$this->commandSections[$part][$sectionName] = array();
		}
		$this->commandSections[$part][$sectionName][] = $path;
	}
	
	/**
	 * @param string $className
	 * @param string $callName
	 * @param string $sectionName
	 * @param boolean $devMode
	 * @throws Exception
	 * @return c_ChangescriptCommand
	 */
	private function getCommandByClassName($className, $sectionName, $devMode)
	{
		$commandClassName = "commands_".$className;
		if (!class_exists($commandClassName))
		{
			throw new Exception("Command class not found $commandClassName");
		}
		$command = new $commandClassName($this, $sectionName, $devMode);
		if (!($command instanceof c_ChangescriptCommand))
		{
			throw new Exception("$commandClassName is not a c_ChangescriptCommand class");
		}
		return $command;
	}
	
	/**
	 * 
	 * @param string[] $commandDirs
	 * @param string $sectionName
	 * @param boolean $devMode
	 */
	private function getCommandByDirs($commandDirs, $sectionName, $devMode = false)
	{
		$cmdNamePrefix = ($sectionName == "framework") ? '' : $sectionName . '.'; 
		$commands = array();
		foreach ($commandDirs as $cmdDir)
		{
			foreach (scandir($cmdDir) as $file)
			{
				$matches = array();
				if (!preg_match('/^([a-zA-Z0-9_]+)\.php$/', $file, $matches))
				{
					continue;
				}

				$commandName = $matches[1];	
				$command = $this->getCommandByClassName($commandName, $sectionName, $devMode);
				$commands[] = $command;
			}
		}
		return $commands;
	}
	
	/**
	 * @return c_ChangescriptCommand[]
	 */
	public function getCommands()
	{
		if ($this->commands === null)
		{
			$this->commands = array();			
			foreach ($this->commandSections as $devStr => $data)
			{
				$devMode = ($devStr === 'DEV');
				foreach ($data as $sectionName => $commandDirs)
				{
					$this->commands  = array_merge($this->commands,  $this->getCommandByDirs($commandDirs, $sectionName, $devMode));
				}
			}
		}
		return $this->commands;
	}
	
	/**
	 * @param string $commandName
	 * @param boolean $throwIfNotFound
	 * @return c_ChangescriptCommand
	 */
	public function getCommand($commandName, $throwIfNotFound = true)
	{
		foreach ($this->getCommands() as $cmd)
		{
			/* @var $cmd c_ChangescriptCommand */
			if ($commandName === $cmd->getCallName() || $commandName === $cmd->getAlias())
			{	
				return $cmd;
			}
		}
		if ($throwIfNotFound)
		{
			throw new Exception("Unable to find command $commandName");
		}
		return null;
	}
		
	/**
	 * @param String[] $args
	 */
	function execute($args)
	{
		try
		{
			if (count($args) == 0)
			{
				$args = array('usage');
			}
			elseif (in_array("-h", $args) || in_array("--help", $args))
			{
				$this->_executeCommand('usage', array_merge(array('getUsage'), $args));
				return;
			}
			elseif ($args[0][0] == "-")
			{
				$args = array('usage', $args[0]);
			}
			
			$cmdName = $args[0];
			switch ($cmdName)
			{
				case "getCommands":
					$this->_executeCommand('usage', $args);
					break;
				case "getOptions":
					$this->_executeCommand('usage', $args);
					break;
				case "getParameters":
					$this->_executeCommand('usage', $args);
					break;
				default:	
					$this->_executeCommand($cmdName, array_slice($args, 1));
					break;
			}
		}
		catch (Exception $e)
		{
			if ($e->getFile() != __FILE__)
			{
				$message =  "Error line ". $e->getLine()." (".$e->getFile()."): ".$e->getMessage() . PHP_EOL;
			}
			else
			{
				$message =  $e->getMessage() . PHP_EOL;
			}
			if (defined('HTTP_MODE'))
			{
				echo "<span class=\"row_31\">", nl2br(htmlspecialchars($message)), "</span>";
			}
			else
			{
				echo $message;
			}
		}
	}
		
	/**
	 * @param String $cmdName
	 * @param String[] $args
	 * @return boolean
	 */
	protected function _executeCommand($cmdName, $args = array())
	{
		$parsedArgs = $this->parseArgs($args);
		$params = $parsedArgs['params'];
		$options = $parsedArgs['options'];
		$command = $this->getCommand($cmdName);
		
		if (!isset($options['ignoreListener']))
		{
			foreach ($this->getListeners($command->getCallName()) as $listener) 
			{
				list($name, $commandName, $args) = $listener;
				$command->addListeners($name, $commandName, $args);
			}
		}
		return $command->execute($params, $options);
	}
	
	/**
	 * Get the value of options (--<optionName>[=value])
	 * @param String[] $args
	 * @return array("options" => array<String, String>, "params" => String[]) where the option array key is the option name, the potential option value or true
	 */
	public function parseArgs($args)
	{
		$options = array();
		$params = array();
		foreach ($args as $key => $arg)
		{
			if (preg_match("/^--([^=]*)(=(.*)){0,1}$/", $arg, $matches) > 0)
			{
				if (isset($matches[3]))
				{
					$optValue = $matches[3];
				}
				else
				{
					$optValue = true;
				}
				$options[$matches[1]] = $optValue;
			}
			else
			{
				$params[] = $arg;
			}
		}
		return array("options" => $options, "params" => $params);
	}
	

	/**
	 * @param string $commandName
	 * @return array<string, string, string[]>[]
	 */
	private function getListeners($commandName)
	{
		$listeners = array();
		foreach ($this->getCommands() as $command)
		{
			/* @var $command c_ChangescriptCommand */
			$events = $command->getEvents();
			if (is_array($events))
			{
				foreach ($events as $eventData) 
				{
					if (!isset($eventData['target']) && !isset($eventData['command'])) {continue;}
					$target = isset($eventData['target']) ? $eventData['target'] : $command->getCallName();
					if ($target !== $commandName) {continue;}

					$name = isset($eventData['name']) ? $eventData['name'] : 'after';
					$executeCmd = isset($eventData['command']) ? $this->getCommand($eventData['command'], false) : $command; 
					if ($executeCmd && in_array($name, array('before', 'after')) && $commandName !== $executeCmd->getCallName())
					{
						$args = isset($eventData['args']) ? $eventData['args'] : array();
						$listeners[] = array($name, $executeCmd->getCallName(), $args);
					}
				}
			}
		}
		return $listeners;
	}
}