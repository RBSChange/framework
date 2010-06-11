<?php
$C_BOOT_STRAP_AS_LIB = true;
require_once 'httpbootstrap.php';

$bootStrap = new c_ChangeBootStrap(WEBEDIT_HOME);
$bootStrap->setAutoloadPath(WEBEDIT_HOME."/.change/autoload");
$bootStrap->setLooseVersions(false);
$bootStrap->addPropertiesLocation(WEBEDIT_HOME);
$bootStrap->addPropertiesLocation("/etc/change");
$bootStrap->dispatch('func:executeChangeCmd');

function executeChangeCmd($argv, $computedDeps)
{
	global $bootStrap;
	$argv = isset($_POST['argv']) ? $_POST['argv'] : array();
	$frameworkInfo = $computedDeps["change-lib"]["framework"];
	$bootStrap->appendToAutoload($frameworkInfo["path"]);
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
	$bootStrap->appendToAutoload($frameworkInfo["path"]);
	$script->addCommandDir($frameworkInfo["path"].'/change-commands');
	$script->addGhostCommandDir($frameworkInfo["path"].'/changedev-commands');	

	$path = WEBEDIT_HOME . "/modules/";
	foreach (new DirectoryIterator($path) as $filePath => $fileInfo)
	{
		if (!$fileInfo->isDot() && $fileInfo->isDir())
		{
			$modulePath = realpath($fileInfo->getPathname());
			$moduleName = basename($fileInfo->getPathname());
			$moduleInAutoload = false;
			if (is_dir($modulePath."/change-commands"))
			{
				$bootStrap->appendToAutoload($modulePath);
				$moduleInAutoload = true;
				$script->addCommandDir($modulePath."/change-commands", "$moduleName|Module $moduleName commands");
			}
	
			$ghostPath = $modulePath.'/changedev-commands';
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