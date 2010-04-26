<?php /** Begin lib/ChangeBootStrap.php **/ ?>
<?php
if (!defined("C_DEBUG"))
{
	define("C_DEBUG", getenv("C_DEBUG") == "true");
}

// messages functions
if (!function_exists("c_error"))
{
	function c_error($msg, $exitCode = 1)
	{
		echo "[ERROR] $msg\n";
		if ($exitCode !== null)
		{
			exit($exitCode);
		}
	}
}

if (!function_exists("c_warning"))
{
	function c_warning($msg)
	{
		echo "[WARN] $msg\n";
	}
}

if (!function_exists("c_message"))
{
	function c_message($msg)
	{
		echo $msg."\n";
	}
}

if (!function_exists("c_debug"))
{
	function c_debug($msg)
	{
		if (C_DEBUG)
		{
			echo "[DEBUG] $msg\n";
		}
	}
}

//
function c_assert_php_version($version)
{
	if (version_compare(PHP_VERSION, $version, '>='))
	{
		c_debug("PHP Version >= $version");
	}
	else
	{
		c_error("PHP version (".PHP_VERSION.") < $version", true);
	}
}

// First thing we do is check PHP version, outside of any class (maybe running and old PHP4 version ?)
c_assert_php_version("5.1.6");

/**
 * @param Exception $exception
 */
function c_change_exception_handler($exception)
{
	$code = $exception->getCode();
	if ($code == 0)
	{
		$code = 1;
	}
	$msg = $exception->getMessage();
	if (C_DEBUG)
	{
		$msg .= "\n".$exception->getTraceAsString();
	}
	c_error($msg, $code);
}

if (!defined("C_LIB_DIR"))
{
	define("C_LIB_DIR", realpath(dirname(__FILE__)."/../lib"));
}


class c_ChangeBootStrap
{
	static $DEP_FRAMEWORK = 7;
	static $DEP_CHANGE_LIB = 1;
	static $DEP_LIB = 2;
	static $DEP_MODULE = 3;
	static $DEP_EXTENSION = 4;
	static $DEP_PEAR = 5;
	static $DEP_BIN = 6;
	static $DEP_CHANGE_TOOL = 8;
	static $DEP_CHANGE_PROJECT = 9;
	static $DEP_PEAR_LIB = 10;

	/**
	 * WEBEDIT_HOME
	 * @var String
	 */
	private $wd;

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
	private $repositories;

	/**
	 * @var cboot_Properties
	 */
	private $properties;

	/**
	 * @var Boolean
	 */
	private $deepCheck;

	/**
	 * @var Boolean
	 */
	private $looseVersions = false;

	private static $currentDownloadInfo;
	
	/**
	 * @var Boolean
	 */
	private $onlyCheck = false;

	/**
	 * @var c_ChangeBootStrap
	 */
	private static $instance;
	
	/**
	 * @param String $path
	 */
	function __construct($path)
	{
		$this->assert_ext("dom", "Dom extension is required to read XML documents");
		$this->wd = $path;
		self::$instance = $this;
	}
	
	/**
	 * @return c_ChangeBootStrap
	 */
	static function getLastInstance()
	{
		return self::$instance;
	}

	/**
	 * @var cboot_Configuration
	 */
	private $configuration;

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

	function setCheckOnly()
	{
		$this->onlyCheck = true;
		$repo = tempnam(null, "ChangeBootStrapCheckRepo");
		unlink($repo);
		mkdir($repo);
		$this->setAutoloadPath($repo."/autoload");
		$this->localRepositories = array($repo => true);
		$this->pearInfos = array("include_path" => null, "path" => null, "writeable" => true, "installed" => true);
	}
	
	/**
	 * @var String
	 */
	private $name;

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
		c_debug(__METHOD__.": $path");
		$this->getConfiguration()->addLocation($path);
	}

	/**
	 * @param Boolean $deep
	 * @return c_ChangeBootStrap
	 */
	function setDeepCheck($deep)
	{
		$this->deepCheck = $deep;
		return $this;
	}

	/**
	 * @param Boolean $looseVersions
	 * @return c_ChangeBootStrap
	 */
	function setLooseVersions($looseVersions)
	{
		$this->looseVersions = $looseVersions;
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
	 */
	function dispatch($target = null)
	{
		$this->assert_ext("curl", "Curl extension is required to download packages");
		if (C_DEBUG)
		{
			$this->assert_ext("zip", "Zip extension is recommanded to unpack packages.", true);
		}

		// Check change.xml existence
		$descPath = $this->getDescriptorPath();
		if (!is_file($descPath))
		{
			throw new Exception("Could not find $descPath");
		}

		$components = null;
		if ($target != "")
		{
			$scriptPath = null;
			if (cboot_StringUtils::startsWith($target, "dep:"))
			{
				$this->checkAndLoadDependencies($components);
				list(, $component, $relativePath) = explode(":", $target);
				if (isset($components[$component]))
				{
					$scriptPath = $components[$component]["path"]."/".$relativePath;
				}
			}
			elseif (cboot_StringUtils::startsWith($target, "func:"))
			{
				list(, $scriptFunction) = explode(":", $target);
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

			$descComputedPath = $this->getAutoloadPath()."/.computedChangeComponents.ser";
			$lastUserConfigActionTime = filemtime($descPath);
			foreach ($this->getConfiguration()->getLocations() as $location)
			{
				$propFile = $location."/change.properties";
				if (!is_file($propFile))
				{
					continue;
				}
				$mtime = filemtime($propFile);
				if ($mtime > $lastUserConfigActionTime)
				{
					$lastUserConfigActionTime = $mtime;
				}
			}
			if (!is_file($descComputedPath) || filemtime($descComputedPath) < $lastUserConfigActionTime)
			{
				$computedComponents = $this->generateComputedChangeComponents($components);
				if (!file_put_contents($descComputedPath, serialize($computedComponents)))
				{
					throw new Exception("Could not write to ".$descComputedPath.". Please adjust permissions");
				}
			}

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
				passthru("php $scriptPath ".join(" ", $_SERVER["argv"]), $returnCode);
				exit($returnCode);
			}
			else
			{
				$computedDeps = unserialize(file_get_contents($descComputedPath));
				if (!is_array($computedDeps))
				{
					throw new Exception("Could not load $descComputedPath. Please delete $descComputedPath and run it again. If it persists, try to report the problem.");
				}
				$scriptFunction($_SERVER["argv"], $computedDeps);
			}
		}
	}
	
	private function generateComputedChangeComponents($components)
	{
		if ($components === null)
		{
			$this->checkAndLoadDependencies($components);
		}
		$computedComponents = array();
		$computedComponents["PEAR_DIR"] = $this->pearInfos['include_path'];
		$computedComponents["USE_CHANGE_PEAR_LIB"] = $this->useChangePearLib();
		$computedComponents["PEAR_WRITEABLE"] = $this->pearInfos['writeable'];
		
		$repo = array_keys($this->getLocalRepositories());
		$computedComponents["LOCAL_REPOSITORY"] = $repo[0];
		$computedComponents["WWW_GROUP"] = $this->getProperties()->getProperty("WWW_GROUP", "www-data");
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
		$computedComponents["c_bootstrap"] = __FILE__;
		
		foreach ($components as $componentInfo)
		{
			$type = $componentInfo[0];
			$name = $componentInfo[1];
			$version = $componentInfo[2];
			$computedDeps = null;
			if ($type === self::$DEP_PEAR && $computedComponents["USE_CHANGE_PEAR_LIB"])
			{
				$type = self::$DEP_PEAR_LIB;
			}
			$typeStr = $this->getDepTypeAsString($type);
			if (isset($computedComponents[$typeStr][$name]) && $computedComponents[$typeStr][$name]["version"] == $version)
			{
				continue;
			}
			
			if ($componentInfo["path"] !== null)
			{
				$computedDeps = $this->getComputedDepencies($componentInfo["path"]);
				if (! isset($computedComponents[$typeStr]))
				{
					$computedComponents[$typeStr] = array();
				}
			}
			
			$computedComponents[$typeStr][$name] = array("version" => $version, "path" => $componentInfo["path"]);			
			if ($computedDeps === null)
			{
				continue;
			}
			
			foreach ($computedDeps as $componentType => $deps)
			{		
				if ($componentType === self::$DEP_PEAR && $computedComponents["USE_CHANGE_PEAR_LIB"])
				{
					$componentType = self::$DEP_PEAR_LIB;
				}	
				$componentTypeStr = $this->getDepTypeAsString($componentType);			
				foreach ($deps as $componentName => $componentVersions)
				{
					if (!isset($computedComponents[$componentTypeStr]))
					{
						$computedComponents[$componentTypeStr] = array();
					}
					$componentVersion = end($componentVersions);
					if (! isset($computedComponents[$componentTypeStr][$componentName]))
					{
						$componentPath = $this->getComponentPath($componentType, $componentName, $componentVersion);
						$computedComponents[$componentTypeStr][$componentName] = array("version" => $componentVersion, "path" => $componentPath);
					}
					else
					{
						$actualVersion = $computedComponents[$componentTypeStr][$componentName]["version"];
						if ($this->compareVersion($componentVersion, $actualVersion) > 0)
						{
							$componentPath = $this->getComponentPath($componentType, $componentName, $componentVersion);
							$computedComponents[$componentTypeStr][$componentName] = array("version" => $componentVersion, "path" => $componentPath);
						}
					}
				}
			}
		}	

		return $computedComponents;
	}

	function autoload($className)
	{
		$relativeClassLocation = str_replace('_', '/', $className).'/to_include';
		$defFileName = $this->getAutoloadPath().'/'.$relativeClassLocation;
		if (is_file($defFileName) && is_readable($defFileName))
		{
			require($defFileName);
			return true;
		}
		if (php_sapi_name() == "cli")
		{
			// if framework is loaded, give a try to f_autoload()
			if (!defined("FRAMEWORK_HOME"))
			{
				echo "Unable to autoload $className.\n";
				echo "You should run 'change.php --refresh-cli-autoload'";
				echo "\n";
			}
		}
		return false;
	}

	private $autoloadPath;
	private $autoloaded = array();
	private $autoloadRegistered = false;
	private $refreshAutoload = false;

	function setAutoloadPath($autoloadPath = ".change/autoload")
	{
		if ($autoloadPath[0] != "/")
		{
			$this->autoloadPath = $this->wd."/".$autoloadPath;
		}
		else
		{
			$this->autoloadPath = $autoloadPath;
		}
		if (!is_dir($this->autoloadPath) && !mkdir($this->autoloadPath, 0777, true))
		{
			throw new Exception("Could not create autoload directory ".$this->autoloadPath);
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

	function appendToAutoload($componentPath, $followDeps = true)
	{
		$autoloadPath = $this->getAutoloadPath();
		$autoloadedFlag = $autoloadPath."/".str_replace('/', '_', $componentPath).".autoloaded";

		if (!$this->autoloadRegistered)
		{
			if (!is_dir($autoloadPath) && !@mkdir($autoloadPath, 0777, true))
			{
				throw new Exception("Could not create $autoloadPath");
			}
			spl_autoload_register(array($this, "autoload"));
			$this->autoloadRegistered = true;
		}

		if (isset($this->autoloaded[$componentPath]) || (!$this->refreshAutoload) && file_exists($autoloadedFlag))
		{
			$this->autoloaded[$componentPath] = true;
			return;
		}

		if (file_exists($autoloadedFlag))
		{
			unlink($autoloadedFlag);
		}

		c_debug(__METHOD__." $componentPath\n");

		if (file_exists($componentPath."/classes.ser"))
		{
			$classes = unserialize($componentPath."/classes.ser");
		}
		else
		{
			// Probably a dev repository
			$analyzer = new cboot_ClassDirAnalyzer($componentPath, array("only-declared" => true));
			$dirInfo = $analyzer->analyze();
			$classes = $dirInfo["declaredClasses"];
		}

		foreach ($classes as $className => $relPath)
		{
			$linkPath = $autoloadPath."/".str_replace('_', '/', $className).'/to_include';
			$linkDir = dirname($linkPath);
			if (!is_dir($linkDir) && !mkdir($linkDir, 0777, true))
			{
				throw new Exception("Could not create $linkDir");
			}
			$linkTarget = $componentPath."/".$relPath;
			if ((!is_link($linkPath) || (readlink($linkPath) != $linkTarget && unlink($linkPath)))
			&& !symlink($linkTarget, $linkPath))
			{
				throw new Exception("Could not symlink ".$componentPath."/".$relPath." to $linkPath");
			}
		}
		$this->autoloaded[$componentPath] = true;

		if ($followDeps)
		{
			$computedDeps = $this->getComputedDepencies($componentPath);
			foreach ($computedDeps as $depType => $deps)
			{
				foreach ($deps as $depName => $depVersions)
				{
					$depVersion = end($depVersions);
					$componentPath = $this->getComponentPath($depType, $depName, $depVersion);
					if ($componentPath !== null)
					{
						$this->appendToAutoload($componentPath, false);
					}
				}
			}
		}
		touch($autoloadedFlag);
	}

	// private methods
	private function assert_ext($extName, $comment = '', $optionnal = false)
	{
		if (extension_loaded($extName))
		{
			c_debug($extName." extension loaded");
			return true;
		}
		else
		{
			$msg = $extName." extension not loaded. $comment";
			if ($optionnal)
			{
				c_warning($msg);
			}
			else
			{
				c_error($msg);
			}
			return false;
		}
	}

	/**
	 * @return String
	 */
	private function getDescriptorPath()
	{
		if ($this->descriptorPath === null)
		{
			if ($this->descriptor[0] == "/")
			{
				$this->descriptorPath = $this->descriptor;
			}
			else
			{
				$this->descriptorPath = $this->wd."/".$this->descriptor;
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
	private function getRepositories()
	{
		if ($this->repositories === null)
		{
			$this->repositories = array_unique(explode(",", $this->getProperties()->getProperty("REMOTE_REPOSITORIES", "http://osrepo.rbschange.fr")));
		}
		return $this->repositories;
	}

	/**
	 * @return array<String, Boolean> path => writeable
	 */
	private function getLocalRepositories()
	{
		if ($this->localRepositories === null)
		{
			// Local repositories
			$this->localRepositories = array();
			foreach (array_unique(explode(",", $this->getProperties()->getProperty("LOCAL_REPOSITORY", $this->wd."/repository,".getenv("HOME")."/.change/repository,/home/change/repository"))) as $localRepoPath)
			{
				$localRepoPath = $this->expandLocalPath($localRepoPath);
				if (!file_exists($localRepoPath) && !is_writable(dirname($localRepoPath)))
				{
					c_debug("Ignoring non existing (and non creatable) local repository $localRepoPath");
					continue;
				}
				if (is_file($localRepoPath))
				{
					c_warning("$localRepoPath exists and is not a directory");
					continue;
				}
				if (!is_dir($localRepoPath) && !@mkdir($localRepoPath, 0777, true))
				{
					throw new Exception("Could not create $localRepoPath");
				}
				$this->localRepositories[realpath($localRepoPath)] = is_writeable($localRepoPath);
			}
		}
		return $this->localRepositories;
	}

	/**
	 * Used by "makeRepo" script
	 * @param $path
	 */
	function setLocalRepository($path)
	{
		if (!is_dir($path))
		{
			throw new Exception("$path directory does not exists");
		}
		$this->localRepositories = array($path => is_writable($path));
	}

	/**
	 * @return String
	 */
	private function getProxy()
	{
		return $this->getProperties()->getProperty("PROXY");
	}

	private function escapeArg(&$value, $key)
	{
		$value = '"'.str_replace('"', '\"', $value).'"';
	}

	/**
	 * @param String $path
	 * @return String
	 */
	private function expandLocalPath($path)
	{
		if (!strncmp($path, "~/", 2))
		{
			return getenv("HOME")."/".substr($path, 2);
		}
		return $path;
	}

	/**
	 * @param array $components
	 */
	private function checkAndLoadDependencies(&$components)
	{
		$desc = new DOMDocument();
		$loaded = $desc->load($this->getDescriptorPath());
		if ($loaded === false)
		{
			throw new Exception("Could not load " . $this->getDescriptorPath());
		}
		
		$this->loadPearInfo();
		
		$triedComponents = array();
		
		$xpath = new DOMXPath($desc);
		$xpath->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$nodes = $xpath->query("c:dependencies/c:framework");
		$components = array();
		if ($nodes->length == 1)
		{
			$frameworkVersion = $nodes->item(0)->textContent;
			$components["framework"] = array(self::$DEP_CHANGE_LIB, "framework", $frameworkVersion);
		}

		foreach ($xpath->query("c:dependencies/c:modules/c:module") as $moduleElem)
		{
			$matches = array();
			if (!preg_match('/^(.*)-([0-9].*)$/', $moduleElem->textContent, $matches))
			{
				throw new Exception("Invalid version number module ".$moduleElem->textContent);
			}
			$components["module/".$matches[1]] = array(self::$DEP_MODULE, $matches[1], $matches[2]);
		}

		foreach ($xpath->query("c:dependencies/c:libs/c:lib") as $libElem)
		{
			$matches = array();
			if (!preg_match('/^(.*)-([0-9].*)$/', $libElem->textContent, $matches))
			{
				throw new Exception("Invalid version number library ".$libElem->textContent);
			}
			$components["lib/".$matches[1]] = array(self::$DEP_LIB, $matches[1], $matches[2]);
		}

		foreach ($xpath->query("c:dependencies/c:change-libs/c:lib") as $libElem)
		{
			$matches = array();
			if (!preg_match('/^(.*)-([0-9].*)$/', $libElem->textContent, $matches))
			{
				throw new Exception("Invalid version number change library ".$libElem->textContent);
			}
			$components["change-lib/".$matches[1]] = array(self::$DEP_CHANGE_LIB, $matches[1], $matches[2]);
		}
			 
		foreach ($components as &$componentInfo)
		{
			try
			{
				$triedComponents[$componentInfo[0].'/'.$componentInfo[1]] = array($componentInfo[2]);
				$componentPath = null;
				if ($this->hasComponentLocally($componentInfo[0], $componentInfo[1], $componentInfo[2], $componentPath))
				{
					c_debug($this->getFullName($componentInfo[0], $componentInfo[1], $componentInfo[2])." is present locally");
					if ($this->deepCheck && $componentPath !== null)
					{
						$this->checkAndLoadComponentDependencies($triedComponents, $componentPath);
					}
				}
				else
				{
					$componentPath = $this->downloadComponent($triedComponents, $componentInfo[0], $componentInfo[1], $componentInfo[2]);
				}
				$componentInfo["path"] = $componentPath;
			}
			catch (Exception $e)
			{
				c_error($e->getMessage(), ($this->onlyCheck) ? null : 1);
			}
		}
	}

	private function getComputedDepencies($componentPath)
	{
		if (!file_exists($componentPath."/.computedDeps.ser"))
		{
			// probably a dev repository => we do not store the file as the result can evolve
			list(, $computedDeps) = $this->getDependencies($componentPath);
			return $computedDeps;
		}
		$computedDeps = unserialize(file_get_contents($componentPath."/.computedDeps.ser"));
		if ($computedDeps === false)
		{
			throw new Exception("Could not read $componentPath dependencies");
		}
		return $computedDeps;
	}

	private $checked = array();

	private function checkAndLoadComponentDependencies(&$triedComponents, $componentPath)
	{
		if (isset($this->checked[$componentPath]))
		{
			return;
		}
		$this->checked[$componentPath] = true;
		if ($this->onlyCheck)
		{
			$keys = array_keys($this->localRepositories);
			c_message("Check ".substr(str_replace($keys, "", $componentPath." dependencies"), 1));
		}
		else
		{
			c_message("Check ".$componentPath." dependencies");
		}
		//debug_print_backtrace();
		$computedDeps = $this->getComputedDepencies($componentPath);
		if ($computedDeps === null)
		{
			// no dependencies
			return;
		}
		foreach ($computedDeps as $componentType => $deps)
		{
			foreach ($deps as $componentName => $componentVersions)
			{
				try
				{
					if (empty($componentVersions))
					{
						throw new Exception("Invalid dependency declaration (no version): $componentType/$componentName");
					}
					usort($componentVersions, array($this, "compareVersion"));
					$version = $componentVersions[count($componentVersions)-1];
					if (isset($triedComponents[$componentType.'/'.$componentName]))
					{
						$triedComponentsCount = count($triedComponents[$componentType.'/'.$componentName]);
						if (in_array($version, $triedComponents[$componentType.'/'.$componentName]))
						{
							c_debug("Already tried $componentType/$componentName-$version");
						}
						elseif ($this->compareVersion($version, $triedComponents[$componentType.'/'.$componentName][$triedComponentsCount-1]) <= 0)
						{
							$triedComponents[$componentType.'/'.$componentName][] = $version;
							usort($triedComponents[$componentType.'/'.$componentName], array($this, "compareVersion"));
							c_message("We already got a newer version than $version for $componentType/$componentName (".$triedComponents[$componentType.'/'.$componentName][$triedComponentsCount].")");
						}
						else
						{
							$triedComponents[$componentType.'/'.$componentName][] = $version;
							$subComponentPath = null;
							if (!$this->hasComponentLocally($componentType, $componentName, $version, $subComponentPath))
							{
								$this->downloadComponent($triedComponents, $componentType, $componentName, $version);
							}
							elseif ($this->deepCheck && $subComponentPath !== null)
							{
								$this->checkAndLoadComponentDependencies($triedComponents, $subComponentPath);
							}
						}
					}
					else
					{
						$triedComponents[$componentType.'/'.$componentName][] = $version;
						if (!$this->hasComponentLocally($componentType, $componentName, $version, $subComponentPath))
						{
							$this->downloadComponent($triedComponents, $componentType, $componentName, $version);
						}
						elseif ($this->deepCheck && $subComponentPath !== null)
						{
							$this->checkAndLoadComponentDependencies($triedComponents, $subComponentPath);
						}
					}
				}
				catch (Exception $e)
				{
					c_error($e->getMessage(), ($this->onlyCheck) ? null : 1);
				}
			}
		}
	}

	function compareVersion($version1, $version2)
	{
		if ($version1 == $version2)
		{
			return 0;
		}
		//echo "Compare $version1 to $version2\n";
		$matches1 = array();
		$matches2 = array();
		$versionPattern = '/^([^.+])(\.[^.]+)*(-[0-9]+){0,1}(\.r[0-9]+){0,1}$/';
		if (!preg_match($versionPattern, $version1, $matches1) || !preg_match($versionPattern, $version2, $matches2))
		{
			throw new Exception("Can not compare $version1 to $version2: invalid version");
		}

		$matches1Count = count($matches1);
		$matches2Count = count($matches2);
		$count = min($matches1Count, $matches2Count);
		for ($i = 0; $i < $count; $i++)
		{
			if ($matches1[$i] < $matches2[$i])
			{
				//echo "$version1 < $version2\n";
				return -1;
			}
			elseif ($matches2[$i] < $matches2[$i])
			{
				//echo "$version1 > $version2\n";
				return 1;
			}
		}
		if ($matches1Count > $matches2Count)
		{
			//echo "$version1 > $version2\n";
			return 1;
		}
		//echo "$version1 < $version2\n";
		return -1;
	}

	private $hasComponentLocallyCache = array();

	private function componentLocallyOK($componentType, $componentName, $version, $componentPath = true)
	{
		$this->hasComponentLocallyCache[$componentType."/".$componentName."/".$version] = $componentPath;
	}

	private function componentLocallyKO($componentType, $componentName, $version)
	{
		$this->hasComponentLocallyCache[$componentType."/".$componentName."/".$version] = false;
	}

	private function hasComponentLocallyCache($componentType, $componentName, $version)
	{
		$key = $componentType."/".$componentName."/".$version;
		if (array_key_exists($key, $this->hasComponentLocallyCache))
		{
			return $this->hasComponentLocallyCache[$key];
		}
		return null;
	}

	/**
	 * @param String $componentType {change-module|change-lib|change-tool|lib|lib-pear}
	 * @param String $componentName
	 * @param String $version
	 * @param String $componentPath
	 * @return Boolean
	 */
	private function hasComponentLocally($componentType, $componentName, $version, &$componentPath)
	{
		if (($componentPath = $this->hasComponentLocallyCache($componentType, $componentName, $version)) !== null)
		{
			if ($componentPath === false)
			{
				return false;
			}
			return true;
		}
		if ($componentType == self::$DEP_EXTENSION)
		{
			// Can not trust phpversion($componentName) as it returns no or outdated data
			// TODO. Use ReflectionExtension. Available since version ... ?
			if ($this->onlyCheck)
			{
				echo "Check PHP extension ".$componentName." (>= $version)\n";
			}
			if (extension_loaded($componentName))
			{
				$this->componentLocallyOK($componentType, $componentName, $version);
				return true;
			}
			$this->componentLocallyKO($componentType, $componentName, $version);
			return false;
		}
		elseif ($componentType == self::$DEP_PEAR)
		{
			if (!$this->useChangePearLib())
			{
				c_debug("USE hasPearPackageLocally $componentName");
				return $this->hasPearPackageLocally($componentType, $componentName, $version);
			}
			else
			{
				c_debug("USE Internal Change pear lib for $componentName");
				$componentPath = $this->getComponentPath(self::$DEP_PEAR_LIB, $componentName, $version);
				if ($componentPath !== null)
				{
					$this->componentLocallyOK($componentType, $componentName, $version, $componentPath);
					return true;
				}
				return false;
			}
		}
		elseif ($componentType == self::$DEP_BIN)
		{
			$binary = $componentName;
			foreach (explode(":", getenv("PATH")) as $dir)
			{
				if (is_executable($dir."/".$binary))
				{
					c_debug("$binary binary founded");
					$this->componentLocallyOK($componentType, $componentName, $version);
					return true;
				}
			}
			$msg = "Could not find $binary binary in path";
			c_warning($msg);
			$this->componentLocallyKO($componentType, $componentName, $version);
			return false;
		}
		$componentPath = $this->getComponentPath($componentType, $componentName, $version);
		if ($componentPath !== null)
		{
			$this->componentLocallyOK($componentType, $componentName, $version, $componentPath);
			return true;
		}
		return false;
	}

	function getComponentPath($componentType, $componentName, $version)
	{
		$relativePath = $this->getRelativeComponentPath($componentType, $componentName, $version);
		if ($relativePath === null)
		{
			return null;
		}
		foreach (array_keys($this->getLocalRepositories()) as $localRepoPath)
		{
			if (is_dir($localRepoPath."/".$relativePath))
			{
				return $localRepoPath."/".$relativePath;
			}
		}
		return null;
	}

	private function getRelativeComponentPath($componentType, $componentName, $version)
	{
		if ($componentName == "framework")
		{
			return "framework/framework-".$version;
		}
		switch ($componentType)
		{
			case self::$DEP_FRAMEWORK:
				return "framework/framework-".$version;
			case self::$DEP_MODULE:
				return "modules/".$componentName."/".$componentName."-".$version;
			case self::$DEP_CHANGE_LIB:
				return "change-lib/".$componentName."/".$componentName."-".$version;
			case self::$DEP_CHANGE_TOOL:
				return "change-tools/".$componentName."/".$componentName."-".$version;
			case self::$DEP_CHANGE_PROJECT:
				return "change-project/".$componentName."/".$componentName."-".$version;
			case self::$DEP_LIB:
				return "libs/".$componentName."/".$componentName."-".$version;
			case self::$DEP_PEAR:
			case self::$DEP_PEAR_LIB:
				return "pearlibs/".$componentName."/".$componentName."-".$version;
		}
		return null;
	}

	private function getType($typeStr)
	{
		switch ($typeStr)
		{
			case "modules":
			case "module":
				return self::$DEP_MODULE;
			case "change-lib":
			case "framework":
				return self::$DEP_CHANGE_LIB;
			case "change-tools":
				return self::$DEP_CHANGE_TOOL;
			case "change-project":
			case "projects":
				return self::$DEP_CHANGE_PROJECT;
			case "libs":
			case "lib":
				return self::$DEP_LIB;
			case "lib-pear":
				return self::$DEP_PEAR_LIB;
			case "extension":
				return self::$DEP_EXTENSION;
			case "pear":
				return self::$DEP_PEAR;
			case "bin":
				return self::$DEP_BIN;
		}
		throw new Exception("Unknown type $typeStr");
	}

	function getDepTypeAsString($type)
	{
		switch ($type)
		{
			case self::$DEP_FRAMEWORK:
				return "framework";
			case self::$DEP_MODULE:
				return "module";
			case self::$DEP_CHANGE_LIB:
				return "change-lib";
			case self::$DEP_CHANGE_TOOL:
				return "change-tool";
			case self::$DEP_CHANGE_PROJECT:
				return "change-project";
			case self::$DEP_LIB:
				return "lib";
			case self::$DEP_PEAR_LIB:
				return "lib-pear";
			case self::$DEP_EXTENSION:
				return "extension";
			case self::$DEP_PEAR:
				return "pear";
			case self::$DEP_BIN:
				return "bin";
		}
		throw new Exception("Unknown type ".$type);
	}

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

	private function getFullName($componentType, $componentName, $version)
	{
		return $componentType."/".$componentName."-".$version;
	}

	private $repositoryContents = array();

	private function getRepositoryContent($repository)
	{
		if (!isset($this->repositoryContents[$repository]))
		{
			$repositoryContent = $this->download($repository."/repository.xml");
			$components = array();
			$doc = new DOMDocument();
			//echo $repositoryContent;
			if (!@$doc->loadXML($repositoryContent))
			{
				throw new Exception("Could not parse $repository catalog");
			}
			$xpath = new DOMXPath($doc);
			$result = $xpath->query("element");
			for ($i = 0; $i < $result->length; $i++)
			{
				$elementName = $result->item($i)->getAttribute("url");
				if ($elementName == "framework")
				{
					$elementName = self::$DEP_CHANGE_LIB."/framework";
				}
				elseif (substr($elementName, 0, 8) == "modules/")
				{
					$elementName = self::$DEP_MODULE."/".substr($elementName, 8);
				}
				foreach ($xpath->query("versions/version", $result->item($i)) as $versionElem)
				{
					$version = $versionElem->textContent;
					if (substr($version, -6) == "-trunk")
					{
						$version = substr($version, 0, -6);
					}
					$components[$elementName."-".$version] = array(
						"md5" => $versionElem->getAttribute("md5"),
						"sha1" => $versionElem->getAttribute("sha1"));
				}
			}

			$elemsResult = $xpath->query("elements");
			for ($i = 0; $i < $elemsResult->length; $i++)
			{
				$elements = $elemsResult->item($i);
				$type = $this->getType($elements->getAttribute("type"));
				$elemResult = $xpath->query("element", $elements);
				for ($j = 0; $j < $elemResult->length; $j++)
				{
					$elementName = $type."/".$elemResult->item($j)->getAttribute("name");
					foreach ($xpath->query("versions/version", $elemResult->item($j)) as $versionElem)
					{
						$version = $versionElem->textContent;
						if (substr($version, -6) == "-trunk")
						{
							$version = substr($version, 0, -6);
						}
						$components[$elementName."-".$version] = array(
							"md5" => $versionElem->getAttribute("md5"), 
							"sha1" => $versionElem->getAttribute("sha1"));
					}
				}
			}

			$this->repositoryContents[$repository] = $components;
			return $components;
		}
		return $this->repositoryContents[$repository];
	}

	function getRemoteModules()
	{
		// TODO: add dependencies to be able to check project compatibility
		// TODO: add caching
		ob_start();
		$modules = array();
		$modulePrefix = self::$DEP_MODULE."/";
		$modulePrefixLen = strlen($modulePrefix);
		foreach ($this->getRepositories() as $repository)
		{
			try
			{
				$content = $this->getRepositoryContent($repository);
				foreach (array_keys($content) as $componentName)
				{
					if (cboot_StringUtils::startsWith($componentName, $modulePrefix))
					{
						$modules[] = substr($componentName, $modulePrefixLen);
					}
				}
			}
			catch (Exception $e)
			{
				c_debug($e->getMessage());
			}
		}
		ob_get_clean();
		return array_unique($modules);
	}

	// Dependencies
	/**
	* @param String $dir
	* @return multitype:NULL
	*/
	function getDependencies($dir)
	{
		if (is_file($dir."/.computedDeps.ser") && is_file($dir."/.declaredDeps.ser"))
		{
			$declaredDeps = @unserialize(@file_get_contents($dir."/.declaredDeps.ser"));
			$computedDeps = @unserialize(@file_get_contents($dir."/.computedDeps.ser"));
			if (is_array($declaredDeps) && is_array($computedDeps))
			{
				return array($declaredDeps, $computedDeps);
			}
		}

		$changeDescFile = $dir."/change.xml";
		$computedDeps = $declaredDeps = null;
		if (is_file($changeDescFile))
		{
			$declaredDeps = $this->getDeclaredDependenciesFromXML($changeDescFile);
			$componentName = basename(dirname($dir));
			if ($componentName == "framework")
			{
				$repoDir = $dir."/../..";
			}
			else
			{
				$repoDir = $dir."/../../..";
			}
			$this->resolveDependencies($declaredDeps, $computedDeps, $repoDir);
			return array($declaredDeps, $computedDeps);
		}

		return array(array(), array());
	}

	private static $declaredDeps = array();

	/**
	 * @param String $changeXMLPath
	 * @return array
	 */
	private function getDeclaredDependenciesFromXML($changeXMLPath)
	{
		$declaredDeps = array();
		if (!is_file($changeXMLPath))
		{
			return $declaredDeps;
		}
		$changeXMLPath = realpath($changeXMLPath);
		if (isset(self::$declaredDeps[$changeXMLPath]))
		{
			return self::$declaredDeps[$changeXMLPath];
		}
		$doc = new DOMDocument();
		$doc->load($changeXMLPath);
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace("cc", "http://www.rbs.fr/schema/change-component/1.0");
		$deps = $xpath->query("cc:dependencies/cc:dependency");
		for ($i = 0; $i < $deps->length; $i++)
		{
			$dep = $deps->item($i);
			if ($dep->hasAttribute("optionnal") && $dep->getAttribute("optionnal") == "true")
			{
				continue;
			}
			$name = $xpath->query("cc:name", $dep);
			if ($name->length == 0)
			{
				throw new Exception("Invalid dependency: no name");
			}
			$matches = null;
			if (!preg_match('/^([^\/]*)\/(.*)$/', $name->item(0)->textContent, $matches))
			{
				throw new Exception("Invalid component name ".$name->item(0)->textContent);
			}
			$depType = $matches[1];
			$depName = $matches[2];

			$depTypeKey = null;
			switch ($depType)
			{
				case "change-lib":
					$depTypeKey = self::$DEP_CHANGE_LIB;
					break;
				case "lib":
					$depTypeKey = self::$DEP_LIB;
					break;
				case "module":
				case "change-module":
					$depTypeKey = self::$DEP_MODULE;
					break;
				case "extension":
					$depTypeKey = self::$DEP_EXTENSION;
					break;
				case "pear":
				case "pecl":
					$depTypeKey = self::$DEP_PEAR;
					break;
				case "lib-pear":
					$depTypeKey = self::$DEP_PEAR_LIB;
					break;
				case "bin":
					$depTypeKey = self::$DEP_BIN;
					break;
				default:
					echo "Unknown dep: ".$depType."\n";
					// ignore other dependencies
					continue;
			}
			
			if (!isset($declaredDeps[$depTypeKey]))
			{
				$declaredDeps[$depTypeKey] = array();
			}

			$versions = array();

			foreach ($xpath->query("cc:versions/cc:version", $dep) as $version)
			{
				$versions[] = $version->textContent;
			}

			$declaredDeps[$depTypeKey][$depName] = $versions;
		}
		self::$declaredDeps[$changeXMLPath] = $declaredDeps;
		return $declaredDeps;
	}

	private function resolveDependencies($depsByType, &$computedDeps = null, $repoDir)
	{
		if ($computedDeps === null)
		{
			$computedDeps = array();
		}

		foreach ($depsByType as $depType => $deps)
		{
			if (!isset($computedDeps[$depType]))
			{
				$computedDeps[$depType] = array();
			}

			foreach ($deps as $depName => $depVersions)
			{
				if (isset($computedDeps[$depType][$depName]))
				{
					$computedVersions = array_intersect($computedDeps[$depType][$depName], $depVersions);
					if ($this->looseVersions)
					{
						if (count($computedVersions) == 0)
						{
							c_warning("$depName dependency: no version intersection between {".join(", ", $depVersions)."} and {".join(", ", $computedDeps[$depType][$depName])."}");
						}
						$computedVersions = array_unique(array_merge($computedDeps[$depType][$depName], $depVersions));
					}
					else
					{
						// already known dependency: only retain versions that are common
						if (count($computedVersions) == 0)
						{
							// TODO better message
							throw new Exception("Dependency $depName conflict detected: no version intersection between {".join(", ", $depVersions)."} and {".join(", ", $computedDeps[$depType][$depName])."}");
						}
					}
					$computedDeps[$depType][$depName] = $computedVersions;
				}
				else
				{
					// new dependency
					$computedDeps[$depType][$depName] = $depVersions;
					if (count($depVersions) == 1)
					{
						$repoLocation = null;
						if (!$this->hasComponentLocally($depType, $depName, $depVersions[0], $repoLocation))
						{
							$repoLocation = $this->installComponent($depType, $depName, $depVersions[0]);
						}

						if ($repoLocation !== null)
						{
							$newDepsByType = $this->getDeclaredDependenciesFromXML($repoLocation."/change.xml");
							if ($newDepsByType !== null)
							{
								$this->resolveDependencies($newDepsByType, $computedDeps, $repoDir);
							}
							else
							{
								throw new Exception("Could not get dependencies from $repoLocation");
							}
						}
					}
				}
			}
		}
		return $computedDeps;
	}

	private function getRepoLocation($depType, $depName, $depVersion, $repoDir)
	{
		if ($depName == "framework")
		{
			$shortName = "framework/framework-$depVersion";
		}
		else
		{
			switch ($depType)
			{
				case self::$DEP_CHANGE_LIB:
					$pathSuffix = 'change-lib';
					break;
				case self::$DEP_MODULE:
					$pathSuffix = 'modules';
					break;
				case self::$DEP_LIB:
					$pathSuffix = 'libs';
					break;
				case self::$DEP_PEAR_LIB:
					$pathSuffix = 'pearlibs';
					break;
				default:
					return null;
			}
			$shortName = $pathSuffix.'/'.$depName.'/'.$depName.'-'.$depVersion;
		}

		$path = $repoDir.'/'.$shortName;
		if (!is_dir($path))
		{
			throw new Exception("Could not locate $shortName in $repoDir");
		}
		return $path;
	}
	// END dependencies

	function installComponent($componentType, $componentName, $version)
	{
		$componentPath = null;
		if ($this->hasComponentLocally($componentType, $componentName, $version, $componentPath))
		{
			return $componentPath;
		}
		$triedComponents = array();
		$this->downloadComponent($triedComponents, $componentType, $componentName, $version);
		return $this->getComponentPath($componentType, $componentName, $version);
	}

	private $downloads = array();

	/**
	 * @param String $componentType
	 * @param String $componentName
	 * @param String $version
	 * @return String the location of the downloaded component
	 */
	private function downloadComponent(&$triedComponents, $componentType, $componentName, $version)
	{
		if (isset($this->downloads[$componentType.'/'.$componentName]))
		{
			return;
		}
		try
		{
			if ($componentType == self::$DEP_PEAR)
			{
				if (!$this->useChangePearLib())
				{
					$this->downloadPearPackage($componentName, $version);
					$this->downloads[$componentType.'/'.$componentName][] = $version;
					return;
				}
				else
				{
					$componentType = self::$DEP_PEAR_LIB;
				}
			}
			
			if ($componentType == self::$DEP_EXTENSION)
			{
				if (!isset($this->downloads[$componentType.'/'.$componentName]))
				{
					throw new Exception("Please install PHP extension $componentName-$version (newer version may work but they are not certified)");
				}
				$this->downloads[$componentType.'/'.$componentName][] = $version;
				return;
			}
			$fullName = $this->getFullName($componentType, $componentName, $version);
			if (!$this->onlyCheck)
			{
				echo "Must download ".$this->getDepTypeAsString($componentType)."/$componentName-$version\n";
			}
			$writeRepo = $this->getWriteRepository();
			if ($writeRepo === null)
			{
				throw new Exception("Could not find any local repository where to write ".$fullName);
			}
			$relativePath = $this->getRelativeComponentPath($componentType, $componentName, $version);
			if ($relativePath === null)
			{
				throw new Exception("Can not download component ".$fullName);
			}

			$destFile = $writeRepo."/".$relativePath.".zip";
			foreach ($this->getRepositories() as $repository)
			{
				try
				{
					$repositoryContent = $this->getRepositoryContent($repository);
					if (isset($repositoryContent[$fullName]))
					{
						c_debug($fullName." is in $repository");
						if ($this->onlyCheck)
						{
							$destFile = $writeRepo."/".$relativePath."/change.xml";
							$this->download($repository."/".$relativePath.".xml", $destFile);
							$triedComponents[$componentType.'/'.$componentName][] = $version;
							$this->checkAndLoadComponentDependencies($triedComponents, dirname($destFile));
						}
						else
						{
							$this->download($repository."/".$relativePath.".zip", $destFile);
							if (md5_file($destFile) != $repositoryContent[$fullName]["md5"] ||
							sha1_file($destFile) != $repositoryContent[$fullName]["sha1"])
							{
								unlink($destFile);
								throw new Exception("Checksum of $destFile failed");
							}
							// TODO: something using sys_get_temp_dir when available
							$tempPath = tempnam(null, $fullName."-descriptor");
							unlink($tempPath);
							mkdir($tempPath);
							cboot_Zip::unzip($destFile, $tempPath, array(basename($relativePath)."/.computedDeps.ser"));
							$this->checkAndLoadComponentDependencies($triedComponents, $tempPath."/".basename($relativePath));
							cboot_Zip::unzip($destFile, dirname($writeRepo."/".$relativePath));
							unlink($destFile);
						}
						$this->downloads[$componentType.'/'.$componentName][] = $version;
						return $writeRepo."/".$relativePath;
					}
					else
					{
						c_debug($fullName." not present in $repository");
					}
				}
				catch (Exception $e)
				{
					// Give a try to next repository
					c_warning($e->getMessage());
					c_debug($e->getTraceAsString());
				}
			}
			throw new Exception("Unable to download component ".$this->getDepTypeAsString($componentType)."/$componentName-$version from any repository");
		}
		catch (Exception $e)
		{
			$this->downloads[$componentType.'/'.$componentName][] = $version;
			c_error($e->getMessage(), $this->onlyCheck ? null : 1);
		}
	}

	private function download($url, $destFile = null)
	{
		self::$currentDownloadInfo = array();
		if (!$this->onlyCheck)
		{
			c_message("Downloading $url");
		}
		if ($destFile !== null)
		{
			$destDir = dirname($destFile);
			if (!is_dir($destDir) && !mkdir($destDir, 0777, true))
			{
				throw new Exception("Can not create directory ".$destDir);
			}
			$fh = fopen($destFile, "w");
			if ($fh === false)
			{
				throw new Exception("Could not write to $destFile");
			}
		}
		$ch = curl_init();
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array("c_ChangeBootStrap", "trapHeader"));
		$proxy = $this->getProxy();
		if ($proxy !== null)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
		if ($destFile !== null)
		{
			curl_setopt($ch, CURLOPT_FILE, $fh);
		}
		else
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		$result = curl_exec($ch);
		if ($destFile !== null)
		{
			fclose($fh);
		}
		if ($result === false)
		{
			throw new Exception("Could not download $url");
		}
		$info = array_merge(curl_getinfo($ch), self::$currentDownloadInfo);
		curl_close($ch);
		if (!strncmp($url, "http://", 7) || !strncmp($url, "https://", 8))
		{
			if ($info["http_code"] != "200")
			{
				throw new Exception("Could not download $url: bad http status (".$info["http_code"].")");
			}
		}

		if ($destFile === null)
		{
			return $result;
		}
	}

	private static function trapHeader($ch, $header)
	{
		$matches = null;
		if (preg_match('/^(.+): (.+)$/', $header, $matches))
		{
			self::$currentDownloadInfo[$matches[1]] = $matches[2];
		}
		return strlen($header);
	}
	
	//PEAR INSTALLATION
	
	/**
	 * @var array
	 */
	private $pearInfos;
	
	private function loadPearInfo()
	{
		if ($this->pearInfos === null)
		{
			$pearDir = $this->getProperties()->getProperty("PEAR_DIR");
			$pearCmd = $this->getProperties()->getProperty("PEAR_CMD");		
			$pearConf = $this->getProperties()->getProperty("PEAR_CONF");
			$include_path = $this->getProperties()->getProperty("PEAR_INCLUDE_PATH");
			
			if ($pearDir !== null && $pearCmd === null && $pearConf === null && $include_path === null)
			{
				//Previous config
				$pearCmd = $pearDir.'/bin/pear';
				$pearConf = $pearDir.'/pear.conf';
			}
			
			if ($pearCmd !== null)
			{
				$pearCmd = $this->expandLocalPath($pearCmd);
				if (!file_exists($pearCmd))
				{
					if ($pearDir === null)
					{
						throw new Exception("Missing PEAR_DIR config parameter");
					}
					$pearDir = $this->expandLocalPath($pearDir);
					$this->installPear($pearDir);
					clearstatcache();
					
					if (!file_exists($pearCmd))
					{
						throw new Exception("La commande $pearCmd , n'existe pas");
					}
				}
			}
			if ($include_path === null && $pearDir === null)
			{
				throw new Exception("Missing PEAR_DIR or PEAR_INCLUDE_PATH config parameter");
			}
			if ($pearConf !== null) 
			{
				$pearConf = $this->expandLocalPath($pearConf);			
				if (!file_exists($pearConf))
				{
					throw new Exception("Missing PEAR_CONF value ($pearConf), file not found.");
				}
			}
			
			if ($include_path === null)
			{
				$include_path = $pearDir . '/PEAR';
			}
			else
			{
				$include_path = $this->expandLocalPath($include_path);
			}
						
			if (!file_exists($include_path) && !@mkdir($include_path, 0777, true))
			{
				throw new Exception("Pear php Folder not found :" . $include_path);
			}
			
			$writeable = is_writeable($include_path);
		
			$this->pearInfos = array("include_path" => $include_path, "writeable" => $writeable,
									 "path" => $pearDir, "command" => $pearCmd, "conf" => $pearConf);
			
			c_debug(var_export($this->pearInfos, true));
		}
	}
	
	/**
	 * @return boolean
	 */
	private function useChangePearLib()
	{
		$this->loadPearInfo();
		return $this->pearInfos['command'] === null;
	}
	
	private function hasPearPackageLocally($componentType, $componentName, $version)
	{		
		$packageName = $componentName;		
		
		$pearDir = $this->pearInfos["path"];
		c_debug("Search for PEAR component: $componentName");
		try
		{
			$cmd = $this->pearInfos['command'];
			if ($this->pearInfos['conf'])
			{
				$cmd .= " -c " . $this->pearInfos['conf'];
			}
			$cmd .= " info $packageName";
			$result = cboot_System::execArray($cmd);
			c_debug("hasPearPackageLocally cmd $cmd");					
			foreach ($result as $resultLine)
			{
				$matches = null;
				if (preg_match('/^ABOUT .*-([0-9].*)$/i', $resultLine, $matches))
				{
					$installedVersion = strtolower($matches[1]);					
					$compareResult = $this->compareVersion($version, $installedVersion);
					if ($compareResult == 0)
					{
						c_debug("Exact version match for $packageName-$version in $pearDir");
						$this->componentLocallyOK($componentType, $componentName, $version);
						return true;
					}
					elseif ($compareResult < 0)
					{
						c_debug("$packageName found in PEAR with higher version as expected ($installedVersion > $version)");
						$this->componentLocallyOK($componentType, $componentName, $version);
						return true;
					}
					else
					{
						c_debug("$packageName found in PEAR with lower version as expected ($installedVersion < $version)");
						$this->componentLocallyOK($componentType, $componentName, $version);
						return true;
					}
				}
			}
			c_warning("$packageName found in PEAR, but no version detected. Check your pear config");
			$this->componentLocallyOK($componentType, $componentName, $version);
			return true;
		}
		catch (Exception $e)
		{
			c_debug($e->getMessage());
			c_message("$packageName not found in PEAR");
		}
		$this->componentLocallyKO($componentType, $componentName, $version);
		return false;
	}
		
	private function installPear($pearDir)
	{	
		if (!file_exists($pearDir) && !@mkdir($pearDir, 0777, true))
		{
			throw new Exception('Unable to create folder :' . $pearDir);
		}
		$pearIn = join("\n", array("", $this->getProxy(), "1", $pearDir, "", "n", "n", ""));
		$goPearPath = null;
		if (!$this->hasComponentLocally(self::$DEP_LIB, "go-pear", "281637", $goPearPath))
		{
			$triedDummy = array();
			$goPearPath = $this->downloadComponent($triedDummy, self::$DEP_LIB, "go-pear", "281637");
		}
		cboot_System::exec("php -q $goPearPath/go-pear local", "Installing pear in $pearDir (this can be long, be patient)", !C_DEBUG, $pearIn);
	}



	/**
	 * @param String $componentName
	 * @param String $version
	 * @return boolean
	 */
	private function downloadPearPackage($componentName, $version)
	{
		if ($this->pearInfos['writeable'])
		{
			c_message("Install PEAR Package ".$componentName."-".$version);
			$this->execPearCmd("install --onlyreqdeps ".$componentName."-".$version, "Installing $componentName");
		}
		else
		{
			c_message("PEAR is read only. Install Package ".$componentName."-".$version . " manualy");
		}
		return true;
	}
	
	private function execPearCmd($pearCmd, $msg = null)
	{
		try 
		{
			$cmd = $this->pearInfos['command'];
			if ($this->pearInfos['conf'])
			{
				$cmd .= " -c " . $this->pearInfos['conf'];
			}
			$cmd .= " " .$pearCmd;
			cboot_System::exec($cmd, $msg, !C_DEBUG);
		}
		catch (Exception $e)
		{
			c_debug($e->getMessage());
		}
	}
}
/** End lib/ChangeBootStrap.php **/ ?>
<?php /** Begin lib/ClassDirAnalyzer.php **/ ?>
<?php

class cboot_ClassDirAnalyzer
{
	private $path;
	private $pathLength;
	private $options;
	private $verbose = false;

	function __construct($path, $options = null)
	{
		$this->path = $path;
		$this->pathLength = strlen($path)+1;
		if ($options === null)
		{
			$options = array();
		}
		if (is_file($path."/c_autoload.properties"))
		{
			$props = new cboot_Properties($path."/c_autoload.properties");
			if ($props->hasProperty("exclude"))
			{
				$exclude = explode(",", $props->getProperty("exclude"));
				if (isset($options["exclude"]))
				{
					$exclude = array_merge($exclude, $options["exclude"]);
				}
				$options["exclude"] = $exclude;
			}
		}
		if (isset($options["exclude"]))
		{
			$options["excludedFile"] = array();
			$options["excludedDir"] = array();
			foreach ($options["exclude"] as $exclude)
			{
				if (is_dir($path."/".$exclude))
				{
					$options["excludedDir"][] = $exclude;
				}
				elseif (is_file($path."/".$exclude))
				{
					$options["excludedFile"][] = $exclude;
				}
			}
		}

		$this->options = $options;
	}

	/**
	 * @param Boolean $verbose
	 */
	function setVerbose($verbose)
	{
		$this->verbose = $verbose;
	}

	function analyze()
	{
		$info = null;
		$this->findDependencies($info);
		return $info;
	}

	/**
	 * @param array $dependencies {"declaredClasses":{}, "declaredFunctions":{}, "classes":{}, "functions":{}}
	 * @return array the dependencies {"declaredClasses":{}, "declaredFunctions":{}, "classes":{}, "functions":{}}
	 */
	private function findDependencies(&$dependencies = null)
	{
		if ($dependencies === null)
		{
			$dependencies = array("declaredClasses" => array(), "declaredFunctions" => array(), "classes" => array(), "functions" => array(), "methods" => array());
		}
		if ($this->verbose)
		{
			echo "Scanning from ".$this->path."\n";
		}
		$this->getDependencies($this->path, $this->options, $dependencies);

		foreach($dependencies as $dependency => &$dependencyDetail)
		{
			ksort($dependencyDetail);
		}
		return $dependencies;
	}

	// private methods

	private function getDependencies($path, $options, &$dependencies = null)
	{
		if (is_dir($path))
		{
			$this->getDependenciesFromDir($path, $options, $dependencies);
		}
		else
		{
			$this->getDependenciesFromFile($path, $options, $dependencies);
		}
	}

	private function getDependenciesFromDir($path, $options, &$dependencies)
	{
		if (isset($options["excludedDir"]))
		{
			$relPath = $this->getRelativePath($path);
			if (in_array($relPath, $options["excludedDir"]))
			{
				if ($this->verbose)
				{
					echo "Exclude dir $path\n";
				}
				return;
			}
		}

		foreach (scandir($path) as $file)
		{
			if ($file == "." || $file == "..")
			{
				continue;
			}

			$filePath = $path.DIRECTORY_SEPARATOR.$file;
			if (is_file($filePath))
			{
				$this->getDependenciesFromFile($filePath, $options, $dependencies);
			}
			elseif (is_dir($filePath))
			{
				$this->getDependenciesFromDir($filePath, $options, $dependencies);
			}
			else
			{
				throw new Exception("Unknown type of file $filePath");
			}
		}
	}

	private function printToken($token)
	{
		if (is_int($token[0]))
		{
			echo "TOKEN: ".token_name($token[0]).": ".$token[1];
		}
		else
		{
			echo "TOKEN: ".$token[0];
		}
		echo "\n";
	}

	private function getDependenciesFromFile($file, $options, &$dependencies)
	{
		$ext = substr($file, -4);
		if ($ext == ".php" || $ext == ".inc")
		{
			if (isset($options["excludedFile"]))
			{
				$relPath = $this->getRelativePath($file);
				if (in_array($relPath, $options["excludedFile"]))
				{
					if ($this->verbose)
					{
						echo "Ignoring $file.\n";
					}
					return;
				}
			}
			if ($this->verbose)
			{
				echo "Analyzing $file...";
			}
			$tokenArray = token_get_all(file_get_contents($file));
			$previousTokenType = null;

			$bracketNumber = 0;
			$closing = 0;
			$inClass = null;
			foreach ($tokenArray as $index => $token)
			{
				//$this->printToken($token);
				$tokenType = $token[0];
				if ("{" == $tokenType || T_CURLY_OPEN == $tokenType)
				{
					$bracketNumber++;
					//echo "Increment $bracketNumber\n";
				}
				elseif ("}" == $tokenType)
				{
					$bracketNumber--;
					if ($bracketNumber === $inClass)
					{
						$inClass = null;
					}
					//echo "Decrement $bracketNumber\n";
				}
				elseif ($tokenType == T_STRING)
				{
					switch ($previousTokenType)
					{
						case T_CLASS:
						case T_INTERFACE:
							$className = $token[1];
							$inClass = $bracketNumber;
							//echo "Class $className begins: $bracketNumber\n";
							$this->addElement($dependencies["declaredClasses"], $className, $file, true);
							continue 2;
							break;

						case T_OBJECT_OPERATOR:
							// instance method call
							continue 2;
							break;
						case T_DOUBLE_COLON:
							// class method call
							continue 2;
							break;
						case T_NEW:
						case T_EXTENDS:
							if (!isset($options["only-declared"]))
							{
								$className = $token[1];
								if ($className != "self")
								{
									$this->addElement($dependencies["classes"], $className, $file);
								}
							}
							continue 2;
							break;
						case T_FUNCTION:
							if ($inClass === null)
							{
								$functionName = $token[1];
								$this->addElement($dependencies["declaredFunctions"], $functionName, $file);
							}
							continue 2;
							break;
					}

					$functionCallIndex = $this->findNextMeanfullTokenIndex($tokenArray, $index+1);
					switch ($tokenArray[$functionCallIndex][0])
					{
						case "(":
							if (!isset($options["only-declared"]))
							{
								$funcToken = $tokenArray[$functionCallIndex];
								$funcName = $token[1];
								$this->addElement($dependencies["functions"], $funcName, $file);
							}
							break;
						case T_DOUBLE_COLON:
							if (!isset($options["only-declared"]))
							{
								$className = $token[1];
								if ($className != "self" && $className != "parent")
								{
									$this->addElement($dependencies["classes"], $className, $file);

									$functionName = $this->findFunctionCallFromIndex($tokenArray, $functionCallIndex+1);
									if ($functionName !== null)
									{
										$this->addElement($dependencies["methods"], $className."::".$functionName, $file);
									}
								}
							}
							break;
					}
				}

				if ($tokenType != T_WHITESPACE && $tokenType != T_COMMENT && $tokenType != "&")
				{
					$previousTokenType = $tokenType;
				}
			}
			if ($this->verbose)
			{
				echo " done\n";
			}
		}
	}

	private function findFunctionCallFromIndex($tokenArray, $fromIndex)
	{
		$index1 = $this->findNextMeanfullTokenIndex($tokenArray, $fromIndex);
		if ($tokenArray[$index1][0] != T_STRING)
		{
			return null;
		}
		$index2 = $this->findNextMeanfullTokenIndex($tokenArray, $index1+1);
		if ($tokenArray[$index2][0] != "(")
		{
			return null;
		}
		return $tokenArray[$index1][1];
	}

	private function findNextMeanfullTokenIndex($tokenArray, $fromIndex)
	{
		$index = $fromIndex;
		while ($tokenArray[$index] == T_WHITESPACE)
		{
			$index++;
		}
		return $index;
	}

	private function getRelativePath($path)
	{
		return substr($path, $this->pathLength);
	}

	private function addElement(&$array, $key, $file, $unique = false)
	{
		$relPath = $this->getRelativePath($file);
		if ($unique)
		{
			if (isset($array[$key]))
			{
				throw new Exception("Duplicate $key in $file and ".$array[$key]);
			}
			$array[$key] = $relPath;
		}
		else
		{
			if (!isset($array[$key]))
			{
				$array[$key] = array($relPath);
			}
			else
			{
				$array[$key][] = $relPath;
			}
		}
	}
}
/** End lib/ClassDirAnalyzer.php **/ ?>
<?php /** Begin lib/Properties.php **/ ?>
<?php
class cboot_Properties
{
	/**
	 * @var array<String,String>
	 */
	private $properties;

	/**
	 * @var Boolean
	 */
	private $preserveComments = false;

	/**
	 * @var Boolean
	 */
	private $preserveEmptyLines = false;

	function __construct($path = null)
	{
		if ($path !== null)
		{
			$this->load($path);
		}
	}

	/**
	 * @param String $path
	 */
	function load($path)
	{
		if (!is_file($path) || !is_readable($path))
		{
			throw new Exception("Can not read file $path");
		}
		$this->parse($path);
	}
	
	/**
	 * @param String $path
	 */
	function save($path)
	{
		$dir = dirname($path);
		if ((!file_exists($path) && !is_writable($dir)) || (file_exists($path) && !is_writable($path)))
		{
			throw new Exception("Can not write to $path");
		}
		if (file_put_contents($path, $this->__toString()) === false)
		{
			throw new Exception("Could not write to $path");
		}
	}

	/**
	 * (by defaults, comments are not preserved)
	 * @param Boolean $preserveComments
	 */
	function setPreserveComments($preserveComments)
	{
		$this->preserveComments = $preserveComments;
	}

	/**
	 * (by defaults, comments are not preserved)
	 * @param Boolean $preserveComments
	 */
	function setPreserveEmptyLines($preserveEmptyLines)
	{
		$this->preserveEmptyLines = $preserveEmptyLines;
	}

	/**
	 * @return String
	 */
	public function __toString()
	{
		if ($this->properties !== null)
		{
			$buf = "";
			foreach($this->properties as $key => $item)
			{
				if ($this->preserveComments && is_int($key))
				{
					$buf .= $item."\n";
				}
				else
				{
					$buf .= $key . "=" . $this->writeValue($item)."\n";
				}
			}
			return $buf;
		}
		return "";
	}

	/**
	 * Returns copy of internal properties hash.
	 * Mostly for performance reasons, property hashes are often
	 * preferable to passing around objects.
	 *
	 * @return array
	 */
	function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Get value for specified property.
	 * This is the same as get() method.
	 *
	 * @param string $prop The property name (key).
	 * @return mixed
	 * @see get()
	 */
	function getProperty($prop, $defaultValue = null)
	{
		if (!isset($this->properties[$prop]))
		{
			return $defaultValue;
		}
		return $this->properties[$prop];
	}

	/**
	 * Set the value for a property.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed Old property value or NULL if none was set.
	 */
	function setProperty($key, $value)
	{
		$oldValue = @$this->properties[$key];
		$this->properties[$key] = $value;
		return $oldValue;
	}
	
	/**
	 * @param array<String, String> $properties
	 */
	function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * Same as keys() function, returns an array of property names.
	 * @return array
	 */
	function propertyNames()
	{
		return $this->keys();
	}

	/**
	 * Whether loaded properties array contains specified property name.
	 * @return boolean
	 */
	function hasProperty($key)
	{
		return isset($this->properties[$key]);
	}

	/**
	 * Whether properties list is empty.
	 * @return boolean
	 */
	function isEmpty()
	{
		return empty($this->properties);
	}

	// protected methods

	/**
	 * @param unknown_type $val
	 * @return unknown
	 */
	protected function readValue($val)
	{
		if ($val === "true")
		{
			$val = true;
		}
		elseif ($val === "false")
		{
			$val = false;
		}
		else
		{
			$valLength = strlen($val);
			if ($val[0] == "'" && $val[$valLength-1] == "'" || $val[0] == "\"" && $val[$valLength-1] == "\"")
			{
				$val = substr($val, 1, -1);
			}
		}
		return $val;
	}

	/**
	 * Process values when being written out to properties file.
	 * does things like convert true => "true"
	 * @param mixed $val The property value (may be boolean, etc.)
	 * @return string
	 */
	protected function writeValue($val)
	{
		if ($val === true)
		{
			$val = "true";
		}
		elseif ($val === false)
		{
			$val = "false";
		}
		return $val;
	}

	// private methods

	/**
	 * @param String $filePath
	 */
	private function parse($filePath)
	{
		$lines = @file($filePath);
		if ($lines === false)
		{
			throw new Exception("Could not read $filePath");
		}
		if ($this->properties === null)
		{
			$this->properties = array();
		}
		foreach($lines as $line)
		{
			$line = trim($line);
			if($line == "")
			{
				if ($this->preserveEmptyLines)
				{
					$this->properties[] = " ";
				}
				continue;
			}

			if ($line[0] == '#' || $line[0] == ';')
			{
				// it's a comment, so continue to next line
				if ($this->preserveComments)
				{
					$this->properties[] = $line;
				}
				continue;
			}
			else
			{
				$pos = strpos($line, '=');
				if ($pos === false)
				{
					throw new Exception("Invalid property file line $line");
				}
				$property = trim(substr($line, 0, $pos));
				$value = trim(substr($line, $pos + 1));
				$this->properties[$property] = $this->readValue($value);
			}
		}
	}
}
/** End lib/Properties.php **/ ?>
<?php /** Begin lib/System.php **/ ?>
<?php
class cboot_System
{
	public static function escapeCmd($cmd)
	{
		$cmd = mb_ereg_replace(", ", "\\, ", $cmd);
		$cmd = mb_ereg_replace(" ", "\\ ", $cmd);
		return $cmd;
	}

	/**
	 * @param String $cmd
	 * @param String $msg
	 * @param Boolean $captureStdout
	 * @param String input
	 * @return String the output result of execution
	 */
	public static function exec($cmd, $msg = null, $captureStdout = true, $input = null)
	{
		if ($msg !== null)
		{
			echo $msg."...";
		}

		$cmd .= " 2>&1";
		
		$descriptorspec = array(
		0 => array('pipe', 'r'), // stdin
		1 => array('pipe', 'w'), // stdout
		2 => array('pipe', 'w') // stderr
		);
		$proc = proc_open($cmd, $descriptorspec, $pipes);
		if (!is_resource($proc))
		{
			throw new Exception("Can not execute $cmd");
		}
		stream_set_blocking($pipes[2], 0);
		if ($input !== null)
		{
			fwrite($pipes[0], $input);
		}
		fclose($pipes[0]);
		$output = "";
		while (!feof($pipes[1]))
		{
			$s = fread($pipes[1], 512);
			if ($s === false)
			{
				throw new Exception("Error while executing $cmd: could not read further execution result");
			}
			$output .= $s;
			if (!$captureStdout)
			{
				echo $s;
			}
		}

		$retVal = proc_close($proc);
		if (0 != $retVal)
		{
			throw new Exception("Could not execute $cmd (exit code $retVal):\n".$output);
		}
		if ($msg !== null)
		{
			echo " done\n";
		}
		return trim($output);
	}

	/**
	 * @param String $cmd
	 * @param String $msg
	 * @param Boolean $captureStdout
	 * @param String input
	 * @return array the output result of execution
	 */
	public static function execArray($cmd, $msg = null, $captureStdout = true, $input = null)
	{
		$out = self::exec($cmd, $msg, $captureStdout, $input);
		if (empty($out))
		{
			return array();
		}
		return explode("\n", $out);
	}
}
/** End lib/System.php **/ ?>
<?php /** Begin lib/Zip.php **/ ?>
<?php
class cboot_Zip
{
	/**
	 * @var String
	 */
	private static $driverClassName;

	/**
	 * @return cboot_Zipper
	 */
	private static function getInstance($zipPath)
	{
		if (self::$driverClassName === null)
		{
			if (extension_loaded("zip") && class_exists("cboot_PeclZip"))
			{
				self::$driverClassName = "cboot_PeclZip";
			}
			else
			{
				self::$driverClassName = "cboot_PclZip";
			}
		}
		return new self::$driverClassName($zipPath);
	}

	/**
	 * @param String $zipPath
	 * @param String $dest
	 * @param String[] $entries
	 */
	static function unzip($zipPath, $dest, $entries = null)
	{
		$zip = self::getInstance($zipPath);
		$zip->extractTo($dest, $entries);
		$zip->close();
	}

	/**
	 * @param String $zipPath
	 * @param cboot_Zipcontent $content
	 */
	function zip($zipPath, $content)
	{
		if (file_exists($zipPath))
		{
			// TODO: handle directories, ... etc.
			unlink($zipPath);
		}
		$zip = self::getInstance($zipPath);
		$zip->add($content);
		$zip->close();
	}
}

class cboot_ZipContent
{
	private $entries;

	/**
	 * @example new cboot_ZipContent('afile', array('file2', 'file3'), array('file4' => 'localFile4Path'))
	 */
	function __construct()
	{
		foreach (func_get_args() as $arg)
		{
			if (is_array($arg))
			{
				if (empty($arg))
				{
					continue;
				}
				if (isset($arg[0]))
				{
					// numeric indexed
					foreach ($arg as $path)
					{
						$this->entries[$path] = null;
					}
				}
				else
				{
					// String indexed : means rewrited paths
					foreach ($arg as $path => $localPath)
					{
						$this->entries[$path] = $localPath;
					}
				}
			}
			else
			{
				$this->entries[$arg] = null;
			}
		}
	}

	/**
	 * @param String $fileOrDirectory
	 * @param String $localPath
	 * @return cboot_ZipContent
	 */
	function add($fileOrDirectory, $localPath = null)
	{
		$this->entries[$fileOrDirectory] = $localPath;
		return $this;
	}

	/**
	 * @param String $fileOrDirectory
	 * @param String $localPath
	 * @return cboot_ZipContent
	 */
	function addMultiple($filesOrDirectories)
	{
		foreach ($filesOrDirectories as $file)
		{
			$this->entries[$file] = $null;
		}
		return $this;
	}

	/**
	 * @return array<String, String|null>
	 */
	function getEntries()
	{
		return $this->entries;
	}
}

interface cboot_Zipper
{
	/**
	 * @param String $zipPath
	 */
	function __construct($zipPath);

	function close();

	/**
	 * @param String $path
	 * @param String[] $entries
	 */
	function extractTo($path, $entries = null);

	/**
	 * @param cboot_ZipContent $content
	 */
	function add($content);
}
/** End lib/Zip.php **/ ?>
<?php /** Begin lib/Configuration.php **/ ?>
<?php

class cboot_Configuration
{
	private static $instances = array();
	private static $propertiesLocation = array();
	
	private $name;
	
	private $properties = array();

	function __construct($name = null)
	{
		$this->name = $name;
	}

	static function getInstance($name)
	{
		if (!isset(self::$instances[$name]))
		{
			self::$instances[$name] = new self($name);
		}
		return self::$instances[$name];
	}

	function addLocation($path)
	{
		self::$propertiesLocation[] = $path;
	}
	
	function getLocations()
	{
		return self::$propertiesLocation;
	}

	/**
	 * @return cboot_Properties
	 */
	function getProperties($propFileName = null)
	{
		// echo __METHOD__." ".$propFileName."\n";
		if ($propFileName === null)
		{
			$propFileName = $this->name;
		}
		if (!isset($this->properties[$propFileName]))
		{
			// Load properties: first element has priority over the others
			$props = new cboot_Properties();
			foreach (array_reverse(self::$propertiesLocation) as $propLocation)
			{
				$propPath = $propLocation . "/".$propFileName.".properties";
				//echo $propPath."\n";
				if (is_file($propPath) && is_readable($propPath))
				{
					$props->load($propPath);
				}
			}
			$this->properties[$propFileName] = $props;
		}
		return $this->properties[$propFileName];
	}

	static function getFilePath($relativePath, $strict = true)
	{
		foreach (array_reverse(self::$propertiesLocation) as $propLocation)
		{
			$path = $propLocation."/".$relativePath;
			if (is_readable($path))
			{
				return $path;
			}
		}

		if ($strict)
		{
			throw new Exception("Could not find any readable '$relativePath' configuration file");
		}
		return null;
	}

	function getProperty($propFileName = null, $propertyName, $strict = true)
	{
		$props = $this->getProperties($propFileName);
		$propValue = $props->getProperty($propertyName);
		if ($propValue === null && $strict)
		{
			throw new Exception("Could not find $propertyName value in any configuration file");
		}
		return $propValue;
	}
}
/** End lib/Configuration.php **/ ?>
<?php /** Begin lib/StringUtils.php **/ ?>
<?php
class cboot_StringUtils
{
	const CASE_SENSITIVE   = 1;
	const CASE_INSENSITIVE = 2;
	
	static function startsWith($haystack, $needle, $caseSensitive = self::CASE_INSENSITIVE)
	{
		if ($caseSensitive === self::CASE_SENSITIVE)
		{
			return substr($haystack, 0, strlen($needle)) == $needle;
		}
		return strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
	}
}
/** End lib/StringUtils.php **/ ?>
<?php /** Begin lib/PclZip.php **/ ?>
<?php
if (!defined("PCLZIP_TEMPORARY_DIR"))
{
	$tmpDir = null;
	if (function_exists("sys_get_temp_dir"))
	{
		$tmpDir = sys_get_temp_dir();
	} else {
		$tmpDir = "/tmp";
	}

	define("PCLZIP_TEMPORARY_DIR", $tmpDir."/".uniqid("pclzip"));
}

class cboot_PclZip implements cboot_Zipper
{
	/**
	 * @var PclZip
	 */
	private $zip;
	/**
	 * @var String
	 */
	private $zipPath;

	function __construct($zipPath)
	{
		$this->zip = new PclZip($zipPath);
		$this->zipPath = $zipPath;
	}

	function close()
	{
		$this->zip = null;
	}

	/**
	 * @param String $path
	 * @param String[] $entries
	 */
	function extractTo($path, $entries = null)
	{
		if ($entries === null)
		{
			if ($this->zip->extract(PCLZIP_OPT_PATH, $path) == 0)
			{
				throw new Exception("Could not extract ".$this->zipPath." to $path: ".$this->zip->errorInfo(true));
			}
		}
		else
		{
			if ($this->zip->extract(PCLZIP_OPT_PATH, $path, PCLZIP_OPT_BY_NAME, $entries) == 0)
			{
				throw new Exception("Could not extract ".$this->zipPath." to $path: ".$this->zip->errorInfo(true));
			}
		}
	}

	/**
	 * @param cboot_ZipContent $content
	 */
	function add($content)
	{
		foreach ($content->getEntries() as $path => $localPath)
		{
			if ($localPath === null)
			{
				$this->zip->add($path);
			}
			else
			{
				if (basename($path) == basename($localPath))
				{
					if (is_dir($path))
					{
						$this->zip->add($path, PCLZIP_OPT_REMOVE_PATH, $path, PCLZIP_OPT_ADD_PATH, $localPath);
					}
					else
					{
						$this->zip->add($path, PCLZIP_OPT_REMOVE_PATH, dirname($path), PCLZIP_OPT_ADD_PATH, dirname($localPath));
					}
				}
				else
				{
					// TODO: directories ...
					$pathRenamed = $this->getWorkDir()."/".basename($localPath);
					if (!copy($path, $pathRenamed))
					{
						throw new Exception("Could not copy $path to $pathRenamed");
					}
					if (is_dir($path))
					{
						$this->zip->add($pathRenamed, PCLZIP_OPT_REMOVE_PATH, $pathRenamed, PCLZIP_OPT_ADD_PATH, $localPath);
					}
					else
					{
						$this->zip->add($pathRenamed, PCLZIP_OPT_REMOVE_PATH, dirname($pathRenamed), PCLZIP_OPT_ADD_PATH, dirname($localPath));
					}
				}
			}
		}
	}

	function getWorkDir()
	{
		if ($this->workDir === null)
		{
			$this->workDir = PCLZIP_TEMPORARY_DIR;
			if (!is_dir($this->workDir) && !mkdir($this->workDir, 0777, true))
			{
				throw new Exception("Could not create ".$this->workDir);
			}
		}

		return $this->workDir;
	}

	function __destruct()
	{
		if ($this->zip !== null)
		{
			$this->close();
			
		}
		if ($this->workDir !== null)
		{
			// TODO: PHP version
			exec("rm -rf ".$this->workDir);
		}
	}
}

/** End lib/PclZip.php **/ ?>
<?php /** Begin lib/pclzip.lib.php **/ ?>
<?php
// --------------------------------------------------------------------------------
// PhpConcept Library - Zip Module 2.8.2
// --------------------------------------------------------------------------------
// License GNU/LGPL - Vincent Blavet - August 2009
// http://www.phpconcept.net
// --------------------------------------------------------------------------------
//
// Presentation :
//   PclZip is a PHP library that manage ZIP archives.
//   So far tests show that archives generated by PclZip are readable by
//   WinZip application and other tools.
//
// Description :
//   See readme.txt and http://www.phpconcept.net
//
// Warning :
//   This library and the associated files are non commercial, non professional
//   work.
//   It should not have unexpected results. However if any damage is caused by
//   this software the author can not be responsible.
//   The use of this software is at the risk of the user.
//
// --------------------------------------------------------------------------------
// $Id: pclzip.lib.php,v 1.60 2009/09/30 21:01:04 vblavet Exp $
// --------------------------------------------------------------------------------

  // ----- Constants
  if (!defined('PCLZIP_READ_BLOCK_SIZE')) {
    define( 'PCLZIP_READ_BLOCK_SIZE', 2048 );
  }
  
  // ----- File list separator
  // In version 1.x of PclZip, the separator for file list is a space
  // (which is not a very smart choice, specifically for windows paths !).
  // A better separator should be a comma (,). This constant gives you the
  // abilty to change that.
  // However notice that changing this value, may have impact on existing
  // scripts, using space separated filenames.
  // Recommanded values for compatibility with older versions :
  //define( 'PCLZIP_SEPARATOR', ' ' );
  // Recommanded values for smart separation of filenames.
  if (!defined('PCLZIP_SEPARATOR')) {
    define( 'PCLZIP_SEPARATOR', ',' );
  }

  // ----- Error configuration
  // 0 : PclZip Class integrated error handling
  // 1 : PclError external library error handling. By enabling this
  //     you must ensure that you have included PclError library.
  // [2,...] : reserved for futur use
  if (!defined('PCLZIP_ERROR_EXTERNAL')) {
    define( 'PCLZIP_ERROR_EXTERNAL', 0 );
  }

  // ----- Optional static temporary directory
  //       By default temporary files are generated in the script current
  //       path.
  //       If defined :
  //       - MUST BE terminated by a '/'.
  //       - MUST be a valid, already created directory
  //       Samples :
  // define( 'PCLZIP_TEMPORARY_DIR', '/temp/' );
  // define( 'PCLZIP_TEMPORARY_DIR', 'C:/Temp/' );
  if (!defined('PCLZIP_TEMPORARY_DIR')) {
    define( 'PCLZIP_TEMPORARY_DIR', '' );
  }

  // ----- Optional threshold ratio for use of temporary files
  //       Pclzip sense the size of the file to add/extract and decide to
  //       use or not temporary file. The algorythm is looking for 
  //       memory_limit of PHP and apply a ratio.
  //       threshold = memory_limit * ratio.
  //       Recommended values are under 0.5. Default 0.47.
  //       Samples :
  // define( 'PCLZIP_TEMPORARY_FILE_RATIO', 0.5 );
  if (!defined('PCLZIP_TEMPORARY_FILE_RATIO')) {
    define( 'PCLZIP_TEMPORARY_FILE_RATIO', 0.47 );
  }

// --------------------------------------------------------------------------------
// ***** UNDER THIS LINE NOTHING NEEDS TO BE MODIFIED *****
// --------------------------------------------------------------------------------

  // ----- Global variables
  $g_pclzip_version = "2.8.2";

  // ----- Error codes
  //   -1 : Unable to open file in binary write mode
  //   -2 : Unable to open file in binary read mode
  //   -3 : Invalid parameters
  //   -4 : File does not exist
  //   -5 : Filename is too long (max. 255)
  //   -6 : Not a valid zip file
  //   -7 : Invalid extracted file size
  //   -8 : Unable to create directory
  //   -9 : Invalid archive extension
  //  -10 : Invalid archive format
  //  -11 : Unable to delete file (unlink)
  //  -12 : Unable to rename file (rename)
  //  -13 : Invalid header checksum
  //  -14 : Invalid archive size
  define( 'PCLZIP_ERR_USER_ABORTED', 2 );
  define( 'PCLZIP_ERR_NO_ERROR', 0 );
  define( 'PCLZIP_ERR_WRITE_OPEN_FAIL', -1 );
  define( 'PCLZIP_ERR_READ_OPEN_FAIL', -2 );
  define( 'PCLZIP_ERR_INVALID_PARAMETER', -3 );
  define( 'PCLZIP_ERR_MISSING_FILE', -4 );
  define( 'PCLZIP_ERR_FILENAME_TOO_LONG', -5 );
  define( 'PCLZIP_ERR_INVALID_ZIP', -6 );
  define( 'PCLZIP_ERR_BAD_EXTRACTED_FILE', -7 );
  define( 'PCLZIP_ERR_DIR_CREATE_FAIL', -8 );
  define( 'PCLZIP_ERR_BAD_EXTENSION', -9 );
  define( 'PCLZIP_ERR_BAD_FORMAT', -10 );
  define( 'PCLZIP_ERR_DELETE_FILE_FAIL', -11 );
  define( 'PCLZIP_ERR_RENAME_FILE_FAIL', -12 );
  define( 'PCLZIP_ERR_BAD_CHECKSUM', -13 );
  define( 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP', -14 );
  define( 'PCLZIP_ERR_MISSING_OPTION_VALUE', -15 );
  define( 'PCLZIP_ERR_INVALID_OPTION_VALUE', -16 );
  define( 'PCLZIP_ERR_ALREADY_A_DIRECTORY', -17 );
  define( 'PCLZIP_ERR_UNSUPPORTED_COMPRESSION', -18 );
  define( 'PCLZIP_ERR_UNSUPPORTED_ENCRYPTION', -19 );
  define( 'PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE', -20 );
  define( 'PCLZIP_ERR_DIRECTORY_RESTRICTION', -21 );

  // ----- Options values
  define( 'PCLZIP_OPT_PATH', 77001 );
  define( 'PCLZIP_OPT_ADD_PATH', 77002 );
  define( 'PCLZIP_OPT_REMOVE_PATH', 77003 );
  define( 'PCLZIP_OPT_REMOVE_ALL_PATH', 77004 );
  define( 'PCLZIP_OPT_SET_CHMOD', 77005 );
  define( 'PCLZIP_OPT_EXTRACT_AS_STRING', 77006 );
  define( 'PCLZIP_OPT_NO_COMPRESSION', 77007 );
  define( 'PCLZIP_OPT_BY_NAME', 77008 );
  define( 'PCLZIP_OPT_BY_INDEX', 77009 );
  define( 'PCLZIP_OPT_BY_EREG', 77010 );
  define( 'PCLZIP_OPT_BY_PREG', 77011 );
  define( 'PCLZIP_OPT_COMMENT', 77012 );
  define( 'PCLZIP_OPT_ADD_COMMENT', 77013 );
  define( 'PCLZIP_OPT_PREPEND_COMMENT', 77014 );
  define( 'PCLZIP_OPT_EXTRACT_IN_OUTPUT', 77015 );
  define( 'PCLZIP_OPT_REPLACE_NEWER', 77016 );
  define( 'PCLZIP_OPT_STOP_ON_ERROR', 77017 );
  // Having big trouble with crypt. Need to multiply 2 long int
  // which is not correctly supported by PHP ...
  //define( 'PCLZIP_OPT_CRYPT', 77018 );
  define( 'PCLZIP_OPT_EXTRACT_DIR_RESTRICTION', 77019 );
  define( 'PCLZIP_OPT_TEMP_FILE_THRESHOLD', 77020 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_THRESHOLD', 77020 ); // alias
  define( 'PCLZIP_OPT_TEMP_FILE_ON', 77021 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_ON', 77021 ); // alias
  define( 'PCLZIP_OPT_TEMP_FILE_OFF', 77022 );
  define( 'PCLZIP_OPT_ADD_TEMP_FILE_OFF', 77022 ); // alias
  
  // ----- File description attributes
  define( 'PCLZIP_ATT_FILE_NAME', 79001 );
  define( 'PCLZIP_ATT_FILE_NEW_SHORT_NAME', 79002 );
  define( 'PCLZIP_ATT_FILE_NEW_FULL_NAME', 79003 );
  define( 'PCLZIP_ATT_FILE_MTIME', 79004 );
  define( 'PCLZIP_ATT_FILE_CONTENT', 79005 );
  define( 'PCLZIP_ATT_FILE_COMMENT', 79006 );

  // ----- Call backs values
  define( 'PCLZIP_CB_PRE_EXTRACT', 78001 );
  define( 'PCLZIP_CB_POST_EXTRACT', 78002 );
  define( 'PCLZIP_CB_PRE_ADD', 78003 );
  define( 'PCLZIP_CB_POST_ADD', 78004 );
  /* For futur use
  define( 'PCLZIP_CB_PRE_LIST', 78005 );
  define( 'PCLZIP_CB_POST_LIST', 78006 );
  define( 'PCLZIP_CB_PRE_DELETE', 78007 );
  define( 'PCLZIP_CB_POST_DELETE', 78008 );
  */

  // --------------------------------------------------------------------------------
  // Class : PclZip
  // Description :
  //   PclZip is the class that represent a Zip archive.
  //   The public methods allow the manipulation of the archive.
  // Attributes :
  //   Attributes must not be accessed directly.
  // Methods :
  //   PclZip() : Object creator
  //   create() : Creates the Zip archive
  //   listContent() : List the content of the Zip archive
  //   extract() : Extract the content of the archive
  //   properties() : List the properties of the archive
  // --------------------------------------------------------------------------------
  class PclZip
  {
    // ----- Filename of the zip file
    var $zipname = '';

    // ----- File descriptor of the zip file
    var $zip_fd = 0;

    // ----- Internal error handling
    var $error_code = 1;
    var $error_string = '';
    
    // ----- Current status of the magic_quotes_runtime
    // This value store the php configuration for magic_quotes
    // The class can then disable the magic_quotes and reset it after
    var $magic_quotes_status;

  // --------------------------------------------------------------------------------
  // Function : PclZip()
  // Description :
  //   Creates a PclZip object and set the name of the associated Zip archive
  //   filename.
  //   Note that no real action is taken, if the archive does not exist it is not
  //   created. Use create() for that.
  // --------------------------------------------------------------------------------
  function PclZip($p_zipname)
  {

    // ----- Tests the zlib
    if (!function_exists('gzopen'))
    {
      die('Abort '.basename(__FILE__).' : Missing zlib extensions');
    }

    // ----- Set the attributes
    $this->zipname = $p_zipname;
    $this->zip_fd = 0;
    $this->magic_quotes_status = -1;

    // ----- Return
    return;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   create($p_filelist, $p_add_dir="", $p_remove_dir="")
  //   create($p_filelist, $p_option, $p_option_value, ...)
  // Description :
  //   This method supports two different synopsis. The first one is historical.
  //   This method creates a Zip Archive. The Zip file is created in the
  //   filesystem. The files and directories indicated in $p_filelist
  //   are added in the archive. See the parameters description for the
  //   supported format of $p_filelist.
  //   When a directory is in the list, the directory and its content is added
  //   in the archive.
  //   In this synopsis, the function takes an optional variable list of
  //   options. See bellow the supported options.
  // Parameters :
  //   $p_filelist : An array containing file or directory names, or
  //                 a string containing one filename or one directory name, or
  //                 a string containing a list of filenames and/or directory
  //                 names separated by spaces.
  //   $p_add_dir : A path to add before the real path of the archived file,
  //                in order to have it memorized in the archive.
  //   $p_remove_dir : A path to remove from the real path of the file to archive,
  //                   in order to have a shorter path memorized in the archive.
  //                   When $p_add_dir and $p_remove_dir are set, $p_remove_dir
  //                   is removed first, before $p_add_dir is added.
  // Options :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_COMMENT :
  //   PCLZIP_CB_PRE_ADD :
  //   PCLZIP_CB_POST_ADD :
  // Return Values :
  //   0 on failure,
  //   The list of the added files, with a status of the add action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function create($p_filelist)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Set default values
    $v_options = array();
    $v_options[PCLZIP_OPT_NO_COMPRESSION] = FALSE;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove from the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_ADD => 'optional',
                                                   PCLZIP_CB_POST_ADD => 'optional',
                                                   PCLZIP_OPT_NO_COMPRESSION => 'optional',
                                                   PCLZIP_OPT_COMMENT => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
                                                   //, PCLZIP_OPT_CRYPT => 'optional'
                                             ));
        if ($v_result != 1) {
          return 0;
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_options[PCLZIP_OPT_ADD_PATH] = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_options[PCLZIP_OPT_REMOVE_PATH] = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                       "Invalid number / type of arguments");
          return 0;
        }
      }
    }
    
    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Init
    $v_string_list = array();
    $v_att_list = array();
    $v_filedescr_list = array();
    $p_result_list = array();
    
    // ----- Look if the $p_filelist is really an array
    if (is_array($p_filelist)) {
    
      // ----- Look if the first element is also an array
      //       This will mean that this is a file description entry
      if (isset($p_filelist[0]) && is_array($p_filelist[0])) {
        $v_att_list = $p_filelist;
      }
      
      // ----- The list is a list of string names
      else {
        $v_string_list = $p_filelist;
      }
    }

    // ----- Look if the $p_filelist is a string
    else if (is_string($p_filelist)) {
      // ----- Create a list from the string
      $v_string_list = explode(PCLZIP_SEPARATOR, $p_filelist);
    }

    // ----- Invalid variable type for $p_filelist
    else {
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_filelist");
      return 0;
    }
    
    // ----- Reformat the string list
    if (sizeof($v_string_list) != 0) {
      foreach ($v_string_list as $v_string) {
        if ($v_string != '') {
          $v_att_list[][PCLZIP_ATT_FILE_NAME] = $v_string;
        }
        else {
        }
      }
    }
    
    // ----- For each file in the list check the attributes
    $v_supported_attributes
    = array ( PCLZIP_ATT_FILE_NAME => 'mandatory'
             ,PCLZIP_ATT_FILE_NEW_SHORT_NAME => 'optional'
             ,PCLZIP_ATT_FILE_NEW_FULL_NAME => 'optional'
             ,PCLZIP_ATT_FILE_MTIME => 'optional'
             ,PCLZIP_ATT_FILE_CONTENT => 'optional'
             ,PCLZIP_ATT_FILE_COMMENT => 'optional'
						);
    foreach ($v_att_list as $v_entry) {
      $v_result = $this->privFileDescrParseAtt($v_entry,
                                               $v_filedescr_list[],
                                               $v_options,
                                               $v_supported_attributes);
      if ($v_result != 1) {
        return 0;
      }
    }

    // ----- Expand the filelist (expand directories)
    $v_result = $this->privFileDescrExpand($v_filedescr_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Call the create fct
    $v_result = $this->privCreate($v_filedescr_list, $p_result_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Return
    return $p_result_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   add($p_filelist, $p_add_dir="", $p_remove_dir="")
  //   add($p_filelist, $p_option, $p_option_value, ...)
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This methods add the list of files in an existing archive.
  //   If a file with the same name already exists, it is added at the end of the
  //   archive, the first one is still present.
  //   If the archive does not exist, it is created.
  // Parameters :
  //   $p_filelist : An array containing file or directory names, or
  //                 a string containing one filename or one directory name, or
  //                 a string containing a list of filenames and/or directory
  //                 names separated by spaces.
  //   $p_add_dir : A path to add before the real path of the archived file,
  //                in order to have it memorized in the archive.
  //   $p_remove_dir : A path to remove from the real path of the file to archive,
  //                   in order to have a shorter path memorized in the archive.
  //                   When $p_add_dir and $p_remove_dir are set, $p_remove_dir
  //                   is removed first, before $p_add_dir is added.
  // Options :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_COMMENT :
  //   PCLZIP_OPT_ADD_COMMENT :
  //   PCLZIP_OPT_PREPEND_COMMENT :
  //   PCLZIP_CB_PRE_ADD :
  //   PCLZIP_CB_POST_ADD :
  // Return Values :
  //   0 on failure,
  //   The list of the added files, with a status of the add action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function add($p_filelist)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Set default values
    $v_options = array();
    $v_options[PCLZIP_OPT_NO_COMPRESSION] = FALSE;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove form the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_ADD => 'optional',
                                                   PCLZIP_CB_POST_ADD => 'optional',
                                                   PCLZIP_OPT_NO_COMPRESSION => 'optional',
                                                   PCLZIP_OPT_COMMENT => 'optional',
                                                   PCLZIP_OPT_ADD_COMMENT => 'optional',
                                                   PCLZIP_OPT_PREPEND_COMMENT => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
                                                   //, PCLZIP_OPT_CRYPT => 'optional'
												   ));
        if ($v_result != 1) {
          return 0;
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_options[PCLZIP_OPT_ADD_PATH] = $v_add_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_options[PCLZIP_OPT_REMOVE_PATH] = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Init
    $v_string_list = array();
    $v_att_list = array();
    $v_filedescr_list = array();
    $p_result_list = array();
    
    // ----- Look if the $p_filelist is really an array
    if (is_array($p_filelist)) {
    
      // ----- Look if the first element is also an array
      //       This will mean that this is a file description entry
      if (isset($p_filelist[0]) && is_array($p_filelist[0])) {
        $v_att_list = $p_filelist;
      }
      
      // ----- The list is a list of string names
      else {
        $v_string_list = $p_filelist;
      }
    }

    // ----- Look if the $p_filelist is a string
    else if (is_string($p_filelist)) {
      // ----- Create a list from the string
      $v_string_list = explode(PCLZIP_SEPARATOR, $p_filelist);
    }

    // ----- Invalid variable type for $p_filelist
    else {
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type '".gettype($p_filelist)."' for p_filelist");
      return 0;
    }
    
    // ----- Reformat the string list
    if (sizeof($v_string_list) != 0) {
      foreach ($v_string_list as $v_string) {
        $v_att_list[][PCLZIP_ATT_FILE_NAME] = $v_string;
      }
    }
    
    // ----- For each file in the list check the attributes
    $v_supported_attributes
    = array ( PCLZIP_ATT_FILE_NAME => 'mandatory'
             ,PCLZIP_ATT_FILE_NEW_SHORT_NAME => 'optional'
             ,PCLZIP_ATT_FILE_NEW_FULL_NAME => 'optional'
             ,PCLZIP_ATT_FILE_MTIME => 'optional'
             ,PCLZIP_ATT_FILE_CONTENT => 'optional'
             ,PCLZIP_ATT_FILE_COMMENT => 'optional'
						);
    foreach ($v_att_list as $v_entry) {
      $v_result = $this->privFileDescrParseAtt($v_entry,
                                               $v_filedescr_list[],
                                               $v_options,
                                               $v_supported_attributes);
      if ($v_result != 1) {
        return 0;
      }
    }

    // ----- Expand the filelist (expand directories)
    $v_result = $this->privFileDescrExpand($v_filedescr_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Call the create fct
    $v_result = $this->privAdd($v_filedescr_list, $p_result_list, $v_options);
    if ($v_result != 1) {
      return 0;
    }

    // ----- Return
    return $p_result_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : listContent()
  // Description :
  //   This public method, gives the list of the files and directories, with their
  //   properties.
  //   The properties of each entries in the list are (used also in other functions) :
  //     filename : Name of the file. For a create or add action it is the filename
  //                given by the user. For an extract function it is the filename
  //                of the extracted file.
  //     stored_filename : Name of the file / directory stored in the archive.
  //     size : Size of the stored file.
  //     compressed_size : Size of the file's data compressed in the archive
  //                       (without the headers overhead)
  //     mtime : Last known modification date of the file (UNIX timestamp)
  //     comment : Comment associated with the file
  //     folder : true | false
  //     index : index of the file in the archive
  //     status : status of the action (depending of the action) :
  //              Values are :
  //                ok : OK !
  //                filtered : the file / dir is not extracted (filtered by user)
  //                already_a_directory : the file can not be extracted because a
  //                                      directory with the same name already exists
  //                write_protected : the file can not be extracted because a file
  //                                  with the same name already exists and is
  //                                  write protected
  //                newer_exist : the file was not extracted because a newer file exists
  //                path_creation_fail : the file is not extracted because the folder
  //                                     does not exist and can not be created
  //                write_error : the file was not extracted because there was a
  //                              error while writing the file
  //                read_error : the file was not extracted because there was a error
  //                             while reading the file
  //                invalid_header : the file was not extracted because of an archive
  //                                 format error (bad file header)
  //   Note that each time a method can continue operating when there
  //   is an action error on a file, the error is only logged in the file status.
  // Return Values :
  //   0 on an unrecoverable failure,
  //   The list of the files in the archive.
  // --------------------------------------------------------------------------------
  function listContent()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Call the extracting fct
    $p_list = array();
    if (($v_result = $this->privList($p_list)) != 1)
    {
      unset($p_list);
      return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   extract($p_path="./", $p_remove_path="")
  //   extract([$p_option, $p_option_value, ...])
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This method extract all the files / directories from the archive to the
  //   folder indicated in $p_path.
  //   If you want to ignore the 'root' part of path of the memorized files
  //   you can indicate this in the optional $p_remove_path parameter.
  //   By default, if a newer file with the same name already exists, the
  //   file is not extracted.
  //
  //   If both PCLZIP_OPT_PATH and PCLZIP_OPT_ADD_PATH aoptions
  //   are used, the path indicated in PCLZIP_OPT_ADD_PATH is append
  //   at the end of the path value of PCLZIP_OPT_PATH.
  // Parameters :
  //   $p_path : Path where the files and directories are to be extracted
  //   $p_remove_path : First part ('root' part) of the memorized path
  //                    (if any similar) to remove while extracting.
  // Options :
  //   PCLZIP_OPT_PATH :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_CB_PRE_EXTRACT :
  //   PCLZIP_CB_POST_EXTRACT :
  // Return Values :
  //   0 or a negative value on failure,
  //   The list of the extracted files, with a status of the action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function extract()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();
//    $v_path = "./";
    $v_path = '';
    $v_remove_path = "";
    $v_remove_all_path = false;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Default values for option
    $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;

    // ----- Look for arguments
    if ($v_size > 0) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_EXTRACT => 'optional',
                                                   PCLZIP_CB_POST_EXTRACT => 'optional',
                                                   PCLZIP_OPT_SET_CHMOD => 'optional',
                                                   PCLZIP_OPT_BY_NAME => 'optional',
                                                   PCLZIP_OPT_BY_EREG => 'optional',
                                                   PCLZIP_OPT_BY_PREG => 'optional',
                                                   PCLZIP_OPT_BY_INDEX => 'optional',
                                                   PCLZIP_OPT_EXTRACT_AS_STRING => 'optional',
                                                   PCLZIP_OPT_EXTRACT_IN_OUTPUT => 'optional',
                                                   PCLZIP_OPT_REPLACE_NEWER => 'optional'
                                                   ,PCLZIP_OPT_STOP_ON_ERROR => 'optional'
                                                   ,PCLZIP_OPT_EXTRACT_DIR_RESTRICTION => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
												    ));
        if ($v_result != 1) {
          return 0;
        }

        // ----- Set the arguments
        if (isset($v_options[PCLZIP_OPT_PATH])) {
          $v_path = $v_options[PCLZIP_OPT_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_PATH])) {
          $v_remove_path = $v_options[PCLZIP_OPT_REMOVE_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
          $v_remove_all_path = $v_options[PCLZIP_OPT_REMOVE_ALL_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_ADD_PATH])) {
          // ----- Check for '/' in last path char
          if ((strlen($v_path) > 0) && (substr($v_path, -1) != '/')) {
            $v_path .= '/';
          }
          $v_path .= $v_options[PCLZIP_OPT_ADD_PATH];
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_remove_path = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Trace

    // ----- Call the extracting fct
    $p_list = array();
    $v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path,
	                                     $v_remove_all_path, $v_options);
    if ($v_result < 1) {
      unset($p_list);
      return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------


  // --------------------------------------------------------------------------------
  // Function :
  //   extractByIndex($p_index, $p_path="./", $p_remove_path="")
  //   extractByIndex($p_index, [$p_option, $p_option_value, ...])
  // Description :
  //   This method supports two synopsis. The first one is historical.
  //   This method is doing a partial extract of the archive.
  //   The extracted files or folders are identified by their index in the
  //   archive (from 0 to n).
  //   Note that if the index identify a folder, only the folder entry is
  //   extracted, not all the files included in the archive.
  // Parameters :
  //   $p_index : A single index (integer) or a string of indexes of files to
  //              extract. The form of the string is "0,4-6,8-12" with only numbers
  //              and '-' for range or ',' to separate ranges. No spaces or ';'
  //              are allowed.
  //   $p_path : Path where the files and directories are to be extracted
  //   $p_remove_path : First part ('root' part) of the memorized path
  //                    (if any similar) to remove while extracting.
  // Options :
  //   PCLZIP_OPT_PATH :
  //   PCLZIP_OPT_ADD_PATH :
  //   PCLZIP_OPT_REMOVE_PATH :
  //   PCLZIP_OPT_REMOVE_ALL_PATH :
  //   PCLZIP_OPT_EXTRACT_AS_STRING : The files are extracted as strings and
  //     not as files.
  //     The resulting content is in a new field 'content' in the file
  //     structure.
  //     This option must be used alone (any other options are ignored).
  //   PCLZIP_CB_PRE_EXTRACT :
  //   PCLZIP_CB_POST_EXTRACT :
  // Return Values :
  //   0 on failure,
  //   The list of the extracted files, with a status of the action.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  //function extractByIndex($p_index, options...)
  function extractByIndex($p_index)
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();
//    $v_path = "./";
    $v_path = '';
    $v_remove_path = "";
    $v_remove_all_path = false;

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Default values for option
    $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;

    // ----- Look for arguments
    if ($v_size > 1) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Remove form the options list the first argument
      array_shift($v_arg_list);
      $v_size--;

      // ----- Look for first arg
      if ((is_integer($v_arg_list[0])) && ($v_arg_list[0] > 77000)) {

        // ----- Parse the options
        $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                            array (PCLZIP_OPT_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_PATH => 'optional',
                                                   PCLZIP_OPT_REMOVE_ALL_PATH => 'optional',
                                                   PCLZIP_OPT_EXTRACT_AS_STRING => 'optional',
                                                   PCLZIP_OPT_ADD_PATH => 'optional',
                                                   PCLZIP_CB_PRE_EXTRACT => 'optional',
                                                   PCLZIP_CB_POST_EXTRACT => 'optional',
                                                   PCLZIP_OPT_SET_CHMOD => 'optional',
                                                   PCLZIP_OPT_REPLACE_NEWER => 'optional'
                                                   ,PCLZIP_OPT_STOP_ON_ERROR => 'optional'
                                                   ,PCLZIP_OPT_EXTRACT_DIR_RESTRICTION => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_THRESHOLD => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_ON => 'optional',
                                                   PCLZIP_OPT_TEMP_FILE_OFF => 'optional'
												   ));
        if ($v_result != 1) {
          return 0;
        }

        // ----- Set the arguments
        if (isset($v_options[PCLZIP_OPT_PATH])) {
          $v_path = $v_options[PCLZIP_OPT_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_PATH])) {
          $v_remove_path = $v_options[PCLZIP_OPT_REMOVE_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
          $v_remove_all_path = $v_options[PCLZIP_OPT_REMOVE_ALL_PATH];
        }
        if (isset($v_options[PCLZIP_OPT_ADD_PATH])) {
          // ----- Check for '/' in last path char
          if ((strlen($v_path) > 0) && (substr($v_path, -1) != '/')) {
            $v_path .= '/';
          }
          $v_path .= $v_options[PCLZIP_OPT_ADD_PATH];
        }
        if (!isset($v_options[PCLZIP_OPT_EXTRACT_AS_STRING])) {
          $v_options[PCLZIP_OPT_EXTRACT_AS_STRING] = FALSE;
        }
        else {
        }
      }

      // ----- Look for 2 args
      // Here we need to support the first historic synopsis of the
      // method.
      else {

        // ----- Get the first argument
        $v_path = $v_arg_list[0];

        // ----- Look for the optional second argument
        if ($v_size == 2) {
          $v_remove_path = $v_arg_list[1];
        }
        else if ($v_size > 2) {
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid number / type of arguments");

          // ----- Return
          return 0;
        }
      }
    }

    // ----- Trace

    // ----- Trick
    // Here I want to reuse extractByRule(), so I need to parse the $p_index
    // with privParseOptions()
    $v_arg_trick = array (PCLZIP_OPT_BY_INDEX, $p_index);
    $v_options_trick = array();
    $v_result = $this->privParseOptions($v_arg_trick, sizeof($v_arg_trick), $v_options_trick,
                                        array (PCLZIP_OPT_BY_INDEX => 'optional' ));
    if ($v_result != 1) {
        return 0;
    }
    $v_options[PCLZIP_OPT_BY_INDEX] = $v_options_trick[PCLZIP_OPT_BY_INDEX];

    // ----- Look for default option values
    $this->privOptionDefaultThreshold($v_options);

    // ----- Call the extracting fct
    if (($v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path, $v_remove_all_path, $v_options)) < 1) {
        return(0);
    }

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function :
  //   delete([$p_option, $p_option_value, ...])
  // Description :
  //   This method removes files from the archive.
  //   If no parameters are given, then all the archive is emptied.
  // Parameters :
  //   None or optional arguments.
  // Options :
  //   PCLZIP_OPT_BY_INDEX :
  //   PCLZIP_OPT_BY_NAME :
  //   PCLZIP_OPT_BY_EREG : 
  //   PCLZIP_OPT_BY_PREG :
  // Return Values :
  //   0 on failure,
  //   The list of the files which are still present in the archive.
  //   (see PclZip::listContent() for list entry format)
  // --------------------------------------------------------------------------------
  function delete()
  {
    $v_result=1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Set default values
    $v_options = array();

    // ----- Look for variable options arguments
    $v_size = func_num_args();

    // ----- Look for arguments
    if ($v_size > 0) {
      // ----- Get the arguments
      $v_arg_list = func_get_args();

      // ----- Parse the options
      $v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options,
                                        array (PCLZIP_OPT_BY_NAME => 'optional',
                                               PCLZIP_OPT_BY_EREG => 'optional',
                                               PCLZIP_OPT_BY_PREG => 'optional',
                                               PCLZIP_OPT_BY_INDEX => 'optional' ));
      if ($v_result != 1) {
          return 0;
      }
    }

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Call the delete fct
    $v_list = array();
    if (($v_result = $this->privDeleteByRule($v_list, $v_options)) != 1) {
      $this->privSwapBackMagicQuotes();
      unset($v_list);
      return(0);
    }

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : deleteByIndex()
  // Description :
  //   ***** Deprecated *****
  //   delete(PCLZIP_OPT_BY_INDEX, $p_index) should be prefered.
  // --------------------------------------------------------------------------------
  function deleteByIndex($p_index)
  {
    
    $p_list = $this->delete(PCLZIP_OPT_BY_INDEX, $p_index);

    // ----- Return
    return $p_list;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : properties()
  // Description :
  //   This method gives the properties of the archive.
  //   The properties are :
  //     nb : Number of files in the archive
  //     comment : Comment associated with the archive file
  //     status : not_exist, ok
  // Parameters :
  //   None
  // Return Values :
  //   0 on failure,
  //   An array with the archive properties.
  // --------------------------------------------------------------------------------
  function properties()
  {

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      $this->privSwapBackMagicQuotes();
      return(0);
    }

    // ----- Default properties
    $v_prop = array();
    $v_prop['comment'] = '';
    $v_prop['nb'] = 0;
    $v_prop['status'] = 'not_exist';

    // ----- Look if file exists
    if (@is_file($this->zipname))
    {
      // ----- Open the zip file
      if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0)
      {
        $this->privSwapBackMagicQuotes();
        
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in binary read mode');

        // ----- Return
        return 0;
      }

      // ----- Read the central directory informations
      $v_central_dir = array();
      if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
      {
        $this->privSwapBackMagicQuotes();
        return 0;
      }

      // ----- Close the zip file
      $this->privCloseFd();

      // ----- Set the user attributes
      $v_prop['comment'] = $v_central_dir['comment'];
      $v_prop['nb'] = $v_central_dir['entries'];
      $v_prop['status'] = 'ok';
    }

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_prop;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : duplicate()
  // Description :
  //   This method creates an archive by copying the content of an other one. If
  //   the archive already exist, it is replaced by the new one without any warning.
  // Parameters :
  //   $p_archive : The filename of a valid archive, or
  //                a valid PclZip object.
  // Return Values :
  //   1 on success.
  //   0 or a negative value on error (error code).
  // --------------------------------------------------------------------------------
  function duplicate($p_archive)
  {
    $v_result = 1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Look if the $p_archive is a PclZip object
    if ((is_object($p_archive)) && (get_class($p_archive) == 'pclzip'))
    {

      // ----- Duplicate the archive
      $v_result = $this->privDuplicate($p_archive->zipname);
    }

    // ----- Look if the $p_archive is a string (so a filename)
    else if (is_string($p_archive))
    {

      // ----- Check that $p_archive is a valid zip file
      // TBC : Should also check the archive format
      if (!is_file($p_archive)) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "No file with filename '".$p_archive."'");
        $v_result = PCLZIP_ERR_MISSING_FILE;
      }
      else {
        // ----- Duplicate the archive
        $v_result = $this->privDuplicate($p_archive);
      }
    }

    // ----- Invalid variable
    else
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_archive_to_add");
      $v_result = PCLZIP_ERR_INVALID_PARAMETER;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : merge()
  // Description :
  //   This method merge the $p_archive_to_add archive at the end of the current
  //   one ($this).
  //   If the archive ($this) does not exist, the merge becomes a duplicate.
  //   If the $p_archive_to_add archive does not exist, the merge is a success.
  // Parameters :
  //   $p_archive_to_add : It can be directly the filename of a valid zip archive,
  //                       or a PclZip object archive.
  // Return Values :
  //   1 on success,
  //   0 or negative values on error (see below).
  // --------------------------------------------------------------------------------
  function merge($p_archive_to_add)
  {
    $v_result = 1;

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Check archive
    if (!$this->privCheckFormat()) {
      return(0);
    }

    // ----- Look if the $p_archive_to_add is a PclZip object
    if ((is_object($p_archive_to_add)) && (get_class($p_archive_to_add) == 'pclzip'))
    {

      // ----- Merge the archive
      $v_result = $this->privMerge($p_archive_to_add);
    }

    // ----- Look if the $p_archive_to_add is a string (so a filename)
    else if (is_string($p_archive_to_add))
    {

      // ----- Create a temporary archive
      $v_object_archive = new PclZip($p_archive_to_add);

      // ----- Merge the archive
      $v_result = $this->privMerge($v_object_archive);
    }

    // ----- Invalid variable
    else
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid variable type p_archive_to_add");
      $v_result = PCLZIP_ERR_INVALID_PARAMETER;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------



  // --------------------------------------------------------------------------------
  // Function : errorCode()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorCode()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorCode());
    }
    else {
      return($this->error_code);
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : errorName()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorName($p_with_code=false)
  {
    $v_name = array ( PCLZIP_ERR_NO_ERROR => 'PCLZIP_ERR_NO_ERROR',
                      PCLZIP_ERR_WRITE_OPEN_FAIL => 'PCLZIP_ERR_WRITE_OPEN_FAIL',
                      PCLZIP_ERR_READ_OPEN_FAIL => 'PCLZIP_ERR_READ_OPEN_FAIL',
                      PCLZIP_ERR_INVALID_PARAMETER => 'PCLZIP_ERR_INVALID_PARAMETER',
                      PCLZIP_ERR_MISSING_FILE => 'PCLZIP_ERR_MISSING_FILE',
                      PCLZIP_ERR_FILENAME_TOO_LONG => 'PCLZIP_ERR_FILENAME_TOO_LONG',
                      PCLZIP_ERR_INVALID_ZIP => 'PCLZIP_ERR_INVALID_ZIP',
                      PCLZIP_ERR_BAD_EXTRACTED_FILE => 'PCLZIP_ERR_BAD_EXTRACTED_FILE',
                      PCLZIP_ERR_DIR_CREATE_FAIL => 'PCLZIP_ERR_DIR_CREATE_FAIL',
                      PCLZIP_ERR_BAD_EXTENSION => 'PCLZIP_ERR_BAD_EXTENSION',
                      PCLZIP_ERR_BAD_FORMAT => 'PCLZIP_ERR_BAD_FORMAT',
                      PCLZIP_ERR_DELETE_FILE_FAIL => 'PCLZIP_ERR_DELETE_FILE_FAIL',
                      PCLZIP_ERR_RENAME_FILE_FAIL => 'PCLZIP_ERR_RENAME_FILE_FAIL',
                      PCLZIP_ERR_BAD_CHECKSUM => 'PCLZIP_ERR_BAD_CHECKSUM',
                      PCLZIP_ERR_INVALID_ARCHIVE_ZIP => 'PCLZIP_ERR_INVALID_ARCHIVE_ZIP',
                      PCLZIP_ERR_MISSING_OPTION_VALUE => 'PCLZIP_ERR_MISSING_OPTION_VALUE',
                      PCLZIP_ERR_INVALID_OPTION_VALUE => 'PCLZIP_ERR_INVALID_OPTION_VALUE',
                      PCLZIP_ERR_UNSUPPORTED_COMPRESSION => 'PCLZIP_ERR_UNSUPPORTED_COMPRESSION',
                      PCLZIP_ERR_UNSUPPORTED_ENCRYPTION => 'PCLZIP_ERR_UNSUPPORTED_ENCRYPTION'
                      ,PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE => 'PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE'
                      ,PCLZIP_ERR_DIRECTORY_RESTRICTION => 'PCLZIP_ERR_DIRECTORY_RESTRICTION'
                    );

    if (isset($v_name[$this->error_code])) {
      $v_value = $v_name[$this->error_code];
    }
    else {
      $v_value = 'NoName';
    }

    if ($p_with_code) {
      return($v_value.' ('.$this->error_code.')');
    }
    else {
      return($v_value);
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : errorInfo()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function errorInfo($p_full=false)
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      return(PclErrorString());
    }
    else {
      if ($p_full) {
        return($this->errorName(true)." : ".$this->error_string);
      }
      else {
        return($this->error_string." [code ".$this->error_code."]");
      }
    }
  }
  // --------------------------------------------------------------------------------


// --------------------------------------------------------------------------------
// ***** UNDER THIS LINE ARE DEFINED PRIVATE INTERNAL FUNCTIONS *****
// *****                                                        *****
// *****       THESES FUNCTIONS MUST NOT BE USED DIRECTLY       *****
// --------------------------------------------------------------------------------



  // --------------------------------------------------------------------------------
  // Function : privCheckFormat()
  // Description :
  //   This method check that the archive exists and is a valid zip archive.
  //   Several level of check exists. (futur)
  // Parameters :
  //   $p_level : Level of check. Default 0.
  //              0 : Check the first bytes (magic codes) (default value))
  //              1 : 0 + Check the central directory (futur)
  //              2 : 1 + Check each file header (futur)
  // Return Values :
  //   true on success,
  //   false on error, the error code is set.
  // --------------------------------------------------------------------------------
  function privCheckFormat($p_level=0)
  {
    $v_result = true;

	// ----- Reset the file system cache
    clearstatcache();

    // ----- Reset the error handler
    $this->privErrorReset();

    // ----- Look if the file exits
    if (!is_file($this->zipname)) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "Missing archive file '".$this->zipname."'");
      return(false);
    }

    // ----- Check that the file is readeable
    if (!is_readable($this->zipname)) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to read archive '".$this->zipname."'");
      return(false);
    }

    // ----- Check the magic code
    // TBC

    // ----- Check the central header
    // TBC

    // ----- Check each file header
    // TBC

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privParseOptions()
  // Description :
  //   This internal methods reads the variable list of arguments ($p_options_list,
  //   $p_size) and generate an array with the options and values ($v_result_list).
  //   $v_requested_options contains the options that can be present and those that
  //   must be present.
  //   $v_requested_options is an array, with the option value as key, and 'optional',
  //   or 'mandatory' as value.
  // Parameters :
  //   See above.
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privParseOptions(&$p_options_list, $p_size, &$v_result_list, $v_requested_options=false)
  {
    $v_result=1;
    
    // ----- Read the options
    $i=0;
    while ($i<$p_size) {

      // ----- Check if the option is supported
      if (!isset($v_requested_options[$p_options_list[$i]])) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid optional parameter '".$p_options_list[$i]."' for this method");

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Look for next option
      switch ($p_options_list[$i]) {
        // ----- Look for options that request a path value
        case PCLZIP_OPT_PATH :
        case PCLZIP_OPT_REMOVE_PATH :
        case PCLZIP_OPT_ADD_PATH :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_result_list[$p_options_list[$i]] = PclZipUtilTranslateWinPath($p_options_list[$i+1], FALSE);
          $i++;
        break;

        case PCLZIP_OPT_TEMP_FILE_THRESHOLD :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");
            return PclZip::errorCode();
          }
          
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_OFF])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_OFF'");
            return PclZip::errorCode();
          }
          
          // ----- Check the value
          $v_value = $p_options_list[$i+1];
          if ((!is_integer($v_value)) || ($v_value<0)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Integer expected for option '".PclZipUtilOptionText($p_options_list[$i])."'");
            return PclZip::errorCode();
          }

          // ----- Get the value (and convert it in bytes)
          $v_result_list[$p_options_list[$i]] = $v_value*1048576;
          $i++;
        break;

        case PCLZIP_OPT_TEMP_FILE_ON :
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_OFF])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_OFF'");
            return PclZip::errorCode();
          }
          
          $v_result_list[$p_options_list[$i]] = true;
        break;

        case PCLZIP_OPT_TEMP_FILE_OFF :
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_ON])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_ON'");
            return PclZip::errorCode();
          }
          // ----- Check for incompatible options
          if (isset($v_result_list[PCLZIP_OPT_TEMP_FILE_THRESHOLD])) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Option '".PclZipUtilOptionText($p_options_list[$i])."' can not be used with option 'PCLZIP_OPT_TEMP_FILE_THRESHOLD'");
            return PclZip::errorCode();
          }
          
          $v_result_list[$p_options_list[$i]] = true;
        break;

        case PCLZIP_OPT_EXTRACT_DIR_RESTRICTION :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (   is_string($p_options_list[$i+1])
              && ($p_options_list[$i+1] != '')) {
            $v_result_list[$p_options_list[$i]] = PclZipUtilTranslateWinPath($p_options_list[$i+1], FALSE);
            $i++;
          }
          else {
          }
        break;

        // ----- Look for options that request an array of string for value
        case PCLZIP_OPT_BY_NAME :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]][0] = $p_options_list[$i+1];
          }
          else if (is_array($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that request an EREG or PREG expression
        case PCLZIP_OPT_BY_EREG :
          // ereg() is deprecated starting with PHP 5.3. Move PCLZIP_OPT_BY_EREG
          // to PCLZIP_OPT_BY_PREG
          $p_options_list[$i] = PCLZIP_OPT_BY_PREG;
        case PCLZIP_OPT_BY_PREG :
        //case PCLZIP_OPT_CRYPT :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Wrong parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that takes a string
        case PCLZIP_OPT_COMMENT :
        case PCLZIP_OPT_ADD_COMMENT :
        case PCLZIP_OPT_PREPEND_COMMENT :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE,
			                     "Missing parameter value for option '"
								 .PclZipUtilOptionText($p_options_list[$i])
								 ."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          if (is_string($p_options_list[$i+1])) {
              $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE,
			                     "Wrong parameter value for option '"
								 .PclZipUtilOptionText($p_options_list[$i])
								 ."'");

            // ----- Return
            return PclZip::errorCode();
          }
          $i++;
        break;

        // ----- Look for options that request an array of index
        case PCLZIP_OPT_BY_INDEX :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_work_list = array();
          if (is_string($p_options_list[$i+1])) {

              // ----- Remove spaces
              $p_options_list[$i+1] = strtr($p_options_list[$i+1], ' ', '');

              // ----- Parse items
              $v_work_list = explode(",", $p_options_list[$i+1]);
          }
          else if (is_integer($p_options_list[$i+1])) {
              $v_work_list[0] = $p_options_list[$i+1].'-'.$p_options_list[$i+1];
          }
          else if (is_array($p_options_list[$i+1])) {
              $v_work_list = $p_options_list[$i+1];
          }
          else {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Value must be integer, string or array for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }
          
          // ----- Reduce the index list
          // each index item in the list must be a couple with a start and
          // an end value : [0,3], [5-5], [8-10], ...
          // ----- Check the format of each item
          $v_sort_flag=false;
          $v_sort_value=0;
          for ($j=0; $j<sizeof($v_work_list); $j++) {
              // ----- Explode the item
              $v_item_list = explode("-", $v_work_list[$j]);
              $v_size_item_list = sizeof($v_item_list);
              
              // ----- TBC : Here we might check that each item is a
              // real integer ...
              
              // ----- Look for single value
              if ($v_size_item_list == 1) {
                  // ----- Set the option value
                  $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
                  $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[0];
              }
              elseif ($v_size_item_list == 2) {
                  // ----- Set the option value
                  $v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
                  $v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[1];
              }
              else {
                  // ----- Error log
                  PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Too many values in index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");

                  // ----- Return
                  return PclZip::errorCode();
              }


              // ----- Look for list sort
              if ($v_result_list[$p_options_list[$i]][$j]['start'] < $v_sort_value) {
                  $v_sort_flag=true;

                  // ----- TBC : An automatic sort should be writen ...
                  // ----- Error log
                  PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Invalid order of index range for option '".PclZipUtilOptionText($p_options_list[$i])."'");

                  // ----- Return
                  return PclZip::errorCode();
              }
              $v_sort_value = $v_result_list[$p_options_list[$i]][$j]['start'];
          }
          
          // ----- Sort the items
          if ($v_sort_flag) {
              // TBC : To Be Completed
          }

          // ----- Next option
          $i++;
        break;

        // ----- Look for options that request no value
        case PCLZIP_OPT_REMOVE_ALL_PATH :
        case PCLZIP_OPT_EXTRACT_AS_STRING :
        case PCLZIP_OPT_NO_COMPRESSION :
        case PCLZIP_OPT_EXTRACT_IN_OUTPUT :
        case PCLZIP_OPT_REPLACE_NEWER :
        case PCLZIP_OPT_STOP_ON_ERROR :
          $v_result_list[$p_options_list[$i]] = true;
        break;

        // ----- Look for options that request an octal value
        case PCLZIP_OPT_SET_CHMOD :
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_result_list[$p_options_list[$i]] = $p_options_list[$i+1];
          $i++;
        break;

        // ----- Look for options that request a call-back
        case PCLZIP_CB_PRE_EXTRACT :
        case PCLZIP_CB_POST_EXTRACT :
        case PCLZIP_CB_PRE_ADD :
        case PCLZIP_CB_POST_ADD :
        /* for futur use
        case PCLZIP_CB_PRE_DELETE :
        case PCLZIP_CB_POST_DELETE :
        case PCLZIP_CB_PRE_LIST :
        case PCLZIP_CB_POST_LIST :
        */
          // ----- Check the number of parameters
          if (($i+1) >= $p_size) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_MISSING_OPTION_VALUE, "Missing parameter value for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Get the value
          $v_function_name = $p_options_list[$i+1];

          // ----- Check that the value is a valid existing function
          if (!function_exists($v_function_name)) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_OPTION_VALUE, "Function '".$v_function_name."()' is not an existing function for option '".PclZipUtilOptionText($p_options_list[$i])."'");

            // ----- Return
            return PclZip::errorCode();
          }

          // ----- Set the attribute
          $v_result_list[$p_options_list[$i]] = $v_function_name;
          $i++;
        break;

        default :
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                       "Unknown parameter '"
							   .$p_options_list[$i]."'");

          // ----- Return
          return PclZip::errorCode();
      }

      // ----- Next options
      $i++;
    }

    // ----- Look for mandatory options
    if ($v_requested_options !== false) {
      for ($key=reset($v_requested_options); $key=key($v_requested_options); $key=next($v_requested_options)) {
        // ----- Look for mandatory option
        if ($v_requested_options[$key] == 'mandatory') {
          // ----- Look if present
          if (!isset($v_result_list[$key])) {
            // ----- Error log
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Missing mandatory parameter ".PclZipUtilOptionText($key)."(".$key.")");

            // ----- Return
            return PclZip::errorCode();
          }
        }
      }
    }
    
    // ----- Look for default values
    if (!isset($v_result_list[PCLZIP_OPT_TEMP_FILE_THRESHOLD])) {
      
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privOptionDefaultThreshold()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privOptionDefaultThreshold(&$p_options)
  {
    $v_result=1;
    
    if (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
        || isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) {
      return $v_result;
    }
    
    // ----- Get 'memory_limit' configuration value
    $v_memory_limit = ini_get('memory_limit');
    $v_memory_limit = trim($v_memory_limit);
    $last = strtolower(substr($v_memory_limit, -1));
 
    if($last == 'g')
        //$v_memory_limit = $v_memory_limit*1024*1024*1024;
        $v_memory_limit = $v_memory_limit*1073741824;
    if($last == 'm')
        //$v_memory_limit = $v_memory_limit*1024*1024;
        $v_memory_limit = $v_memory_limit*1048576;
    if($last == 'k')
        $v_memory_limit = $v_memory_limit*1024;
            
    $p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] = floor($v_memory_limit*PCLZIP_TEMPORARY_FILE_RATIO);
    

    // ----- Sanity check : No threshold if value lower than 1M
    if ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] < 1048576) {
      unset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD]);
    }
          
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privFileDescrParseAtt()
  // Description :
  // Parameters :
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privFileDescrParseAtt(&$p_file_list, &$p_filedescr, $v_options, $v_requested_options=false)
  {
    $v_result=1;
    
    // ----- For each file in the list check the attributes
    foreach ($p_file_list as $v_key => $v_value) {
    
      // ----- Check if the option is supported
      if (!isset($v_requested_options[$v_key])) {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid file attribute '".$v_key."' for this file");

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Look for attribute
      switch ($v_key) {
        case PCLZIP_ATT_FILE_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['filename'] = PclZipUtilPathReduction($v_value);
          
          if ($p_filedescr['filename'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

        break;

        case PCLZIP_ATT_FILE_NEW_SHORT_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['new_short_name'] = PclZipUtilPathReduction($v_value);

          if ($p_filedescr['new_short_name'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty short filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }
        break;

        case PCLZIP_ATT_FILE_NEW_FULL_NAME :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['new_full_name'] = PclZipUtilPathReduction($v_value);

          if ($p_filedescr['new_full_name'] == '') {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid empty full filename for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }
        break;

        // ----- Look for options that takes a string
        case PCLZIP_ATT_FILE_COMMENT :
          if (!is_string($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". String expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['comment'] = $v_value;
        break;

        case PCLZIP_ATT_FILE_MTIME :
          if (!is_integer($v_value)) {
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ATTRIBUTE_VALUE, "Invalid type ".gettype($v_value).". Integer expected for attribute '".PclZipUtilOptionText($v_key)."'");
            return PclZip::errorCode();
          }

          $p_filedescr['mtime'] = $v_value;
        break;

        case PCLZIP_ATT_FILE_CONTENT :
          $p_filedescr['content'] = $v_value;
        break;

        default :
          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER,
		                           "Unknown parameter '".$v_key."'");

          // ----- Return
          return PclZip::errorCode();
      }

      // ----- Look for mandatory options
      if ($v_requested_options !== false) {
        for ($key=reset($v_requested_options); $key=key($v_requested_options); $key=next($v_requested_options)) {
          // ----- Look for mandatory option
          if ($v_requested_options[$key] == 'mandatory') {
            // ----- Look if present
            if (!isset($p_file_list[$key])) {
              PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Missing mandatory parameter ".PclZipUtilOptionText($key)."(".$key.")");
              return PclZip::errorCode();
            }
          }
        }
      }
    
    // end foreach
    }
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privFileDescrExpand()
  // Description :
  //   This method look for each item of the list to see if its a file, a folder
  //   or a string to be added as file. For any other type of files (link, other)
  //   just ignore the item.
  //   Then prepare the information that will be stored for that file.
  //   When its a folder, expand the folder with all the files that are in that 
  //   folder (recursively).
  // Parameters :
  // Return Values :
  //   1 on success.
  //   0 on failure.
  // --------------------------------------------------------------------------------
  function privFileDescrExpand(&$p_filedescr_list, &$p_options)
  {
    $v_result=1;
    
    // ----- Create a result list
    $v_result_list = array();
    
    // ----- Look each entry
    for ($i=0; $i<sizeof($p_filedescr_list); $i++) {
      
      // ----- Get filedescr
      $v_descr = $p_filedescr_list[$i];
      
      // ----- Reduce the filename
      $v_descr['filename'] = PclZipUtilTranslateWinPath($v_descr['filename'], false);
      $v_descr['filename'] = PclZipUtilPathReduction($v_descr['filename']);
      
      // ----- Look for real file or folder
      if (file_exists($v_descr['filename'])) {
        if (@is_file($v_descr['filename'])) {
          $v_descr['type'] = 'file';
        }
        else if (@is_dir($v_descr['filename'])) {
          $v_descr['type'] = 'folder';
        }
        else if (@is_link($v_descr['filename'])) {
          // skip
          continue;
        }
        else {
          // skip
          continue;
        }
      }
      
      // ----- Look for string added as file
      else if (isset($v_descr['content'])) {
        $v_descr['type'] = 'virtual_file';
      }
      
      // ----- Missing file
      else {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "File '".$v_descr['filename']."' does not exist");

        // ----- Return
        return PclZip::errorCode();
      }
      
      // ----- Calculate the stored filename
      $this->privCalculateStoredFilename($v_descr, $p_options);
      
      // ----- Add the descriptor in result list
      $v_result_list[sizeof($v_result_list)] = $v_descr;
      
      // ----- Look for folder
      if ($v_descr['type'] == 'folder') {
        // ----- List of items in folder
        $v_dirlist_descr = array();
        $v_dirlist_nb = 0;
        if ($v_folder_handler = @opendir($v_descr['filename'])) {
          while (($v_item_handler = @readdir($v_folder_handler)) !== false) {

            // ----- Skip '.' and '..'
            if (($v_item_handler == '.') || ($v_item_handler == '..')) {
                continue;
            }
            
            // ----- Compose the full filename
            $v_dirlist_descr[$v_dirlist_nb]['filename'] = $v_descr['filename'].'/'.$v_item_handler;
            
            // ----- Look for different stored filename
            // Because the name of the folder was changed, the name of the
            // files/sub-folders also change
            if (($v_descr['stored_filename'] != $v_descr['filename'])
                 && (!isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH]))) {
              if ($v_descr['stored_filename'] != '') {
                $v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_descr['stored_filename'].'/'.$v_item_handler;
              }
              else {
                $v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_item_handler;
              }
            }
      
            $v_dirlist_nb++;
          }
          
          @closedir($v_folder_handler);
        }
        else {
          // TBC : unable to open folder in read mode
        }
        
        // ----- Expand each element of the list
        if ($v_dirlist_nb != 0) {
          // ----- Expand
          if (($v_result = $this->privFileDescrExpand($v_dirlist_descr, $p_options)) != 1) {
            return $v_result;
          }
          
          // ----- Concat the resulting list
          $v_result_list = array_merge($v_result_list, $v_dirlist_descr);
        }
        else {
        }
          
        // ----- Free local array
        unset($v_dirlist_descr);
      }
    }
    
    // ----- Get the result list
    $p_filedescr_list = $v_result_list;

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCreate()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privCreate($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();
    
    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the file in write mode
    if (($v_result = $this->privOpenFd('wb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Add the list of files
    $v_result = $this->privAddList($p_filedescr_list, $p_result_list, $p_options);

    // ----- Close
    $this->privCloseFd();

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAdd()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAdd($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();

    // ----- Look if the archive exists or is empty
    if ((!is_file($this->zipname)) || (filesize($this->zipname) == 0))
    {

      // ----- Do a create
      $v_result = $this->privCreate($p_filedescr_list, $p_result_list, $p_options);

      // ----- Return
      return $v_result;
    }
    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Magic quotes trick
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Creates a temporay file
    $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0)
    {
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_zip_temp_name.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = $v_central_dir['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Swap the file descriptor
    // Here is a trick : I swap the temporary fd with the zip fd, in order to use
    // the following methods on the temporary fil and not the real archive
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Add the files
    $v_header_list = array();
    if (($v_result = $this->privAddFileList($p_filedescr_list, $v_header_list, $p_options)) != 1)
    {
      fclose($v_zip_temp_fd);
      $this->privCloseFd();
      @unlink($v_zip_temp_name);
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($this->zip_fd);

    // ----- Copy the block of file headers from the old archive
    $v_size = $v_central_dir['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_zip_temp_fd, $v_read_size);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Create the Central Dir files header
    for ($i=0, $v_count=0; $i<sizeof($v_header_list); $i++)
    {
      // ----- Create the file header
      if ($v_header_list[$i]['status'] == 'ok') {
        if (($v_result = $this->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
          fclose($v_zip_temp_fd);
          $this->privCloseFd();
          @unlink($v_zip_temp_name);
          $this->privSwapBackMagicQuotes();

          // ----- Return
          return $v_result;
        }
        $v_count++;
      }

      // ----- Transform the header to a 'usable' info
      $this->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
    }

    // ----- Zip file comment
    $v_comment = $v_central_dir['comment'];
    if (isset($p_options[PCLZIP_OPT_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_COMMENT];
    }
    if (isset($p_options[PCLZIP_OPT_ADD_COMMENT])) {
      $v_comment = $v_comment.$p_options[PCLZIP_OPT_ADD_COMMENT];
    }
    if (isset($p_options[PCLZIP_OPT_PREPEND_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_PREPEND_COMMENT].$v_comment;
    }

    // ----- Calculate the size of the central header
    $v_size = @ftell($this->zip_fd)-$v_offset;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_count+$v_central_dir['entries'], $v_size, $v_offset, $v_comment)) != 1)
    {
      // ----- Reset the file list
      unset($v_header_list);
      $this->privSwapBackMagicQuotes();

      // ----- Return
      return $v_result;
    }

    // ----- Swap back the file descriptor
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Close
    $this->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Delete the zip file
    // TBC : I should test the result ...
    @unlink($this->zipname);

    // ----- Rename the temporary file
    // TBC : I should test the result ...
    //@rename($v_zip_temp_name, $this->zipname);
    PclZipUtilRename($v_zip_temp_name, $this->zipname);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privOpenFd()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privOpenFd($p_mode)
  {
    $v_result=1;

    // ----- Look if already open
    if ($this->zip_fd != 0)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Zip file \''.$this->zipname.'\' already open');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Open the zip file
    if (($this->zip_fd = @fopen($this->zipname, $p_mode)) == 0)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in '.$p_mode.' mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCloseFd()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privCloseFd()
  {
    $v_result=1;

    if ($this->zip_fd != 0)
      @fclose($this->zip_fd);
    $this->zip_fd = 0;

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddList()
  // Description :
  //   $p_add_dir and $p_remove_dir will give the ability to memorize a path which is
  //   different from the real path of the file. This is usefull if you want to have PclTar
  //   running in any directory, and memorize relative path from an other directory.
  // Parameters :
  //   $p_list : An array containing the file or directory names to add in the tar
  //   $p_result_list : list of added files with their properties (specially the status field)
  //   $p_add_dir : Path to add in the filename path archived
  //   $p_remove_dir : Path to remove in the filename path archived
  // Return Values :
  // --------------------------------------------------------------------------------
//  function privAddList($p_list, &$p_result_list, $p_add_dir, $p_remove_dir, $p_remove_all_dir, &$p_options)
  function privAddList($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;

    // ----- Add the files
    $v_header_list = array();
    if (($v_result = $this->privAddFileList($p_filedescr_list, $v_header_list, $p_options)) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($this->zip_fd);

    // ----- Create the Central Dir files header
    for ($i=0,$v_count=0; $i<sizeof($v_header_list); $i++)
    {
      // ----- Create the file header
      if ($v_header_list[$i]['status'] == 'ok') {
        if (($v_result = $this->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
          // ----- Return
          return $v_result;
        }
        $v_count++;
      }

      // ----- Transform the header to a 'usable' info
      $this->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
    }

    // ----- Zip file comment
    $v_comment = '';
    if (isset($p_options[PCLZIP_OPT_COMMENT])) {
      $v_comment = $p_options[PCLZIP_OPT_COMMENT];
    }

    // ----- Calculate the size of the central header
    $v_size = @ftell($this->zip_fd)-$v_offset;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_count, $v_size, $v_offset, $v_comment)) != 1)
    {
      // ----- Reset the file list
      unset($v_header_list);

      // ----- Return
      return $v_result;
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFileList()
  // Description :
  // Parameters :
  //   $p_filedescr_list : An array containing the file description 
  //                      or directory names to add in the zip
  //   $p_result_list : list of added files with their properties (specially the status field)
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFileList($p_filedescr_list, &$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_header = array();

    // ----- Recuperate the current number of elt in list
    $v_nb = sizeof($p_result_list);

    // ----- Loop on the files
    for ($j=0; ($j<sizeof($p_filedescr_list)) && ($v_result==1); $j++) {
      // ----- Format the filename
      $p_filedescr_list[$j]['filename']
      = PclZipUtilTranslateWinPath($p_filedescr_list[$j]['filename'], false);
      

      // ----- Skip empty file names
      // TBC : Can this be possible ? not checked in DescrParseAtt ?
      if ($p_filedescr_list[$j]['filename'] == "") {
        continue;
      }

      // ----- Check the filename
      if (   ($p_filedescr_list[$j]['type'] != 'virtual_file')
          && (!file_exists($p_filedescr_list[$j]['filename']))) {
        PclZip::privErrorLog(PCLZIP_ERR_MISSING_FILE, "File '".$p_filedescr_list[$j]['filename']."' does not exist");
        return PclZip::errorCode();
      }

      // ----- Look if it is a file or a dir with no all path remove option
      // or a dir with all its path removed
//      if (   (is_file($p_filedescr_list[$j]['filename']))
//          || (   is_dir($p_filedescr_list[$j]['filename'])
      if (   ($p_filedescr_list[$j]['type'] == 'file')
          || ($p_filedescr_list[$j]['type'] == 'virtual_file')
          || (   ($p_filedescr_list[$j]['type'] == 'folder')
              && (   !isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH])
                  || !$p_options[PCLZIP_OPT_REMOVE_ALL_PATH]))
          ) {

        // ----- Add the file
        $v_result = $this->privAddFile($p_filedescr_list[$j], $v_header,
                                       $p_options);
        if ($v_result != 1) {
          return $v_result;
        }

        // ----- Store the file infos
        $p_result_list[$v_nb++] = $v_header;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFile($p_filedescr, &$p_header, &$p_options)
  {
    $v_result=1;
    
    // ----- Working variable
    $p_filename = $p_filedescr['filename'];

    // TBC : Already done in the fileAtt check ... ?
    if ($p_filename == "") {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_PARAMETER, "Invalid file list parameter (invalid or empty list)");

      // ----- Return
      return PclZip::errorCode();
    }
  
    // ----- Look for a stored different filename 
    /* TBC : Removed
    if (isset($p_filedescr['stored_filename'])) {
      $v_stored_filename = $p_filedescr['stored_filename'];
    }
    else {
      $v_stored_filename = $p_filedescr['stored_filename'];
    }
    */

    // ----- Set the file properties
    clearstatcache();
    $p_header['version'] = 20;
    $p_header['version_extracted'] = 10;
    $p_header['flag'] = 0;
    $p_header['compression'] = 0;
    $p_header['crc'] = 0;
    $p_header['compressed_size'] = 0;
    $p_header['filename_len'] = strlen($p_filename);
    $p_header['extra_len'] = 0;
    $p_header['disk'] = 0;
    $p_header['internal'] = 0;
    $p_header['offset'] = 0;
    $p_header['filename'] = $p_filename;
// TBC : Removed    $p_header['stored_filename'] = $v_stored_filename;
    $p_header['stored_filename'] = $p_filedescr['stored_filename'];
    $p_header['extra'] = '';
    $p_header['status'] = 'ok';
    $p_header['index'] = -1;

    // ----- Look for regular file
    if ($p_filedescr['type']=='file') {
      $p_header['external'] = 0x00000000;
      $p_header['size'] = filesize($p_filename);
    }
    
    // ----- Look for regular folder
    else if ($p_filedescr['type']=='folder') {
      $p_header['external'] = 0x00000010;
      $p_header['mtime'] = filemtime($p_filename);
      $p_header['size'] = filesize($p_filename);
    }
    
    // ----- Look for virtual file
    else if ($p_filedescr['type'] == 'virtual_file') {
      $p_header['external'] = 0x00000000;
      $p_header['size'] = strlen($p_filedescr['content']);
    }
    

    // ----- Look for filetime
    if (isset($p_filedescr['mtime'])) {
      $p_header['mtime'] = $p_filedescr['mtime'];
    }
    else if ($p_filedescr['type'] == 'virtual_file') {
      $p_header['mtime'] = time();
    }
    else {
      $p_header['mtime'] = filemtime($p_filename);
    }

    // ------ Look for file comment
    if (isset($p_filedescr['comment'])) {
      $p_header['comment_len'] = strlen($p_filedescr['comment']);
      $p_header['comment'] = $p_filedescr['comment'];
    }
    else {
      $p_header['comment_len'] = 0;
      $p_header['comment'] = '';
    }

    // ----- Look for pre-add callback
    if (isset($p_options[PCLZIP_CB_PRE_ADD])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_header, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_ADD].'(PCLZIP_CB_PRE_ADD, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_ADD](PCLZIP_CB_PRE_ADD, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_header['status'] = "skipped";
        $v_result = 1;
      }

      // ----- Update the informations
      // Only some fields can be modified
      if ($p_header['stored_filename'] != $v_local_header['stored_filename']) {
        $p_header['stored_filename'] = PclZipUtilPathReduction($v_local_header['stored_filename']);
      }
    }

    // ----- Look for empty stored filename
    if ($p_header['stored_filename'] == "") {
      $p_header['status'] = "filtered";
    }
    
    // ----- Check the path length
    if (strlen($p_header['stored_filename']) > 0xFF) {
      $p_header['status'] = 'filename_too_long';
    }

    // ----- Look if no error, or file not skipped
    if ($p_header['status'] == 'ok') {

      // ----- Look for a file
      if ($p_filedescr['type'] == 'file') {
        // ----- Look for using temporary file to zip
        if ( (!isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) 
            && (isset($p_options[PCLZIP_OPT_TEMP_FILE_ON])
                || (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
                    && ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] <= $p_header['size'])) ) ) {
          $v_result = $this->privAddFileUsingTempFile($p_filedescr, $p_header, $p_options);
          if ($v_result < PCLZIP_ERR_NO_ERROR) {
            return $v_result;
          }
        }
        
        // ----- Use "in memory" zip algo
        else {

        // ----- Open the source file
        if (($v_file = @fopen($p_filename, "rb")) == 0) {
          PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to open file '$p_filename' in binary read mode");
          return PclZip::errorCode();
        }

        // ----- Read the file content
        $v_content = @fread($v_file, $p_header['size']);

        // ----- Close the file
        @fclose($v_file);

        // ----- Calculate the CRC
        $p_header['crc'] = @crc32($v_content);
        
        // ----- Look for no compression
        if ($p_options[PCLZIP_OPT_NO_COMPRESSION]) {
          // ----- Set header parameters
          $p_header['compressed_size'] = $p_header['size'];
          $p_header['compression'] = 0;
        }
        
        // ----- Look for normal compression
        else {
          // ----- Compress the content
          $v_content = @gzdeflate($v_content);

          // ----- Set header parameters
          $p_header['compressed_size'] = strlen($v_content);
          $p_header['compression'] = 8;
        }
        
        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
          @fclose($v_file);
          return $v_result;
        }

        // ----- Write the compressed (or not) content
        @fwrite($this->zip_fd, $v_content, $p_header['compressed_size']);

        }

      }

      // ----- Look for a virtual file (a file from string)
      else if ($p_filedescr['type'] == 'virtual_file') {
          
        $v_content = $p_filedescr['content'];

        // ----- Calculate the CRC
        $p_header['crc'] = @crc32($v_content);
        
        // ----- Look for no compression
        if ($p_options[PCLZIP_OPT_NO_COMPRESSION]) {
          // ----- Set header parameters
          $p_header['compressed_size'] = $p_header['size'];
          $p_header['compression'] = 0;
        }
        
        // ----- Look for normal compression
        else {
          // ----- Compress the content
          $v_content = @gzdeflate($v_content);

          // ----- Set header parameters
          $p_header['compressed_size'] = strlen($v_content);
          $p_header['compression'] = 8;
        }
        
        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
          @fclose($v_file);
          return $v_result;
        }

        // ----- Write the compressed (or not) content
        @fwrite($this->zip_fd, $v_content, $p_header['compressed_size']);
      }

      // ----- Look for a directory
      else if ($p_filedescr['type'] == 'folder') {
        // ----- Look for directory last '/'
        if (@substr($p_header['stored_filename'], -1) != '/') {
          $p_header['stored_filename'] .= '/';
        }

        // ----- Set the file properties
        $p_header['size'] = 0;
        //$p_header['external'] = 0x41FF0010;   // Value for a folder : to be checked
        $p_header['external'] = 0x00000010;   // Value for a folder : to be checked

        // ----- Call the header generation
        if (($v_result = $this->privWriteFileHeader($p_header)) != 1)
        {
          return $v_result;
        }
      }
    }

    // ----- Look for post-add callback
    if (isset($p_options[PCLZIP_CB_POST_ADD])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_header, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_ADD].'(PCLZIP_CB_POST_ADD, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_ADD](PCLZIP_CB_POST_ADD, $v_local_header);
      if ($v_result == 0) {
        // ----- Ignored
        $v_result = 1;
      }

      // ----- Update the informations
      // Nothing can be modified
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privAddFileUsingTempFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privAddFileUsingTempFile($p_filedescr, &$p_header, &$p_options)
  {
    $v_result=PCLZIP_ERR_NO_ERROR;
    
    // ----- Working variable
    $p_filename = $p_filedescr['filename'];


    // ----- Open the source file
    if (($v_file = @fopen($p_filename, "rb")) == 0) {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, "Unable to open file '$p_filename' in binary read mode");
      return PclZip::errorCode();
    }

    // ----- Creates a compressed temporary file
    $v_gzip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.gz';
    if (($v_file_compressed = @gzopen($v_gzip_temp_name, "wb")) == 0) {
      fclose($v_file);
      PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary write mode');
      return PclZip::errorCode();
    }

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = filesize($p_filename);
    while ($v_size != 0) {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_file, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @gzputs($v_file_compressed, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close the file
    @fclose($v_file);
    @gzclose($v_file_compressed);

    // ----- Check the minimum file size
    if (filesize($v_gzip_temp_name) < 18) {
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'gzip temporary file \''.$v_gzip_temp_name.'\' has invalid filesize - should be minimum 18 bytes');
      return PclZip::errorCode();
    }

    // ----- Extract the compressed attributes
    if (($v_file_compressed = @fopen($v_gzip_temp_name, "rb")) == 0) {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }

    // ----- Read the gzip file header
    $v_binary_data = @fread($v_file_compressed, 10);
    $v_data_header = unpack('a1id1/a1id2/a1cm/a1flag/Vmtime/a1xfl/a1os', $v_binary_data);

    // ----- Check some parameters
    $v_data_header['os'] = bin2hex($v_data_header['os']);

    // ----- Read the gzip file footer
    @fseek($v_file_compressed, filesize($v_gzip_temp_name)-8);
    $v_binary_data = @fread($v_file_compressed, 8);
    $v_data_footer = unpack('Vcrc/Vcompressed_size', $v_binary_data);

    // ----- Set the attributes
    $p_header['compression'] = ord($v_data_header['cm']);
    //$p_header['mtime'] = $v_data_header['mtime'];
    $p_header['crc'] = $v_data_footer['crc'];
    $p_header['compressed_size'] = filesize($v_gzip_temp_name)-18;

    // ----- Close the file
    @fclose($v_file_compressed);

    // ----- Call the header generation
    if (($v_result = $this->privWriteFileHeader($p_header)) != 1) {
      return $v_result;
    }

    // ----- Add the compressed data
    if (($v_file_compressed = @fopen($v_gzip_temp_name, "rb")) == 0)
    {
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    fseek($v_file_compressed, 10);
    $v_size = $p_header['compressed_size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($v_file_compressed, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close the file
    @fclose($v_file_compressed);

    // ----- Unlink the temporary file
    @unlink($v_gzip_temp_name);
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCalculateStoredFilename()
  // Description :
  //   Based on file descriptor properties and global options, this method
  //   calculate the filename that will be stored in the archive.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privCalculateStoredFilename(&$p_filedescr, &$p_options)
  {
    $v_result=1;
    
    // ----- Working variables
    $p_filename = $p_filedescr['filename'];
    if (isset($p_options[PCLZIP_OPT_ADD_PATH])) {
      $p_add_dir = $p_options[PCLZIP_OPT_ADD_PATH];
    }
    else {
      $p_add_dir = '';
    }
    if (isset($p_options[PCLZIP_OPT_REMOVE_PATH])) {
      $p_remove_dir = $p_options[PCLZIP_OPT_REMOVE_PATH];
    }
    else {
      $p_remove_dir = '';
    }
    if (isset($p_options[PCLZIP_OPT_REMOVE_ALL_PATH])) {
      $p_remove_all_dir = $p_options[PCLZIP_OPT_REMOVE_ALL_PATH];
    }
    else {
      $p_remove_all_dir = 0;
    }


    // ----- Look for full name change
    if (isset($p_filedescr['new_full_name'])) {
      // ----- Remove drive letter if any
      $v_stored_filename = PclZipUtilTranslateWinPath($p_filedescr['new_full_name']);
    }
    
    // ----- Look for path and/or short name change
    else {

      // ----- Look for short name change
      // Its when we cahnge just the filename but not the path
      if (isset($p_filedescr['new_short_name'])) {
        $v_path_info = pathinfo($p_filename);
        $v_dir = '';
        if ($v_path_info['dirname'] != '') {
          $v_dir = $v_path_info['dirname'].'/';
        }
        $v_stored_filename = $v_dir.$p_filedescr['new_short_name'];
      }
      else {
        // ----- Calculate the stored filename
        $v_stored_filename = $p_filename;
      }

      // ----- Look for all path to remove
      if ($p_remove_all_dir) {
        $v_stored_filename = basename($p_filename);
      }
      // ----- Look for partial path remove
      else if ($p_remove_dir != "") {
        if (substr($p_remove_dir, -1) != '/')
          $p_remove_dir .= "/";

        if (   (substr($p_filename, 0, 2) == "./")
            || (substr($p_remove_dir, 0, 2) == "./")) {
            
          if (   (substr($p_filename, 0, 2) == "./")
              && (substr($p_remove_dir, 0, 2) != "./")) {
            $p_remove_dir = "./".$p_remove_dir;
          }
          if (   (substr($p_filename, 0, 2) != "./")
              && (substr($p_remove_dir, 0, 2) == "./")) {
            $p_remove_dir = substr($p_remove_dir, 2);
          }
        }

        $v_compare = PclZipUtilPathInclusion($p_remove_dir,
                                             $v_stored_filename);
        if ($v_compare > 0) {
          if ($v_compare == 2) {
            $v_stored_filename = "";
          }
          else {
            $v_stored_filename = substr($v_stored_filename,
                                        strlen($p_remove_dir));
          }
        }
      }
      
      // ----- Remove drive letter if any
      $v_stored_filename = PclZipUtilTranslateWinPath($v_stored_filename);
      
      // ----- Look for path to add
      if ($p_add_dir != "") {
        if (substr($p_add_dir, -1) == "/")
          $v_stored_filename = $p_add_dir.$v_stored_filename;
        else
          $v_stored_filename = $p_add_dir."/".$v_stored_filename;
      }
    }

    // ----- Filename (reduce the path of stored name)
    $v_stored_filename = PclZipUtilPathReduction($v_stored_filename);
    $p_filedescr['stored_filename'] = $v_stored_filename;
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Store the offset position of the file
    $p_header['offset'] = ftell($this->zip_fd);

    // ----- Transform UNIX mtime to DOS format mdate/mtime
    $v_date = getdate($p_header['mtime']);
    $v_mtime = ($v_date['hours']<<11) + ($v_date['minutes']<<5) + $v_date['seconds']/2;
    $v_mdate = (($v_date['year']-1980)<<9) + ($v_date['mon']<<5) + $v_date['mday'];

    // ----- Packed data
    $v_binary_data = pack("VvvvvvVVVvv", 0x04034b50,
	                      $p_header['version_extracted'], $p_header['flag'],
                          $p_header['compression'], $v_mtime, $v_mdate,
                          $p_header['crc'], $p_header['compressed_size'],
						  $p_header['size'],
                          strlen($p_header['stored_filename']),
						  $p_header['extra_len']);

    // ----- Write the first 148 bytes of the header in the archive
    fputs($this->zip_fd, $v_binary_data, 30);

    // ----- Write the variable fields
    if (strlen($p_header['stored_filename']) != 0)
    {
      fputs($this->zip_fd, $p_header['stored_filename'], strlen($p_header['stored_filename']));
    }
    if ($p_header['extra_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['extra'], $p_header['extra_len']);
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteCentralFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteCentralFileHeader(&$p_header)
  {
    $v_result=1;

    // TBC
    //for(reset($p_header); $key = key($p_header); next($p_header)) {
    //}

    // ----- Transform UNIX mtime to DOS format mdate/mtime
    $v_date = getdate($p_header['mtime']);
    $v_mtime = ($v_date['hours']<<11) + ($v_date['minutes']<<5) + $v_date['seconds']/2;
    $v_mdate = (($v_date['year']-1980)<<9) + ($v_date['mon']<<5) + $v_date['mday'];


    // ----- Packed data
    $v_binary_data = pack("VvvvvvvVVVvvvvvVV", 0x02014b50,
	                      $p_header['version'], $p_header['version_extracted'],
                          $p_header['flag'], $p_header['compression'],
						  $v_mtime, $v_mdate, $p_header['crc'],
                          $p_header['compressed_size'], $p_header['size'],
                          strlen($p_header['stored_filename']),
						  $p_header['extra_len'], $p_header['comment_len'],
                          $p_header['disk'], $p_header['internal'],
						  $p_header['external'], $p_header['offset']);

    // ----- Write the 42 bytes of the header in the zip file
    fputs($this->zip_fd, $v_binary_data, 46);

    // ----- Write the variable fields
    if (strlen($p_header['stored_filename']) != 0)
    {
      fputs($this->zip_fd, $p_header['stored_filename'], strlen($p_header['stored_filename']));
    }
    if ($p_header['extra_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['extra'], $p_header['extra_len']);
    }
    if ($p_header['comment_len'] != 0)
    {
      fputs($this->zip_fd, $p_header['comment'], $p_header['comment_len']);
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privWriteCentralHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privWriteCentralHeader($p_nb_entries, $p_size, $p_offset, $p_comment)
  {
    $v_result=1;

    // ----- Packed data
    $v_binary_data = pack("VvvvvVVv", 0x06054b50, 0, 0, $p_nb_entries,
	                      $p_nb_entries, $p_size,
						  $p_offset, strlen($p_comment));

    // ----- Write the 22 bytes of the header in the zip file
    fputs($this->zip_fd, $v_binary_data, 22);

    // ----- Write the variable fields
    if (strlen($p_comment) != 0)
    {
      fputs($this->zip_fd, $p_comment, strlen($p_comment));
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privList()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privList(&$p_list)
  {
    $v_result=1;

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Open the zip file
    if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0)
    {
      // ----- Magic quotes trick
      $this->privSwapBackMagicQuotes();
      
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \''.$this->zipname.'\' in binary read mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Go to beginning of Central Dir
    @rewind($this->zip_fd);
    if (@fseek($this->zip_fd, $v_central_dir['offset']))
    {
      $this->privSwapBackMagicQuotes();

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read each entry
    for ($i=0; $i<$v_central_dir['entries']; $i++)
    {
      // ----- Read the file header
      if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1)
      {
        $this->privSwapBackMagicQuotes();
        return $v_result;
      }
      $v_header['index'] = $i;

      // ----- Get the only interesting attributes
      $this->privConvertHeader2FileInfo($v_header, $p_list[$i]);
      unset($v_header);
    }

    // ----- Close the zip file
    $this->privCloseFd();

    // ----- Magic quotes trick
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privConvertHeader2FileInfo()
  // Description :
  //   This function takes the file informations from the central directory
  //   entries and extract the interesting parameters that will be given back.
  //   The resulting file infos are set in the array $p_info
  //     $p_info['filename'] : Filename with full path. Given by user (add),
  //                           extracted in the filesystem (extract).
  //     $p_info['stored_filename'] : Stored filename in the archive.
  //     $p_info['size'] = Size of the file.
  //     $p_info['compressed_size'] = Compressed size of the file.
  //     $p_info['mtime'] = Last modification date of the file.
  //     $p_info['comment'] = Comment associated with the file.
  //     $p_info['folder'] = true/false : indicates if the entry is a folder or not.
  //     $p_info['status'] = status of the action on the file.
  //     $p_info['crc'] = CRC of the file content.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privConvertHeader2FileInfo($p_header, &$p_info)
  {
    $v_result=1;

    // ----- Get the interesting attributes
    $v_temp_path = PclZipUtilPathReduction($p_header['filename']);
    $p_info['filename'] = $v_temp_path;
    $v_temp_path = PclZipUtilPathReduction($p_header['stored_filename']);
    $p_info['stored_filename'] = $v_temp_path;
    $p_info['size'] = $p_header['size'];
    $p_info['compressed_size'] = $p_header['compressed_size'];
    $p_info['mtime'] = $p_header['mtime'];
    $p_info['comment'] = $p_header['comment'];
    $p_info['folder'] = (($p_header['external']&0x00000010)==0x00000010);
    $p_info['index'] = $p_header['index'];
    $p_info['status'] = $p_header['status'];
    $p_info['crc'] = $p_header['crc'];

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractByRule()
  // Description :
  //   Extract a file or directory depending of rules (by index, by name, ...)
  // Parameters :
  //   $p_file_list : An array where will be placed the properties of each
  //                  extracted file
  //   $p_path : Path to add while writing the extracted files
  //   $p_remove_path : Path to remove (from the file memorized path) while writing the
  //                    extracted files. If the path does not match the file path,
  //                    the file is extracted with its memorized path.
  //                    $p_remove_path does not apply to 'list' mode.
  //                    $p_path and $p_remove_path are commulative.
  // Return Values :
  //   1 on success,0 or less on error (see error code list)
  // --------------------------------------------------------------------------------
  function privExtractByRule(&$p_file_list, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    // ----- Magic quotes trick
    $this->privDisableMagicQuotes();

    // ----- Check the path
    if (   ($p_path == "")
	    || (   (substr($p_path, 0, 1) != "/")
		    && (substr($p_path, 0, 3) != "../")
			&& (substr($p_path,1,2)!=":/")))
      $p_path = "./".$p_path;

    // ----- Reduce the path last (and duplicated) '/'
    if (($p_path != "./") && ($p_path != "/"))
    {
      // ----- Look for the path end '/'
      while (substr($p_path, -1) == "/")
      {
        $p_path = substr($p_path, 0, strlen($p_path)-1);
      }
    }

    // ----- Look for path to remove format (should end by /)
    if (($p_remove_path != "") && (substr($p_remove_path, -1) != '/'))
    {
      $p_remove_path .= '/';
    }
    $p_remove_path_size = strlen($p_remove_path);

    // ----- Open the zip file
    if (($v_result = $this->privOpenFd('rb')) != 1)
    {
      $this->privSwapBackMagicQuotes();
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      // ----- Close the zip file
      $this->privCloseFd();
      $this->privSwapBackMagicQuotes();

      return $v_result;
    }

    // ----- Start at beginning of Central Dir
    $v_pos_entry = $v_central_dir['offset'];

    // ----- Read each entry
    $j_start = 0;
    for ($i=0, $v_nb_extracted=0; $i<$v_central_dir['entries']; $i++)
    {

      // ----- Read next Central dir entry
      @rewind($this->zip_fd);
      if (@fseek($this->zip_fd, $v_pos_entry))
      {
        // ----- Close the zip file
        $this->privCloseFd();
        $this->privSwapBackMagicQuotes();

        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read the file header
      $v_header = array();
      if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1)
      {
        // ----- Close the zip file
        $this->privCloseFd();
        $this->privSwapBackMagicQuotes();

        return $v_result;
      }

      // ----- Store the index
      $v_header['index'] = $i;

      // ----- Store the file position
      $v_pos_entry = ftell($this->zip_fd);

      // ----- Look for the specific extract rules
      $v_extract = false;

      // ----- Look for extract by name rule
      if (   (isset($p_options[PCLZIP_OPT_BY_NAME]))
          && ($p_options[PCLZIP_OPT_BY_NAME] != 0)) {

          // ----- Look if the filename is in the list
          for ($j=0; ($j<sizeof($p_options[PCLZIP_OPT_BY_NAME])) && (!$v_extract); $j++) {

              // ----- Look for a directory
              if (substr($p_options[PCLZIP_OPT_BY_NAME][$j], -1) == "/") {

                  // ----- Look if the directory is in the filename path
                  if (   (strlen($v_header['stored_filename']) > strlen($p_options[PCLZIP_OPT_BY_NAME][$j]))
                      && (substr($v_header['stored_filename'], 0, strlen($p_options[PCLZIP_OPT_BY_NAME][$j])) == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_extract = true;
                  }
              }
              // ----- Look for a filename
              elseif ($v_header['stored_filename'] == $p_options[PCLZIP_OPT_BY_NAME][$j]) {
                  $v_extract = true;
              }
          }
      }

      // ----- Look for extract by ereg rule
      // ereg() is deprecated with PHP 5.3
      /* 
      else if (   (isset($p_options[PCLZIP_OPT_BY_EREG]))
               && ($p_options[PCLZIP_OPT_BY_EREG] != "")) {

          if (ereg($p_options[PCLZIP_OPT_BY_EREG], $v_header['stored_filename'])) {
              $v_extract = true;
          }
      }
      */

      // ----- Look for extract by preg rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_PREG]))
               && ($p_options[PCLZIP_OPT_BY_PREG] != "")) {

          if (preg_match($p_options[PCLZIP_OPT_BY_PREG], $v_header['stored_filename'])) {
              $v_extract = true;
          }
      }

      // ----- Look for extract by index rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_INDEX]))
               && ($p_options[PCLZIP_OPT_BY_INDEX] != 0)) {
          
          // ----- Look if the index is in the list
          for ($j=$j_start; ($j<sizeof($p_options[PCLZIP_OPT_BY_INDEX])) && (!$v_extract); $j++) {

              if (($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['start']) && ($i<=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end'])) {
                  $v_extract = true;
              }
              if ($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end']) {
                  $j_start = $j+1;
              }

              if ($p_options[PCLZIP_OPT_BY_INDEX][$j]['start']>$i) {
                  break;
              }
          }
      }

      // ----- Look for no rule, which means extract all the archive
      else {
          $v_extract = true;
      }

	  // ----- Check compression method
	  if (   ($v_extract)
	      && (   ($v_header['compression'] != 8)
		      && ($v_header['compression'] != 0))) {
          $v_header['status'] = 'unsupported_compression';

          // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
          if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		      && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

              $this->privSwapBackMagicQuotes();
              
              PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_COMPRESSION,
			                       "Filename '".$v_header['stored_filename']."' is "
				  	    	  	   ."compressed by an unsupported compression "
				  	    	  	   ."method (".$v_header['compression'].") ");

              return PclZip::errorCode();
		  }
	  }
	  
	  // ----- Check encrypted files
	  if (($v_extract) && (($v_header['flag'] & 1) == 1)) {
          $v_header['status'] = 'unsupported_encryption';

          // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
          if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		      && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

              $this->privSwapBackMagicQuotes();

              PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_ENCRYPTION,
			                       "Unsupported encryption for "
				  	    	  	   ." filename '".$v_header['stored_filename']
								   ."'");

              return PclZip::errorCode();
		  }
    }

      // ----- Look for real extraction
      if (($v_extract) && ($v_header['status'] != 'ok')) {
          $v_result = $this->privConvertHeader2FileInfo($v_header,
		                                        $p_file_list[$v_nb_extracted++]);
          if ($v_result != 1) {
              $this->privCloseFd();
              $this->privSwapBackMagicQuotes();
              return $v_result;
          }

          $v_extract = false;
      }
      
      // ----- Look for real extraction
      if ($v_extract)
      {

        // ----- Go to the file position
        @rewind($this->zip_fd);
        if (@fseek($this->zip_fd, $v_header['offset']))
        {
          // ----- Close the zip file
          $this->privCloseFd();

          $this->privSwapBackMagicQuotes();

          // ----- Error log
          PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

          // ----- Return
          return PclZip::errorCode();
        }

        // ----- Look for extraction as string
        if ($p_options[PCLZIP_OPT_EXTRACT_AS_STRING]) {

          $v_string = '';

          // ----- Extracting the file
          $v_result1 = $this->privExtractFileAsString($v_header, $v_string, $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted])) != 1)
          {
            // ----- Close the zip file
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();

            return $v_result;
          }

          // ----- Set the file content
          $p_file_list[$v_nb_extracted]['content'] = $v_string;

          // ----- Next extracted file
          $v_nb_extracted++;
          
          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
        // ----- Look for extraction in standard output
        elseif (   (isset($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT]))
		        && ($p_options[PCLZIP_OPT_EXTRACT_IN_OUTPUT])) {
          // ----- Extracting the file in standard output
          $v_result1 = $this->privExtractFileInOutput($v_header, $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result;
          }

          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
        // ----- Look for normal extraction
        else {
          // ----- Extracting the file
          $v_result1 = $this->privExtractFile($v_header,
		                                      $p_path, $p_remove_path,
											  $p_remove_all_path,
											  $p_options);
          if ($v_result1 < 1) {
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();
            return $v_result1;
          }

          // ----- Get the only interesting attributes
          if (($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1)
          {
            // ----- Close the zip file
            $this->privCloseFd();
            $this->privSwapBackMagicQuotes();

            return $v_result;
          }

          // ----- Look for user callback abort
          if ($v_result1 == 2) {
          	break;
          }
        }
      }
    }

    // ----- Close the zip file
    $this->privCloseFd();
    $this->privSwapBackMagicQuotes();

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFile()
  // Description :
  // Parameters :
  // Return Values :
  //
  // 1 : ... ?
  // PCLZIP_ERR_USER_ABORTED(2) : User ask for extraction stop in callback
  // --------------------------------------------------------------------------------
  function privExtractFile(&$p_entry, $p_path, $p_remove_path, $p_remove_all_path, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
    {
      // ----- Return
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for all path to remove
    if ($p_remove_all_path == true) {
        // ----- Look for folder entry that not need to be extracted
        if (($p_entry['external']&0x00000010)==0x00000010) {

            $p_entry['status'] = "filtered";

            return $v_result;
        }

        // ----- Get the basename of the path
        $p_entry['filename'] = basename($p_entry['filename']);
    }

    // ----- Look for path to remove
    else if ($p_remove_path != "")
    {
      if (PclZipUtilPathInclusion($p_remove_path, $p_entry['filename']) == 2)
      {

        // ----- Change the file status
        $p_entry['status'] = "filtered";

        // ----- Return
        return $v_result;
      }

      $p_remove_path_size = strlen($p_remove_path);
      if (substr($p_entry['filename'], 0, $p_remove_path_size) == $p_remove_path)
      {

        // ----- Remove the path
        $p_entry['filename'] = substr($p_entry['filename'], $p_remove_path_size);

      }
    }

    // ----- Add the path
    if ($p_path != '') {
      $p_entry['filename'] = $p_path."/".$p_entry['filename'];
    }
    
    // ----- Check a base_dir_restriction
    if (isset($p_options[PCLZIP_OPT_EXTRACT_DIR_RESTRICTION])) {
      $v_inclusion
      = PclZipUtilPathInclusion($p_options[PCLZIP_OPT_EXTRACT_DIR_RESTRICTION],
                                $p_entry['filename']); 
      if ($v_inclusion == 0) {

        PclZip::privErrorLog(PCLZIP_ERR_DIRECTORY_RESTRICTION,
			                     "Filename '".$p_entry['filename']."' is "
								 ."outside PCLZIP_OPT_EXTRACT_DIR_RESTRICTION");

        return PclZip::errorCode();
      }
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      
      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }


    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

    // ----- Look for specific actions while the file exist
    if (file_exists($p_entry['filename']))
    {

      // ----- Look if file is a directory
      if (is_dir($p_entry['filename']))
      {

        // ----- Change the file status
        $p_entry['status'] = "already_a_directory";
        
        // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
        // For historical reason first PclZip implementation does not stop
        // when this kind of error occurs.
        if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		    && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

            PclZip::privErrorLog(PCLZIP_ERR_ALREADY_A_DIRECTORY,
			                     "Filename '".$p_entry['filename']."' is "
								 ."already used by an existing directory");

            return PclZip::errorCode();
		    }
      }
      // ----- Look if file is write protected
      else if (!is_writeable($p_entry['filename']))
      {

        // ----- Change the file status
        $p_entry['status'] = "write_protected";

        // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
        // For historical reason first PclZip implementation does not stop
        // when this kind of error occurs.
        if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		    && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

            PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL,
			                     "Filename '".$p_entry['filename']."' exists "
								 ."and is write protected");

            return PclZip::errorCode();
		    }
      }

      // ----- Look if the extracted file is older
      else if (filemtime($p_entry['filename']) > $p_entry['mtime'])
      {
        // ----- Change the file status
        if (   (isset($p_options[PCLZIP_OPT_REPLACE_NEWER]))
		    && ($p_options[PCLZIP_OPT_REPLACE_NEWER]===true)) {
	  	  }
		    else {
            $p_entry['status'] = "newer_exist";

            // ----- Look for PCLZIP_OPT_STOP_ON_ERROR
            // For historical reason first PclZip implementation does not stop
            // when this kind of error occurs.
            if (   (isset($p_options[PCLZIP_OPT_STOP_ON_ERROR]))
		        && ($p_options[PCLZIP_OPT_STOP_ON_ERROR]===true)) {

                PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL,
			             "Newer version of '".$p_entry['filename']."' exists "
					    ."and option PCLZIP_OPT_REPLACE_NEWER is not selected");

                return PclZip::errorCode();
		      }
		    }
      }
      else {
      }
    }

    // ----- Check the directory availability and create it if necessary
    else {
      if ((($p_entry['external']&0x00000010)==0x00000010) || (substr($p_entry['filename'], -1) == '/'))
        $v_dir_to_check = $p_entry['filename'];
      else if (!strstr($p_entry['filename'], "/"))
        $v_dir_to_check = "";
      else
        $v_dir_to_check = dirname($p_entry['filename']);

        if (($v_result = $this->privDirCheck($v_dir_to_check, (($p_entry['external']&0x00000010)==0x00000010))) != 1) {
  
          // ----- Change the file status
          $p_entry['status'] = "path_creation_fail";
  
          // ----- Return
          //return $v_result;
          $v_result = 1;
        }
      }
    }

    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010))
      {
        // ----- Look for not compressed file
        if ($p_entry['compression'] == 0) {

    		  // ----- Opening destination file
          if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0)
          {

            // ----- Change the file status
            $p_entry['status'] = "write_error";

            // ----- Return
            return $v_result;
          }


          // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
          $v_size = $p_entry['compressed_size'];
          while ($v_size != 0)
          {
            $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
            $v_buffer = @fread($this->zip_fd, $v_read_size);
            /* Try to speed up the code
            $v_binary_data = pack('a'.$v_read_size, $v_buffer);
            @fwrite($v_dest_file, $v_binary_data, $v_read_size);
            */
            @fwrite($v_dest_file, $v_buffer, $v_read_size);            
            $v_size -= $v_read_size;
          }

          // ----- Closing the destination file
          fclose($v_dest_file);

          // ----- Change the file mtime
          touch($p_entry['filename'], $p_entry['mtime']);
          

        }
        else {
          // ----- TBC
          // Need to be finished
          if (($p_entry['flag'] & 1) == 1) {
            PclZip::privErrorLog(PCLZIP_ERR_UNSUPPORTED_ENCRYPTION, 'File \''.$p_entry['filename'].'\' is encrypted. Encrypted files are not supported.');
            return PclZip::errorCode();
          }


          // ----- Look for using temporary file to unzip
          if ( (!isset($p_options[PCLZIP_OPT_TEMP_FILE_OFF])) 
              && (isset($p_options[PCLZIP_OPT_TEMP_FILE_ON])
                  || (isset($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD])
                      && ($p_options[PCLZIP_OPT_TEMP_FILE_THRESHOLD] <= $p_entry['size'])) ) ) {
            $v_result = $this->privExtractFileUsingTempFile($p_entry, $p_options);
            if ($v_result < PCLZIP_ERR_NO_ERROR) {
              return $v_result;
            }
          }
          
          // ----- Look for extract in memory
          else {

          
            // ----- Read the compressed file in a buffer (one shot)
            $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
            
            // ----- Decompress the file
            $v_file_content = @gzinflate($v_buffer);
            unset($v_buffer);
            if ($v_file_content === FALSE) {
  
              // ----- Change the file status
              // TBC
              $p_entry['status'] = "error";
              
              return $v_result;
            }
            
            // ----- Opening destination file
            if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {
  
              // ----- Change the file status
              $p_entry['status'] = "write_error";
  
              return $v_result;
            }
  
            // ----- Write the uncompressed data
            @fwrite($v_dest_file, $v_file_content, $p_entry['size']);
            unset($v_file_content);
  
            // ----- Closing the destination file
            @fclose($v_dest_file);
            
          }

          // ----- Change the file mtime
          @touch($p_entry['filename'], $p_entry['mtime']);
        }

        // ----- Look for chmod option
        if (isset($p_options[PCLZIP_OPT_SET_CHMOD])) {

          // ----- Change the mode of the file
          @chmod($p_entry['filename'], $p_options[PCLZIP_OPT_SET_CHMOD]);
        }

      }
    }

  	// ----- Change abort status
  	if ($p_entry['status'] == "aborted") {
        $p_entry['status'] = "skipped";
  	}
	
    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileUsingTempFile()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileUsingTempFile(&$p_entry, &$p_options)
  {
    $v_result=1;
        
    // ----- Creates a temporary file
    $v_gzip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.gz';
    if (($v_dest_file = @fopen($v_gzip_temp_name, "wb")) == 0) {
      fclose($v_file);
      PclZip::privErrorLog(PCLZIP_ERR_WRITE_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary write mode');
      return PclZip::errorCode();
    }


    // ----- Write gz file format header
    $v_binary_data = pack('va1a1Va1a1', 0x8b1f, Chr($p_entry['compression']), Chr(0x00), time(), Chr(0x00), Chr(3));
    @fwrite($v_dest_file, $v_binary_data, 10);

    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = $p_entry['compressed_size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($this->zip_fd, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($v_dest_file, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Write gz file format footer
    $v_binary_data = pack('VV', $p_entry['crc'], $p_entry['size']);
    @fwrite($v_dest_file, $v_binary_data, 8);

    // ----- Close the temporary file
    @fclose($v_dest_file);

    // ----- Opening destination file
    if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {
      $p_entry['status'] = "write_error";
      return $v_result;
    }

    // ----- Open the temporary gz file
    if (($v_src_file = @gzopen($v_gzip_temp_name, 'rb')) == 0) {
      @fclose($v_dest_file);
      $p_entry['status'] = "read_error";
      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_gzip_temp_name.'\' in binary read mode');
      return PclZip::errorCode();
    }


    // ----- Read the file by PCLZIP_READ_BLOCK_SIZE octets blocks
    $v_size = $p_entry['size'];
    while ($v_size != 0) {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @gzread($v_src_file, $v_read_size);
      //$v_binary_data = pack('a'.$v_read_size, $v_buffer);
      @fwrite($v_dest_file, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }
    @fclose($v_dest_file);
    @gzclose($v_src_file);

    // ----- Delete the temporary file
    @unlink($v_gzip_temp_name);
    
    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileInOutput()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileInOutput(&$p_entry, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    if (($v_result = $this->privReadFileHeader($v_header)) != 1) {
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }

      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }

    // ----- Trace

    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010)) {
        // ----- Look for not compressed file
        if ($p_entry['compressed_size'] == $p_entry['size']) {

          // ----- Read the file in a buffer (one shot)
          $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);

          // ----- Send the file to the output
          echo $v_buffer;
          unset($v_buffer);
        }
        else {

          // ----- Read the compressed file in a buffer (one shot)
          $v_buffer = @fread($this->zip_fd, $p_entry['compressed_size']);
          
          // ----- Decompress the file
          $v_file_content = gzinflate($v_buffer);
          unset($v_buffer);

          // ----- Send the file to the output
          echo $v_file_content;
          unset($v_file_content);
        }
      }
    }

	// ----- Change abort status
	if ($p_entry['status'] == "aborted") {
      $p_entry['status'] = "skipped";
	}

    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privExtractFileAsString()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privExtractFileAsString(&$p_entry, &$p_string, &$p_options)
  {
    $v_result=1;

    // ----- Read the file header
    $v_header = array();
    if (($v_result = $this->privReadFileHeader($v_header)) != 1)
    {
      // ----- Return
      return $v_result;
    }


    // ----- Check that the file header is coherent with $p_entry info
    if ($this->privCheckFileHeaders($v_header, $p_entry) != 1) {
        // TBC
    }

    // ----- Look for pre-extract callback
    if (isset($p_options[PCLZIP_CB_PRE_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_PRE_EXTRACT].'(PCLZIP_CB_PRE_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_PRE_EXTRACT](PCLZIP_CB_PRE_EXTRACT, $v_local_header);
      if ($v_result == 0) {
        // ----- Change the file status
        $p_entry['status'] = "skipped";
        $v_result = 1;
      }
      
      // ----- Look for abort result
      if ($v_result == 2) {
        // ----- This status is internal and will be changed in 'skipped'
        $p_entry['status'] = "aborted";
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }

      // ----- Update the informations
      // Only some fields can be modified
      $p_entry['filename'] = $v_local_header['filename'];
    }


    // ----- Look if extraction should be done
    if ($p_entry['status'] == 'ok') {

      // ----- Do the extraction (if not a folder)
      if (!(($p_entry['external']&0x00000010)==0x00000010)) {
        // ----- Look for not compressed file
  //      if ($p_entry['compressed_size'] == $p_entry['size'])
        if ($p_entry['compression'] == 0) {
  
          // ----- Reading the file
          $p_string = @fread($this->zip_fd, $p_entry['compressed_size']);
        }
        else {
  
          // ----- Reading the file
          $v_data = @fread($this->zip_fd, $p_entry['compressed_size']);
          
          // ----- Decompress the file
          if (($p_string = @gzinflate($v_data)) === FALSE) {
              // TBC
          }
        }
  
        // ----- Trace
      }
      else {
          // TBC : error : can not extract a folder in a string
      }
      
    }

  	// ----- Change abort status
  	if ($p_entry['status'] == "aborted") {
        $p_entry['status'] = "skipped";
  	}
	
    // ----- Look for post-extract callback
    elseif (isset($p_options[PCLZIP_CB_POST_EXTRACT])) {

      // ----- Generate a local information
      $v_local_header = array();
      $this->privConvertHeader2FileInfo($p_entry, $v_local_header);
      
      // ----- Swap the content to header
      $v_local_header['content'] = $p_string;
      $p_string = '';

      // ----- Call the callback
      // Here I do not use call_user_func() because I need to send a reference to the
      // header.
//      eval('$v_result = '.$p_options[PCLZIP_CB_POST_EXTRACT].'(PCLZIP_CB_POST_EXTRACT, $v_local_header);');
      $v_result = $p_options[PCLZIP_CB_POST_EXTRACT](PCLZIP_CB_POST_EXTRACT, $v_local_header);

      // ----- Swap back the content to header
      $p_string = $v_local_header['content'];
      unset($v_local_header['content']);

      // ----- Look for abort result
      if ($v_result == 2) {
      	$v_result = PCLZIP_ERR_USER_ABORTED;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Read the 4 bytes signature
    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);

    // ----- Check signature
    if ($v_data['id'] != 0x04034b50)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the first 42 bytes of the header
    $v_binary_data = fread($this->zip_fd, 26);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 26)
    {
      $p_header['filename'] = "";
      $p_header['status'] = "invalid_header";

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);

    // ----- Get filename
    $p_header['filename'] = fread($this->zip_fd, $v_data['filename_len']);

    // ----- Get extra_fields
    if ($v_data['extra_len'] != 0) {
      $p_header['extra'] = fread($this->zip_fd, $v_data['extra_len']);
    }
    else {
      $p_header['extra'] = '';
    }

    // ----- Extract properties
    $p_header['version_extracted'] = $v_data['version'];
    $p_header['compression'] = $v_data['compression'];
    $p_header['size'] = $v_data['size'];
    $p_header['compressed_size'] = $v_data['compressed_size'];
    $p_header['crc'] = $v_data['crc'];
    $p_header['flag'] = $v_data['flag'];
    $p_header['filename_len'] = $v_data['filename_len'];

    // ----- Recuperate date in UNIX format
    $p_header['mdate'] = $v_data['mdate'];
    $p_header['mtime'] = $v_data['mtime'];
    if ($p_header['mdate'] && $p_header['mtime'])
    {
      // ----- Extract time
      $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
      $v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
      $v_seconde = ($p_header['mtime'] & 0x001F)*2;

      // ----- Extract date
      $v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
      $v_month = ($p_header['mdate'] & 0x01E0) >> 5;
      $v_day = $p_header['mdate'] & 0x001F;

      // ----- Get UNIX date format
      $p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

    }
    else
    {
      $p_header['mtime'] = time();
    }

    // TBC
    //for(reset($v_data); $key = key($v_data); next($v_data)) {
    //}

    // ----- Set the stored filename
    $p_header['stored_filename'] = $p_header['filename'];

    // ----- Set the status field
    $p_header['status'] = "ok";

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadCentralFileHeader()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadCentralFileHeader(&$p_header)
  {
    $v_result=1;

    // ----- Read the 4 bytes signature
    $v_binary_data = @fread($this->zip_fd, 4);
    $v_data = unpack('Vid', $v_binary_data);

    // ----- Check signature
    if ($v_data['id'] != 0x02014b50)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read the first 42 bytes of the header
    $v_binary_data = fread($this->zip_fd, 42);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 42)
    {
      $p_header['filename'] = "";
      $p_header['status'] = "invalid_header";

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid block size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $p_header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);

    // ----- Get filename
    if ($p_header['filename_len'] != 0)
      $p_header['filename'] = fread($this->zip_fd, $p_header['filename_len']);
    else
      $p_header['filename'] = '';

    // ----- Get extra
    if ($p_header['extra_len'] != 0)
      $p_header['extra'] = fread($this->zip_fd, $p_header['extra_len']);
    else
      $p_header['extra'] = '';

    // ----- Get comment
    if ($p_header['comment_len'] != 0)
      $p_header['comment'] = fread($this->zip_fd, $p_header['comment_len']);
    else
      $p_header['comment'] = '';

    // ----- Extract properties

    // ----- Recuperate date in UNIX format
    //if ($p_header['mdate'] && $p_header['mtime'])
    // TBC : bug : this was ignoring time with 0/0/0
    if (1)
    {
      // ----- Extract time
      $v_hour = ($p_header['mtime'] & 0xF800) >> 11;
      $v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
      $v_seconde = ($p_header['mtime'] & 0x001F)*2;

      // ----- Extract date
      $v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
      $v_month = ($p_header['mdate'] & 0x01E0) >> 5;
      $v_day = $p_header['mdate'] & 0x001F;

      // ----- Get UNIX date format
      $p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

    }
    else
    {
      $p_header['mtime'] = time();
    }

    // ----- Set the stored filename
    $p_header['stored_filename'] = $p_header['filename'];

    // ----- Set default status to ok
    $p_header['status'] = 'ok';

    // ----- Look if it is a directory
    if (substr($p_header['filename'], -1) == '/') {
      //$p_header['external'] = 0x41FF0010;
      $p_header['external'] = 0x00000010;
    }


    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privCheckFileHeaders()
  // Description :
  // Parameters :
  // Return Values :
  //   1 on success,
  //   0 on error;
  // --------------------------------------------------------------------------------
  function privCheckFileHeaders(&$p_local_header, &$p_central_header)
  {
    $v_result=1;

  	// ----- Check the static values
  	// TBC
  	if ($p_local_header['filename'] != $p_central_header['filename']) {
  	}
  	if ($p_local_header['version_extracted'] != $p_central_header['version_extracted']) {
  	}
  	if ($p_local_header['flag'] != $p_central_header['flag']) {
  	}
  	if ($p_local_header['compression'] != $p_central_header['compression']) {
  	}
  	if ($p_local_header['mtime'] != $p_central_header['mtime']) {
  	}
  	if ($p_local_header['filename_len'] != $p_central_header['filename_len']) {
  	}
  
  	// ----- Look for flag bit 3
  	if (($p_local_header['flag'] & 8) == 8) {
          $p_local_header['size'] = $p_central_header['size'];
          $p_local_header['compressed_size'] = $p_central_header['compressed_size'];
          $p_local_header['crc'] = $p_central_header['crc'];
  	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privReadEndCentralDir()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privReadEndCentralDir(&$p_central_dir)
  {
    $v_result=1;

    // ----- Go to the end of the zip file
    $v_size = filesize($this->zipname);
    @fseek($this->zip_fd, $v_size);
    if (@ftell($this->zip_fd) != $v_size)
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to go to the end of the archive \''.$this->zipname.'\'');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- First try : look if this is an archive with no commentaries (most of the time)
    // in this case the end of central dir is at 22 bytes of the file end
    $v_found = 0;
    if ($v_size > 26) {
      @fseek($this->zip_fd, $v_size-22);
      if (($v_pos = @ftell($this->zip_fd)) != ($v_size-22))
      {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read for bytes
      $v_binary_data = @fread($this->zip_fd, 4);
      $v_data = @unpack('Vid', $v_binary_data);

      // ----- Check signature
      if ($v_data['id'] == 0x06054b50) {
        $v_found = 1;
      }

      $v_pos = ftell($this->zip_fd);
    }

    // ----- Go back to the maximum possible size of the Central Dir End Record
    if (!$v_found) {
      $v_maximum_size = 65557; // 0xFFFF + 22;
      if ($v_maximum_size > $v_size)
        $v_maximum_size = $v_size;
      @fseek($this->zip_fd, $v_size-$v_maximum_size);
      if (@ftell($this->zip_fd) != ($v_size-$v_maximum_size))
      {
        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, 'Unable to seek back to the middle of the archive \''.$this->zipname.'\'');

        // ----- Return
        return PclZip::errorCode();
      }

      // ----- Read byte per byte in order to find the signature
      $v_pos = ftell($this->zip_fd);
      $v_bytes = 0x00000000;
      while ($v_pos < $v_size)
      {
        // ----- Read a byte
        $v_byte = @fread($this->zip_fd, 1);

        // -----  Add the byte
        //$v_bytes = ($v_bytes << 8) | Ord($v_byte);
        // Note we mask the old value down such that once shifted we can never end up with more than a 32bit number 
        // Otherwise on systems where we have 64bit integers the check below for the magic number will fail. 
        $v_bytes = ( ($v_bytes & 0xFFFFFF) << 8) | Ord($v_byte); 

        // ----- Compare the bytes
        if ($v_bytes == 0x504b0506)
        {
          $v_pos++;
          break;
        }

        $v_pos++;
      }

      // ----- Look if not found end of central dir
      if ($v_pos == $v_size)
      {

        // ----- Error log
        PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Unable to find End of Central Dir Record signature");

        // ----- Return
        return PclZip::errorCode();
      }
    }

    // ----- Read the first 18 bytes of the header
    $v_binary_data = fread($this->zip_fd, 18);

    // ----- Look for invalid block size
    if (strlen($v_binary_data) != 18)
    {

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT, "Invalid End of Central Dir Record size : ".strlen($v_binary_data));

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Extract the values
    $v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);

    // ----- Check the global size
    if (($v_pos + $v_data['comment_size'] + 18) != $v_size) {

	  // ----- Removed in release 2.2 see readme file
	  // The check of the file size is a little too strict.
	  // Some bugs where found when a zip is encrypted/decrypted with 'crypt'.
	  // While decrypted, zip has training 0 bytes
	  if (0) {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_BAD_FORMAT,
	                       'The central dir is not at the end of the archive.'
						   .' Some trailing bytes exists after the archive.');

      // ----- Return
      return PclZip::errorCode();
	  }
    }

    // ----- Get comment
    if ($v_data['comment_size'] != 0) {
      $p_central_dir['comment'] = fread($this->zip_fd, $v_data['comment_size']);
    }
    else
      $p_central_dir['comment'] = '';

    $p_central_dir['entries'] = $v_data['entries'];
    $p_central_dir['disk_entries'] = $v_data['disk_entries'];
    $p_central_dir['offset'] = $v_data['offset'];
    $p_central_dir['size'] = $v_data['size'];
    $p_central_dir['disk'] = $v_data['disk'];
    $p_central_dir['disk_start'] = $v_data['disk_start'];

    // TBC
    //for(reset($p_central_dir); $key = key($p_central_dir); next($p_central_dir)) {
    //}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDeleteByRule()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDeleteByRule(&$p_result_list, &$p_options)
  {
    $v_result=1;
    $v_list_detail = array();

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Scan all the files
    // ----- Start at beginning of Central Dir
    $v_pos_entry = $v_central_dir['offset'];
    @rewind($this->zip_fd);
    if (@fseek($this->zip_fd, $v_pos_entry))
    {
      // ----- Close the zip file
      $this->privCloseFd();

      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Read each entry
    $v_header_list = array();
    $j_start = 0;
    for ($i=0, $v_nb_extracted=0; $i<$v_central_dir['entries']; $i++)
    {

      // ----- Read the file header
      $v_header_list[$v_nb_extracted] = array();
      if (($v_result = $this->privReadCentralFileHeader($v_header_list[$v_nb_extracted])) != 1)
      {
        // ----- Close the zip file
        $this->privCloseFd();

        return $v_result;
      }


      // ----- Store the index
      $v_header_list[$v_nb_extracted]['index'] = $i;

      // ----- Look for the specific extract rules
      $v_found = false;

      // ----- Look for extract by name rule
      if (   (isset($p_options[PCLZIP_OPT_BY_NAME]))
          && ($p_options[PCLZIP_OPT_BY_NAME] != 0)) {

          // ----- Look if the filename is in the list
          for ($j=0; ($j<sizeof($p_options[PCLZIP_OPT_BY_NAME])) && (!$v_found); $j++) {

              // ----- Look for a directory
              if (substr($p_options[PCLZIP_OPT_BY_NAME][$j], -1) == "/") {

                  // ----- Look if the directory is in the filename path
                  if (   (strlen($v_header_list[$v_nb_extracted]['stored_filename']) > strlen($p_options[PCLZIP_OPT_BY_NAME][$j]))
                      && (substr($v_header_list[$v_nb_extracted]['stored_filename'], 0, strlen($p_options[PCLZIP_OPT_BY_NAME][$j])) == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_found = true;
                  }
                  elseif (   (($v_header_list[$v_nb_extracted]['external']&0x00000010)==0x00000010) /* Indicates a folder */
                          && ($v_header_list[$v_nb_extracted]['stored_filename'].'/' == $p_options[PCLZIP_OPT_BY_NAME][$j])) {
                      $v_found = true;
                  }
              }
              // ----- Look for a filename
              elseif ($v_header_list[$v_nb_extracted]['stored_filename'] == $p_options[PCLZIP_OPT_BY_NAME][$j]) {
                  $v_found = true;
              }
          }
      }

      // ----- Look for extract by ereg rule
      // ereg() is deprecated with PHP 5.3
      /*
      else if (   (isset($p_options[PCLZIP_OPT_BY_EREG]))
               && ($p_options[PCLZIP_OPT_BY_EREG] != "")) {

          if (ereg($p_options[PCLZIP_OPT_BY_EREG], $v_header_list[$v_nb_extracted]['stored_filename'])) {
              $v_found = true;
          }
      }
      */

      // ----- Look for extract by preg rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_PREG]))
               && ($p_options[PCLZIP_OPT_BY_PREG] != "")) {

          if (preg_match($p_options[PCLZIP_OPT_BY_PREG], $v_header_list[$v_nb_extracted]['stored_filename'])) {
              $v_found = true;
          }
      }

      // ----- Look for extract by index rule
      else if (   (isset($p_options[PCLZIP_OPT_BY_INDEX]))
               && ($p_options[PCLZIP_OPT_BY_INDEX] != 0)) {

          // ----- Look if the index is in the list
          for ($j=$j_start; ($j<sizeof($p_options[PCLZIP_OPT_BY_INDEX])) && (!$v_found); $j++) {

              if (($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['start']) && ($i<=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end'])) {
                  $v_found = true;
              }
              if ($i>=$p_options[PCLZIP_OPT_BY_INDEX][$j]['end']) {
                  $j_start = $j+1;
              }

              if ($p_options[PCLZIP_OPT_BY_INDEX][$j]['start']>$i) {
                  break;
              }
          }
      }
      else {
      	$v_found = true;
      }

      // ----- Look for deletion
      if ($v_found)
      {
        unset($v_header_list[$v_nb_extracted]);
      }
      else
      {
        $v_nb_extracted++;
      }
    }

    // ----- Look if something need to be deleted
    if ($v_nb_extracted > 0) {

        // ----- Creates a temporay file
        $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

        // ----- Creates a temporary zip archive
        $v_temp_zip = new PclZip($v_zip_temp_name);

        // ----- Open the temporary zip file in write mode
        if (($v_result = $v_temp_zip->privOpenFd('wb')) != 1) {
            $this->privCloseFd();

            // ----- Return
            return $v_result;
        }

        // ----- Look which file need to be kept
        for ($i=0; $i<sizeof($v_header_list); $i++) {

            // ----- Calculate the position of the header
            @rewind($this->zip_fd);
            if (@fseek($this->zip_fd,  $v_header_list[$i]['offset'])) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Error log
                PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

                // ----- Return
                return PclZip::errorCode();
            }

            // ----- Read the file header
            $v_local_header = array();
            if (($v_result = $this->privReadFileHeader($v_local_header)) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }
            
            // ----- Check that local file header is same as central file header
            if ($this->privCheckFileHeaders($v_local_header,
			                                $v_header_list[$i]) != 1) {
                // TBC
            }
            unset($v_local_header);

            // ----- Write the file header
            if (($v_result = $v_temp_zip->privWriteFileHeader($v_header_list[$i])) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }

            // ----- Read/write the data block
            if (($v_result = PclZipUtilCopyBlock($this->zip_fd, $v_temp_zip->zip_fd, $v_header_list[$i]['compressed_size'])) != 1) {
                // ----- Close the zip file
                $this->privCloseFd();
                $v_temp_zip->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }
        }

        // ----- Store the offset of the central dir
        $v_offset = @ftell($v_temp_zip->zip_fd);

        // ----- Re-Create the Central Dir files header
        for ($i=0; $i<sizeof($v_header_list); $i++) {
            // ----- Create the file header
            if (($v_result = $v_temp_zip->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
                $v_temp_zip->privCloseFd();
                $this->privCloseFd();
                @unlink($v_zip_temp_name);

                // ----- Return
                return $v_result;
            }

            // ----- Transform the header to a 'usable' info
            $v_temp_zip->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
        }


        // ----- Zip file comment
        $v_comment = '';
        if (isset($p_options[PCLZIP_OPT_COMMENT])) {
          $v_comment = $p_options[PCLZIP_OPT_COMMENT];
        }

        // ----- Calculate the size of the central header
        $v_size = @ftell($v_temp_zip->zip_fd)-$v_offset;

        // ----- Create the central dir footer
        if (($v_result = $v_temp_zip->privWriteCentralHeader(sizeof($v_header_list), $v_size, $v_offset, $v_comment)) != 1) {
            // ----- Reset the file list
            unset($v_header_list);
            $v_temp_zip->privCloseFd();
            $this->privCloseFd();
            @unlink($v_zip_temp_name);

            // ----- Return
            return $v_result;
        }

        // ----- Close
        $v_temp_zip->privCloseFd();
        $this->privCloseFd();

        // ----- Delete the zip file
        // TBC : I should test the result ...
        @unlink($this->zipname);

        // ----- Rename the temporary file
        // TBC : I should test the result ...
        //@rename($v_zip_temp_name, $this->zipname);
        PclZipUtilRename($v_zip_temp_name, $this->zipname);
    
        // ----- Destroy the temporary archive
        unset($v_temp_zip);
    }
    
    // ----- Remove every files : reset the file
    else if ($v_central_dir['entries'] != 0) {
        $this->privCloseFd();

        if (($v_result = $this->privOpenFd('wb')) != 1) {
          return $v_result;
        }

        if (($v_result = $this->privWriteCentralHeader(0, 0, 0, '')) != 1) {
          return $v_result;
        }

        $this->privCloseFd();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDirCheck()
  // Description :
  //   Check if a directory exists, if not it creates it and all the parents directory
  //   which may be useful.
  // Parameters :
  //   $p_dir : Directory path to check.
  // Return Values :
  //    1 : OK
  //   -1 : Unable to create directory
  // --------------------------------------------------------------------------------
  function privDirCheck($p_dir, $p_is_dir=false)
  {
    $v_result = 1;


    // ----- Remove the final '/'
    if (($p_is_dir) && (substr($p_dir, -1)=='/'))
    {
      $p_dir = substr($p_dir, 0, strlen($p_dir)-1);
    }

    // ----- Check the directory availability
    if ((is_dir($p_dir)) || ($p_dir == ""))
    {
      return 1;
    }

    // ----- Extract parent directory
    $p_parent_dir = dirname($p_dir);

    // ----- Just a check
    if ($p_parent_dir != $p_dir)
    {
      // ----- Look for parent directory
      if ($p_parent_dir != "")
      {
        if (($v_result = $this->privDirCheck($p_parent_dir)) != 1)
        {
          return $v_result;
        }
      }
    }

    // ----- Create the directory
    if (!@mkdir($p_dir, 0777))
    {
      // ----- Error log
      PclZip::privErrorLog(PCLZIP_ERR_DIR_CREATE_FAIL, "Unable to create directory '$p_dir'");

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privMerge()
  // Description :
  //   If $p_archive_to_add does not exist, the function exit with a success result.
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privMerge(&$p_archive_to_add)
  {
    $v_result=1;

    // ----- Look if the archive_to_add exists
    if (!is_file($p_archive_to_add->zipname))
    {

      // ----- Nothing to merge, so merge is a success
      $v_result = 1;

      // ----- Return
      return $v_result;
    }

    // ----- Look if the archive exists
    if (!is_file($this->zipname))
    {

      // ----- Do a duplicate
      $v_result = $this->privDuplicate($p_archive_to_add->zipname);

      // ----- Return
      return $v_result;
    }

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('rb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir = array();
    if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1)
    {
      $this->privCloseFd();
      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($this->zip_fd);

    // ----- Open the archive_to_add file
    if (($v_result=$p_archive_to_add->privOpenFd('rb')) != 1)
    {
      $this->privCloseFd();

      // ----- Return
      return $v_result;
    }

    // ----- Read the central directory informations
    $v_central_dir_to_add = array();
    if (($v_result = $p_archive_to_add->privReadEndCentralDir($v_central_dir_to_add)) != 1)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();

      return $v_result;
    }

    // ----- Go to beginning of File
    @rewind($p_archive_to_add->zip_fd);

    // ----- Creates a temporay file
    $v_zip_temp_name = PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb')) == 0)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open temporary file \''.$v_zip_temp_name.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = $v_central_dir['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Copy the files from the archive_to_add into the temporary file
    $v_size = $v_central_dir_to_add['offset'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($p_archive_to_add->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Store the offset of the central dir
    $v_offset = @ftell($v_zip_temp_fd);

    // ----- Copy the block of file headers from the old archive
    $v_size = $v_central_dir['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($this->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Copy the block of file headers from the archive_to_add
    $v_size = $v_central_dir_to_add['size'];
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = @fread($p_archive_to_add->zip_fd, $v_read_size);
      @fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Merge the file comments
    $v_comment = $v_central_dir['comment'].' '.$v_central_dir_to_add['comment'];

    // ----- Calculate the size of the (new) central header
    $v_size = @ftell($v_zip_temp_fd)-$v_offset;

    // ----- Swap the file descriptor
    // Here is a trick : I swap the temporary fd with the zip fd, in order to use
    // the following methods on the temporary fil and not the real archive fd
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Create the central dir footer
    if (($v_result = $this->privWriteCentralHeader($v_central_dir['entries']+$v_central_dir_to_add['entries'], $v_size, $v_offset, $v_comment)) != 1)
    {
      $this->privCloseFd();
      $p_archive_to_add->privCloseFd();
      @fclose($v_zip_temp_fd);
      $this->zip_fd = null;

      // ----- Reset the file list
      unset($v_header_list);

      // ----- Return
      return $v_result;
    }

    // ----- Swap back the file descriptor
    $v_swap = $this->zip_fd;
    $this->zip_fd = $v_zip_temp_fd;
    $v_zip_temp_fd = $v_swap;

    // ----- Close
    $this->privCloseFd();
    $p_archive_to_add->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Delete the zip file
    // TBC : I should test the result ...
    @unlink($this->zipname);

    // ----- Rename the temporary file
    // TBC : I should test the result ...
    //@rename($v_zip_temp_name, $this->zipname);
    PclZipUtilRename($v_zip_temp_name, $this->zipname);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDuplicate()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDuplicate($p_archive_filename)
  {
    $v_result=1;

    // ----- Look if the $p_archive_filename exists
    if (!is_file($p_archive_filename))
    {

      // ----- Nothing to duplicate, so duplicate is a success.
      $v_result = 1;

      // ----- Return
      return $v_result;
    }

    // ----- Open the zip file
    if (($v_result=$this->privOpenFd('wb')) != 1)
    {
      // ----- Return
      return $v_result;
    }

    // ----- Open the temporary file in write mode
    if (($v_zip_temp_fd = @fopen($p_archive_filename, 'rb')) == 0)
    {
      $this->privCloseFd();

      PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive file \''.$p_archive_filename.'\' in binary write mode');

      // ----- Return
      return PclZip::errorCode();
    }

    // ----- Copy the files from the archive to the temporary file
    // TBC : Here I should better append the file and go back to erase the central dir
    $v_size = filesize($p_archive_filename);
    while ($v_size != 0)
    {
      $v_read_size = ($v_size < PCLZIP_READ_BLOCK_SIZE ? $v_size : PCLZIP_READ_BLOCK_SIZE);
      $v_buffer = fread($v_zip_temp_fd, $v_read_size);
      @fwrite($this->zip_fd, $v_buffer, $v_read_size);
      $v_size -= $v_read_size;
    }

    // ----- Close
    $this->privCloseFd();

    // ----- Close the temporary file
    @fclose($v_zip_temp_fd);

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privErrorLog()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privErrorLog($p_error_code=0, $p_error_string='')
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclError($p_error_code, $p_error_string);
    }
    else {
      $this->error_code = $p_error_code;
      $this->error_string = $p_error_string;
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privErrorReset()
  // Description :
  // Parameters :
  // --------------------------------------------------------------------------------
  function privErrorReset()
  {
    if (PCLZIP_ERROR_EXTERNAL == 1) {
      PclErrorReset();
    }
    else {
      $this->error_code = 0;
      $this->error_string = '';
    }
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privDisableMagicQuotes()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privDisableMagicQuotes()
  {
    $v_result=1;

    // ----- Look if function exists
    if (   (!function_exists("get_magic_quotes_runtime"))
	    || (!function_exists("set_magic_quotes_runtime"))) {
      return $v_result;
	}

    // ----- Look if already done
    if ($this->magic_quotes_status != -1) {
      return $v_result;
	}

	// ----- Get and memorize the magic_quote value
	$this->magic_quotes_status = @get_magic_quotes_runtime();

	// ----- Disable magic_quotes
	if ($this->magic_quotes_status == 1) {
	  @set_magic_quotes_runtime(0);
	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : privSwapBackMagicQuotes()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function privSwapBackMagicQuotes()
  {
    $v_result=1;

    // ----- Look if function exists
    if (   (!function_exists("get_magic_quotes_runtime"))
	    || (!function_exists("set_magic_quotes_runtime"))) {
      return $v_result;
	}

    // ----- Look if something to do
    if ($this->magic_quotes_status != -1) {
      return $v_result;
	}

	// ----- Swap back magic_quotes
	if ($this->magic_quotes_status == 1) {
  	  @set_magic_quotes_runtime($this->magic_quotes_status);
	}

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  }
  // End of class
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilPathReduction()
  // Description :
  // Parameters :
  // Return Values :
  // --------------------------------------------------------------------------------
  function PclZipUtilPathReduction($p_dir)
  {
    $v_result = "";

    // ----- Look for not empty path
    if ($p_dir != "") {
      // ----- Explode path by directory names
      $v_list = explode("/", $p_dir);

      // ----- Study directories from last to first
      $v_skip = 0;
      for ($i=sizeof($v_list)-1; $i>=0; $i--) {
        // ----- Look for current path
        if ($v_list[$i] == ".") {
          // ----- Ignore this directory
          // Should be the first $i=0, but no check is done
        }
        else if ($v_list[$i] == "..") {
		  $v_skip++;
        }
        else if ($v_list[$i] == "") {
		  // ----- First '/' i.e. root slash
		  if ($i == 0) {
            $v_result = "/".$v_result;
		    if ($v_skip > 0) {
		        // ----- It is an invalid path, so the path is not modified
		        // TBC
		        $v_result = $p_dir;
                $v_skip = 0;
		    }
		  }
		  // ----- Last '/' i.e. indicates a directory
		  else if ($i == (sizeof($v_list)-1)) {
            $v_result = $v_list[$i];
		  }
		  // ----- Double '/' inside the path
		  else {
            // ----- Ignore only the double '//' in path,
            // but not the first and last '/'
		  }
        }
        else {
		  // ----- Look for item to skip
		  if ($v_skip > 0) {
		    $v_skip--;
		  }
		  else {
            $v_result = $v_list[$i].($i!=(sizeof($v_list)-1)?"/".$v_result:"");
		  }
        }
      }
      
      // ----- Look for skip
      if ($v_skip > 0) {
        while ($v_skip > 0) {
            $v_result = '../'.$v_result;
            $v_skip--;
        }
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilPathInclusion()
  // Description :
  //   This function indicates if the path $p_path is under the $p_dir tree. Or,
  //   said in an other way, if the file or sub-dir $p_path is inside the dir
  //   $p_dir.
  //   The function indicates also if the path is exactly the same as the dir.
  //   This function supports path with duplicated '/' like '//', but does not
  //   support '.' or '..' statements.
  // Parameters :
  // Return Values :
  //   0 if $p_path is not inside directory $p_dir
  //   1 if $p_path is inside directory $p_dir
  //   2 if $p_path is exactly the same as $p_dir
  // --------------------------------------------------------------------------------
  function PclZipUtilPathInclusion($p_dir, $p_path)
  {
    $v_result = 1;
    
    // ----- Look for path beginning by ./
    if (   ($p_dir == '.')
        || ((strlen($p_dir) >=2) && (substr($p_dir, 0, 2) == './'))) {
      $p_dir = PclZipUtilTranslateWinPath(getcwd(), FALSE).'/'.substr($p_dir, 1);
    }
    if (   ($p_path == '.')
        || ((strlen($p_path) >=2) && (substr($p_path, 0, 2) == './'))) {
      $p_path = PclZipUtilTranslateWinPath(getcwd(), FALSE).'/'.substr($p_path, 1);
    }

    // ----- Explode dir and path by directory separator
    $v_list_dir = explode("/", $p_dir);
    $v_list_dir_size = sizeof($v_list_dir);
    $v_list_path = explode("/", $p_path);
    $v_list_path_size = sizeof($v_list_path);

    // ----- Study directories paths
    $i = 0;
    $j = 0;
    while (($i < $v_list_dir_size) && ($j < $v_list_path_size) && ($v_result)) {

      // ----- Look for empty dir (path reduction)
      if ($v_list_dir[$i] == '') {
        $i++;
        continue;
      }
      if ($v_list_path[$j] == '') {
        $j++;
        continue;
      }

      // ----- Compare the items
      if (($v_list_dir[$i] != $v_list_path[$j]) && ($v_list_dir[$i] != '') && ( $v_list_path[$j] != ''))  {
        $v_result = 0;
      }

      // ----- Next items
      $i++;
      $j++;
    }

    // ----- Look if everything seems to be the same
    if ($v_result) {
      // ----- Skip all the empty items
      while (($j < $v_list_path_size) && ($v_list_path[$j] == '')) $j++;
      while (($i < $v_list_dir_size) && ($v_list_dir[$i] == '')) $i++;

      if (($i >= $v_list_dir_size) && ($j >= $v_list_path_size)) {
        // ----- There are exactly the same
        $v_result = 2;
      }
      else if ($i < $v_list_dir_size) {
        // ----- The path is shorter than the dir
        $v_result = 0;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilCopyBlock()
  // Description :
  // Parameters :
  //   $p_mode : read/write compression mode
  //             0 : src & dest normal
  //             1 : src gzip, dest normal
  //             2 : src normal, dest gzip
  //             3 : src & dest gzip
  // Return Values :
  // --------------------------------------------------------------------------------
  function PclZipUtilCopyBlock($p_src, $p_dest, $p_size, $p_mode=0)
  {
    $v_result = 1;

    if ($p_mode==0)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @fread($p_src, $v_read_size);
        @fwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==1)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @gzread($p_src, $v_read_size);
        @fwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==2)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @fread($p_src, $v_read_size);
        @gzwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }
    else if ($p_mode==3)
    {
      while ($p_size != 0)
      {
        $v_read_size = ($p_size < PCLZIP_READ_BLOCK_SIZE ? $p_size : PCLZIP_READ_BLOCK_SIZE);
        $v_buffer = @gzread($p_src, $v_read_size);
        @gzwrite($p_dest, $v_buffer, $v_read_size);
        $p_size -= $v_read_size;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilRename()
  // Description :
  //   This function tries to do a simple rename() function. If it fails, it
  //   tries to copy the $p_src file in a new $p_dest file and then unlink the
  //   first one.
  // Parameters :
  //   $p_src : Old filename
  //   $p_dest : New filename
  // Return Values :
  //   1 on success, 0 on failure.
  // --------------------------------------------------------------------------------
  function PclZipUtilRename($p_src, $p_dest)
  {
    $v_result = 1;

    // ----- Try to rename the files
    if (!@rename($p_src, $p_dest)) {

      // ----- Try to copy & unlink the src
      if (!@copy($p_src, $p_dest)) {
        $v_result = 0;
      }
      else if (!@unlink($p_src)) {
        $v_result = 0;
      }
    }

    // ----- Return
    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilOptionText()
  // Description :
  //   Translate option value in text. Mainly for debug purpose.
  // Parameters :
  //   $p_option : the option value.
  // Return Values :
  //   The option text value.
  // --------------------------------------------------------------------------------
  function PclZipUtilOptionText($p_option)
  {
    
    $v_list = get_defined_constants();
    for (reset($v_list); $v_key = key($v_list); next($v_list)) {
	    $v_prefix = substr($v_key, 0, 10);
	    if ((   ($v_prefix == 'PCLZIP_OPT')
           || ($v_prefix == 'PCLZIP_CB_')
           || ($v_prefix == 'PCLZIP_ATT'))
	        && ($v_list[$v_key] == $p_option)) {
        return $v_key;
	    }
    }
    
    $v_result = 'Unknown';

    return $v_result;
  }
  // --------------------------------------------------------------------------------

  // --------------------------------------------------------------------------------
  // Function : PclZipUtilTranslateWinPath()
  // Description :
  //   Translate windows path by replacing '\' by '/' and optionally removing
  //   drive letter.
  // Parameters :
  //   $p_path : path to translate.
  //   $p_remove_disk_letter : true | false
  // Return Values :
  //   The path translated.
  // --------------------------------------------------------------------------------
  function PclZipUtilTranslateWinPath($p_path, $p_remove_disk_letter=true)
  {
    if (stristr(php_uname(), 'windows')) {
      // ----- Look for potential disk letter
      if (($p_remove_disk_letter) && (($v_position = strpos($p_path, ':')) != false)) {
          $p_path = substr($p_path, $v_position+1);
      }
      // ----- Change potential windows directory separator
      if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0,1) == '\\')) {
          $p_path = strtr($p_path, '\\', '/');
      }
    }
    return $p_path;
  }
  // --------------------------------------------------------------------------------
/** End lib/pclzip.lib.php **/ ?>