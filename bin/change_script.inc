<?php
define("WEBEDIT_HOME", getcwd());
define("CHANGE_DEV_MODE", getenv("CHANGE_DEV_MODE") == "true");
if (!defined('PROFILE') )
{
	$profile = file_get_contents(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'profile');
	if ($profile === false || $profile == '')
	{
		throw new Exception('Profile not defined. Please define a profile in file ./profile.');
	}
	define('PROFILE', trim($profile));
}

// Load c_ChangeBootStrap
//ob_start(); // avoid shebang display (when all classes are aggregated into one file).
require_once dirname(__FILE__) . '/bootstrap.php';
//ob_get_clean();
$bootStrap = new c_ChangeBootStrap(WEBEDIT_HOME);
$bootStrap->setAutoloadPath(WEBEDIT_HOME."/cache/autoload");

$computedDeps = $bootStrap->getComputedDependencies();
if (!isset($computedDeps["change-lib"]["framework"]))
{
	throw new Exception("Could not find change-lib/framework dependency");
}
$frameworkInfo = $computedDeps["change-lib"]["framework"];

$script = new c_Changescript($_SERVER['argv'][0], $frameworkInfo['path']);
$script->setBootStrap($bootStrap);
$script->setEnvVar("computedDeps", $computedDeps);

$cmdPath  = $frameworkInfo["path"].'/'.$script->getName()."-commands";
$bootStrap->appendToAutoload($cmdPath);
$script->addCommandDir($cmdPath);

if (is_array($ghostChangeScripts))
{
	foreach ($ghostChangeScripts as $ghostName)
	{
		$ghostPath = $frameworkInfo["path"].'/'.$ghostName."-commands";
		if (is_dir($ghostPath))
		{
			$bootStrap->appendToAutoload($ghostPath);
			$script->addGhostCommandDir($ghostPath);
		}
	}
}
$path = WEBEDIT_HOME."/modules";
if (is_dir($path))
{
	foreach (new DirectoryIterator($path) as $fileInfo)
	{
		if (!$fileInfo->isDot() && $fileInfo->isDir())
		{
			$moduleName = basename($fileInfo->getPathname());
			$modulePath =  (isset($computedDeps['module'][$moduleName])) ? $computedDeps['module'][$moduleName]['path'] : realpath($fileInfo->getPathname());
			$cmdPath = $modulePath."/".$script->getName()."-commands";
			if (is_dir($cmdPath))
			{
				$bootStrap->appendToAutoload($cmdPath);
				$script->addCommandDir($cmdPath, "$moduleName|Module $moduleName commands");
			}
	
			if (is_array($ghostChangeScripts))
			{
				foreach ($ghostChangeScripts as $ghostName)
				{
					$ghostPath = $modulePath.'/'.$ghostName."-commands";
					if (is_dir($ghostPath))
					{
						$bootStrap->appendToAutoload($ghostPath);
						$script->addGhostCommandDir($ghostPath, "$moduleName|Module $moduleName commands");
					}
				}
			}
		}
	}
}
$script->execute(array_slice($_SERVER['argv'], 1));