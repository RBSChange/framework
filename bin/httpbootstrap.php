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
	 * @var String
	 */
	private $pearDir;

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
	 * @var boolean
	 */
	private $useLocalOnly = false;
	
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
		$this->pearDir = array("path" => null, "writeable" => true, "installed" => true);
	}
	
	function ignorePearInstall()
	{
		$this->setUseLocalOnly(true);
	}
	
	function setUseLocalOnly($localOnly)
	{
		$this->useLocalOnly = $localOnly;
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

	private function loadPearInfo()
	{
		if ($this->pearDir === null)
		{
			$pearDir = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_DIR", "pear"));
			if ($this->useLocalOnly)
			{
				$installed = file_exists($pearDir."/PEAR/pearcmd.php");
				$this->pearDir = array("path" => realpath($pearDir), "writeable" => false, "installed" => $installed);
			}
			else
			{
				if (!file_exists($pearDir) && !@mkdir($pearDir, 0777, true))
				{
					throw new Exception("Could not create $pearDir");
				}
				if (is_file($pearDir))
				{
					throw new Exception("$pearDir exists and is not a directory");
				}
				$this->pearDir = array("path" => realpath($pearDir), "writeable" => is_writeable($pearDir), "installed" => $installed);
			}
		}
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
		$computedComponents["PEAR_DIR"] = $this->expandLocalPath($this->getProperties()->getProperty("PEAR_DIR", "pear"));
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
				if ($this->hasComponentLocally($componentInfo[0], $componentInfo[1], $componentInfo[2], false, $componentPath))
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
							if (!$this->hasComponentLocally($componentType, $componentName, $version, false, $subComponentPath))
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
						if (!$this->hasComponentLocally($componentType, $componentName, $version, false, $subComponentPath))
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
	 * @param String $componentType {change-module|change-lib|change-tool|lib}
	 * @param String $componentName
	 * @param String $version
	 * @param Boolean $optionnal
	 * @param String $componentPath
	 * @return Boolean
	 */
	private function hasComponentLocally($componentType, $componentName, $version, $optionnal = false, &$componentPath)
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
			$this->loadPearInfo();		
			if ($this->useLocalOnly) 
			{
				$this->componentLocallyOK($componentType, $componentName, $version);
				return true;
			}

			if (strpos($componentName, '/') === false)
			{
				$packageName = "pear/".$componentName;
			}
			else
			{
				$packageName = $componentName;
			}
					
			if ($this->pearDir["installed"])
			{	
				$pearDir = $this->pearDir["path"];
				c_debug("Search for $componentType/$componentName in $pearDir");
				try
				{
					//$result = cboot_System::execArray("php -d include_path=$pearDir/PEAR $pearDir/PEAR/pearcmd.php -c ".$this->wd."/.pearrc info $packageName");
					$cmd = "php -d include_path=$pearDir/PEAR $pearDir/PEAR/pearcmd.php";
					if (file_exists($this->pearDir["path"]."/pear.conf"))
					{
						$cmd .= " -c ".$this->pearDir["path"]."/pear.conf";
					}
					$cmd .= " info $packageName";
					$result = cboot_System::execArray($cmd);
					foreach ($result as $resultLine)
					{
						$matches = null;
						if (preg_match('/^ABOUT .*-([0-9].*)$/', $resultLine, $matches))
						{
							$installedVersion = strtolower($matches[1]);
							$compareResult = $this->compareVersion($version, $installedVersion);
							if ($compareResult == 0)
							{
								c_debug("Exact version match for $componentType/$componentName-$version in $pearDir");
								$this->componentLocallyOK($componentType, $componentName, $version);
								return true;
							}
							elseif ($compareResult < 0)
							{
								c_debug("$componentType/$componentName found in $pearDir with higher version as expected ($installedVersion > $version)");
								$this->componentLocallyOK($componentType, $componentName, $version);
								return true;
							}
							break;
						}
					}
				}
				catch (Exception $e)
				{
					// This could be pear that exited with a non zero value
				}
				$this->installPearPackage($packageName, $version);
				$this->componentLocallyOK($componentType, $componentName, $version);
				return true;
			}
			else
			{
				
				$this->installPear();
				$this->installPearPackage($packageName, $version);
				$this->componentLocallyOK($componentType, $componentName, $version);
				return true;
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

	private function installPear()
	{
		if ($this->onlyCheck)
		{
			return;
		}
		$this->loadPearInfo();
		if (!$this->pearDir["installed"])
		{
			$pearIn = join("\n", array(
				"",
			$this->getProxy(),
				"1",
			$this->pearDir["path"],
				"",
				"n",
				"n",
				""));
			$goPearPath = null;
			if (!$this->hasComponentLocally(self::$DEP_LIB, "go-pear", "281637", false, $goPearPath))
			{
				$triedDummy = array();
				$goPearPath = $this->downloadComponent($triedDummy, self::$DEP_LIB, "go-pear", "281637");
			}
			cboot_System::exec("php -q $goPearPath/go-pear local", "Installing pear in ".$this->pearDir["path"]." (this can be long, be patient)", !C_DEBUG, $pearIn);

			$this->pearDir["installed"] = true;
		}
	}

	private function installPearPackage($packageName, $version)
	{
		if ($this->onlyCheck)
		{
			echo "Assume PEAR $packageName-$version is installable\n";
			return;
		}
		echo "Install PEAR ".$packageName."\n";
		$packageInfo = explode("/", $packageName);
		if (count($packageInfo) > 1)
		{
			if (!is_file($this->pearDir["path"]."/PEAR/.channels/".$packageInfo[0].".reg")
			&& !is_file($this->pearDir["path"]."/PEAR/.channels/.alias/".$packageInfo[0].".txt"))
			{
				$this->execPearCmd("channel-discover ".$packageInfo[0], "Discovering channel ".$packageInfo[0]);
			}
		}
		$this->execPearCmd("install --onlyreqdeps ".$packageName."-".$version, "Installing $packageName");
	}

	private function execPearCmd($pearCmd, $msg = null)
	{
		//$cmd = "php -d include_path=".$this->pearDir["path"]."/PEAR ".$this->pearDir["path"]."/PEAR/pearcmd.php -c ".$this->wd."/.pearrc ".$pearCmd;
		$cmd = "php -d include_path=".$this->pearDir["path"]."/PEAR ".$this->pearDir["path"]."/PEAR/pearcmd.php";
		if (file_exists($this->pearDir["path"]."/pear.conf"))
		{
			$cmd .= " -c ".$this->pearDir["path"]."/pear.conf";
		}
		$cmd .= " $pearCmd";
		return cboot_System::exec($cmd, $msg, !C_DEBUG);
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
						if (!$this->hasComponentLocally($depType, $depName, $depVersions[0], false, $repoLocation))
						{
							$repoLocation = $this->installComponent($depType, $depName, $depVersions[0]);
						}
						//$repoLocation = $this->getRepolocation($depType, $depName, $depVersions[0], $repoDir);
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
		if ($this->hasComponentLocally($componentType, $componentName, $version, false, $componentPath))
		{
			return $componentPath;
		}
		else if ($this->useLocalOnly)
		{
			return $this->getComponentPath($componentType, $componentName, $version);
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