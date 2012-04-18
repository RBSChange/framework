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
	$argv = array_values($argv);
	$bootStrap->cleanDependenciesCache();
}

clearstatcache();

$script->setBootStrap($bootStrap);
$script->setEnvVar('computedDeps', $bootStrap->getComputedDependencies());

$addDevCmds = $bootStrap->getProperties()->getProperty('DEVELOPMENT_MODE') == true;
if ($addDevCmds && !defined('CHANGE_DEV_MODE')) {define('CHANGE_DEV_MODE', true);}

$cmdPath  = FRAMEWORK_HOME . '/change-commands';
$bootStrap->appendToAutoload($cmdPath);
$script->addCommandDir($cmdPath);

if ($addDevCmds)
{
	$devPath = FRAMEWORK_HOME.'/changedev-commands';
	$bootStrap->appendToAutoload($devPath);
	$script->addGhostCommandDir($devPath);
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
			$cmdPath = $modulePath.'/change-commands';
			if (is_dir($cmdPath))
			{
				$bootStrap->appendToAutoload($cmdPath);
				$script->addCommandDir($cmdPath, "$moduleName|Module $moduleName commands");
			}
	
			if ($addDevCmds)
			{
				$devPath = $modulePath.'/changedev-commands';
				if (is_dir($devPath))
				{
					$bootStrap->appendToAutoload($devPath);
					$script->addGhostCommandDir($devPath, "$moduleName|Module $moduleName commands");
				}
			}
		}
	}
}
$script->execute($argv);