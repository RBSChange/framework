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
		$modules = array();	
		$bootStrap = c_ChangeBootStrap::getInstance();
		$definitions = $bootStrap->getReleaseDefinition($bootStrap->getCurrentReleaseName());
		
		$moduleType = $bootStrap->convertToCategory(c_ChangeBootStrap::$DEP_MODULE);
		$moduleService = ModuleService::getInstance();
		
		foreach ($definitions as $key => $dependencyInfos)
		{
			if ($dependencyInfos['type'] != $moduleType)
			{
				continue;
			}
			$moduleName = $dependencyInfos['name'];
			if ($moduleService->moduleExists($moduleName))
			{
				continue;
			}
			$modules[] = $moduleName . '-' . $dependencyInfos['version'];
		}
		return $modules;
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
		$type = c_ChangeBootStrap::$DEP_MODULE;
		$moduleName = $matches[1];
		$moduleVersion = $matches[2];
		
		$computedDeps = $this->getComputedDeps();
		if (ModuleService::getInstance()->isInstalled($moduleName))
		{
			$installedVersion = ModuleService::getInstance()->getModuleVersion($moduleName);
			$this->message($moduleName . " module is already installed in version ". $installedVersion);
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
			
		if (!$bootStrap->dependencyInLocalRepository($type, $moduleName, $moduleVersion))
		{
			try
			{
				$url = null;
				$localPath = $bootStrap->downloadDependency($type, $moduleName, $moduleVersion, $url);
				$this->message("Module downloaded in: " . $localPath . ", from: ". $url);
			} 
			catch (Exception $e) 
			{
				return $this->quitError($e->getMessage());
			}
		}
		else
		{
			$this->message("Module already downloaded.");
		}
		
		if (!$bootStrap->linkToProject($type, $moduleName, $moduleVersion))
		{
			return $this->quitError("Unable to link '$moduleName' in project");
		}
		
		if (!$bootStrap->updateProjectDependencies($type, $moduleName, $moduleVersion))
		{
			return $this->quitError("Unable to update file project dependencies change.xml");
		}
		
		$this->message("Check dependencies integrity");
		
		$this->getParent()->executeCommand('updateDependencies');
		
		$this->changecmd("compile-all");
		$this->changecmd("generate-database");
		$this->changecmd("import-init-data", array($moduleName));		
		$this->changecmd("init-patch-db", array('modules_' . $moduleName));
		
		$updatedComputedDebs = $this->getComputedDeps();
		foreach ($updatedComputedDebs['module'] as $mN => $ignore) 
		{
			// Import-init-data for all the other modules automatically installed with $moduleName
			if (!isset($computedDeps['module'][$mN]) && $mN !== $moduleName)
			{
				$this->changecmd("import-init-data", array($mN));
				$this->changecmd("init-patch-db", array("modules_" . $mN));
			}
		}
        $this->changecmd("init-webapp");
		return $this->quitOk("Install OK");
	}
}