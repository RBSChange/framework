<?php
require_once dirname(__FILE__) . '/bootstrap.php';
$bootStrap = new c_ChangeBootStrap(WEBEDIT_HOME);
$bootStrap->setAutoloadPath(WEBEDIT_HOME."/cache/autoload");
$bootStrap->dispatch('func:executeChangeCmd');

function executeChangeCmd($argv, $computedDeps)
{
	global $bootStrap;
	$argv = isset($_POST['argv']) ? $_POST['argv'] : array();
	$frameworkInfo = $computedDeps["change-lib"]["framework"];
	$script = new c_Changescripthttp('change.php', $frameworkInfo['path']);
	$script->setBootStrap($bootStrap);
	$script->setEnvVar("computedDeps", $computedDeps);
	registerCommands($script, $computedDeps, $bootStrap);
	ob_start();
	$script->execute($argv);
	ob_flush();
}

/**
 * @param c_Changescript $script
 * @param array $computedDeps
 * @param c_ChangeBootStrap $bootStrap
 */
function registerCommands($script, $computedDeps, $bootStrap)
{	
	$frameworkInfo = $computedDeps["change-lib"]["framework"];
	$cmdPath  = $frameworkInfo["path"].'/change-commands';
	$bootStrap->appendToAutoload($cmdPath);
	$script->addCommandDir($cmdPath);
	
	$cmdPath  = $frameworkInfo["path"].'/changedev-commands';
	$bootStrap->appendToAutoload($cmdPath);
	$script->addGhostCommandDir($cmdPath);	

	$path = WEBEDIT_HOME . "/modules/";
	foreach (new DirectoryIterator($path) as $fileInfo)
	{
		if (!$fileInfo->isDot() && $fileInfo->isDir())
		{
			$moduleName = basename($fileInfo->getPathname());
			$modulePath =  (isset($computedDeps['module'][$moduleName])) ? $computedDeps['module'][$moduleName]['path'] : realpath($fileInfo->getPathname());
			
			$cmdPath = $modulePath."/change-commands";
			if (is_dir($cmdPath))
			{
				$bootStrap->appendToAutoload($cmdPath);
				$script->addCommandDir($cmdPath, "$moduleName|Module $moduleName commands");
			}
	
			$ghostPath = $modulePath.'/changedev-commands';
			if (is_dir($ghostPath))
			{
				$bootStrap->appendToAutoload($ghostPath);
				$script->addGhostCommandDir($ghostPath, "$moduleName|Module $moduleName commands");
			}
		}
	}
}