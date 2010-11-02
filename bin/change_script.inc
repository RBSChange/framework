<?php
if (!defined("PROFILE") || !defined("WEBEDIT_HOME") || !isset($bootStrap) || !isset($script))
{
	exit(-1);
}
if (!isset($argv)) {$argv = array();}

$clearKey = array_search('--clear', $argv);
if ($clearKey !== false)
{
	unset($argv[$clearKey]);
	$bootStrap->cleanDependenciesCache();
}

$script->setBootStrap($bootStrap);
$script->setEnvVar('computedDeps', $bootStrap->getComputedDependencies());

$cmdPath  = FRAMEWORK_HOME . '/'.$script->getName()."-commands";
$bootStrap->appendToAutoload($cmdPath);
$script->addCommandDir($cmdPath);

if (is_array($ghostChangeScripts))
{
	foreach ($ghostChangeScripts as $ghostName)
	{
		$ghostPath = FRAMEWORK_HOME.'/'.$ghostName."-commands";
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
$script->execute($argv);