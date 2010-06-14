<?php
define("WEBEDIT_HOME", getcwd());
define("CHANGE_DEV_MODE", getenv("CHANGE_DEV_MODE") == "true");

// Get computed dependencies (calculated by global change.php using change.xml descriptor)
$computedDepsPath = WEBEDIT_HOME."/.change/autoload/.computedChangeComponents.ser";
if (!file_exists($computedDepsPath))
{
	throw new Exception("$computedDepsPath does not exists. Be sure you do not called ".__FILE__." directly");
}
$computedDeps = unserialize(file_get_contents($computedDepsPath));
if (!is_array($computedDeps))
{
	throw new Exception("Could not load $computedDepsPath. Please delete $computedDepsPath and run it again. If it persists, try to report the problem.");
}

// Load c_ChangeBootStrap
$C_BOOT_STRAP_AS_LIB = true;
if (!isset($computedDeps["c_bootstrap"]))
{
	throw new Exception("Could not find ChangeBootStrap location. Be sure you do not called ".__FILE__." directly. If not, please delete $computedDepsPath and run it again. If it persists, try to report the problem.");
}

if (!is_file($computedDeps["c_bootstrap"]) || !is_readable($computedDeps["c_bootstrap"]))
{
	throw new Exception("Could not load ".$computedDeps["c_bootstrap"].". Please delete $computedDepsPath and run it again. If it persists, try to report the problem.");
}
ob_start(); // avoid shebang display (when all classes are aggregated into one file).
require_once($computedDeps["c_bootstrap"]);
ob_get_clean();
$bootStrap = new c_ChangeBootStrap(WEBEDIT_HOME);
$bootStrap->setAutoloadPath(WEBEDIT_HOME."/.change/autoload");
if (($refreshKey = array_search("--refresh-cli-autoload", $_SERVER["argv"])) !== false)
{
	$bootStrap->setRefreshAutoload(true);
	unset($_SERVER["argv"][$refreshKey]);
}

$bootStrap->setLooseVersions(getenv("C_LOOSE_VERSIONS") == "true");
$bootStrap->addPropertiesLocation($_SERVER["HOME"]."/.change");
$bootStrap->addPropertiesLocation("/etc/change");

if (!isset($computedDeps["change-lib"]["framework"]))
{
	throw new Exception("Could not find change-lib/framework dependency");
}
$frameworkInfo = $computedDeps["change-lib"]["framework"];
$bootStrap->appendToAutoload($frameworkInfo["path"]);

$scriptBaseDir = realpath(dirname(realpath($_SERVER['SCRIPT_FILENAME']))."/..");
$script = new c_Changescript($_SERVER['argv'][0], $scriptBaseDir);
$script->setBootStrap($bootStrap);
$script->setEnvVar("computedDeps", $computedDeps);
$script->addCommandDir($frameworkInfo["path"].'/'.$script->getName()."-commands");
if (is_array($ghostChangeScripts))
{
	foreach ($ghostChangeScripts as $ghostName)
	{
		$ghostPath = $frameworkInfo["path"].'/'.$ghostName."-commands";
		if (is_dir($ghostPath))
		{
			$script->addGhostCommandDir($ghostPath);
		}
	}
}
if (is_dir(getcwd()."/modules"))
{
	$path = getcwd()."/modules";
	foreach (new DirectoryIterator($path) as $fileInfo)
	{
		if (!$fileInfo->isDot() && $fileInfo->isDir())
		{
			$moduleName = basename($fileInfo->getPathname());
			$modulePath =  (isset($computedDeps['module'][$moduleName])) ? $computedDeps['module'][$moduleName]['path'] : realpath($fileInfo->getPathname());
			$moduleInAutoload = false;
			if (is_dir($modulePath."/".$script->getName()."-commands"))
			{
				$bootStrap->appendToAutoload($modulePath);
				$moduleInAutoload = true;
				$script->addCommandDir($modulePath."/".$script->getName()."-commands", "$moduleName|Module $moduleName commands");
			}
	
			if (is_array($ghostChangeScripts))
			{
				foreach ($ghostChangeScripts as $ghostName)
				{
					$ghostPath = $modulePath.'/'.$ghostName."-commands";
					if (is_dir($ghostPath))
					{
						$script->addGhostCommandDir($ghostPath, "$moduleName|Module $moduleName commands");
						if (!$moduleInAutoload)
						{
							$bootStrap->appendToAutoload($modulePath);
						}
					}
				}
			}
		}
	}
}
$script->execute(array_slice($_SERVER['argv'], 1));