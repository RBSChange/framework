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
	 * @var array
	 */
	private $pearInfos;
	
	/**
	 * @var string
	 */
	private $autoloadPath;
	/**
	 * @var string[]
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
		
	/**
	 * @param String $path
	 */
	function addPropertiesLocation($path)
	{
		$this->getConfiguration()->addLocation($path);
	}
	
	function setAutoloadPath($autoloadPath)
	{
		$this->autoloadPath = $autoloadPath;
	}
	
	private function getAutoloadPath()
	{
		if ($this->autoloadPath === null)
		{
			$this->autoloadPath = $this->projectHomePath . "/cache/autoload";
		}
		return $this->autoloadPath;
	}
	
	function setRefreshAutoload($refresh)
	{
		$this->refreshAutoload = $refresh;
	}
	
	/**
	 * @param string $className
	 * @return boolean
	 */
	function autoload($className)
	{
		try
		{
			require_once(ClassResolver::getInstance()->getPath($className));
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	/**
	 * @param string $componentPath
	 */
	function appendToAutoload($componentPath)
	{	
		$autoloadPath = $this->getAutoloadPath();
		$autoloadedFlag = $autoloadPath . "/" . md5($componentPath) . ".autoloaded";
		
		if (!$this->autoloadRegistered)
		{
			spl_autoload_register(array($this, "autoload"));
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
		ClassResolver::getInstance()->appendRealDir($componentPath);
		$this->autoloaded[$componentPath] = true;
		touch($autoloadedFlag);
	}
	
	
	/**
	 * @return string
	 */
	public function getInstanceProjectKey()
	{
		if ($this->instanceProjectKey === null)
		{
			$license = $this->getProperties()->getProperty("PROJECT_LICENSE");
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
			$this->instanceProjectKey = 'Change/' . $release . ';License/' . $license. ';Profile/' . $profile . ';PId/' . $pId. ';FQDN/'. $fqdn;
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
	
	private function generateComputedChangeComponents()
	{
		$pearInfos = $this->loadPearInfo();
		$computedComponents = array('dependencies' => $this->getProjectDependencies());
		$computedComponents["PEAR_DIR"] = $pearInfos['include_path'];
		$computedComponents["ZEND_FRAMEWORK_PATH"] = $this->getProperties()->getProperty('ZEND_FRAMEWORK_PATH');
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
		return $computedComponents;
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
		return array("http://osrepo.rbschange.fr");
	}
	
	/**
	 * @return string
	 */
	public function getReleaseRepository()
	{
		return 'http://repo.ssxb-wf-inthause.fr';
	}
	
	/**
	 * @return c_Package[]
	 */
	public function getProjectDependencies()
	{ 
		if ($this->projectDependencies === null)
		{
			$this->projectDependencies = array();		
			$installXMLDoc = f_util_DOMUtils::fromPath($this->getDescriptorPath());
			foreach ($installXMLDoc->getElementsByTagName('package') as $package) 
			{
				/* @var $package DOMElement */
				$infos = c_Package::getInstanceFromPackageElement($package, $this->projectHomePath);
				$this->projectDependencies[$infos->getKey()] = $infos;
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
				if ($infos->getHotfixedVersion() != $updatedPackage->getHotfixedVersion())
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
		foreach ($installXMLDoc->getElementsByTagName('package') as $package) 
		{
			/* @var $package DOMElement */
			$infos = c_Package::getInstanceFromPackageElement($package, $this->projectHomePath);
			$declaredDeps[$infos->getKey()] = $infos;
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
			$destFile = null;
			$result = $this->downloadFile($url, $destFile);
			if ($result === true)
			{
				$doc = f_util_DOMUtils::fromPath($destFile);
				if ($doc && $doc->documentElement)
				{
					$this->releaseDocuments[$releaseURL] = array();
					foreach ($doc->getElementsByTagName('package') as $node) 
					{
						$package = c_Package::getInstanceFromPackageElement($node, $this->projectHomePath);
						if ($package->getTypeAsInt() != self::$DEP_UNKNOWN)
						{
							if ($package->getDownloadURL() == null)
							{
								$downloadURL = $releaseURL . $package->getRelativeReleasePath() . '.zip';
								$package->setDownloadURL($downloadURL);
							}
							$this->releaseDocuments[$releaseURL][$package->getKey()] = $package;
						}
					}
				}
				else
				{
					echo 'Invlid XML document : ', $destFile, ' ', $url, PHP_EOL;
				}
				unlink($destFile);
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
		return $this->getProperties()->getProperty("PROXY");
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
	 * @param string $url
	 * @param string $destFile
	 * @return true or error string
	 */
	public function downloadFile($url, &$destFile)
	{
		if (!$destFile) {$destFile = tempnam($this->getTmpPath(), 'tmp');}
		
		if (($ch = curl_init($url)) == false)
		{
			return "Error on curl initialize for url $url.";
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
			return "Could not open file $destFile in write mode";
		}
		
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		if (curl_exec($ch) === false)
		{
			$curlErr = curl_errno($ch) . ':' . curl_error($ch);
			fclose($fp);
			unlink($destFile);
			curl_close($ch);
			return "Error ($curlErr) for url $url in $destFile";
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
	 * @param string $zipPath
	 * @param string $tmpPath
	 * @return c_Package or null
	 */
	public function unzipPackage($zipPath, $tmpPath)
	{
		$package = null;
		if (class_exists('ZipArchive'))
		{
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
		}
		return $package;
	}
	
	
	
	/**
	 * @return array
	 */
	public function loadPearInfo()
	{
		if ($this->pearInfos === null)
		{
			$include_path = $this->getProperties()->getProperty("PEAR_INCLUDE_PATH");
			if ($include_path === null)
			{
				$include_path = $this->projectHomePath . "/pear";
			}
			$this->pearInfos = array("include_path" => $include_path);
		}
		return $this->pearInfos;
	}
}