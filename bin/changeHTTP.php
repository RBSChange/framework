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
	if (isset($_POST['argv']))
	{		
		$frameworkInfo = $computedDeps["change-lib"]["framework"];
		$bootStrap->appendToAutoload($frameworkInfo["path"]);
		$argv = $_POST['argv'];
		$script = new c_Changescripthttp('change.php', $frameworkInfo['path']);
		$script->setBootStrap($bootStrap);
		$script->setEnvVar("computedDeps", $computedDeps);
		registerCommands($script, $computedDeps, $bootStrap);
		ob_start();
		$script->execute($argv);
		ob_flush();
	}
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
	foreach (glob(WEBEDIT_HOME . "/modules/*", GLOB_ONLYDIR) as $module)
	{
		$modulePath = realpath($module);
		$moduleName = basename($module);
		$inAutoload = false;
		if (is_dir($modulePath."/change-commands"))
		{
			$script->addCommandDir($modulePath."/change-commands", "$moduleName|Module $moduleName commands");
			$bootStrap->appendToAutoload($modulePath);
			$inAutoload = true;
		}

		$ghostPath = $modulePath.'/changedev-commands';
		if (is_dir($ghostPath))
		{
			$script->addGhostCommandDir($ghostPath, "$moduleName|Module $moduleName commands");
			if (!$inAutoload)
			{
				$bootStrap->appendToAutoload($modulePath);
			}
		}
	}	
}