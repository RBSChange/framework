<?php
class commands_InstallModule extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName>-<moduleVersion>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "install a module";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		$bootStrap = c_ChangeBootStrap::getInstance();
		$remoteModules = $bootStrap->getRemoteModules(Framework::getVersion());
		$moduleService = ModuleService::getInstance();
		foreach ($remoteModules as $key => $module)
		{
			$matches = null;
			if (!preg_match('/^(.*?)-([0-9].*)$/', $module, $matches))
			{
				// this should not happen ...
				continue;
			}
			$moduleName = $matches[1];
			if (!$moduleService->moduleExists($moduleName))
			{
				continue;
			}
			$installedVersion = $moduleService->getModuleVersion($moduleName);
			$remoteVersion = $matches[2];
			if ($bootStrap->compareVersion($installedVersion, $remoteVersion) <= 0)
			{
				unset($remoteModules[$key]);
			}
		}
		return $remoteModules;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$moduleFullName = $params[0];
		$this->message("== Install module $moduleFullName ==");
		
		$matches = null;
		if (!preg_match("/^(.*?)-([0-9].*)$/", $moduleFullName, $matches))
		{
			return $this->quitError("'$moduleFullName' is not a valid component name");
		}
		
		$bootStrap = $this->getParent()->getBootStrap();
		$moduleName = $matches[1];
		$moduleVersion = $matches[2];
		$parts = explode('-', $moduleVersion);
		if (count($parts) > 2)
		{
			return $this->quitError("'$moduleVersion' is not a valid version");
		}
		else if (count($parts) == 2)
		{
			$hotFix = $parts[1];
			$moduleVersion = $parts[0];
		}
		else
		{
			$hotFix = null;
		}
		
		$computedDeps = $this->getComputedDeps();
		if (ModuleService::getInstance()->isInstalled($moduleName) && isset($computedDeps['module'][$moduleName]))
		{
			$installedVersion = $computedDeps['module'][$moduleName]['version'];
			$this->message("$moduleName module is already installed in version ".$installedVersion);
			switch ($bootStrap->compareVersion($moduleVersion, $installedVersion))
			{
				case 0:
					return $this->quitOk("Nothing to do.");
				case 1:
					return $this->quitOk("Installed version '".$installedVersion."' is newer than requested version ".$moduleVersion.".");
				case -1:
					return $this->quitError("Can not upgrade module version for now. You must use a migration script");
			}		
		}
			
		$modulePath = $bootStrap->installComponent(c_ChangeBootStrap::$DEP_MODULE, $moduleName, $moduleVersion, $hotFix);
		if ($modulePath === null)
		{
			return $this->quitError("Unable to download '$moduleFullName' in local repository.");
		}
		if (!$bootStrap->linkToProject(c_ChangeBootStrap::$DEP_MODULE, $moduleName, $moduleVersion, $hotFix))
		{
			return $this->quitError("Unable to link '$moduleName' in project");
		}
		
		if (!$bootStrap->updateProjectDependencies(c_ChangeBootStrap::$DEP_MODULE, $moduleName, $moduleVersion, $hotFix))
		{
			return $this->quitError("Unable to update file project dependencies change.xml");
		}
		
		$this->message("Check dependencies integrity");
		$this->getParent()->executeCommand('updateDependencies');
		
		$this->changecmd("compile-all");
		$this->changecmd("generate-database");
		$this->changecmd("import-init-data", array($moduleName));		
		$this->changecmd("init-patch-db", array("modules_$moduleName"));
		
		$updatedComputedDebs = $this->getComputedDeps();
		foreach ($updatedComputedDebs['module'] as $mN => $ignore) 
		{
			// Import-init-data for all the other modules automatically installed with $moduleName
			if (!isset($computedDeps['module'][$mN]) && $mN !== $moduleName)
			{
				$this->changecmd("import-init-data", array($mN));
				$this->changecmd("init-patch-db", array("modules_".$mN));
			}
		}
		
        $this->changecmd("init-webapp");

		return $this->quitOk("Install OK");
	}
}